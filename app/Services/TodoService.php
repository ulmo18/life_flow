<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;

final class TodoService
{
    private CalendarRepository $calendarRepository;
    private ExecutionEnergyService $executionEnergyService;

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
        $this->executionEnergyService = new ExecutionEnergyService();
    }

    /** @return array{date: string, items: array<int, array<string, mixed>>} */
    public function getTodayData(int $userId): array
    {
        $date = date('Y-m-d');
        $day = $this->calendarRepository->getOrCreateDay($userId, $date);
        $items = array_map(function (array $item): array {
            $isTimed = $item['start_index'] !== null && $item['end_index'] !== null;

            return [
                'id' => (int) $item['daily_plan_item_id'],
                'title' => (string) $item['title'],
                'importance' => (string) ($item['importance'] ?? 'D'),
                'goalTitle' => $item['goal_title'] === null ? null : (string) $item['goal_title'],
                'startIndex' => $isTimed ? (int) $item['start_index'] : null,
                'endIndex' => $isTimed ? (int) $item['end_index'] : null,
                'timeRange' => $isTimed
                    ? $this->formatTime((int) $item['start_index']) . '–' . $this->formatTime((int) $item['end_index'])
                    : t('todo.untimed'),
                'isCompleted' => $item['linked_event_id'] !== null,
            ];
        }, $this->calendarRepository->getDailyPlanItems($userId, (int) $day['id']));

        return ['date' => $date, 'items' => $items];
    }

    /** @return array<string, mixed> */
    public function complete(int $userId, int $itemId, mixed $requestedStart, mixed $requestedEnd): array
    {
        $item = $this->calendarRepository->getDailyPlanItemForCompletion($userId, $itemId);
        if ($item === null || (string) $item['calendar_date'] !== date('Y-m-d')) {
            return ['ok' => false, 'status' => 404, 'message' => t('todo.invalid')];
        }
        if ($item['linked_event_id'] !== null) {
            return ['ok' => true, 'message' => t('todo.already_completed')];
        }

        $hasRequestedRange = $requestedStart !== null && $requestedStart !== ''
            && $requestedEnd !== null && $requestedEnd !== '';
        $startIndex = $hasRequestedRange ? filter_var($requestedStart, FILTER_VALIDATE_INT) : $item['start_index'];
        $endIndex = $hasRequestedRange ? filter_var($requestedEnd, FILTER_VALIDATE_INT) : $item['end_index'];

        if ($startIndex === null || $endIndex === null || $startIndex === false || $endIndex === false) {
            [$suggestedStart, $suggestedEnd] = $this->suggestedRange();
            return $this->needsTime(t('todo.need_time'), $suggestedStart, $suggestedEnd);
        }

        $startIndex = (int) $startIndex;
        $endIndex = (int) $endIndex;
        if ($startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) {
            [$suggestedStart, $suggestedEnd] = $this->suggestedRange();
            return $this->needsTime(t('todo.need_time'), $suggestedStart, $suggestedEnd);
        }

        $date = (string) $item['calendar_date'];
        if ($this->calendarRepository->hasOverlappingActualEvent($userId, $date, $startIndex, $endIndex)) {
            return $this->needsTime(t('todo.time_conflict'), $startIndex, $endIndex);
        }

        $eventId = $this->calendarRepository->createActualEvent(
            $userId,
            $date,
            (string) $item['title'],
            'timed',
            $startIndex,
            $endIndex,
            $itemId,
            null,
            ''
        );

        if ($eventId === null) {
            $latest = $this->calendarRepository->getDailyPlanItemForCompletion($userId, $itemId);
            if ($latest !== null && $latest['linked_event_id'] !== null) {
                return ['ok' => true, 'message' => t('todo.already_completed')];
            }
            return $this->needsTime(t('todo.time_conflict'), $startIndex, $endIndex);
        }

        $this->executionEnergyService->saveDefaultEventSetting($userId, $eventId);

        return ['ok' => true, 'eventId' => $eventId, 'message' => t('todo.completed_message')];
    }

    /** @return array<string, mixed> */
    private function needsTime(string $message, int $startIndex, int $endIndex): array
    {
        return [
            'ok' => false,
            'status' => 409,
            'needsTime' => true,
            'startIndex' => $startIndex,
            'endIndex' => $endIndex,
            'message' => $message,
        ];
    }

    /** @return array{0: int, 1: int} */
    private function suggestedRange(): array
    {
        $start = ((int) date('G') * 6) + intdiv((int) date('i'), 10);
        $start = min($start, 138);

        return [$start, $start + 6];
    }

    private function formatTime(int $index): string
    {
        return $index === 144
            ? '24:00'
            : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10);
    }
}
