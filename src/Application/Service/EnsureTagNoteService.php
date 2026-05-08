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

use Fisharebest\Webtrees\Tree;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\SettingsRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\MediaObjectGateway;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SharedNoteGateway;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SourceGateway;
use Hartenthaler\Webtrees\Module\SourceTranscription\SourceTranscription;

final class EnsureTagNoteService
{
    /**
     * @param SettingsRepository $settingsRepository
     * @param TranscriptionRepository $transcriptionRepository
     * @param SharedNoteGateway $sharedNoteGateway
     * @param SourceGateway $sourceGateway
     * @param MediaObjectGateway $mediaObjectGateway
     */
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly SharedNoteGateway $sharedNoteGateway,
        private readonly SourceGateway $sourceGateway,
        private readonly MediaObjectGateway $mediaObjectGateway,
    ) {
    }

    public function ensureForTranscription(int $transcription_id): string
    {
        $transcription = $this->transcriptionRepository->find($transcription_id);

        if ($transcription === null) {
            throw new \RuntimeException('Transcription not found: ' . $transcription_id);
        }

        $tag_text = $this->settingsRepository->get('default_tag_text',
                        SourceTranscription::DEFAULT_TAG_PREFIX . SourceTranscription::DEFAULT_TAG_VALUE);

        $existing_note_xref = $this->findExistingTagNote(
            $transcription->tree,
            $transcription->source_xref,
            $transcription->media_xref,
            $tag_text
        );

        if ($existing_note_xref !== null) {
            $this->linkNoteToTranscriptionTarget(
                $transcription->tree,
                $transcription->source_xref,
                $transcription->media_xref,
                $existing_note_xref
            );
            $this->transcriptionRepository->setTagNoteXref($transcription_id, $existing_note_xref);

            return $existing_note_xref;
        }

        $note_xref = $this->sharedNoteGateway->createSharedNote(
            $transcription->tree,
            $tag_text
        );

        $this->linkNoteToTranscriptionTarget(
            $transcription->tree,
            $transcription->source_xref,
            $transcription->media_xref,
            $note_xref
        );

        $this->transcriptionRepository->setTagNoteXref($transcription_id, $note_xref);

        return $note_xref;
    }

    private function linkNoteToTranscriptionTarget(
        Tree $tree,
        string $source_xref,
        ?string $media_xref,
        string $note_xref
    ): void {
        $linked_to_media = $media_xref !== null &&
            $this->mediaObjectGateway->linkNoteToMedia(
                $tree,
                $media_xref,
                $note_xref
            );

        if ($linked_to_media) {
            $this->sourceGateway->unlinkNoteFromSource(
                $tree,
                $source_xref,
                $note_xref
            );
        } else {
            $this->sourceGateway->linkNoteToSource(
                $tree,
                $source_xref,
                $note_xref
            );
        }
    }

    private function findExistingTagNote(
        Tree $tree,
        string $source_xref,
        ?string $media_xref,
        string $tag_text
    ): ?string {
        $media_gedcom = $media_xref === null ?
            null :
            $this->mediaObjectGateway->gedcom($tree, $media_xref);
        $note_xref = $media_gedcom === null ?
            null :
            $this->findExistingTagNoteInGedcom($tree, $media_gedcom, $tag_text);

        if ($note_xref !== null) {
            return $note_xref;
        }

        $source_gedcom = $this->sourceGateway->gedcom($tree, $source_xref);

        return $source_gedcom === null ?
            null :
            $this->findExistingTagNoteInGedcom($tree, $source_gedcom, $tag_text);
    }

    private function findExistingTagNoteInGedcom(Tree $tree, string $gedcom, string $tag_text): ?string
    {
        foreach (preg_split('/\R/u', $gedcom) ?: [] as $line) {
            if (!preg_match('/^\d+\s+NOTE\s+@([^@]+)@/u', $line, $match)) {
                continue;
            }

            $note_xref = $match[1];
            $note_text = $this->sharedNoteGateway->readSharedNote($tree, $note_xref);

            if (trim((string) $note_text) === trim($tag_text)) {
                return $note_xref;
            }
        }

        return null;
    }
}
