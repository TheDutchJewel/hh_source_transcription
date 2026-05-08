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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use RuntimeException;

final class SourceGateway
{
    public function linkNoteToSource(Tree $tree, string $source_xref, string $note_xref): void
    {
        $source = Registry::sourceFactory()->make($source_xref, $tree);

        if ($source === null) {
            throw new RuntimeException('Source not found: ' . $source_xref);
        }

        if (preg_match('/^\d+\s+NOTE\s+@' . preg_quote($note_xref, '/') . '@/mu', $source->gedcom())) {
            return;
        }

        $source->createFact('1 NOTE @' . $note_xref . '@', true);
    }

    public function gedcom(Tree $tree, string $source_xref): ?string
    {
        return Registry::sourceFactory()->make($source_xref, $tree)?->gedcom();
    }

    public function unlinkNoteFromSource(Tree $tree, string $source_xref, string $note_xref): void
    {
        $source = Registry::sourceFactory()->make($source_xref, $tree);

        if ($source === null) {
            return;
        }

        foreach ($source->facts([], false, Auth::PRIV_HIDE, true) as $fact) {
            if (preg_match('/^1 NOTE @' . preg_quote($note_xref, '/') . '@$/u', $fact->gedcom())) {
                $source->deleteFact($fact->id(), true);
                return;
            }
        }
    }
}
