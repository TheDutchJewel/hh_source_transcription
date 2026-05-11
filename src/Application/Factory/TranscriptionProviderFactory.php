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
 * A webtrees (https://www.webtrees.net) 2.2 custom module to transcribe sources
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Factory;

use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\CreatesTranscriptionsInterface;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\InternalCollaborationProvider;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\ManualTranscriptionProvider;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\OpensCollaborationInterface;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\TranscriptionProviderInterface;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use InvalidArgumentException;

final class TranscriptionProviderFactory
{
    public function __construct(
        private readonly ManualTranscriptionProvider $manualProvider,
        private readonly InternalCollaborationProvider $internalCollaborationProvider,
    ) {
    }

    public function forKey(string $provider_key): TranscriptionProviderInterface
    {
        return match ($provider_key) {
            ProviderKey::MANUAL => $this->manualProvider,
            ProviderKey::INTERNAL => $this->internalCollaborationProvider,
            default => throw new InvalidArgumentException('Unknown transcription provider: ' . $provider_key),
        };
    }

    public function creatorForKey(string $provider_key): CreatesTranscriptionsInterface
    {
        $provider = $this->forKey($provider_key);

        if (!$provider instanceof CreatesTranscriptionsInterface) {
            throw new InvalidArgumentException('Provider cannot create transcriptions: ' . $provider_key);
        }

        return $provider;
    }

    public function collaborationOpenerForKey(string $provider_key): OpensCollaborationInterface
    {
        $provider = $this->forKey($provider_key);

        if (!$provider instanceof OpensCollaborationInterface) {
            throw new InvalidArgumentException('Provider cannot open collaboration: ' . $provider_key);
        }

        return $provider;
    }
}
