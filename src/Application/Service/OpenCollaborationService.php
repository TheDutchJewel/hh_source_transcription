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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\OpenCollaborationCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Factory\TranscriptionProviderFactory;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;

final class OpenCollaborationService
{
    public function __construct(
        private readonly TranscriptionProviderFactory $providerFactory,
    ) {
    }

    public function open(OpenCollaborationCommand $command): void
    {
        $this->providerFactory
            ->collaborationOpenerForKey(ProviderKey::INTERNAL)
            ->openCollaboration($command);
    }
}
