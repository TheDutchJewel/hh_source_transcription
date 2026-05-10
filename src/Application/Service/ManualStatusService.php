<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use RuntimeException;

final class ManualStatusService
{
    public function __construct(
        private readonly TranscriptionRepository $transcriptionRepository,
    ) {
    }

    public function finalize(int $transcription_id): void
    {
        $this->setStatus($transcription_id, TranscriptionStatus::FINAL);
    }

    public function reopen(int $transcription_id): void
    {
        $this->setStatus($transcription_id, TranscriptionStatus::REOPENED);
    }

    private function setStatus(int $transcription_id, TranscriptionStatus $status): void
    {
        $transcription = $this->transcriptionRepository->find($transcription_id);

        if ($transcription === null) {
            throw new RuntimeException('Transcription not found: ' . $transcription_id);
        }

        $this->transcriptionRepository->updateStatus($transcription_id, $status->value);
    }
}
