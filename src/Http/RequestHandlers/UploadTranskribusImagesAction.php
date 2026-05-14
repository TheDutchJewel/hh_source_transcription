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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Validator;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\UploadTranskribusImagesCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\TranskribusUploadService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\ModuleFlashMessages as FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;
use function trim;

final class UploadTranskribusImagesAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isEditor($tree)) {
            return redirect(route('source-transcription-dashboard', ['tree' => $tree->name()]));
        }

        $source_xref = trim(Validator::parsedBody($request)->string('source_xref', ''), '@');
        $media_xref = trim(Validator::parsedBody($request)->string('media_xref', ''), '@');
        $title = trim(Validator::parsedBody($request)->string('title', ''));
        $media_file_fact_ids = Validator::parsedBody($request)->array('media_file_fact_ids');

        try {
            $result = Registry::container()->get(TranskribusUploadService::class)->upload(
                new UploadTranskribusImagesCommand(
                    tree: $tree,
                    source_xref: $source_xref,
                    media_xref: $media_xref,
                    title: $title,
                    media_file_fact_ids: $media_file_fact_ids,
                    user_id: Auth::id(),
                )
            );

            FlashMessages::addMessage(
                I18N::translate('Transkribus job #%s: %s', (string) $result['job_id'], $result['message']),
                $result['failed'] === 0 ? 'success' : 'warning'
            );
        } catch (\Throwable $ex) {
            FlashMessages::addMessage($ex->getMessage(), 'danger');
        }

        return redirect(route('source-transcription-dashboard', ['tree' => $tree->name()]));
    }
}
