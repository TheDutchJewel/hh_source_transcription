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
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema\SchemaManager;
use Hartenthaler\Webtrees\Module\SourceTranscription\Support\SecretBox;

use function json_decode;
use function json_encode;

final class ProviderCredentialRepository
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    /**
     * @return array{
     *     user_id:int,
     *     provider_key:string,
     *     settings:array<string,string>,
     *     secret:string,
     *     has_secret:bool,
     *     last_test_status:string|null,
     *     last_test_message:string|null,
     *     last_test_at:string|null
     * }|null
     */
    public function find(int $user_id, string $provider_key): ?array
    {
        $row = DB::table(SchemaManager::TABLE_PROVIDER_CREDENTIALS)
            ->where('user_id', '=', $user_id)
            ->where('provider_key', '=', $provider_key)
            ->first();

        if ($row === null) {
            return null;
        }

        $secret_box = new SecretBox($this->settingsRepository);
        $settings = json_decode((string) ($row->settings_json ?? '{}'), true);

        return [
            'user_id'           => (int) $row->user_id,
            'provider_key'      => (string) $row->provider_key,
            'settings'          => is_array($settings) ? array_map('strval', $settings) : [],
            'secret'            => $secret_box->decrypt((string) ($row->secret_ciphertext ?? '')),
            'has_secret'        => (string) ($row->secret_ciphertext ?? '') !== '',
            'last_test_status'  => $row->last_test_status === null ? null : (string) $row->last_test_status,
            'last_test_message' => $row->last_test_message === null ? null : (string) $row->last_test_message,
            'last_test_at'      => $row->last_test_at === null ? null : (string) $row->last_test_at,
        ];
    }

    /**
     * @param array<string,string> $settings
     */
    public function save(int $user_id, string $provider_key, array $settings, ?string $secret): void
    {
        $existing = $this->find($user_id, $provider_key);
        $secret_box = new SecretBox($this->settingsRepository);
        $secret_ciphertext = $secret === null ? ($existing['has_secret'] ?? false ? $this->existingCiphertext($user_id, $provider_key) : null) : $secret_box->encrypt($secret);

        DB::table(SchemaManager::TABLE_PROVIDER_CREDENTIALS)->updateOrInsert(
            [
                'user_id'      => $user_id,
                'provider_key' => $provider_key,
            ],
            [
                'settings_json'     => json_encode($settings, JSON_THROW_ON_ERROR),
                'secret_ciphertext' => $secret_ciphertext,
                'updated_at'        => DB::raw('CURRENT_TIMESTAMP'),
            ]
        );
    }

    public function delete(int $user_id, string $provider_key): void
    {
        DB::table(SchemaManager::TABLE_PROVIDER_CREDENTIALS)
            ->where('user_id', '=', $user_id)
            ->where('provider_key', '=', $provider_key)
            ->delete();
    }

    public function recordTestResult(int $user_id, string $provider_key, bool $success, string $message): void
    {
        DB::table(SchemaManager::TABLE_PROVIDER_CREDENTIALS)
            ->where('user_id', '=', $user_id)
            ->where('provider_key', '=', $provider_key)
            ->update([
                'last_test_status'  => $success ? 'success' : 'failed',
                'last_test_message' => $message,
                'last_test_at'      => DB::raw('CURRENT_TIMESTAMP'),
                'updated_at'        => DB::raw('CURRENT_TIMESTAMP'),
            ]);
    }

    private function existingCiphertext(int $user_id, string $provider_key): ?string
    {
        $value = DB::table(SchemaManager::TABLE_PROVIDER_CREDENTIALS)
            ->where('user_id', '=', $user_id)
            ->where('provider_key', '=', $provider_key)
            ->value('secret_ciphertext');

        return $value === null ? null : (string) $value;
    }
}
