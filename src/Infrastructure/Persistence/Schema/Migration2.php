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
 * Upgrade the database schema from version 2 to version 3.
 */
class Migration2 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (!DB::schema()->hasTable(SchemaManager::TABLE_COLLABORATORS)) {
            DB::schema()->create(SchemaManager::TABLE_COLLABORATORS, static function ($table): void {
                $table->increments('id');
                $table->integer('transcription_id');
                $table->integer('user_id');
                $table->string('role', 32);
                $table->integer('invited_by_user_id');
                $table->timestamp('invited_at')->useCurrent();
                $table->timestamp('accepted_at')->nullable();
                $table->boolean('is_active')->default(true);

                $table->unique(['transcription_id', 'user_id'], 'idx_transcription_collaborators_unique');
                $table->index('transcription_id', 'idx_transcription_collaborators_transcription');
                $table->index('user_id', 'idx_transcription_collaborators_user');
            });
        }
    }
}
