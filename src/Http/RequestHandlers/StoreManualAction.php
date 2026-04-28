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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\CreateTranscriptionCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\CreateTranscriptionService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

class StoreManualAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $params = (array) $request->getParsedBody();

        $tree_id = (int) ($params['tree_id'] ?? 0);
        $source_xref = trim((string) ($params['source_xref'] ?? ''));
        $media_xref = trim((string) ($params['media_xref'] ?? ''));
        $title = trim((string) ($params['title'] ?? ''));
        $initial_text = (string) ($params['initial_text'] ?? '');

        $service = Registry::container()->get(CreateTranscriptionService::class);

        $transcription_id = $service->createManual(new CreateTranscriptionCommand(
            tree_id: $tree_id,
            source_xref: $source_xref,
            media_xref: $media_xref !== '' ? $media_xref : null,
            title: $title,
            provider_key: ProviderKey::MANUAL,
            user_id: 1,
            initial_text: $initial_text,
            comment: I18N::translate('Created from UI')
        ));

        FlashMessages::addMessage(I18N::translate('The transcription has been created.'), 'success');

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
