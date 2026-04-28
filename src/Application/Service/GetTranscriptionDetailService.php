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

use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\RevisionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SharedNoteGateway;

final class GetTranscriptionDetailService
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly RevisionRepository $revisionRepository,
        private readonly SharedNoteGateway $sharedNoteGateway,
    ) {
    }

    public function get(int $transcription_id): array
    {
        $transcription = $this->transcriptionRepository->find($transcription_id);

        if ($transcription === null) {
            throw new \RuntimeException('Transcription not found: ' . $transcription_id);
        }

        $note_text = null;

        if ($transcription->current_note_xref !== null) {
            $note_text = $this->sharedNoteGateway->readSharedNote(
                $transcription->tree_id,
                $transcription->current_note_xref
            );
        }

        return [
            'transcription' => $transcription,
            'revisions' => $this->revisionRepository->findByTranscription($transcription_id),
            'note_text' => $note_text,
        ];
    }
}