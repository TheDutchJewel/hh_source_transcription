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

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\GetTranscriptionDetailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function response;
use function view;

class DetailAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $title = I18N::translate('Transcription');

        $transcription_id = (int) $request->getAttribute('transcription_id');
        $service = Registry::container()->get(GetTranscriptionDetailService::class);
        $data = $service->get($tree, $transcription_id);

        $content = view('hh_source_transcription::detail', [
            'title'         => $title,
            'tree'          => $tree,
            'transcription' => $data['transcription'],
            'revisions'     => $data['revisions'],
            'note_text'     => $data['note_text'],
            'source'        => $data['source'],
            'media_object'  => $data['media_object'],
            'media_files'   => $data['media_files'],
        ]);

        return response(view('layouts/default', [
            'title'   => $title,
            'tree'    => $tree,
            'request' => $request,
            'content' => $content,
        ]));
    }
}
