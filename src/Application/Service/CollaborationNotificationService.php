<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\MessageService;
use Fisharebest\Webtrees\Services\UserService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Entity\Transcription;

use function route;

final class CollaborationNotificationService
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly UserService $userService,
    ) {
    }

    /**
     * @param array<int,int> $recipient_user_ids
     */
    public function notifyCollaborationOpened(
        Transcription $transcription,
        int $sender_user_id,
        array $recipient_user_ids,
        ?string $message = null
    ): void {
        $sender = $this->userService->find($sender_user_id);

        if ($sender === null) {
            return;
        }

        $url = route('source-transcription-detail', [
            'tree' => $transcription->tree->name(),
            'transcription_id' => $transcription->id,
        ]);

        $subject = I18N::translate('Collaboration started for transcription: %s', $transcription->title);
        $body = trim((string) $message);
        if ($body === '') {
            $body = I18N::translate('You have been invited to collaborate on a source transcription.');
        }

        foreach (array_unique(array_map('intval', $recipient_user_ids)) as $recipient_user_id) {
            if ($recipient_user_id === $sender_user_id) {
                continue;
            }

            $recipient = $this->userService->find($recipient_user_id);

            if ($recipient !== null) {
                $this->messageService->deliverMessage($sender, $recipient, $subject, $body, $url, '');
            }
        }
    }

    /**
     * @param array<int,int> $recipient_user_ids
     */
    public function notifyRevisionCreated(
        Transcription $transcription,
        int $sender_user_id,
        int $revision_no,
        array $recipient_user_ids
    ): void {
        $this->notifyTeam(
            $transcription,
            $sender_user_id,
            $recipient_user_ids,
            I18N::translate('New revision for transcription: %s', $transcription->title),
            I18N::translate('A new revision has been created: %s', (string) $revision_no)
        );
    }

    /**
     * @param array<int,int> $recipient_user_ids
     */
    public function notifyStatusChanged(
        Transcription $transcription,
        int $sender_user_id,
        string $status,
        array $recipient_user_ids
    ): void {
        $this->notifyTeam(
            $transcription,
            $sender_user_id,
            $recipient_user_ids,
            I18N::translate('Status changed for transcription: %s', $transcription->title),
            I18N::translate('The transcription status has changed to: %s', $status)
        );
    }

    /**
     * @param array<int,int> $recipient_user_ids
     */
    private function notifyTeam(
        Transcription $transcription,
        int $sender_user_id,
        array $recipient_user_ids,
        string $subject,
        string $body
    ): void {
        $sender = $this->userService->find($sender_user_id);

        if ($sender === null) {
            return;
        }

        $url = route('source-transcription-detail', [
            'tree' => $transcription->tree->name(),
            'transcription_id' => $transcription->id,
        ]);

        foreach (array_unique(array_map('intval', $recipient_user_ids)) as $recipient_user_id) {
            if ($recipient_user_id === $sender_user_id) {
                continue;
            }

            $recipient = $this->userService->find($recipient_user_id);

            if ($recipient !== null) {
                $this->messageService->deliverMessage($sender, $recipient, $subject, $body, $url, '');
            }
        }
    }
}
