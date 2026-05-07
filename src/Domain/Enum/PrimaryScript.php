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

/**
 * Values based on ISO 15924
 */
enum PrimaryScript: string
{
    case LATIN = 'Latn';
    case LATIN_FRAKTUR = 'Latf';    // besonders relevant - Kirchenbücher, Urkunden wurden bis ins 20. Jahrhundert oft in Kurrent oder Fraktur verfasst
    case CYRILLIC = 'Cyrl';
    case HEBREW = 'Hebr';
    case ARABIC = 'Arab';
    case GREEK = 'Grek';
    case GEORGIAN = 'Geor';
    case ARMENIAN = 'Armn';
    case GLAGOLITIC = 'Glag';       // älteste slawische Schrift
    case COPTIC = 'Copt';
    case SYRIAC = 'Syrc';
    case OLD_ITALIC = 'Ital';
    case RUNIC = 'Runr';
    case OSMANYA = 'Osma';
    case GUJARATI = 'Gujr';
    case Xsux = 'Xsux';
    case UNKNOWN = '?';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::LATIN->value         => I18N::translate('Latin script') . " (" . self::LATIN->value . ")",
            self::LATIN_FRAKTUR->value => I18N::translate('Fraktur (Latin Fraktur)') . " (" . self::LATIN_FRAKTUR->value . ")",
            self::CYRILLIC->value      => I18N::translate('Cyrillic script') . " (" . self::CYRILLIC->value . ")",
            self::HEBREW->value        => I18N::translate('Hebrew script') . " (" . self::HEBREW->value . ")",
            self::ARABIC->value        => I18N::translate('Arabic script') . " (" . self::ARABIC->value . ")",
            self::GREEK->value         => I18N::translate('Greek script') . " (" . self::GREEK->value . ")",
            self::GEORGIAN->value      => I18N::translate('Georgian script') . " (" . self::GEORGIAN->value . ")",
            self::ARMENIAN->value      => I18N::translate('Armenian script') . " (" . self::ARMENIAN->value . ")",
            self::GLAGOLITIC->value    => I18N::translate('Glagolitic script') . " (" . self::GLAGOLITIC->value . ")",
            self::COPTIC->value        => I18N::translate('Coptic script') . " (" . self::COPTIC->value . ")",
            self::SYRIAC->value        => I18N::translate('Syriac script') . " (" . self::SYRIAC->value . ")",
            self::OLD_ITALIC->value    => I18N::translate('Old Italic script') . " (" . self::OLD_ITALIC->value . ")",
            self::RUNIC->value         => I18N::translate('Runic script') . " (" . self::RUNIC->value . ")",
            self::OSMANYA->value       => I18N::translate('Osmanya script') . " (" . self::OSMANYA->value . ")",
            self::GUJARATI->value      => I18N::translate('Gujarati script') . " (" . self::GUJARATI->value . ")",
            self::Xsux->value          => I18N::translate('Cuneiform script') . " (" . self::Xsux->value . ")",
            self::UNKNOWN->value       => I18N::translate('Unknown script'),
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
            return I18N::translate('Script not specified');
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