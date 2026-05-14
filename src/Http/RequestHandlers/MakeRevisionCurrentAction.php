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

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\GenerateOrUpdateNoteService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranscriptionStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\NoteStrategy;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\RevisionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionCollaboratorRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\ModuleFlashMessages as FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function redirect;
use function response;
use function route;

class MakeRevisionCurrentAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');

        if (!Auth::isEditor($tree)) {
            return response('');
        }

        $user = $request->getAttribute('user');
        $transcription_id = (int) $request->getAttribute('transcription_id');
        $revision_id = (int) $request->getAttribute('revision_id');

        $transcription_repository = Registry::container()->get(TranscriptionRepository::class);
        $revision_repository = Registry::container()->get(RevisionRepository::class);

        $transcription = $transcription_repository->find($transcription_id);
        $revision = $revision_repository->find($revision_id);

        if ($transcription === null || $revision === null || $revision->transcription_id !== $transcription_id) {
            return response('');
        }

        $transcription_is_editable = !in_array($transcription->status, [
            TranscriptionStatus::FINAL->value,
            TranscriptionStatus::CANCELED->value,
        ], true);

        $can_edit = $transcription_is_editable && (
            $transcription->provider_key !== ProviderKey::INTERNAL ||
            Registry::container()
                ->get(TranscriptionCollaboratorRepository::class)
                ->isActiveCollaborator($transcription_id, $user->id())
        );

        if (!$can_edit) {
            return response('');
        }

        Registry::container()
            ->get(GenerateOrUpdateNoteService::class)
            ->applyRevisionToCurrentNote(
                $transcription_id,
                $revision_id,
                NoteStrategy::ALWAYS_UPDATE
            );

        $revision_repository->markCurrent($transcription_id, $revision_id);

        FlashMessages::addMessage(
            I18N::translate('Revision %s is now the current revision.', (string) $revision->revision_no),
            'success'
        );

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
