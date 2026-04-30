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

final class PrimaryLanguage
{
    public const string GERMAN = 'de';
    public const string LOW_GERMAN = 'nds';
    public const string WESTERN_FRISIAN = 'fy';
    public const string LATIN = 'la';
    public const string ENGLISH = 'en';
    public const string FRENCH = 'fr';
    public const string DUTCH = 'nl';
    public const string UNDETERMINED = '?';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::GERMAN => I18N::translate('German'),
            self::LOW_GERMAN => I18N::translate('Low German'),
            self::WESTERN_FRISIAN => I18N::translate('Western Frisian'),
            self::LATIN => I18N::translate('Latin'),
            self::ENGLISH => I18N::translate('English'),
            self::FRENCH => I18N::translate('French'),
            self::DUTCH => I18N::translate('Dutch'),
            self::UNDETERMINED => I18N::translate('Undetermined'),
        ];
    }

    public static function label(?string $tag): string
    {
        if ($tag === null || $tag === '') {
            return I18N::translate('Language not specified');
        }

        return self::labels()[$tag] ?? $tag;
    }

    public static function isValid(?string $tag): bool
    {
        return $tag === null || $tag === '' || array_key_exists($tag, self::labels());
    }
}
