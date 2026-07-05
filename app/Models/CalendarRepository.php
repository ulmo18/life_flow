<?php

declare(strict_types=1);

namespace App\Models;

final class CalendarRepository
{
    /** @return array{plan: array<int, array<string, string>>, actual: array<int, array<string, string>>} */
    public function getDayEvents(int $userId, string $date): array
    {
        unset($userId, $date);

        $fixturePath = __DIR__ . '/../Data/calendar_fixture.php';
        $events = is_file($fixturePath) ? require $fixturePath : [];

        return [
            'plan' => $events['plan'] ?? [],
            'actual' => $events['actual'] ?? [],
        ];
    }
}
