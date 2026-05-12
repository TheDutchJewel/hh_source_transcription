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

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Application\Transkribus;

use CURLFile;
use Hartenthaler\Webtrees\Module\SourceTranscription\SourceTranscription;
use RuntimeException;

use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function class_exists;
use function file_put_contents;
use function function_exists;
use function http_build_query;
use function is_array;
use function json_decode;
use function strlen;
use function str_starts_with;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const CURLINFO_HTTP_CODE;
use const CURL_HTTP_VERSION_1_1;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_HTTP_VERSION;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

final class TranskribusClient
{
    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     *
     * @return array{reference:string,response:array<string,mixed>}
     */
    public function uploadImage(array $credential, string $filename, string $mime_type, string $contents): array
    {
        if (!function_exists('curl_init') || !class_exists(CURLFile::class)) {
            throw new RuntimeException('The PHP extension curl is required for Transkribus uploads.');
        }

        $access_token = $this->bearerToken($credential);
        $settings = $credential['settings'];
        $upload_url = trim($settings['upload_url'] ?? SourceTranscription::DEFAULT_TRANSKRIBUS_UPLOAD_URL);

        $temporary_file = $this->temporaryFile($contents);

        try {
            $response = $this->request(
                'POST',
                $upload_url,
                [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $access_token,
                    'Expect:',
                ],
                [
                    'file' => new CURLFile($temporary_file, $mime_type, $filename),
                ]
            );
        } finally {
            @unlink($temporary_file);
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new RuntimeException($this->errorMessage('Transkribus upload failed', $response));
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new RuntimeException('Transkribus upload response is not JSON.');
        }

        $reference = $this->referenceFromResponse($json);
        if ($reference === '') {
            throw new RuntimeException('Transkribus upload response did not contain an upload reference.');
        }

        return [
            'reference' => $reference,
            'response'  => $json,
        ];
    }

    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     */
    private function bearerToken(array $credential): string
    {
        $secret = trim($credential['secret']);

        if (str_starts_with($secret, 'sk_')) {
            return $secret;
        }

        return $this->accessToken($credential);
    }

    /**
     * @param array{settings:array<string,string>, secret:string} $credential
     */
    private function accessToken(array $credential): string
    {
        $settings = $credential['settings'];
        $token_url = trim($settings['token_url'] ?? SourceTranscription::DEFAULT_TRANSKRIBUS_TOKEN_URL);
        $client_id = trim($settings['client_id'] ?? SourceTranscription::DEFAULT_TRANSKRIBUS_CLIENT_ID);
        $username = trim($settings['username'] ?? '');
        $password = trim($credential['secret']);

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

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new RuntimeException($this->errorMessage('Could not obtain a Transkribus access token', $response));
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json) || !isset($json['access_token'])) {
            throw new RuntimeException('Transkribus token response did not contain an access token.');
        }

        return (string) $json['access_token'];
    }

    /**
     * @param array<string,mixed> $response
     */
    private function referenceFromResponse(array $response): string
    {
        foreach (['id', 'uploadId', 'upload_id', 'docId', 'doc_id', 'processId', 'process_id'] as $key) {
            if (isset($response[$key]) && (string) $response[$key] !== '') {
                return (string) $response[$key];
            }
        }

        return '';
    }

    private function temporaryFile(string $contents): string
    {
        $temporary_file = tempnam(sys_get_temp_dir(), 'source-transcription-transkribus-');

        if ($temporary_file === false) {
            throw new RuntimeException('Unable to create a temporary upload file.');
        }

        if (file_put_contents($temporary_file, $contents) === false) {
            @unlink($temporary_file);
            throw new RuntimeException('Unable to write a temporary upload file.');
        }

        return $temporary_file;
    }

    /**
     * @param array{status:int,body:string,error:string} $response
     */
    private function errorMessage(string $prefix, array $response): string
    {
        $message = $prefix . '. HTTP status ' . $response['status'] . '.';
        $detail = trim($response['error'] !== '' ? $response['error'] : $response['body']);

        if ($detail === '') {
            return $message;
        }

        if (strlen($detail) > 500) {
            $detail = substr($detail, 0, 500) . '...';
        }

        return $message . ' Response: ' . $detail;
    }

    /**
     * @return array{status:int,body:string,error:string}
     */
    private function request(string $method, string $url, array $headers, string|array $body = ''): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP extension curl is required for Transkribus uploads.');
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
        ];

        if ($body !== '' && $body !== []) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);
        $response_body = curl_exec($handle);
        $error = $response_body === false ? curl_error($handle) : '';
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return [
            'status' => $status,
            'body'   => $response_body === false ? '' : (string) $response_body,
            'error'  => $error,
        ];
    }
}
