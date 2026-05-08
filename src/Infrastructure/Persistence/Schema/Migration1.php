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

use Fisharebest\Webtrees\Schema\MigrationInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Upgrade the database schema from version 1 to version 2.
 */
class Migration1 implements MigrationInterface
{
    public function upgrade(): void
    {
        DB::schema()->table(SchemaManager::TABLE_REVISIONS, static function ($table): void {
            if (!DB::schema()->hasColumn(SchemaManager::TABLE_REVISIONS, 'generated_note_changed_by_user_id')) {
                $table->integer('generated_note_changed_by_user_id')->nullable();
            }

            if (!DB::schema()->hasColumn(SchemaManager::TABLE_REVISIONS, 'generated_note_changed_by_user_name')) {
                $table->string('generated_note_changed_by_user_name', 255)->nullable();
            }

            if (!DB::schema()->hasColumn(SchemaManager::TABLE_REVISIONS, 'generated_note_changed_at')) {
                $table->timestamp('generated_note_changed_at')->nullable();
            }
        });
    }
}
