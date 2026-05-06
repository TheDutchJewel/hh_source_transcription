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
 
namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum;

use Fisharebest\Webtrees\I18N;

enum TranscriptionStatus: string
{
    /** Initial state after creation, before work has started. */
    case NEW = 'new';

    /** The transcription is currently being edited. */
    case IN_PROGRESS = 'in_progress';

    /** The transcription is complete and waiting for review/approval. */
    case READY_FOR_REVIEW = 'ready_for_review';

    /** The transcription has been approved and is finalized. */
    case FINAL = 'final';

    /** A previously finalized, pending, or canceled transcription has been reopened for editing. */
    case REOPENED = 'reopened';

    /** The transcription process was canceled. */
    case CANCELED = 'canceled';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::NEW->value              => I18N::translate('New'),
            self::IN_PROGRESS->value      => I18N::translate('In progress'),
            self::READY_FOR_REVIEW->value => I18N::translate('Ready for review'),
            self::FINAL->value            => I18N::translate('Final'),
            self::REOPENED->value         => I18N::translate('Reopened'),
            self::CANCELED->value        => I18N::translate('Canceled'),
        ];
    }
}