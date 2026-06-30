<?php

declare(strict_types=1);

namespace App\Services;

final class GoogleOAuthService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/google.php';
    }

    public function isConfigured(): bool
    {
        return $this->config['client_id'] !== ''
            && $this->config['client_secret'] !== ''
            && $this->config['redirect_uri'] !== '';
    }

    public function createState(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function getAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public function fetchUserByCode(string $code): ?array
    {
        $tokenResponse = $this->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenResponse['access_token']) || !is_string($tokenResponse['access_token'])) {
            return null;
        }

        $userInfo = $this->getJson('https://openidconnect.googleapis.com/v1/userinfo', [
            'Authorization: Bearer ' . $tokenResponse['access_token'],
        ]);

        if (!is_array($userInfo)) {
            return null;
        }

        return [
            'provider_user_id' => (string) ($userInfo['sub'] ?? ''),
            'email' => (string) ($userInfo['email'] ?? ''),
            'email_verified' => (bool) ($userInfo['email_verified'] ?? false),
            'name' => (string) ($userInfo['name'] ?? ''),
        ];
    }

    private function postForm(string $url, array $data): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            error_log('[google_oauth] userinfo endpoint request failed');
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log('[google_oauth] userinfo endpoint returned non-json response');
            return null;
        }

        return $decoded;
    }

    private function getJson(string $url, array $headers = []): ?array
    {
        $header = "Accept: application/json\r\n";
        if ($headers !== []) {
            $header .= implode("\r\n", $headers) . "\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $header,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            error_log('[google_oauth] token endpoint request failed');
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log('[google_oauth] token endpoint returned non-json response');
            return null;
        }

        return $decoded;
    }
}
