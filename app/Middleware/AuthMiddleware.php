<?php

declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            return false;
        }

        return true;
    }
}
