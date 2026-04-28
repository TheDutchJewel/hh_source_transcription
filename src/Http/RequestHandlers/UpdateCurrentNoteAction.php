<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SharedNoteGateway;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

class UpdateCurrentNoteAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $transcription_id = (int) $request->getAttribute('transcription_id');
        $params = (array) $request->getParsedBody();
        $note_text = (string) ($params['note_text'] ?? '');

        $repo = Registry::container()->get(TranscriptionRepository::class);
        $gateway = Registry::container()->get(SharedNoteGateway::class);

        $transcription = $repo->find($transcription_id);
        if ($transcription !== null && $transcription->current_note_xref !== null) {
            $gateway->updateSharedNote($tree->id(), $transcription->current_note_xref, $note_text);
            FlashMessages::addMessage(I18N::translate('The NOTE has been updated.'), 'success');
        }

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
