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

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service\SaveNoteAsRevisionService;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\RevisionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranscriptionRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees\SharedNoteGateway;
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
        $user = $request->getAttribute('user');

        $transcription_id = (int) $request->getAttribute('transcription_id');

        $params = (array) $request->getParsedBody();

        if (array_key_exists('note_text', $params)) {
            $note_text = (string) $params['note_text'];
            $repo = Registry::container()->get(TranscriptionRepository::class);
            $gateway = Registry::container()->get(SharedNoteGateway::class);

            $transcription = $repo->find($transcription_id);
            if ($transcription !== null && $transcription->current_note_xref !== null) {
                $gateway->updateSharedNote(
                    $transcription->tree,
                    $transcription->current_note_xref,
                    $note_text
                );
            }
        }

        $service = Registry::container()->get(SaveNoteAsRevisionService::class);
        $revision_id = $service->saveCurrentNoteAsRevision(
            $transcription_id,
            $user->id(),
            I18N::translate('Saved from UI')
        );

        $revision = Registry::container()
            ->get(RevisionRepository::class)
            ->find($revision_id);
        $revision_no = $revision?->revision_no ?? $revision_id;

        FlashMessages::addMessage(
            I18N::translate('The current NOTE has been saved as revision %s.', (string) $revision_no),
            'success'
        );

        return redirect(route('source-transcription-detail', [
            'tree' => $tree->name(),
            'transcription_id' => $transcription_id,
        ]));
    }
}
