<?php

declare(strict_types=1);

namespace App\Middleware;

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            return false;
        }

        return true;
    }
}
