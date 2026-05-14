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
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Validator;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Discourse\DiscourseUserApiKeyService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\ModuleFlashMessages as FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function redirect;
use function route;

final class AuthorizeDiscourseAction
{
    public const string PRODUCTION_URL = 'https://discourse.genealogy.net';
    public const string TEST_URL = 'https://discourse-test.genealogy.net';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isMember($tree)) {
            return redirect(route('source-transcription-dashboard', ['tree' => $tree->name()]));
        }

        $base_url = Validator::queryParams($request)->isInArray([
            self::PRODUCTION_URL,
            self::TEST_URL,
        ])->string('base_url', self::PRODUCTION_URL);

        try {
            $authorization_url = Registry::container()->get(DiscourseUserApiKeyService::class)->authorizationUrl(
                Auth::user()->id(),
                $base_url,
                route('source-transcription-discourse-callback', ['tree' => $tree->name()])
            );
        } catch (RuntimeException $ex) {
            FlashMessages::addMessage($ex->getMessage(), 'danger');

            return redirect(route('source-transcription-dashboard', ['tree' => $tree->name()]));
        }

        return redirect($authorization_url);
    }
}
