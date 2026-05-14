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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Support;

use Fisharebest\Webtrees\FlashMessages;
use Hartenthaler\Webtrees\Module\SourceTranscription\SourceTranscription;

final class ModuleFlashMessages
{
    public static function addMessage(string $message, string $status = 'info'): void
    {
        FlashMessages::addMessage('[' . SourceTranscription::CUSTOM_TITLE . '] ' . $message, $status);
    }
}
