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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject;

use Fisharebest\Webtrees\I18N;

final class PrimaryScript
{
    public const string LATIN = 'Latn';
    public const string LATIN_FRAKTUR = 'Latf';
    public const string CYRILLIC = 'Cyrl';
    public const string GREEK = 'Grek';
    public const string HEBREW = 'Hebr';

    public const string UNKNOWN = '?';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::LATIN => I18N::translate('Latin script'),
            self::LATIN_FRAKTUR => I18N::translate('Latin script, Fraktur variant'),
            self::CYRILLIC => I18N::translate('Cyrillic script'),
            self::GREEK => I18N::translate('Greek script'),
            self::HEBREW => I18N::translate('Hebrew script'),
            self::UNKNOWN => I18N::translate('Unknown script'),
        ];
    }

    public static function label(?string $tag): string
    {
        if ($tag === null || $tag === '') {
            return I18N::translate('Script not specified');
        }

        return self::labels()[$tag] ?? $tag;
    }

    public static function isValid(?string $tag): bool
    {
        return $tag === null || $tag === '' || array_key_exists($tag, self::labels());
    }
}