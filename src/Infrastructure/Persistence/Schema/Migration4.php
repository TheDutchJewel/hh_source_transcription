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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema;

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Upgrade the database schema from version 4 to version 5.
 */
class Migration4 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (!DB::schema()->hasTable(SchemaManager::TABLE_TRANSKRIBUS_JOBS)) {
            DB::schema()->create(SchemaManager::TABLE_TRANSKRIBUS_JOBS, static function ($table): void {
                $table->increments('id');
                $table->integer('tree_id');
                $table->string('source_xref', 20);
                $table->string('media_xref', 20);
                $table->string('title', 255);
                $table->integer('created_by_user_id');
                $table->string('status', 32);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->text('last_message')->nullable();

                $table->index(['tree_id', 'source_xref'], 'idx_transkribus_jobs_source');
                $table->index(['tree_id', 'media_xref'], 'idx_transkribus_jobs_media');
                $table->index('created_by_user_id', 'idx_transkribus_jobs_user');
            });
        }

        if (!DB::schema()->hasTable(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES)) {
            DB::schema()->create(SchemaManager::TABLE_TRANSKRIBUS_JOB_FILES, static function ($table): void {
                $table->increments('id');
                $table->integer('job_id');
                $table->string('media_file_fact_id', 64);
                $table->text('filename');
                $table->string('mime_type', 64);
                $table->integer('file_size');
                $table->string('status', 32);
                $table->text('upload_reference')->nullable();
                $table->longText('response_json')->nullable();
                $table->text('message')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('uploaded_at')->nullable();

                $table->index('job_id', 'idx_transkribus_job_files_job');
                $table->index('upload_reference', 'idx_transkribus_job_files_reference');
            });
        }
    }
}
