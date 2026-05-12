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
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto;

use Fisharebest\Webtrees\Tree;

final class UploadTranskribusImagesCommand
{
    /**
     * @param array<int,string> $media_file_fact_ids
     */
    public function __construct(
        public readonly Tree $tree,
        public readonly string $source_xref,
        public readonly string $media_xref,
        public readonly string $title,
        public readonly array $media_file_fact_ids,
        public readonly int $user_id,
    ) {
    }
}
