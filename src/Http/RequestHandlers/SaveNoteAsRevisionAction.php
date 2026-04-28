<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\SaveNoteAsRevisionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

class SaveNoteAsRevisionAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $transcription_id = (int) $request->getAttribute('transcription_id');

        $service = Registry::container()->get(SaveNoteAsRevisionService::class);
        $revision_id = $service->saveCurrentNoteAsRevision(
            $transcription_id,
            1,
            I18N::translate('Saved from UI')
        );

        FlashMessages::addMessage(
            I18N::translate('The current note has been saved as revision %s.', (string) $revision_id),
            'success'
        );

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
