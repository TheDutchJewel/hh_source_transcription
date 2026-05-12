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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Provider;

use Hartenthaler\Webtrees\Module\SourceTranscription\Domain\ValueObject\ProviderKey;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function file_get_contents;
use function function_exists;
use function http_build_query;
use function implode;
use function is_array;
use function json_decode;
use function parse_url;
use function preg_match;
use function rtrim;
use function str_starts_with;
use function stream_context_create;
use function trim;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

final class ProviderConnectionTester
{
    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     *
     * @return array{success:bool,message:string}
     */
    public function test(string $provider_key, array $credential): array
    {
        return match ($provider_key) {
            ProviderKey::DISCOURSE => $this->testDiscourse($credential),
            ProviderKey::TRANSKRIBUS => $this->testTranskribus($credential),
            default => ['success' => false, 'message' => 'No connection test is available for this provider.'],
        };
    }

    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     *
     * @return array{success:bool,message:string}
     */
    private function testDiscourse(array $credential): array
    {
        $settings = $credential['settings'];
        $base_url = rtrim($settings['base_url'] ?? '', '/');
        $api_username = trim($settings['api_username'] ?? '');
        $api_key = trim($credential['secret']);

        if ($base_url === '' || $api_username === '' || $api_key === '') {
            return ['success' => false, 'message' => 'Discourse URL, API username and API key are required.'];
        }

        $response = $this->request(
            'GET',
            $base_url . '/session/current.json',
            [
                'Accept: application/json',
                'Api-Key: ' . $api_key,
                'Api-Username: ' . $api_username,
            ]
        );

        if ($response['status'] >= 200 && $response['status'] < 300) {
            $json = json_decode($response['body'], true);
            if (is_array($json) && (isset($json['current_user']) || isset($json['user']))) {
                return ['success' => true, 'message' => 'Discourse credentials were accepted.'];
            }
        }

        return [
            'success' => false,
            'message' => 'Discourse test failed with HTTP status ' . $response['status'] . '.',
        ];
    }

    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     *
     * @return array{success:bool,message:string}
     */
    private function testTranskribus(array $credential): array
    {
        $settings = $credential['settings'];
        $token_url = trim($settings['token_url'] ?? '');
        $client_id = trim($settings['client_id'] ?? '');
        $username = trim($settings['username'] ?? '');
        $password = trim($credential['secret']);

        if (str_starts_with($password, 'sk_')) {
            return ['success' => true, 'message' => 'Transkribus API key is stored. It will be validated by the upload endpoint.'];
        }

        if ($token_url === '' || $client_id === '' || $username === '' || $password === '') {
            return ['success' => false, 'message' => 'Transkribus token URL, client ID, username and password are required, unless an API key is stored as the secret.'];
        }

        $response = $this->request(
            'POST',
            $token_url,
            ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'password',
                'client_id'  => $client_id,
                'username'   => $username,
                'password'   => $password,
            ])
        );

        if ($response['status'] >= 200 && $response['status'] < 300) {
            $json = json_decode($response['body'], true);
            if (is_array($json) && isset($json['access_token'])) {
                return ['success' => true, 'message' => 'Transkribus credentials were accepted.'];
            }
        }

        return [
            'success' => false,
            'message' => 'Transkribus test failed with HTTP status ' . $response['status'] . '.',
        ];
    }

    /**
     * @return array{status:int,body:string}
     */
    private function request(string $method, string $url, array $headers, string $body = ''): array
    {
        if (parse_url($url, PHP_URL_SCHEME) === null) {
            return ['status' => 0, 'body' => 'Invalid URL.'];
        }

        if (function_exists('curl_init')) {
            $handle = curl_init();
            if ($handle === false) {
                return ['status' => 0, 'body' => 'Unable to initialize cURL.'];
            }

            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_TIMEOUT        => 20,
            ];

            if ($body !== '') {
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            curl_setopt_array($handle, $options);

            $response_body = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            return [
                'status' => $status,
                'body'   => $response_body === false ? '' : (string) $response_body,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => $body,
                'timeout'       => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response_body = @file_get_contents($url, false, $context);
        $status = 0;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches) === 1) {
                $status = (int) $matches[1];
                break;
            }
        }

        return [
            'status' => $status,
            'body'   => $response_body === false ? '' : (string) $response_body,
        ];
    }
}
