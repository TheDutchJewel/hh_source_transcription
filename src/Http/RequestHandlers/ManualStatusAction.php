<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\GenerateOrUpdateNoteService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\ManualStatusService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\SaveNoteAsRevisionService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

final class ManualStatusAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isEditor($tree)) {
            return response('');
        }

        $transcription_id = (int) $request->getAttribute('transcription_id');
        $user = $request->getAttribute('user');
        $params = (array) $request->getParsedBody();
        $action = (string) ($params['manual_action'] ?? '');
        $service = Registry::container()->get(ManualStatusService::class);

        switch ($action) {
            case 'finalize':
                if (array_key_exists('note_text', $params)) {
                    Registry::container()
                        ->get(GenerateOrUpdateNoteService::class)
                        ->updateNoteText($transcription_id, (string) $params['note_text']);
                }

                Registry::container()
                    ->get(SaveNoteAsRevisionService::class)
                    ->saveCurrentNoteAsRevision(
                        $transcription_id,
                        $user->id(),
                        I18N::translate('Saved on finalize')
                    );

                $service->finalize($transcription_id);
                break;

            case 'reopen':
                $service->reopen($transcription_id);
                break;

            default:
                throw new InvalidArgumentException('Unknown manual status action: ' . $action);
        }

        FlashMessages::addMessage(I18N::translate('The transcription status has been updated.'), 'success');

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
