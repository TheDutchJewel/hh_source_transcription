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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Discourse;

use Fisharebest\Webtrees\Session;
use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;
use Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\ProviderCredentialRepository;
use RuntimeException;

use function base64_decode;
use function bin2hex;
use function http_build_query;
use function is_array;
use function is_string;
use function json_decode;
use function openssl_error_string;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function openssl_private_decrypt;
use function random_bytes;
use function rtrim;
use function strtr;
use function time;
use function trim;

use const OPENSSL_KEYTYPE_RSA;
use const OPENSSL_PKCS1_OAEP_PADDING;
use const OPENSSL_PKCS1_PADDING;

final class DiscourseUserApiKeyService
{
    private const string SESSION_KEY = 'hh_source_transcription_discourse_auth';
    private const string CLIENT_ID = 'hh_source_transcription';
    private const string APPLICATION_NAME = 'webtrees Source Transcription';
    private const string SCOPES = 'read,write,session_info';

    public function __construct(
        private readonly ProviderCredentialRepository $credentialRepository,
    ) {
    }

    public function authorizationUrl(int $user_id, string $base_url, string $callback_url): string
    {
        $base_url = rtrim(trim($base_url), '/');

        if ($base_url === '') {
            throw new RuntimeException('Discourse URL is required.');
        }

        $key_pair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key_pair === false) {
            throw new RuntimeException('Unable to create a temporary Discourse authorization key pair.');
        }

        $private_key = '';
        if (!openssl_pkey_export($key_pair, $private_key)) {
            throw new RuntimeException('Unable to export the temporary Discourse authorization key.');
        }

        $details = openssl_pkey_get_details($key_pair);
        if (!is_array($details) || !is_string($details['key'] ?? null)) {
            throw new RuntimeException('Unable to read the temporary Discourse public key.');
        }

        $nonce = bin2hex(random_bytes(16));
        Session::put(self::SESSION_KEY, [
            'user_id'     => $user_id,
            'base_url'    => $base_url,
            'private_key' => $private_key,
            'nonce'       => $nonce,
            'created_at'  => time(),
        ]);

        return $base_url . '/user-api-key/new?' . http_build_query([
            'application_name' => self::APPLICATION_NAME,
            'client_id'        => self::CLIENT_ID,
            'scopes'           => self::SCOPES,
            'public_key'       => $details['key'],
            'nonce'            => $nonce,
            'auth_redirect'    => $callback_url,
            'padding'          => 'oaep',
        ]);
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function storeCallbackPayload(int $user_id, string $payload): array
    {
        $state = Session::pull(self::SESSION_KEY);

        if (!is_array($state) || (int) ($state['user_id'] ?? 0) !== $user_id) {
            return ['success' => false, 'message' => 'The Discourse authorization session has expired.'];
        }

        if (time() - (int) ($state['created_at'] ?? 0) > 900) {
            return ['success' => false, 'message' => 'The Discourse authorization session has expired.'];
        }

        $data = $this->decryptPayload($payload, (string) $state['private_key']);

        if (($data['nonce'] ?? '') !== (string) $state['nonce']) {
            return ['success' => false, 'message' => 'The Discourse authorization response did not match the current request.'];
        }

        $api_key = (string) ($data['key'] ?? $data['api_key'] ?? $data['user_api_key'] ?? '');
        if ($api_key === '') {
            return ['success' => false, 'message' => 'The Discourse authorization response did not contain an API key.'];
        }

        $settings = [
            'base_url'  => (string) $state['base_url'],
            'client_id' => self::CLIENT_ID,
            'scopes'    => self::SCOPES,
        ];

        foreach (['username', 'user_id'] as $field) {
            if (isset($data[$field])) {
                $settings[$field] = (string) $data[$field];
            }
        }

        $this->credentialRepository->save($user_id, ProviderKey::DISCOURSE, $settings, $api_key);

        return ['success' => true, 'message' => 'Discourse authorization has been saved.'];
    }

    /**
     * @return array<string,mixed>
     */
    private function decryptPayload(string $payload, string $private_key): array
    {
        $ciphertext = $this->base64Decode($payload);

        foreach ([OPENSSL_PKCS1_OAEP_PADDING, OPENSSL_PKCS1_PADDING] as $padding) {
            $plaintext = '';
            if (openssl_private_decrypt($ciphertext, $plaintext, $private_key, $padding)) {
                $data = json_decode($plaintext, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        $error = openssl_error_string();
        throw new RuntimeException('Unable to decrypt the Discourse authorization response.' . ($error !== false ? ' ' . $error : ''));
    }

    private function base64Decode(string $payload): string
    {
        $payload = strtr($payload, ' ', '+');
        $decoded = base64_decode($payload, true);

        if ($decoded !== false) {
            return $decoded;
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('The Discourse authorization response was not valid base64 data.');
        }

        return $decoded;
    }
}
