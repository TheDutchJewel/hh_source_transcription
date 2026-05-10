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
 * A webtrees (https://www.webtrees.net) 2.2 custom module to transcribe sources
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Fisharebest\Webtrees\DB;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\CreateTranscriptionCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\EnsureTagNoteService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\GenerateOrUpdateNoteService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\InteractionModel;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\RevisionOriginType;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionType;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\NoteStrategy;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\RevisionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\HashService;
use InvalidArgumentException;

final class ManualTranscriptionProvider implements TranscriptionProviderInterface, CreatesTranscriptionsInterface
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly RevisionRepository $revisionRepository,
        private readonly HashService $hashService,
        private readonly GenerateOrUpdateNoteService $generateOrUpdateNoteService,
        private readonly EnsureTagNoteService $ensureTagNoteService,
    ) {
    }

    public function key(): string
    {
        return ProviderKey::MANUAL;
    }

    public function interactionModel(): string
    {
        return InteractionModel::MANUAL_DIRECT->value;
    }

    public function create(CreateTranscriptionCommand $command): int
    {
        if ($command->provider_key !== $this->key()) {
            throw new InvalidArgumentException('ManualTranscriptionProvider only supports manual transcriptions.');
        }

        if (trim($command->source_xref) === '') {
            throw new InvalidArgumentException('source_xref must not be empty.');
        }

        if (trim($command->title) === '') {
            throw new InvalidArgumentException('title must not be empty.');
        }

        return DB::transaction(function () use ($command): int {
            $transcription_id = $this->transcriptionRepository->create([
                'tree_id' => $command->tree->id(),
                'source_xref' => $command->source_xref,
                'media_xref' => $command->media_xref,
                'title' => $command->title,
                'interaction_model' => $this->interactionModel(),
                'primary_language_tag' => $command->primary_language_tag,
                'primary_script_tag' => $command->primary_script_tag,
                'primary_form' => $command->primary_form,
                'transcription_type' => TranscriptionType::TRANSCRIPTION->value,
                'provider_key' => $this->key(),
                'status' => TranscriptionStatus::NEW->value,
                'tag_note_xref' => null,
                'current_note_xref' => null,
                'created_by_user_id' => $command->user_id,
                'is_active' => 1,
            ]);

            $revision_id = $this->revisionRepository->create([
                'transcription_id' => $transcription_id,
                'revision_no' => 1,
                'provider_key' => $this->key(),
                'origin_type' => RevisionOriginType::MANUAL_ENTRY->value,
                'origin_reference' => null,
                'content_format' => 'text/plain',
                'content_text' => $command->initial_text,
                'content_hash' => $this->hashService->sha256($command->initial_text),
                'created_by_user_id' => $command->user_id,
                'import_comment' => $command->comment,
                'generated_note_xref' => null,
                'is_current_revision' => 1,
            ]);

            $this->revisionRepository->markCurrent($transcription_id, $revision_id);
            $this->generateOrUpdateNoteService->applyRevisionToCurrentNote(
                $transcription_id,
                $revision_id,
                NoteStrategy::UPDATE_IF_UNCHANGED
            );

            $this->ensureTagNoteService->ensureForTranscription($transcription_id);

            return $transcription_id;
        });
    }
}
