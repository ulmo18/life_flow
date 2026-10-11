<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;

final class TodoService
{
    private CalendarRepository $calendarRepository;
    private ExecutionEnergyService $executionEnergyService;
    private CalendarService $calendarService;
    private GoalService $goalService;

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
        $this->executionEnergyService = new ExecutionEnergyService();
        $this->calendarService = new CalendarService();
        $this->goalService = new GoalService();
    }

    /** @return array{date: string, items: array<int, array<string, mixed>>} */
    public function getTodayData(int $userId): array
    {
        return $this->getDateData($userId, date('Y-m-d'));
    }

    /** @return array<string, mixed> */
    public function getDateData(int $userId, string $date): array
    {
        $date = $this->normalizeDate($date);
        $day = $this->calendarRepository->findDay($userId, $date);
        $dayItems = $day === null
            ? []
            : $this->calendarRepository->getDailyPlanItems($userId, (int) $day['id']);
        $items = array_map(function (array $item): array {
            $isTimed = $item['start_index'] !== null && $item['end_index'] !== null;
            $durationMinutes = $isTimed
                ? ((int) $item['end_index'] - (int) $item['start_index']) * 10
                : ($item['estimated_duration_minutes'] === null ? null : (int) $item['estimated_duration_minutes']);

            return [
                'id' => (int) $item['daily_plan_item_id'],
                'title' => (string) $item['title'],
                'importance' => (string) ($item['importance'] ?? 'D'),
                'goalId' => $item['goal_id'] === null ? null : (int) $item['goal_id'],
                'goalTitle' => $item['goal_title'] === null ? null : (string) $item['goal_title'],
                'startIndex' => $isTimed ? (int) $item['start_index'] : null,
                'endIndex' => $isTimed ? (int) $item['end_index'] : null,
                'timeRange' => $isTimed
                    ? $this->formatTime((int) $item['start_index']) . '–' . $this->formatTime((int) $item['end_index'])
                    : t('todo.untimed'),
                'isLongDuration' => $durationMinutes !== null && $durationMinutes >= 60,
                'isCompleted' => $item['linked_event_id'] !== null,
            ];
        }, $dayItems);

        $importanceRank = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        usort($items, static function (array $left, array $right) use ($importanceRank): int {
            if ($left['isCompleted'] !== $right['isCompleted']) {
                return $left['isCompleted'] ? 1 : -1;
            }

            $importanceComparison = ($importanceRank[$left['importance']] ?? 4)
                <=> ($importanceRank[$right['importance']] ?? 4);
            if ($importanceComparison !== 0) {
                return $importanceComparison;
            }

            $timeComparison = ($left['startIndex'] ?? 145) <=> ($right['startIndex'] ?? 145);

            return $timeComparison !== 0 ? $timeComparison : $left['id'] <=> $right['id'];
        });

        return [
            'date' => $date,
            'items' => $items,
            'pendingItems' => array_values(array_filter($items, static fn(array $item): bool => !$item['isCompleted'])),
            'completedItems' => array_values(array_filter($items, static fn(array $item): bool => $item['isCompleted'])),
            'goalOptions' => $date === date('Y-m-d') ? $this->goalService->activeGoalOptions($userId) : [],
        ];
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
        $feedback = $this->executionEnergyService->claimMilestoneFeedback($userId, $date);

        return [
            'ok' => true,
            'eventId' => $eventId,
            'message' => t('todo.completed_message') . ($feedback === null ? '' : ' ' . $feedback),
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function createToday(int $userId, mixed $title): array
    {
        $validation = $this->calendarService->validateDailyPlanItemInput([
            'date' => date('Y-m-d'),
            'title' => $title,
            'importance' => 'D',
            'goal_id' => '',
            'schedule_type' => 'unscheduled',
        ]);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '할 일 이름을 확인해주세요.')];
        }
        if ($this->calendarService->createDailyPlanItem($userId, $validation['data']) === null) {
            return ['ok' => false, 'message' => '할 일을 추가하지 못했습니다.'];
        }

        return ['ok' => true, 'message' => '오늘의 할 일을 추가했습니다.'];
    }

    /** @return array{ok: bool, message: string} */
    public function updateToday(int $userId, array $input): array
    {
        $itemId = filter_var($input['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $item = $itemId === false || $itemId <= 0
            ? null
            : $this->calendarRepository->getDailyPlanItemForCompletion($userId, (int) $itemId);

        if ($item === null || (string) $item['calendar_date'] !== date('Y-m-d')) {
            return ['ok' => false, 'message' => '수정할 오늘의 할 일을 찾을 수 없습니다.'];
        }

        $input['date'] = date('Y-m-d');
        $input['daily_plan_item_id'] = (int) $itemId;
        $validation = $this->calendarService->validateDailyPlanItemInput($input, true);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '할 일 내용을 확인해주세요.')];
        }

        if (!$this->calendarService->updateDailyPlanItem($userId, $validation['data'])) {
            return ['ok' => false, 'message' => '할 일을 수정하지 못했습니다. 계획 시간이 겹치는지 확인해주세요.'];
        }

        return ['ok' => true, 'message' => '할 일을 수정했습니다.'];
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

    private function normalizeDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date) {
                return $date;
            }
        }

        return date('Y-m-d');
    }
}
