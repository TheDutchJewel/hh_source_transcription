<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionTransition;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Service\TranscriptionStateMachine;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionCollaboratorRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use RuntimeException;

final class CollaborationStatusService
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
        private readonly TranscriptionCollaboratorRepository $collaboratorRepository,
        private readonly TranscriptionStateMachine $stateMachine,
        private readonly CollaborationNotificationService $notificationService,
    ) {
    }

    public function submitForReview(int $transcription_id, int $user_id): void
    {
        $this->assertActiveCollaborator($transcription_id, $user_id);
        $this->applyTransition($transcription_id, $user_id, TranscriptionTransition::SUBMIT_FOR_REVIEW);
    }

    public function finalize(int $transcription_id, int $user_id): void
    {
        $this->assertInitiator($transcription_id, $user_id);
        $this->applyTransition($transcription_id, $user_id, TranscriptionTransition::APPROVE);
    }

    public function reopen(int $transcription_id, int $user_id, bool $is_admin = false): void
    {
        if (!$is_admin) {
            $this->assertInitiator($transcription_id, $user_id);
        }

        $this->applyTransition($transcription_id, $user_id, TranscriptionTransition::REOPEN);
    }

    private function applyTransition(int $transcription_id, int $user_id, TranscriptionTransition $transition): void
    {
        $transcription = $this->transcriptionRepository->find($transcription_id);

        if ($transcription === null) {
            throw new RuntimeException('Transcription not found: ' . $transcription_id);
        }

        $new_status = $this->stateMachine->apply(
            TranscriptionStatus::from($transcription->status),
            $transition
        );

        $this->transcriptionRepository->updateStatus($transcription_id, $new_status->value);

        $this->notificationService->notifyStatusChanged(
            $transcription,
            $user_id,
            $new_status->value,
            $this->collaboratorRepository->activeUserIds($transcription_id)
        );
    }

    private function assertActiveCollaborator(int $transcription_id, int $user_id): void
    {
        if (!$this->collaboratorRepository->isActiveCollaborator($transcription_id, $user_id)) {
            throw new RuntimeException('User is not an active collaborator for transcription: ' . $transcription_id);
        }
    }

    private function assertInitiator(int $transcription_id, int $user_id): void
    {
        if (!$this->collaboratorRepository->isInitiator($transcription_id, $user_id)) {
            throw new RuntimeException('Only the initiator can perform this collaboration action.');
        }
    }
}
