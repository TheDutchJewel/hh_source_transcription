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
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum;

use Fisharebest\Webtrees\I18N;

enum TranskribusJobStatus: string
{
    case UPLOADING = 'uploading';
    case UPLOADED = 'uploaded';
    case UPLOAD_FAILED = 'upload_failed';

    /**
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::UPLOADING->value     => I18N::translate('Uploading'),
            self::UPLOADED->value      => I18N::translate('Uploaded'),
            self::UPLOAD_FAILED->value => I18N::translate('Upload failed'),
        ];
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
