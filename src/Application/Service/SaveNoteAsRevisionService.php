<?php
/*
 * webtrees: online genealogy application
 * Copyright (C) 2026 webtrees development team
 *                    <https://webtrees.net>
 *
 * Source Transcription (webtrees custom module):
 * Copyright (C) 2026 Hermann Hartenthaler
 *                     <https://ahnen.hartenthaler.eu>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; If not, see <https://www.gnu.org/licenses/>.
 *
 * Source Transcription
 * A webtrees (https://webtrees.net) 2.2 custom module to transcribe sources
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Fisharebest\Webtrees\DB;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\RevisionOriginType;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\NoteLinkRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\RevisionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SharedNoteGateway;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\HashService;

final class SaveNoteAsRevisionService
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly RevisionRepository $revisionRepository,
        private readonly NoteLinkRepository $noteLinkRepository,
        private readonly SharedNoteGateway $sharedNoteGateway,
        private readonly HashService $hashService,
    ) {
    }

    public function saveCurrentNoteAsRevision(
        int $transcription_id,
        int $user_id,
        ?string $comment = null
    ): int {
        $transcription = $this->transcriptionRepository->find($transcription_id);

        if ($transcription === null) {
            throw new \RuntimeException('Transcription not found: ' . $transcription_id);
        }

        if ($transcription->current_note_xref === null) {
            throw new \RuntimeException('No current note available.');
        }

        $note_text = $this->sharedNoteGateway->readSharedNote(
            $transcription->tree_id,
            $transcription->current_note_xref
        );

        if ($note_text === null) {
            throw new \RuntimeException('Current note could not be read.');
        }

        $note_hash = $this->hashService->sha256($note_text);

        $latest = $this->revisionRepository->latestForTranscription($transcription_id);

        // keine neue Revision erzeugen, wenn identisch
        if ($latest !== null && $latest->content_hash === $note_hash) {
            return $latest->id;
        }

        return DB::transaction(function () use (
            $transcription,
            $note_text,
            $note_hash,
            $user_id,
            $comment
        ): int {
            $revision_no = $this->revisionRepository->nextRevisionNo($transcription->id);

            $revision_id = $this->revisionRepository->create([
                'transcription_id' => $transcription->id,
                'revision_no' => $revision_no,
                'provider_key' => ProviderKey::MANUAL,
                'origin_type' => RevisionOriginType::MANUAL_NOTE_SAVE,
                'origin_reference' => $transcription->current_note_xref,
                'content_format' => 'text/plain',
                'content_text' => $note_text,
                'content_hash' => $note_hash,
                'created_by_user_id' => $user_id,
                'import_comment' => $comment,
                'generated_note_xref' => $transcription->current_note_xref,
                'is_current_revision' => 1,
            ]);

            $this->revisionRepository->markCurrent($transcription->id, $revision_id);

            $this->noteLinkRepository->createLink([
                'transcription_id' => $transcription->id,
                'revision_id' => $revision_id,
                'note_xref' => $transcription->current_note_xref,
                'link_type' => 'manual_note_save',
                'created_by_user_id' => $user_id,
                'is_current' => 1,
                'note_hash_at_link_time' => $note_hash,
            ]);

            $this->noteLinkRepository->markCurrent(
                $transcription->id,
                $transcription->current_note_xref
            );

            return $revision_id;
        });
    }
}