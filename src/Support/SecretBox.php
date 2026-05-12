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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Support;

use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\SettingsRepository;
use RuntimeException;

use function base64_decode;
use function base64_encode;
use function function_exists;
use function hash;
use function json_decode;
use function json_encode;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;

final class SecretBox
{
    private const string KEY_SETTING = 'provider_credentials_encryption_key';
    private const string CIPHER = 'aes-256-gcm';

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    public function encrypt(string $plain_text): string
    {
        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('Unable to encrypt provider credentials because the PHP extension openssl is not available.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plain_text, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Unable to encrypt provider credentials.');
        }

        return base64_encode(json_encode([
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct'  => base64_encode($ciphertext),
        ], JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        if (!function_exists('openssl_decrypt')) {
            return '';
        }

        $decoded = json_decode((string) base64_decode($payload, true), true);
        if (!is_array($decoded)) {
            return '';
        }

        $iv = base64_decode((string) ($decoded['iv'] ?? ''), true);
        $tag = base64_decode((string) ($decoded['tag'] ?? ''), true);
        $ciphertext = base64_decode((string) ($decoded['ct'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            return '';
        }

        $plain_text = openssl_decrypt($ciphertext, self::CIPHER, $this->key(), OPENSSL_RAW_DATA, $iv, $tag);

        return $plain_text === false ? '' : $plain_text;
    }

    private function key(): string
    {
        $key = $this->settingsRepository->get(self::KEY_SETTING, '');

        if ($key === '') {
            $key = base64_encode(random_bytes(32));
            $this->settingsRepository->set(self::KEY_SETTING, $key);
        }

        return hash('sha256', $key, true);
    }
}
