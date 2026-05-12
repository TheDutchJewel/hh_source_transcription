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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository;

use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\Tree;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranskribusJobFileStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\Enum\TranskribusJobStatus;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema\SchemaManager;

use function json_encode;

final class TranskribusJobRepository
{
    public function createJob(Tree $tree, string $source_xref, string $media_xref, string $title, int $user_id): int
    {
        return (int) DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOBS)->insertGetId([
            'tree_id'            => $tree->id(),
            'source_xref'        => $source_xref,
            'media_xref'         => $media_xref,
            'title'              => $title,
            'created_by_user_id' => $user_id,
            'status'             => TranskribusJobStatus::UPLOADING->value,
        ]);
    }

    public function createFile(int $job_id, string $fact_id, string $filename, string $mime_type, int $file_size): int
    {
        return (int) DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES)->insertGetId([
            'job_id'             => $job_id,
            'media_file_fact_id' => $fact_id,
            'filename'           => $filename,
            'mime_type'          => $mime_type,
            'file_size'          => $file_size,
            'status'             => TranskribusJobFileStatus::PENDING->value,
        ]);
    }

    public function markFileUploaded(int $file_id, string $reference, array $response): void
    {
        DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES)
            ->where('id', '=', $file_id)
            ->update([
                'status'           => TranskribusJobFileStatus::UPLOADED->value,
                'upload_reference' => $reference,
                'response_json'    => json_encode($response, JSON_THROW_ON_ERROR),
                'uploaded_at'      => DB::raw('CURRENT_TIMESTAMP'),
            ]);
    }

    public function markFileFailed(int $file_id, string $message): void
    {
        DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES)
            ->where('id', '=', $file_id)
            ->update([
                'status'  => TranskribusJobFileStatus::FAILED->value,
                'message' => $message,
            ]);
    }

    public function finishJob(int $job_id, bool $success, string $message): void
    {
        DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOBS)
            ->where('id', '=', $job_id)
            ->update([
                'status'       => $success ? TranskribusJobStatus::UPLOADED->value : TranskribusJobStatus::UPLOAD_FAILED->value,
                'last_message' => $message,
                'uploaded_at'  => $success ? DB::raw('CURRENT_TIMESTAMP') : null,
                'updated_at'   => DB::raw('CURRENT_TIMESTAMP'),
            ]);
    }

    /**
     * @return array<int,object>
     */
    public function recentForTree(Tree $tree): array
    {
        return DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOBS)
            ->where('tree_id', '=', $tree->id())
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->all();
    }

    /**
     * @return array<int,object>
     */
    public function filesForJob(int $job_id): array
    {
        return DB::table(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES)
            ->where('job_id', '=', $job_id)
            ->orderBy('id')
            ->get()
            ->all();
    }
}
