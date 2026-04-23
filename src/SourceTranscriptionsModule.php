<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\SourceTranscriptions;

use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;

final class SourceTranscriptionsModule extends AbstractModule implements ModuleInterface, ModuleCustomInterface
{
    use ModuleCustomTrait;

    public const CUSTOM_MODULE = 'source_transcriptions';
    public const CURRENT_SCHEMA_VERSION = 1;

    /**
     * Internal module name (must match folder name)
     */
    public function name(): string
    {
        return self::CUSTOM_MODULE;
    }

    /**
     * Display title in UI
     */
    public function title(): string
    {
        return 'Source Transcriptions';
    }

    /**
     * Short description
     */
    public function description(): string
    {
        return 'Manage transcriptions for sources and media objects with support for multiple providers.';
    }

    /**
     * Module version
     */
    public function version(): string
    {
        return '0.1.0';
    }

    /**
     * Author information
     */
    public function author(): string
    {
        return 'Hermann Hartenthaler';
    }

    /**
     * Minimum webtrees version
     */
    public function minimumVersion(): string
    {
        return '2.2.0';
    }

    /**
     * Bootstrap hook
     */
    public function boot(): void
    {
        // später:
        // - Schema prüfen/migrieren
        // - Provider registrieren
        // - Routen laden
    }

    /**
     * Ressourcenverzeichnis
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/../resources/';
    }
}
