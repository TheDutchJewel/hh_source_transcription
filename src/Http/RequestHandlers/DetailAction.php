<?php

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
        $transcription_id = (int) $request->getAttribute('transcription_id');

        $service = Registry::container()->get(GetTranscriptionDetailService::class);
        $data = $service->get($transcription_id);

        return response(view('hh_source_transcription::detail', [
            'title' => I18N::translate('Transcription'),
            'tree' => $tree,
            ...$data,
        ]));
    }
}
