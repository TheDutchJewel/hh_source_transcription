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
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\ProviderCredentialRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranskribusJobRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function response;
use function view;

final class DashboardAction
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isMember($tree)) return response('');

        $title = I18N::translate('Source capture and analysis');

        $repo = Registry::container()->get(TranscriptionRepository::class);
        $job_repo = Registry::container()->get(TranskribusJobRepository::class);
        $credential_repo = Registry::container()->get(ProviderCredentialRepository::class);
        $discourse_credential = $credential_repo->find(Auth::user()->id(), ProviderKey::DISCOURSE);

        $transkribus_jobs = $job_repo->recentForTree($tree);
        $transkribus_job_files = [];

        foreach ($transkribus_jobs as $job) {
            $transkribus_job_files[(int) $job->id] = $job_repo->filesForJob((int) $job->id);
        }

        $content = view('hh_source_transcription::dashboard', [
            'title'            => $title,
            'tree'             => $tree,
            'transcriptions'   => $repo->allActiveForTree($tree),
            'transkribus_jobs' => $transkribus_jobs,
            'transkribus_job_files' => $transkribus_job_files,
            'discourse_authorized' => (bool) ($discourse_credential['has_secret'] ?? false),
            'discourse_settings' => $discourse_credential['settings'] ?? [],
            'discourse_last_test_status' => $discourse_credential['last_test_status'] ?? null,
            'discourse_last_test_message' => $discourse_credential['last_test_message'] ?? null,
        ]);

        return response(view('layouts/default', [
            'title' => $title,
            'tree'    => $tree,
            'request' => $request,
            'content' => $content,
        ]));
    }
}
