<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository;

use Fisharebest\Webtrees\DB;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema\SchemaManager;

final class SettingsRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        $value = DB::table(SchemaManager::TABLE_METADATA)
            ->where('setting_name', '=', $key)
            ->value('setting_value');

        return $value === null ? $default : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        DB::table(SchemaManager::TABLE_METADATA)->updateOrInsert(
            ['setting_name' => $key],
            ['setting_value' => $value]
        );
    }

    public function getSchemaVersion(): int
    {
        return (int) $this->get('schema_version', '0');
    }

    public function setSchemaVersion(int $version): void
    {
        $this->set('schema_version', (string) $version);
    }
}
