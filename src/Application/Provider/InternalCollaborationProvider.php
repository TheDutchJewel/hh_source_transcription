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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\Services\UserService;
use Fisharebest\Webtrees\Tree;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\OpenCollaborationCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\CollaborationNotificationService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\SaveNoteAsRevisionService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\InteractionModel;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionCollaboratorRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use InvalidArgumentException;
use RuntimeException;

final class InternalCollaborationProvider implements TranscriptionProviderInterface, OpensCollaborationInterface
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly TranscriptionCollaboratorRepository $collaboratorRepository,
        private readonly SaveNoteAsRevisionService $saveNoteAsRevisionService,
        private readonly CollaborationNotificationService $notificationService,
        private readonly UserService $userService,
    ) {
    }

    public function key(): string
    {
        return ProviderKey::INTERNAL;
    }

    public function interactionModel(): string
    {
        return InteractionModel::INTERNAL_COLLABORATIVE->value;
    }

    public function openCollaboration(OpenCollaborationCommand $command): void
    {
        if ($command->initiator_user_id <= 0) {
            throw new InvalidArgumentException('initiator_user_id must not be empty.');
        }

        $transcription = $this->transcriptionRepository->find($command->transcription_id);

        if ($transcription === null) {
            throw new RuntimeException('Transcription not found: ' . $command->transcription_id);
        }

        $initiator = $this->userService->find($command->initiator_user_id);
        if ($initiator === null || !Auth::isMember($transcription->tree, $initiator)) {
            throw new RuntimeException('Initiator cannot access this tree.');
        }

        $collaborator_ids = $this->eligibleCollaboratorIds($command, $transcription->tree);
        if ($collaborator_ids === []) {
            throw new InvalidArgumentException('At least one eligible collaborator is required.');
        }

        DB::transaction(function () use ($command, $transcription, $collaborator_ids): void {
            $this->transcriptionRepository->updateProvider(
                $transcription->id,
                $this->key(),
                $this->interactionModel()
            );

            if ($transcription->status === TranscriptionStatus::NEW->value) {
                $this->transcriptionRepository->updateStatus(
                    $transcription->id,
                    TranscriptionStatus::IN_PROGRESS->value
                );
            }

            $this->collaboratorRepository->setInitiator(
                $transcription->id,
                $command->initiator_user_id
            );

            $this->collaboratorRepository->setCollaborators(
                $transcription->id,
                $collaborator_ids,
                $command->initiator_user_id
            );

            if ($command->save_current_note_first && $transcription->current_note_xref !== null) {
                $this->saveNoteAsRevisionService->saveCurrentNoteAsRevision(
                    $transcription->id,
                    $command->initiator_user_id,
                    $command->message,
                    false
                );
            }
        });

        $this->notificationService->notifyCollaborationOpened(
            $transcription,
            $command->initiator_user_id,
            $this->collaboratorRepository->activeUserIds($transcription->id),
            $command->message
        );
    }

    /**
     * @return array<int,int>
     */
    private function eligibleCollaboratorIds(OpenCollaborationCommand $command, Tree $tree): array
    {
        $ids = array_values(array_diff(
            array_unique(array_map('intval', $command->collaborator_user_ids)),
            [$command->initiator_user_id]
        ));

        return array_values(array_filter($ids, function (int $user_id) use ($tree): bool {
            $user = $this->userService->find($user_id);

            return $user !== null && Auth::isMember($tree, $user);
        }));
    }
}
