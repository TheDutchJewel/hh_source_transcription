<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\CollaborationStatusService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;

use function redirect;
use function route;

final class CollaborationStatusAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isMember($tree)) {
            return response('');
        }

        $user = $request->getAttribute('user');
        $transcription_id = (int) $request->getAttribute('transcription_id');
        $params = (array) $request->getParsedBody();
        $action = (string) ($params['collaboration_action'] ?? '');
        $service = Registry::container()->get(CollaborationStatusService::class);

        switch ($action) {
            case 'submit_for_review':
                $service->submitForReview($transcription_id, $user->id());
                break;

            case 'finalize':
                $service->finalize($transcription_id, $user->id());
                break;

            case 'reopen':
                $service->reopen($transcription_id, $user->id(), Auth::isAdmin($user));
                break;

            default:
                throw new InvalidArgumentException('Unknown collaboration action: ' . $action);
        }

        FlashMessages::addMessage(I18N::translate('The collaboration status has been updated.'), 'success');

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
