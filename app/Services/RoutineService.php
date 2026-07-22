<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoalRepository;
use App\Models\RoutineRepository;
use DateTimeImmutable;

final class RoutineService
{
    private const MIN_DURATION_DAYS = 1;
    private const MAX_INITIAL_DURATION_DAYS = 60;
    private const MAX_DURATION_DAYS = 365;
    private const DEFAULT_DURATION_DAYS = 60;
    private const DEFAULT_REMINDER_TIME = '14:00';
    private const CARD_TRACKER_DAYS = 14;
    private const CARD_TRACKER_PAST_DAYS = 10;

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

    /** @return array{routines: array<int, array<string, mixed>>, summary: array<string, int>} */
    public function getRoutinePageData(int $userId): array
    {
        $routines = $this->getRoutineList($userId);

        return ['routines' => $routines, 'summary' => $this->summarizeRoutines($routines)];
    }

    /** @return array<string, int> */
    public function getRoutinePageSummary(int $userId): array
    {
        return $this->summarizeRoutines($this->getRoutineList($userId));
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

    public function markDoneForDate(int $userId, int $routineId, string $date): bool
    {
        $date = $this->normalizeDate($date);
        if ($date > date('Y-m-d')) {
            return false;
        }

        return $this->routineRepository->markDoneForDate($userId, $routineId, $date);
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validateInput(array $input, int $userId, bool $allowExtendedDuration = false): array
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

        $maxDuration = $allowExtendedDuration ? self::MAX_DURATION_DAYS : self::MAX_INITIAL_DURATION_DAYS;
        if ($durationDays === false || $durationDays < self::MIN_DURATION_DAYS || $durationDays > $maxDuration) {
            $errors['duration_days'] = '기간을 확인해주세요.';
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
        $routine = $this->routineRepository->findActive($userId, $routineId);
        if ($routine === null || (string) ($routine['status'] ?? 'active') !== 'active') {
            return false;
        }

        return $this->routineRepository->update(
            $userId,
            $routineId,
            $data['goalId'] === null ? null : (int) $data['goalId'],
            (string) $data['name'],
            (string) $routine['start_date'],
            (int) $routine['duration_days'],
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
        $date = $this->normalizeDate($date);
        if ($date > date('Y-m-d')) {
            return null;
        }

        return $this->routineRepository->cycleLogState($userId, $routineId, $date);
    }

    public function extendRoutine(int $userId, int $routineId, int $extensionDays): bool
    {
        if ($extensionDays < 1) {
            return false;
        }

        $routine = $this->routineRepository->findActive($userId, $routineId);
        if ($routine === null || (string) ($routine['status'] ?? 'active') !== 'active') {
            return false;
        }

        $duration = (int) $routine['duration_days'];
        if ($duration + $extensionDays > self::MAX_DURATION_DAYS) {
            return false;
        }

        return $this->routineRepository->extend($userId, $routineId, $duration + $extensionDays);
    }

    public function finishRoutine(int $userId, int $routineId, string $status): bool
    {
        if (!in_array($status, ['completed', 'stopped'], true)) {
            return false;
        }

        return $this->routineRepository->finish($userId, $routineId, $status, date('Y-m-d'));
    }

    /** @return array<string, mixed>|null */
    public function getRoutineSummary(int $userId, int $routineId, bool $includeDailyData = false): ?array
    {
        $routine = $this->routineRepository->findActiveWithDoneCount($userId, $routineId, date('Y-m-d'));
        if ($routine === null) {
            return null;
        }

        $formatted = $this->formatRoutine($routine);

        return $includeDailyData ? $this->withDailyLogs($userId, $formatted) : $formatted;
    }

    /** @return array<int, int> */
    public function durationOptions(): array
    {
        return range(self::MIN_DURATION_DAYS, self::MAX_INITIAL_DURATION_DAYS);
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
            'status' => (string) ($routine['status'] ?? 'active'),
            'endedAt' => $routine['ended_at'] === null ? null : (string) $routine['ended_at'],
        ];
    }

    /** @return array<string, mixed> */
    private function withDailyLogs(int $userId, array $routine): array
    {
        $startDate = new DateTimeImmutable((string) $routine['startDate']);
        $endDate = new DateTimeImmutable((string) $routine['endDate']);
        $today = new DateTimeImmutable(date('Y-m-d'));
        $lastVisibleDate = $endDate < $today ? $endDate : $today;

        if ($lastVisibleDate < $startDate) {
            $routine['dailyLogs'] = [];
            $routine['trackerCells'] = [];
            $routine['periodGroups'] = [];
            $routine['streakCount'] = 0;
            $routine['streakLabel'] = '';
            $routine['weekDoneCount'] = 0;
            $routine['weekTotalCount'] = 0;
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
                'canEdit' => $dateKey <= date('Y-m-d'),
            ];
        }

        $routine['dailyLogs'] = $logs;
        $routine['trackerCells'] = $this->buildCardTracker($startDate, $endDate, $stateMap, $today);
        $routine['periodGroups'] = $this->buildPeriodGroups($startDate, $endDate, $stateMap, $today);

        $yesterdayStreak = 0;
        for ($date = $today->modify('-1 day'); $date >= $startDate; $date = $date->modify('-1 day')) {
            if (($stateMap[$date->format('Y-m-d')] ?? null) !== 1) {
                break;
            }
            $yesterdayStreak++;
        }
        $todayDone = ($stateMap[$today->format('Y-m-d')] ?? null) === 1;
        $routine['streakCount'] = $yesterdayStreak + ($todayDone ? 1 : 0);
        $routine['streakLabel'] = $routine['streakCount'] > 0
            ? ($routine['streakCount'] >= 3 ? '🔥 ' : '') . '연속 ' . $routine['streakCount'] . '일'
            : '';

        $weekStart = $today->modify('monday this week');
        $weekDoneCount = 0;
        $weekTotalCount = 0;
        foreach ($logs as $log) {
            if ((string) $log['date'] < $weekStart->format('Y-m-d')) {
                continue;
            }
            $weekTotalCount++;
            if ((string) $log['state'] === 'O') {
                $weekDoneCount++;
            }
        }
        $routine['weekDoneCount'] = $weekDoneCount;
        $routine['weekTotalCount'] = $weekTotalCount;

        return $routine;
    }

    /** @param array<string, int> $stateMap @return array<int, array<string, mixed>> */
    private function buildCardTracker(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $stateMap,
        DateTimeImmutable $today
    ): array
    {
        $durationDays = (int) $startDate->diff($endDate)->format('%a') + 1;
        if ($durationDays <= self::CARD_TRACKER_DAYS) {
            $firstDate = $startDate;
            $lastDate = $endDate;
        } else {
            $firstDate = $today->modify('-' . self::CARD_TRACKER_PAST_DAYS . ' days');
            $lastDate = $firstDate->modify('+' . (self::CARD_TRACKER_DAYS - 1) . ' days');

            if ($firstDate < $startDate) {
                $firstDate = $startDate;
                $lastDate = $firstDate->modify('+' . (self::CARD_TRACKER_DAYS - 1) . ' days');
            }
            if ($lastDate > $endDate) {
                $lastDate = $endDate;
                $firstDate = $lastDate->modify('-' . (self::CARD_TRACKER_DAYS - 1) . ' days');
            }
        }

        $cells = [];
        for ($date = $firstDate; $date <= $lastDate; $date = $date->modify('+1 day')) {
            $dateKey = $date->format('Y-m-d');
            $state = $stateMap[$dateKey] ?? null;
            $cells[] = [
                'date' => $dateKey,
                'state' => $state === null ? '' : ($state === 1 ? 'O' : 'X'),
                'isDone' => $state === 1,
                'isToday' => $dateKey === $today->format('Y-m-d'),
                'isFuture' => $date > $today,
            ];
        }

        return $cells;
    }

    /** @param array<string, int> $stateMap @return array<int, array<string, mixed>> */
    private function buildPeriodGroups(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $stateMap,
        DateTimeImmutable $today
    ): array {
        $groups = [];
        $month = $startDate->modify('first day of this month');
        $lastMonth = $endDate->modify('first day of this month');

        for (; $month <= $lastMonth; $month = $month->modify('+1 month')) {
            $cells = [];
            $firstDate = $month < $startDate ? $startDate : $month;
            $monthEnd = $month->modify('last day of this month');
            $lastDate = $monthEnd > $endDate ? $endDate : $monthEnd;
            for ($date = $firstDate; $date <= $lastDate; $date = $date->modify('+1 day')) {
                $dateKey = $date->format('Y-m-d');
                $state = $stateMap[$dateKey] ?? null;
                $cells[] = [
                    'date' => $dateKey,
                    'day' => $date->format('j'),
                    'canEdit' => $date <= $today,
                    'isFuture' => $date > $today,
                    'isToday' => $dateKey === $today->format('Y-m-d'),
                    'state' => $state === null ? '' : ($state === 1 ? 'O' : 'X'),
                ];
            }

            $groups[] = [
                'key' => $month->format('Y-m'),
                'label' => $month->format('Y년 n월'),
                'cells' => $cells,
            ];
        }

        return $groups;
    }

    /** @param array<int, array<string, mixed>> $routines @return array<string, int> */
    private function summarizeRoutines(array $routines): array
    {
        $weekDoneCount = array_sum(array_map(static fn (array $routine): int => (int) ($routine['weekDoneCount'] ?? 0), $routines));
        $weekTotalCount = array_sum(array_map(static fn (array $routine): int => (int) ($routine['weekTotalCount'] ?? 0), $routines));
        $streakCount = $routines === []
            ? 0
            : max(array_map(static fn (array $routine): int => (int) ($routine['streakCount'] ?? 0), $routines));

        return [
            'activeCount' => count($routines),
            'weekDoneCount' => $weekDoneCount,
            'weekTotalCount' => $weekTotalCount,
            'weekAchievementRate' => $weekTotalCount > 0 ? (int) floor(($weekDoneCount / $weekTotalCount) * 100) : 0,
            'streakCount' => $streakCount,
        ];
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
