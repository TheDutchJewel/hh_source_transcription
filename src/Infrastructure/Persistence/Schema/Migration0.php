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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema;

use Illuminate\Database\Capsule\Manager as DB;
use Fisharebest\Webtrees\Schema\MigrationInterface;

/**
 * Upgrade the database schema from version 0 (empty database) to version 1.
 *
 * Known issue: with MySQL an exception is thrown - "PDO error - There is no active transaction"
 * it happens only once, if the table needs to be created. This operation finishs successfully.
 *
 * see also: "[PHP8] PdoException with Transactions and MySQL implicit commits #3856" https://github.com/fisharebest/webtrees/issues/3856
 */
class Migration0 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (!DB::schema()->hasTable(SchemaManager::TABLE_METADATA)) {
            DB::schema()->create(SchemaManager::TABLE_METADATA, static function ($table): void {
                $table->string('setting_name', 64)->primary();
                $table->text('setting_value');
            });
        }

        if (!DB::schema()->hasTable(SchemaManager::TABLE_TRANSCRIPTIONS)) {
            DB::schema()->create(SchemaManager::TABLE_TRANSCRIPTIONS, static function ($table): void {
                $table->increments('id');
                $table->integer('tree_id');
                $table->string('source_xref', 20);
                $table->string('media_xref', 20)->nullable();
                $table->string('title', 255);
                $table->string('interaction_model', 32);
                $table->string('transcription_type', 32);
                $table->string('provider_key', 32);
                $table->string('status', 32);
                $table->string('primary_language_tag', 32)->nullable();
                $table->string('primary_script_tag', 32)->nullable();
                $table->string('primary_form', 32)->nullable();
                $table->string('tag_note_xref', 20)->nullable();
                $table->string('current_note_xref', 20)->nullable();
                $table->integer('created_by_user_id');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->boolean('is_active')->default(true);

                $table->index(['tree_id', 'source_xref'], 'idx_transcriptions_source');
                $table->index(['tree_id', 'media_xref'], 'idx_transcriptions_media');
            });
        }

        if (!DB::schema()->hasTable(SchemaManager::TABLE_REVISIONS)) {
            DB::schema()->create(SchemaManager::TABLE_REVISIONS, static function ($table): void {
                $table->increments('id');
                $table->integer('transcription_id');
                $table->integer('revision_no');
                $table->string('provider_key', 32);
                $table->string('origin_type', 32);
                $table->text('origin_reference')->nullable();
                $table->string('content_format', 32);
                $table->longText('content_text');
                $table->char('content_hash', 64);
                $table->integer('created_by_user_id');
                $table->timestamp('created_at')->useCurrent();
                $table->text('import_comment')->nullable();
                $table->string('generated_note_xref', 20)->nullable();
                $table->boolean('is_current_revision')->default(false);

                $table->index('transcription_id', 'idx_revisions_transcription');
                $table->index('content_hash', 'idx_revisions_hash');
            });
        }

        if (!DB::schema()->hasTable(SchemaManager::TABLE_NOTE_LINKS)) {
            DB::schema()->create(SchemaManager::TABLE_NOTE_LINKS, static function ($table): void {
                $table->increments('id');
                $table->integer('transcription_id');
                $table->integer('revision_id')->nullable();
                $table->string('note_xref', 20);
                $table->string('link_type', 32);
                $table->timestamp('created_at')->useCurrent();
                $table->integer('created_by_user_id');
                $table->boolean('is_current')->default(false);
                $table->char('note_hash_at_link_time', 64)->nullable();

                $table->index('transcription_id', 'idx_note_links_transcription');
                $table->index('note_xref', 'idx_note_links_note');
            });
        }
    }
}
