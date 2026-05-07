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

enum RevisionOriginType: string
{
    case MANUAL_ENTRY = 'manual_entry';
    case MANUAL_NOTE_SAVE = 'manual_note_save';
    case TRANSKRIBUS_IMPORT = 'transkribus_import';
    case TRANSKRIBUS_SYNC = 'transkribus_sync';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::MANUAL_ENTRY->value       => I18N::translate('Manual entry'),
            self::MANUAL_NOTE_SAVE->value   => I18N::translate('Manual note save'),
            self::TRANSKRIBUS_IMPORT->value => I18N::translate('Transkribus import'),
            self::TRANSKRIBUS_SYNC->value   => I18N::translate('Transkribus sync'),
        ];
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    public static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return I18N::translate('Origin not specified');
        }

        return self::labels()[$value] ?? $value;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    public static function isValid(?string $value): bool
    {
        return $value === null || $value === '' || array_key_exists($value, self::labels());
    }
}
