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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\InteractionModel;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;

final class DiscourseTranscriptionProvider implements TranscriptionProviderInterface
{
    public function key(): string
    {
        return ProviderKey::DISCOURSE;
    }

    public function interactionModel(): string
    {
        return InteractionModel::CROWD_ASYNC->value;
    }
}
