<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;
use App\Models\RetrospectRepository;
use App\Models\RoutineRepository;
use DateTimeImmutable;

final class DashboardService
{
    /** @return array<string, mixed> */
    public function getData(int $userId, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now');
        $date = $now->format('Y-m-d');
        $todo = (new TodoService())->getDateData($userId, $date);
        $routines = (new RoutineRepository())->listActiveForDate($userId, $date);
        $report = (new RetrospectRepository())->findReport($userId, $date);
        $calendar = new CalendarRepository();
        $day = $calendar->findDay($userId, $date);
        $events = [];
        foreach ($todo['planItems'] as $item) {
            if (($item['status'] ?? '') === 'cancelled') continue;
            $events[] = ['id' => (int) $item['id'], 'title' => (string) $item['title'], 'kind' => 'plan',
                'start' => $item['startIndex'], 'end' => $item['endIndex']];
        }
        foreach ($day === null ? [] : $calendar->getActualEvents($userId, (int) $day['id']) as $event) {
            if ($event['schedule_type'] !== 'timed') continue;
            $events[] = ['id' => (int) $event['id'], 'title' => (string) $event['title'], 'kind' => 'actual',
                'start' => $event['start_index'], 'end' => $event['end_index']];
        }
        $nearest = $this->nearestEvent($events, ((int) $now->format('H') * 60 + (int) $now->format('i')) * 60 + (int) $now->format('s'));
        return [
            'date' => $date,
            'retrospect' => ['state' => $report === null ? 'empty' : ($report['status'] === 'submitted' ? 'submitted' : 'draft')],
            'routine' => ['total' => count($routines), 'done' => count(array_filter($routines, static fn(array $row): bool => $row['state'] !== null && (int) $row['state'] === 1))],
            'todo' => ['total' => count($todo['items']), 'done' => count($todo['completedItems'])],
            'nearestEvent' => $nearest,
        ];
    }

    /** @param array<int, array<string, mixed>> $events */
    private function nearestEvent(array $events, int $nowSeconds): ?array
    {
        $ranked = [];
        foreach ($events as $event) {
            if ($event['start'] === null || $event['end'] === null) continue;
            $start = (int) $event['start'] * 600;
            $end = (int) $event['end'] * 600;
            if ($start < 0 || $end > 86400 || $start >= $end) continue;
            $ongoing = $start <= $nowSeconds && $nowSeconds < $end;
            $distance = $ongoing ? 0 : min(abs($start - $nowSeconds), abs($end - $nowSeconds));
            $event['state'] = $ongoing ? 'ongoing' : ($start >= $nowSeconds ? 'upcoming' : 'past');
            $event['startLabel'] = $this->clock((int) $event['start']);
            $event['endLabel'] = $this->clock((int) $event['end']);
            $ranked[] = ['event' => $event, 'rank' => [$ongoing ? 0 : 1, $distance,
                $start >= $nowSeconds ? 0 : 1, $event['kind'] === 'actual' ? 0 : 1, $start, $event['id']]];
        }
        usort($ranked, static fn(array $a, array $b): int => $a['rank'] <=> $b['rank']);
        return $ranked[0]['event'] ?? null;
    }

    private function clock(int $index): string
    {
        return sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10);
    }
}
