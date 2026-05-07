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

/*
 * Medienformen für die genealogische Transkription
 * Es gibt keine offiziellen Standard-Codes; folgende projektspezifische Präfixe werden verwendet:
 * ms- Manuskript / handschriftlich
 * pr- Gedruckt (Print)
 * ts- Maschinenschrift (Typescript)
 * av- Audio/Video
 * ph- Fotografisch
 * in- Inschrift
 * dg- Digital
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum;

use Fisharebest\Webtrees\I18N;

enum PrimaryForm: string
{
    // Kirchenbücher
    case MS_CHURCH = 'ms-church';

    // Urkunden
    case MS_DEED = 'ms-deed';
    case PR_DEED = 'pr-deed';

    // Briefe
    case MS_LETTER = 'ms-letter';

    // Presse / Medien
    case PR_NEWS = 'pr-news';
    case PR_JOURNAL = 'pr-journal';
    case PR_BOOK = 'pr-book';

    // Protokolle / Dokumente
    case MS_MEMO = 'ms-memo';
    case MS_STENO = 'ms-steno';
    case TS_TYPED = 'ts-typed';
    case DG_TEXT = 'dg-text';
    case DG_MS = 'dg-ms';

    // Fotografisch / Mikrofilm
    case PH_ANNOT = 'ph-annot';
    case PH_MICRO = 'ph-micro';

    // Inschriften
    case IN_GRAVE = 'in-grave';
    case IN_BUILD = 'in-build';

    // Audio / Video
    case AV_AUDIO = 'av-audio';
    case AV_VIDEO = 'av-video';
    case AV_INTERVIEW = 'av-interview';

    // Allgemein
    case MIXED = 'mixed';
    case OTHER = 'other';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            // Kirchenbücher
            self::MS_CHURCH->value              => I18N::translate('Handwritten church register') . " (" . self::MS_CHURCH->value . ")",

            // Urkunden
            self::MS_DEED->value                => I18N::translate('Handwritten deed/charter') . " (" . self::MS_DEED->value . ")",
            self::PR_DEED->value                => I18N::translate('Printed deed/charter') . " (" . self::PR_DEED->value . ")",

            // Briefe
            self::MS_LETTER->value              => I18N::translate('Handwritten letter') . " (" . self::MS_LETTER->value . ")",

            // Presse / Bücher
            self::PR_NEWS->value                => I18N::translate('Printed newspaper') . " (" . self::PR_NEWS->value . ")",
            self::PR_JOURNAL->value             => I18N::translate('Printed journal/magazine') . " (" . self::PR_JOURNAL->value . ")",
            self::PR_BOOK->value                => I18N::translate('Printed book') . " (" . self::PR_BOOK->value . ")",

            // Protokolle / Dokumente
            self::MS_MEMO->value                => I18N::translate('Memorial record / Minutes from memory') . " (" . self::MS_MEMO->value . ")",
            self::MS_STENO->value               => I18N::translate('Stenographic record') . " (" . self::MS_STENO->value . ")",
            self::TS_TYPED->value               => I18N::translate('Typewritten document') . " (" . self::TS_TYPED->value . ")",
            self::DG_TEXT->value                => I18N::translate('Digital text document') . " (" . self::DG_TEXT->value . ")",
            self::DG_MS->value                  => I18N::translate('Digitized manuscript') . " (" . self::DG_MS->value . ")",

            // Fotografisch / Mikrofilm
            self::PH_ANNOT->value               => I18N::translate('Annotated photograph') . " (" . self::PH_ANNOT->value . ")",
            self::PH_MICRO->value               => I18N::translate('Microfilm / Microfiche') . " (" . self::PH_MICRO->value . ")",

            // Inschriften
            self::IN_GRAVE->value               => I18N::translate('Gravestone inscription') . " (" . self::IN_GRAVE->value . ")",
            self::IN_BUILD->value               => I18N::translate('Building inscription') . " (" . self::IN_BUILD->value . ")",

            // Audio / Video
            self::AV_AUDIO->value               => I18N::translate('Audio recording') . " (" . self::AV_AUDIO->value . ")",
            self::AV_VIDEO->value               => I18N::translate('Video recording') . " (" . self::AV_VIDEO->value . ")",
            self::AV_INTERVIEW->value           => I18N::translate('Video interview') . " (" . self::AV_INTERVIEW->value . ")",

            // Allgemein
            self::MIXED->value                  => I18N::translate('Mixed'),
            self::OTHER->value                  => I18N::translate('Other'),
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
            return I18N::translate('Form not specified');
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
