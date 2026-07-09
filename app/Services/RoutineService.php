<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoalRepository;
use App\Models\RoutineRepository;
use DateTimeImmutable;

final class RoutineService
{
    private const MIN_DURATION_DAYS = 7;
    private const MAX_DURATION_DAYS = 60;
    private const DEFAULT_DURATION_DAYS = 60;
    private const DEFAULT_REMINDER_TIME = '14:00';

    private RoutineRepository $routineRepository;
    private GoalRepository $goalRepository;

    public function __construct()
    {
        $this->routineRepository = new RoutineRepository();
        $this->goalRepository = new GoalRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function getRoutineList(int $userId): array
    {
        return array_map(
            fn (array $routine): array => $this->withDailyLogs($userId, $this->formatRoutine($routine)),
            $this->routineRepository->listActiveWithDoneCounts($userId, date('Y-m-d'))
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getCalendarRoutines(int $userId, string $date): array
    {
        $date = $this->normalizeDate($date);

        return array_map(static fn (array $routine): array => [
            'id' => (int) $routine['id'],
            'name' => (string) $routine['name'],
            'state' => $routine['state'] === null ? '' : ((int) $routine['state'] === 1 ? 'O' : 'X'),
        ], $this->routineRepository->listActiveForDate($userId, $date));
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validateInput(array $input, int $userId): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $startDate = $this->normalizeDate((string) ($input['start_date'] ?? ''));
        $durationDays = filter_var($input['duration_days'] ?? null, FILTER_VALIDATE_INT);
        $goalId = filter_var($input['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $reminderEnabled = isset($input['reminder_enabled']) && (string) $input['reminder_enabled'] === '1';
        $reminderTime = trim((string) ($input['reminder_time'] ?? self::DEFAULT_REMINDER_TIME));
        $errors = [];

        if ($name === '') {
            $errors['name'] = '루틴명을 입력해주세요.';
        } elseif (mb_strlen($name) > 60) {
            $errors['name'] = '루틴명은 60자 이내로 입력해주세요.';
        }

        if ($durationDays === false || $durationDays < self::MIN_DURATION_DAYS || $durationDays > self::MAX_DURATION_DAYS) {
            $errors['duration_days'] = '기간은 7일부터 60일까지 선택해주세요.';
            $durationDays = self::DEFAULT_DURATION_DAYS;
        }

        if ($reminderEnabled && !$this->isValidTime($reminderTime)) {
            $errors['reminder_time'] = '리마인드 시간은 HH:MM 형식으로 입력해주세요.';
        }

        if ($goalId === false || $goalId === 0) {
            $goalId = null;
        }

        if ($goalId !== null && !$this->goalRepository->activeGoalExists($userId, (int) $goalId)) {
            $errors['goal_id'] = '연결할 진행 중 목표를 찾을 수 없습니다.';
            $goalId = null;
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'name' => $name,
                'goalId' => $goalId === null ? null : (int) $goalId,
                'startDate' => $startDate,
                'durationDays' => (int) $durationDays,
                'reminderEnabled' => $reminderEnabled,
                'reminderTime' => $reminderEnabled ? $reminderTime : null,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createRoutine(int $userId, array $data): ?int
    {
        return $this->routineRepository->create(
            $userId,
            $data['goalId'] === null ? null : (int) $data['goalId'],
            (string) $data['name'],
            (string) $data['startDate'],
            (int) $data['durationDays'],
            (bool) $data['reminderEnabled'],
            $data['reminderTime'] === null ? null : (string) $data['reminderTime']
        );
    }

    /** @param array<string, mixed> $data */
    public function updateRoutine(int $userId, int $routineId, array $data): bool
    {
        return $this->routineRepository->update(
            $userId,
            $routineId,
            $data['goalId'] === null ? null : (int) $data['goalId'],
            (string) $data['name'],
            (string) $data['startDate'],
            (int) $data['durationDays'],
            (bool) $data['reminderEnabled'],
            $data['reminderTime'] === null ? null : (string) $data['reminderTime']
        );
    }

    public function deleteRoutine(int $userId, int $routineId): bool
    {
        return $this->routineRepository->softDelete($userId, $routineId);
    }

    public function cycleRoutineState(int $userId, int $routineId, string $date): ?string
    {
        return $this->routineRepository->cycleLogState($userId, $routineId, $this->normalizeDate($date));
    }

    /** @return array<string, mixed>|null */
    public function getRoutineSummary(int $userId, int $routineId): ?array
    {
        $routine = $this->routineRepository->findActiveWithDoneCount($userId, $routineId, date('Y-m-d'));

        return $routine === null ? null : $this->formatRoutine($routine);
    }

    /** @return array<int, int> */
    public function durationOptions(): array
    {
        return range(self::MIN_DURATION_DAYS, self::MAX_DURATION_DAYS);
    }

    public function defaultDurationDays(): int
    {
        return self::DEFAULT_DURATION_DAYS;
    }

    public function defaultReminderTime(): string
    {
        return self::DEFAULT_REMINDER_TIME;
    }

    /** @return array<string, mixed> */
    private function formatRoutine(array $routine): array
    {
        $durationDays = max(self::MIN_DURATION_DAYS, min(self::MAX_DURATION_DAYS, (int) $routine['duration_days']));
        $doneCount = max(0, min($durationDays, (int) ($routine['done_count'] ?? 0)));
        $progressPercent = $durationDays > 0 ? (int) floor(($doneCount / $durationDays) * 100) : 0;
        $startDate = (string) $routine['start_date'];
        $endDate = (new DateTimeImmutable($startDate))->modify('+' . ($durationDays - 1) . ' days')->format('Y-m-d');

        return [
            'id' => (int) $routine['id'],
            'goalId' => $routine['goal_id'] === null ? null : (int) $routine['goal_id'],
            'goalTitle' => $routine['goal_title'] === null ? '' : (string) $routine['goal_title'],
            'goalType' => $routine['goal_type'] === null ? '' : (string) $routine['goal_type'],
            'name' => (string) $routine['name'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'durationDays' => $durationDays,
            'doneCount' => $doneCount,
            'progressPercent' => $progressPercent,
            'todayState' => $routine['today_state'] === null ? '' : ((int) $routine['today_state'] === 1 ? 'O' : 'X'),
            'reminderEnabled' => (int) ($routine['reminder_enabled'] ?? 0) === 1,
            'reminderTime' => (string) ($routine['reminder_time'] ?? self::DEFAULT_REMINDER_TIME),
            'lawnCells' => $this->buildLawnCells($durationDays, $doneCount),
        ];
    }

    /** @return array<int, bool> */
    private function buildLawnCells(int $durationDays, int $doneCount): array
    {
        $cells = [];
        for ($day = 1; $day <= $durationDays; $day++) {
            $cells[] = $day <= $doneCount;
        }

        return $cells;
    }

    /** @return array<string, mixed> */
    private function withDailyLogs(int $userId, array $routine): array
    {
        $startDate = new DateTimeImmutable((string) $routine['startDate']);
        $endDate = new DateTimeImmutable((string) $routine['endDate']);
        $lastVisibleDate = $endDate;

        if ($lastVisibleDate < $startDate) {
            $routine['dailyLogs'] = [];
            return $routine;
        }

        $stateMap = $this->routineRepository->listLogStates(
            $userId,
            (int) $routine['id'],
            $startDate->format('Y-m-d'),
            $lastVisibleDate->format('Y-m-d')
        );

        $logs = [];
        for ($date = $startDate; $date <= $lastVisibleDate; $date = $date->modify('+1 day')) {
            $dateKey = $date->format('Y-m-d');
            $state = $stateMap[$dateKey] ?? null;
            $logs[] = [
                'date' => $dateKey,
                'label' => $date->format('m/d'),
                'state' => $state === null ? '' : ($state === 1 ? 'O' : 'X'),
            ];
        }

        $routine['dailyLogs'] = $logs;

        return $routine;
    }

    private function normalizeDate(?string $date): string
    {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date) {
                return $date;
            }
        }

        return date('Y-m-d');
    }

    private function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }
}
