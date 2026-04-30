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

final class PrimaryForm
{
    public const string HANDWRITTEN_KURRENT = 'handwritten_kurrent';
    public const string HANDWRITTEN_SUETTERLIN = 'handwritten_suetterlin';
    public const string HANDWRITTEN_LATIN = 'handwritten_latin';
    public const string PRINTED_FRAKTUR = 'printed_fraktur';
    public const string PRINTED_ANTIQUA = 'printed_antiqua';
    public const string INSCRIPTION_GRAVESTONE = 'inscription_gravestone';
    public const string AUDIO_INTERVIEW = 'audio_interview';
    public const string AUDIO_LECTURE = 'audio_lecture';
    public const string VIDEO_INTERVIEW = 'video_interview';
    public const string VIDEO_LECTURE = 'video_lecture';
    public const string MIXED = 'mixed';
    public const string OTHER = 'other';
    public const string UNKNOWN = 'unknown';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::HANDWRITTEN_KURRENT => I18N::translate('Handwritten Kurrent'),
            self::HANDWRITTEN_SUETTERLIN => I18N::translate('Handwritten Sütterlin'),
            self::HANDWRITTEN_LATIN => I18N::translate('Handwritten Latin script'),
            self::PRINTED_FRAKTUR => I18N::translate('Printed Fraktur'),
            self::PRINTED_ANTIQUA => I18N::translate('Printed Antiqua'),
            self::INSCRIPTION_GRAVESTONE => I18N::translate('Inscription on gravestone'),
            self::AUDIO_INTERVIEW => I18N::translate('Audio interview'),
            self::AUDIO_LECTURE => I18N::translate('Audio lecture'),
            self::VIDEO_INTERVIEW => I18N::translate('Video interview'),
            self::VIDEO_LECTURE => I18N::translate('Video lecture'),
            self::MIXED => I18N::translate('Mixed'),
            self::OTHER => I18N::translate('Other'),
            self::UNKNOWN => I18N::translate('Unknown'),
        ];
    }

    public static function label(?string $form): string
    {
        if ($form === null || $form === '') {
            return I18N::translate('Form not specified');
        }

        return self::labels()[$form] ?? $form;
    }

    public static function isValid(?string $form): bool
    {
        return $form === null || $form === '' || array_key_exists($form, self::labels());
    }
}