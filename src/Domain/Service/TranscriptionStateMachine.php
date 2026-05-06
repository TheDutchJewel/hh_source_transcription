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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Service;

use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionTransition;
use InvalidArgumentException;

final class TranscriptionStateMachine
{
    /**
     * @return array<TranscriptionTransition>
     */
    public function allowedTransitions(TranscriptionStatus $status): array
    {
        return match ($status) {
            TranscriptionStatus::NEW => [
                TranscriptionTransition::START,
                TranscriptionTransition::CANCEL,
            ],

            TranscriptionStatus::IN_PROGRESS,
            TranscriptionStatus::REOPENED => [
                TranscriptionTransition::SUBMIT_FOR_REVIEW,
                TranscriptionTransition::CANCEL,
            ],

            TranscriptionStatus::READY_FOR_REVIEW => [
                TranscriptionTransition::APPROVE,
                TranscriptionTransition::REOPEN,
            ],

            TranscriptionStatus::FINAL => [
                TranscriptionTransition::REOPEN,
            ],

            TranscriptionStatus::CANCELED => [
                TranscriptionTransition::REOPEN,
            ],
        };
    }

    /**
     * @param TranscriptionStatus $status
     * @param TranscriptionTransition $transition
     * @return bool
     */
    public function canApply(
        TranscriptionStatus $status,
        TranscriptionTransition $transition
    ): bool {
        return in_array($transition, $this->allowedTransitions($status), true);
    }

    /**
     * @param TranscriptionStatus $status
     * @param TranscriptionTransition $transition
     * @return TranscriptionStatus
     */
    public function apply(
        TranscriptionStatus $status,
        TranscriptionTransition $transition
    ): TranscriptionStatus
    {
        return match (true) {
            $status === TranscriptionStatus::NEW
            && $transition === TranscriptionTransition::START
            => TranscriptionStatus::IN_PROGRESS,

            $status === TranscriptionStatus::NEW
            && $transition === TranscriptionTransition::CANCEL
            => TranscriptionStatus::CANCELED,

            in_array($status, [TranscriptionStatus::IN_PROGRESS, TranscriptionStatus::REOPENED], true)
            && $transition === TranscriptionTransition::SUBMIT_FOR_REVIEW
            => TranscriptionStatus::READY_FOR_REVIEW,

            $status === TranscriptionStatus::READY_FOR_REVIEW
            && $transition === TranscriptionTransition::APPROVE
            => TranscriptionStatus::FINAL,

            in_array($status, [TranscriptionStatus::READY_FOR_REVIEW, TranscriptionStatus::FINAL, TranscriptionStatus::CANCELED], true)
            && $transition === TranscriptionTransition::REOPEN
            => TranscriptionStatus::REOPENED,

            default => throw new InvalidArgumentException('Invalid transcription status transition.'),
        };
    }
}
