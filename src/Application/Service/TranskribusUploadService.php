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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Service;

use Fisharebest\Webtrees\MediaFile;
use Fisharebest\Webtrees\Registry;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Dto\UploadTranskribusImagesCommand;
use Hartenthaler\Webtrees\Module\SourceTranscription\Application\Transkribus\TranskribusClient;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\ProviderCredentialRepository;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\TranskribusJobRepository;
use RuntimeException;

use function array_filter;
use function array_values;
use function in_array;
use function trim;

final class TranskribusUploadService
{
    private const int MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const array ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/tiff', 'image/png'];

    public function __construct(
        private readonly ProviderCredentialRepository $credentialRepository,
        private readonly TranskribusJobRepository $jobRepository,
        private readonly TranskribusClient $client,
    ) {
    }

    /**
     * @return array{job_id:int,uploaded:int,failed:int,message:string}
     */
    public function upload(UploadTranskribusImagesCommand $command): array
    {
        if (trim($command->title) === '') {
            throw new RuntimeException('A job title is required.');
        }

        if ($command->media_file_fact_ids === []) {
            throw new RuntimeException('Select at least one media file.');
        }

        $credential = $this->credentialRepository->find($command->user_id, ProviderKey::TRANSKRIBUS);
        if ($credential === null || trim($credential['secret']) === '') {
            throw new RuntimeException('No Transkribus credentials are stored for this user.');
        }

        $media = Registry::mediaFactory()->make($command->media_xref, $command->tree);
        if ($media === null) {
            throw new RuntimeException('The selected media object could not be found.');
        }

        $selected_files = $this->selectedFiles($media->mediaFiles()->all(), $command->media_file_fact_ids);
        if ($selected_files === []) {
            throw new RuntimeException('None of the selected media files could be found.');
        }

        $job_id = $this->jobRepository->createJob(
            $command->tree,
            $command->source_xref,
            $command->media_xref,
            $command->title,
            $command->user_id
        );

        $uploaded = 0;
        $failed = 0;
        $first_error = null;

        foreach ($selected_files as $media_file) {
            $file_id = $this->jobRepository->createFile(
                $job_id,
                $media_file->factId(),
                $media_file->filename(),
                $media_file->mimeType(),
                $this->fileSize($media_file)
            );

            try {
                $this->assertUploadable($media_file);
                $result = $this->client->uploadImage(
                    credential: $credential,
                    filename: $media_file->filename(),
                    mime_type: $media_file->mimeType(),
                    contents: $media_file->fileContents()
                );

                $this->jobRepository->markFileUploaded($file_id, $result['reference'], $result['response']);
                $uploaded++;
            } catch (\Throwable $ex) {
                $this->jobRepository->markFileFailed($file_id, $ex->getMessage());
                $first_error ??= $ex->getMessage();
                $failed++;
            }
        }

        $success = $uploaded > 0 && $failed === 0;
        $message = $failed === 0
            ? 'Uploaded ' . $uploaded . ' image file(s) to Transkribus.'
            : 'Uploaded ' . $uploaded . ' image file(s); ' . $failed . ' image file(s) failed.';

        if ($first_error !== null) {
            $message .= ' First error: ' . $first_error;
        }

        $this->jobRepository->finishJob($job_id, $success, $message);

        return [
            'job_id'   => $job_id,
            'uploaded' => $uploaded,
            'failed'   => $failed,
            'message'  => $message,
        ];
    }

    /**
     * @param array<int,MediaFile> $files
     * @param array<int,string> $fact_ids
     *
     * @return array<int,MediaFile>
     */
    private function selectedFiles(array $files, array $fact_ids): array
    {
        return array_values(array_filter(
            $files,
            static fn (MediaFile $file): bool => in_array($file->factId(), $fact_ids, true)
        ));
    }

    private function assertUploadable(MediaFile $media_file): void
    {
        if ($media_file->isExternal()) {
            throw new RuntimeException('External media files cannot be uploaded directly: ' . $media_file->filename());
        }

        if (!$media_file->fileExists()) {
            throw new RuntimeException('Media file does not exist: ' . $media_file->filename());
        }

        if (!in_array($media_file->mimeType(), self::ACCEPTED_MIME_TYPES, true)) {
            throw new RuntimeException('Unsupported media format: ' . $media_file->filename());
        }

        if ($this->fileSize($media_file) > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Media file exceeds 20 MB: ' . $media_file->filename());
        }

        if ($media_file->fileContents() === '') {
            throw new RuntimeException('Media file could not be read: ' . $media_file->filename());
        }
    }

    private function fileSize(MediaFile $media_file): int
    {
        if ($media_file->isExternal() || !$media_file->fileExists()) {
            return 0;
        }

        try {
            return (int) $media_file->media()->tree()->mediaFilesystem()->fileSize($media_file->filename());
        } catch (\Throwable) {
            return 0;
        }
    }
}
