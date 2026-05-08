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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Factory;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\MediaFile;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Entity\Transcription;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Entity\TranscriptionRevision;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\PrimaryForm;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\PrimaryLanguage;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\PrimaryScript;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderPresentation;

final class NoteContentFactory
{
    public function buildTranscriptNote(
        Transcription         $transcription,
        TranscriptionRevision $revision
    ): string
    {
        $lines = [
            '# ' . I18N::translate('Transcription'),
            '',
            $this->metadataLine(I18N::translate('Title'), $transcription->title),
        ];

        $source = Registry::sourceFactory()->make($transcription->source_xref, $transcription->tree);
        if ($source !== null) {
            $lines[] = $this->metadataLine(I18N::translate('Source'), strip_tags($source->fullName()));
        }

        $lines[] = $this->metadataLine(I18N::translate('Provider'), ProviderPresentation::label($revision->provider_key));

        if ($transcription->primary_language_tag !== null) {
            $lines[] = $this->metadataLine(I18N::translate('Primary language'), PrimaryLanguage::label($transcription->primary_language_tag));
        }

        if ($transcription->primary_script_tag !== null) {
            $lines[] = $this->metadataLine(I18N::translate('Primary script'), PrimaryScript::label($transcription->primary_script_tag));
        }

        if ($transcription->primary_form !== null) {
            $lines[] = $this->metadataLine(I18N::translate('Primary form'), PrimaryForm::label($transcription->primary_form));
        }

        $media = $transcription->media_xref !== null
            ? Registry::mediaFactory()->make($transcription->media_xref, $transcription->tree)
            : null;

        if ($media !== null) {
            $lines[] = $this->metadataLine(I18N::translate('Media object'), strip_tags($media->fullName()));
            $lines[] = '';

            $media_files = $media->mediaFiles();
            if ($media_files->isEmpty()) {
                $lines[] = '## ' . I18N::translate('Transcription text');
                $lines[] = '';
                $lines[] = $revision->content_text;
            } else {
                $is_first_file = true;
                foreach ($media_files as $media_file) {
                    /** @var MediaFile $media_file */
                    $lines[] = '## ' . I18N::translateContext('Heading for one media file in a generated note', 'Media file') . ': ' . $this->mediaFileTitle($media_file);
                    $lines[] = '';
                    if ($is_first_file && $revision->content_text !== '') {
                        $lines[] = $revision->content_text;
                        $lines[] = '';
                    }
                    $is_first_file = false;
                }
            }
        } else {
            $lines[] = '';
            $lines[] = '## ' . I18N::translate('Transcription text');
            $lines[] = '';
            $lines[] = $revision->content_text;
        }

        return trim(implode(PHP_EOL, $lines));
    }

    private function metadataLine(string $label, string $value): string
    {
        return '**' . $label . ':** ' . $value;
    }

    private function mediaFileTitle(MediaFile $media_file): string
    {
        return $media_file->title() !== '' ? $media_file->title() : $media_file->filename();
    }
}
