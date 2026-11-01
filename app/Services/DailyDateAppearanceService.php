<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;
use DateTimeImmutable;

final class DailyDateAppearanceService
{
    public function getClass(string $date): string
    {
        $meta = (new CalendarRepository())->getDateMeta($date);
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        if (!empty($meta['is_holiday']) || !empty($meta['is_substitute_holiday']) || $weekday === 7) {
            return 'is-holiday';
        }

        return $weekday === 6 ? 'is-saturday' : '';
    }
}
