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

use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use RuntimeException;

final class SharedNoteGateway
{
    public function createSharedNote(Tree $tree, string $text): string
    {
        $record = $tree->createRecord($this->buildNewNoteGedcom($text));

        return $record->xref();
    }

    public function readSharedNote(Tree $tree, string $note_xref): ?string
    {
        $note = Registry::noteFactory()->make($note_xref, $tree);

        return $note === null ? null : $this->extractNoteText($note->gedcom());
    }

    public function updateSharedNote(Tree $tree, string $note_xref, string $text): void
    {
        $note = Registry::noteFactory()->make($note_xref, $tree);

        if ($note === null) {
            throw new RuntimeException('Shared NOTE not found: ' . $note_xref);
        }

        $note->updateRecord(
            $this->replaceNoteText($note->gedcom(), $note_xref, $text),
            true
        );
    }

    public function buildNewNoteGedcom(string $text): string
    {
        return $this->buildNoteGedcom('@@', $text);
    }

    private function replaceNoteText(string $gedcom, string $xref, string $text): string
    {
        $replacement = str_replace(['\\', '$'], ['\\\\', '\\$'], $this->buildNoteGedcom('@' . $xref . '@', $text));

        $count = 0;
        $updated = preg_replace(
            '/^0 @' . preg_quote($xref, '/') . '@ NOTE.*(?:\n1 (?:CONT|CONC).*)*/u',
            $replacement,
            $gedcom,
            1,
            $count
        );

        if ($updated === null || $count === 0) {
            throw new RuntimeException('Could not update shared NOTE text: ' . $xref);
        }

        return $updated;
    }

    private function buildNoteGedcom(string $xref, string $text): string
    {
        $text = trim(Registry::elementFactory()->make('NOTE:CONT')->canonical($text));

        if ($text === '') {
            return '0 ' . $xref . ' NOTE';
        }

        return '0 ' . $xref . ' NOTE ' . strtr($text, ["\n" => "\n1 CONT "]);
    }

    private function extractNoteText(string $gedcom): string
    {
        $lines = preg_split('/\R/u', $gedcom) ?: [];

        if ($lines === []) {
            return '';
        }

        $first_line = $lines[0];

        if (preg_match('/^0\s+@[^@]+@\s+NOTE(?:\s+(.*))?$/u', $first_line, $match)) {
            $initial_text = $match[1] ?? '';

            $result = [];

            if ($initial_text !== '') {
                $result[] = $initial_text;
            }

            foreach (array_slice($lines, 1) as $line) {
                if (preg_match('/^\d+\s+CONT(?:\s+(.*))?$/u', $line, $m)) {
                    $result[] = $m[1] ?? '';
                    continue;
                }

                if (preg_match('/^\d+\s+CONC(?:\s+(.*))?$/u', $line, $m)) {
                    if ($result === []) {
                        $result[] = $m[1] ?? '';
                    } else {
                        $result[array_key_last($result)] .= $m[1] ?? '';
                    }
                }
            }

            return implode(PHP_EOL, $result);
        }

        return '';
    }
}
