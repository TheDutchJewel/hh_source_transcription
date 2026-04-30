<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Webtrees;

use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\MediaFile;

final class MediaFileGateway
{
    /**
     * @return array<int,MediaFile>
     */
    public function files(Media $media): array
    {
        $files = [];

        foreach ($media->mediaFiles() as $file) {
            /** @var MediaFile $file */
            $files[] = $file;
        }

        return $files;
    }
}
