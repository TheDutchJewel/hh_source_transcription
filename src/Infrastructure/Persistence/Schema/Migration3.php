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
 * Upgrade the database schema from version 3 to version 4.
 */
class Migration3 implements MigrationInterface
{
    public function upgrade(): void
    {
        if (!DB::schema()->hasTable(SchemaManager::TABLE_PROVIDER_CREDENTIALS)) {
            DB::schema()->create(SchemaManager::TABLE_PROVIDER_CREDENTIALS, static function ($table): void {
                $table->increments('id');
                $table->integer('user_id');
                $table->string('provider_key', 32);
                $table->text('settings_json')->nullable();
                $table->text('secret_ciphertext')->nullable();
                $table->string('last_test_status', 16)->nullable();
                $table->text('last_test_message')->nullable();
                $table->timestamp('last_test_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['user_id', 'provider_key'], 'idx_provider_credentials_unique');
                $table->index('user_id', 'idx_provider_credentials_user');
                $table->index('provider_key', 'idx_provider_credentials_provider');
            });
        }
    }
}
