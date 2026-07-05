<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;

final class CalendarService
{
    private CalendarRepository $calendarRepository;

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
    }

    /** @return array<string, mixed> */
    public function getDayViewData(int $userId): array
    {
        $date = date('Y-m-d');
        $events = $this->calendarRepository->getDayEvents($userId, $date);

        return [
            'dateTitle' => date('m.d'),
            'dateSubTitle' => date('D') . ', Today · DayGrid Prototype',
            'planSegments' => $this->buildSegments($events['plan']),
            'actualSegments' => $this->buildSegments($events['actual']),
        ];
    }

    /** @param array<int, array<string, string>> $events */
    private function buildSegments(array $events): array
    {
        $segments = [];

        foreach ($events as $event) {
            foreach ($this->splitEventByHour($event) as $segment) {
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    /** @param array<string, string> $event */
    private function splitEventByHour(array $event): array
    {
        $start = $this->timeToIndex($event['start'] ?? '00:00');
        $end = $this->timeToIndex($event['end'] ?? '00:00');
        $segments = [];

        for ($i = $start; $i < $end;) {
            $row = (int) floor($i / 6);
            $col = $i % 6;
            $span = min(6 - $col, $end - $i);

            $segments[] = [
                'title' => $event['title'] ?? '',
                'row' => $row,
                'col' => $col,
                'span' => $span,
            ];

            $i += $span;
        }

        return $segments;
    }

    private function timeToIndex(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time, 2), 2, '0');

        return ((int) $hour * 6) + (int) floor(((int) $minute) / 10);
    }
}
