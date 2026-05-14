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
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider\ProviderConnectionTester;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\ProviderCredentialRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\ModuleFlashMessages as FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function redirect;
use function route;

final class DiscourseAuthorizationCallbackAction
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $dashboard_url = route('source-transcription-dashboard', ['tree' => $tree->name()]);

        if (!Auth::isMember($tree)) {
            return redirect($dashboard_url);
        }

        $payload = Validator::queryParams($request)->string('payload', '');

        if ($payload === '') {
            FlashMessages::addMessage('The Discourse authorization response did not contain a payload.', 'danger');

            return redirect($dashboard_url);
        }

        try {
            $result = Registry::container()->get(DiscourseUserApiKeyService::class)->storeCallbackPayload(Auth::user()->id(), $payload);
        } catch (RuntimeException $ex) {
            $result = ['success' => false, 'message' => $ex->getMessage()];
        }

        if ($result['success']) {
            $credential_repository = Registry::container()->get(ProviderCredentialRepository::class);
            $credential = $credential_repository->find(Auth::user()->id(), ProviderKey::DISCOURSE);

            if ($credential !== null) {
                $test = Registry::container()->get(ProviderConnectionTester::class)->test(ProviderKey::DISCOURSE, $credential);
                $credential_repository->recordTestResult(Auth::user()->id(), ProviderKey::DISCOURSE, (bool) $test['success'], (string) $test['message']);
                $result = $test;
            }
        }

        FlashMessages::addMessage((string) $result['message'], (bool) $result['success'] ? 'success' : 'danger');

        return redirect($dashboard_url);
    }
}
