<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function response;
use function view;

class DashboardAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $repo = Registry::container()->get(TranscriptionRepository::class);

        return response(view('hh_source_transcription::dashboard', [
            'title' => I18N::translate('Transcriptions'),
            'tree' => $tree,
            'transcriptions' => $repo->allActive(),
        ]));
    }
}
