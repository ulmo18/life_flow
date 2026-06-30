<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function verify(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}
