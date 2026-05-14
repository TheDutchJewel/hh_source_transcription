<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\OpenCollaborationCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\OpenCollaborationService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\ModuleFlashMessages as FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function route;

final class OpenCollaborationAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isEditor($tree)) {
            return response('');
        }

        $user = $request->getAttribute('user');
        $transcription_id = (int) $request->getAttribute('transcription_id');
        $transcription = Registry::container()
            ->get(TranscriptionRepository::class)
            ->find($transcription_id);

        if ($transcription === null || in_array($transcription->status, [
            TranscriptionStatus::FINAL->value,
            TranscriptionStatus::CANCELED->value,
        ], true)) {
            return response('');
        }

        $params = (array) $request->getParsedBody();
        $collaborator_user_ids = (array) ($params['collaborator_user_ids'] ?? []);

        Registry::container()
            ->get(OpenCollaborationService::class)
            ->open(new OpenCollaborationCommand(
                transcription_id: $transcription_id,
                initiator_user_id: $user->id(),
                collaborator_user_ids: array_map('intval', $collaborator_user_ids),
                message: trim((string) ($params['message'] ?? '')) ?: null,
                save_current_note_first: ((string) ($params['save_current_note_first'] ?? '')) === '1',
            ));

        FlashMessages::addMessage(I18N::translate('The transcription has been opened for internal collaboration.'), 'success');

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
