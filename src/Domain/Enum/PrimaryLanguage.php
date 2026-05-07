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

// Die Codes folgen dem ISO 639-1 Standard (2-stellig) bzw. ISO 639-2/3 (3-stellig) für historische oder weniger verbreitete Sprachen

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum;

use Fisharebest\Webtrees\I18N;

enum PrimaryLanguage: string
{
    case GERMAN = 'de';
    case MIDDLE_HIGH_GERMAN = 'gmh';    // historische Sprachstufe, die in Urkunden und Kirchenbüchern häufig vorkommt
    case LOW_GERMAN = 'nds';
    case WESTERN_FRISIAN = 'fy';
    case LATIN = 'la';                  // wichtig, da über viele Jahrhunderte die Amts- und Kirchensprache in Europa
    case ENGLISH = 'en';
    case FRENCH = 'fr';
    case OLD_FRENCH = 'fro';            // Altfranzösisch ist für mittelalterliche Dokumente aus Frankreich und England relevant
    case SPANISH = 'es';
    case RUSSIAN = 'ru';
    case POLISH = 'pl';
    case ITALIAN = 'it';
    case PORTUGUESE = 'pt';
    case DUTCH = 'nl';
    case HEBREW = 'he';
    case YIDDISH = 'yi';
    case CHURCH_SLAVONIC = 'cu';        // historische Sprachstufe, die in Urkunden und Kirchenbüchern häufig vorkommt
    case ARABIC = 'ar';
    case SWEDISH = 'sv';
    case HUNGARIAN = 'hu';
    case CZECH = 'cs';
    case GREEK = 'el';
    case AKKADIAN = 'akk';              // darunter fällt auch die Dialekt- und Zeitstufe der Altbabylonischen Sprache
    case UNDETERMINED = '?';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::GERMAN->value             => I18N::translate('German') . " (" . self::GERMAN->value . ")",
            self::MIDDLE_HIGH_GERMAN->value => I18N::translate('Middle High German') . " (" . self::MIDDLE_HIGH_GERMAN->value . ")",
            self::LOW_GERMAN->value         => I18N::translate('Low German') . " (" . self::LOW_GERMAN->value . ")",
            self::WESTERN_FRISIAN->value    => I18N::translate('Western Frisian') . " (" . self::WESTERN_FRISIAN->value . ")",
            self::LATIN->value              => I18N::translate('Latin') . " (" . self::LATIN->value . ")",
            self::ENGLISH->value            => I18N::translate('English') . " (" . self::ENGLISH->value . ")",
            self::FRENCH->value             => I18N::translate('French') . " (" . self::FRENCH->value . ")",
            self::OLD_FRENCH->value         => I18N::translate('Old French') . " (" . self::OLD_FRENCH->value . ")",
            self::SPANISH->value            => I18N::translate('Spanish') . " (" . self::SPANISH->value . ")",
            self::RUSSIAN->value            => I18N::translate('Russian') . " (" . self::RUSSIAN->value . ")",
            self::POLISH->value             => I18N::translate('Polish') . " (" . self::POLISH->value . ")",
            self::ITALIAN->value            => I18N::translate('Italian') . " (" . self::ITALIAN->value . ")",
            self::PORTUGUESE->value         => I18N::translate('Portuguese') . " (" . self::PORTUGUESE->value . ")",
            self::DUTCH->value              => I18N::translate('Dutch') . " (" . self::DUTCH->value . ")",
            self::HEBREW->value             => I18N::translate('Hebrew') . " (" . self::HEBREW->value . ")",
            self::YIDDISH->value            => I18N::translate('Yiddish') . " (" . self::YIDDISH->value . ")",
            self::CHURCH_SLAVONIC->value    => I18N::translate('Church Slavonic') . " (" . self::CHURCH_SLAVONIC->value . ")",
            self::ARABIC->value             => I18N::translate('Arabic') . " (" . self::ARABIC->value . ")",
            self::SWEDISH->value            => I18N::translate('Swedish') . " (" . self::SWEDISH->value . ")",
            self::HUNGARIAN->value          => I18N::translate('Hungarian') . " (" . self::HUNGARIAN->value . ")",
            self::CZECH->value              => I18N::translate('Czech') . " (" . self::CZECH->value . ")",
            self::GREEK->value              => I18N::translate('Greek') . " (" . self::GREEK->value . ")",
            self::AKKADIAN->value           => I18N::translate('Akkadian') . " (" . self::AKKADIAN->value . ")",
            self::UNDETERMINED->value        => I18N::translate('Undetermined'),
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
            return I18N::translate('Language not specified');
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
