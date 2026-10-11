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
    private const DEFAULT_DURATION_DAYS = 7;
    private const DEFAULT_REMINDER_TIME = '14:00';
    private const CARD_TRACKER_DAYS = 14;
    private const CARD_TRACKER_PAST_DAYS = 10;
    private const HABIT_TARGET_DAYS = 60;

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

    /** @return array<string, mixed> */
    public function getRoutineManagementData(int $userId, ?string $requestedStatus, ?string $requestedMonth): array
    {
        $status = $requestedStatus === 'completed' ? 'completed' : 'active';
        $month = $this->normalizeMonth($requestedMonth);
        $monthStart = new DateTimeImmutable($month . '-01');
        $today = date('Y-m-d');
        $routines = array_map(function (array $row) use ($userId, $monthStart): array {
            $routine = $this->withDailyLogs($userId, $this->formatRoutine($row));
            return $this->withManagementMonth($userId, $routine, $monthStart);
        }, $this->routineRepository->listForManagement($userId, $today, $status));

        return [
            'routines' => $routines,
            'selectedStatus' => $status,
            'selectedMonth' => $month,
            'monthLabel' => $monthStart->format('Y년 n월'),
            'previousMonth' => $monthStart->modify('-1 month')->format('Y-m'),
            'nextMonth' => $monthStart->modify('+1 month')->format('Y-m'),
        ];
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
    public function getTodayRoutineList(int $userId): array
    {
        return $this->getRoutineListForDate($userId, date('Y-m-d'));
    }

    /** @return array<int, array<string, mixed>> */
    public function getRoutineListForDate(int $userId, ?string $requestedDate): array
    {
        $date = $this->normalizeDate($requestedDate);

        return array_map(
            fn (array $routine): array => $this->formatRoutineForDate($userId, $routine, $date),
            $this->routineRepository->listActiveForDate($userId, $date)
        );
    }

    /** @return array<string, mixed>|null */
    public function getRoutineDayItem(int $userId, int $routineId, string $date): ?array
    {
        foreach ($this->getRoutineListForDate($userId, $date) as $routine) {
            if ((int) $routine['id'] === $routineId) {
                return $routine;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getCalendarRoutines(int $userId, string $date): array
    {
        $date = $this->normalizeDate($date);

        $routines = array_filter(
            $this->routineRepository->listActiveForDate($userId, $date),
            static fn (array $routine): bool => (int) ($routine['state'] ?? 0) !== 1
        );

        return array_map(static fn (array $routine): array => [
            'id' => (int) $routine['id'],
            'name' => (string) $routine['name'],
            'state' => $routine['state'] === null ? '' : ((int) $routine['state'] === 1 ? 'O' : 'X'),
        ], array_values($routines));
    }

    public function markDoneForDate(int $userId, int $routineId, string $date): bool
    {
        $date = $this->normalizeDate($date);
        if ($date !== date('Y-m-d')) {
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
        $endDateInput = trim((string) ($input['end_date'] ?? ''));
        $goalId = filter_var($input['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $reminderEnabled = isset($input['reminder_enabled']) && (string) $input['reminder_enabled'] === '1';
        $reminderTime = trim((string) ($input['reminder_time'] ?? self::DEFAULT_REMINDER_TIME));
        $errors = [];

        if ($name === '') {
            $errors['name'] = '루틴명을 입력해주세요.';
        } elseif (mb_strlen($name) > 60) {
            $errors['name'] = '루틴명은 60자 이내로 입력해주세요.';
        }

        if (!$allowExtendedDuration && $endDateInput !== '') {
            $parsedEndDate = DateTimeImmutable::createFromFormat('!Y-m-d', $endDateInput);
            if (!$parsedEndDate instanceof DateTimeImmutable || $parsedEndDate->format('Y-m-d') !== $endDateInput) {
                $errors['end_date'] = '마감일자를 확인해주세요.';
            } else {
                $parsedStartDate = new DateTimeImmutable($startDate);
                if ($parsedEndDate < $parsedStartDate) {
                    $errors['end_date'] = '마감일자는 시작일보다 빠를 수 없습니다.';
                } else {
                    $selectedDuration = (int) $parsedStartDate->diff($parsedEndDate)->format('%a') + 1;
                    if ($selectedDuration > self::MAX_INITIAL_DURATION_DAYS) {
                        $errors['end_date'] = '최초 루틴의 마감일자는 시작일부터 60일 이내로 선택해주세요.';
                    } else {
                        $durationDays = $selectedDuration;
                    }
                }
            }
        }

        $maxDuration = $allowExtendedDuration ? self::MAX_DURATION_DAYS : self::MAX_INITIAL_DURATION_DAYS;
        if ($durationDays === false || $durationDays < self::MIN_DURATION_DAYS || $durationDays > $maxDuration) {
            $errors['duration_days'] = '기간을 확인해주세요.';
            $durationDays = self::DEFAULT_DURATION_DAYS;
        }

        $calculatedEndDate = (new DateTimeImmutable($startDate))
            ->modify('+' . ((int) $durationDays - 1) . ' days')
            ->format('Y-m-d');

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
                'endDate' => $calculatedEndDate,
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

    /** @return array<string, mixed>|null */
    public function cycleManagementState(int $userId, int $routineId, string $date, ?string $month): ?array
    {
        $before = $this->getRoutineSummary($userId, $routineId, true);
        $state = $this->cycleRoutineState($userId, $routineId, $date);
        if ($state === null) {
            return null;
        }

        $monthStart = new DateTimeImmutable($this->normalizeMonth($month) . '-01');
        $after = $this->getRoutineSummary($userId, $routineId, true);
        if ($after === null) {
            return null;
        }
        $after = $this->withManagementMonth($userId, $after, $monthStart);

        $beforeDone = (int) ($before['doneCount'] ?? 0);
        $afterDone = (int) ($after['doneCount'] ?? 0);
        $beforeStreak = (int) ($before['streakCount'] ?? 0);
        $afterStreak = (int) ($after['streakCount'] ?? 0);
        $celebrationMessage = '';
        if ($state === 'O' && $beforeDone < self::HABIT_TARGET_DAYS && $afterDone >= self::HABIT_TARGET_DAYS) {
            $celebrationMessage = '누적 60일을 달성했어요. 이 루틴이 습관으로 자리 잡았습니다!';
        } elseif ($state === 'O' && $afterStreak > $beforeStreak && in_array($afterStreak, [3, 7, 14, 30, 60], true)) {
            $celebrationMessage = $afterStreak . '일 연속 달성했어요. 좋은 흐름을 이어가고 있어요!';
        }

        return [
            'state' => $state,
            'routine' => $after,
            'celebration' => $celebrationMessage !== '',
            'message' => $celebrationMessage,
        ];
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
            'habitAchieved' => $doneCount >= self::HABIT_TARGET_DAYS,
        ];
    }

    /** @return array<string, mixed> */
    private function formatRoutineForDate(int $userId, array $routine, string $date): array
    {
        $startDate = (string) $routine['start_date'];
        $selectedDate = new DateTimeImmutable($date);
        $today = new DateTimeImmutable(date('Y-m-d'));
        $streakDate = $selectedDate > $today ? $today : $selectedDate;
        $stateMap = $this->routineRepository->listLogStates(
            $userId,
            (int) $routine['id'],
            $startDate,
            $streakDate->format('Y-m-d')
        );

        $streakCount = 0;
        $cursor = $streakDate;
        if (($stateMap[$cursor->format('Y-m-d')] ?? null) !== 1) {
            $cursor = $cursor->modify('-1 day');
        }
        $periodStart = new DateTimeImmutable($startDate);
        for (; $cursor >= $periodStart; $cursor = $cursor->modify('-1 day')) {
            if (($stateMap[$cursor->format('Y-m-d')] ?? null) !== 1) {
                break;
            }
            $streakCount++;
        }

        return [
            'id' => (int) $routine['id'],
            'name' => (string) $routine['name'],
            'state' => $routine['state'] === null ? '' : ((int) $routine['state'] === 1 ? 'O' : 'X'),
            'streakCount' => $streakCount,
        ];
    }

    /** @return array<string, mixed> */
    private function withDailyLogs(int $userId, array $routine): array
    {
        $startDate = new DateTimeImmutable((string) $routine['startDate']);
        $endDate = new DateTimeImmutable((string) $routine['endDate']);
        $effectiveEndDate = $endDate;
        if (!empty($routine['endedAt'])) {
            $endedAt = new DateTimeImmutable((string) $routine['endedAt']);
            if ($endedAt < $effectiveEndDate) {
                $effectiveEndDate = $endedAt;
            }
        }
        $today = new DateTimeImmutable(date('Y-m-d'));
        $lastVisibleDate = $effectiveEndDate < $today ? $effectiveEndDate : $today;

        $stateMap = $lastVisibleDate >= $startDate
            ? $this->routineRepository->listLogStates(
                $userId,
                (int) $routine['id'],
                $startDate->format('Y-m-d'),
                $lastVisibleDate->format('Y-m-d')
            )
            : [];

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
        $routine['periodGroups'] = $this->buildPeriodGroups($startDate, $effectiveEndDate, $stateMap, $today);

        $yesterdayStreak = 0;
        for ($date = $today->modify('-1 day'); $date >= $startDate; $date = $date->modify('-1 day')) {
            if (($stateMap[$date->format('Y-m-d')] ?? null) !== 1) {
                break;
            }
            $yesterdayStreak++;
        }
        $todayDone = ($stateMap[$today->format('Y-m-d')] ?? null) === 1;
        $routine['streakCount'] = $yesterdayStreak + ($todayDone ? 1 : 0);
        $routine['streakLabel'] = $routine['streakCount'] >= 3
            ? '🔥 연속 ' . $routine['streakCount'] . '일'
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

    /** @return array<string, mixed> */
    private function withManagementMonth(int $userId, array $routine, DateTimeImmutable $monthStart): array
    {
        $today = new DateTimeImmutable(date('Y-m-d'));
        $monthEnd = $monthStart->modify('last day of this month');
        $periodStart = new DateTimeImmutable((string) $routine['startDate']);
        $periodEnd = new DateTimeImmutable((string) $routine['endDate']);
        if (!empty($routine['endedAt'])) {
            $endedAt = new DateTimeImmutable((string) $routine['endedAt']);
            if ($endedAt < $periodEnd) {
                $periodEnd = $endedAt;
            }
        }

        $queryStart = $periodStart > $monthStart ? $periodStart : $monthStart;
        $queryEnd = $periodEnd < $monthEnd ? $periodEnd : $monthEnd;
        $stateMap = $queryStart <= $queryEnd
            ? $this->routineRepository->listLogStates($userId, (int) $routine['id'], $queryStart->format('Y-m-d'), $queryEnd->format('Y-m-d'))
            : [];
        $gridStart = $monthStart->modify('monday this week');
        $cells = [];
        $monthDoneCount = 0;
        for ($offset = 0; $offset < 42; $offset++) {
            $date = $gridStart->modify('+' . $offset . ' days');
            $dateKey = $date->format('Y-m-d');
            $inMonth = $date >= $monthStart && $date <= $monthEnd;
            $inPeriod = $date >= $periodStart && $date <= $periodEnd;
            $stateValue = $stateMap[$dateKey] ?? null;
            if ($inMonth && $stateValue === 1) {
                $monthDoneCount++;
            }
            $cells[] = [
                'date' => $dateKey,
                'day' => $date->format('j'),
                'inMonth' => $inMonth,
                'inPeriod' => $inPeriod,
                'isToday' => $date == $today,
                'isFuture' => $date > $today,
                'canEdit' => $inMonth && $inPeriod && $date <= $today,
                'state' => $stateValue === null ? '' : ($stateValue === 1 ? 'O' : 'X'),
            ];
        }

        $status = (string) $routine['status'];
        $isNaturalEnded = $status === 'active' && new DateTimeImmutable((string) $routine['endDate']) < $today;
        $isFuture = $status === 'active' && $periodStart > $today;
        $routine['calendarCells'] = $cells;
        $routine['monthDoneCount'] = $monthDoneCount;
        $routine['monthSummaryLabel'] = $monthStart->format('n월') . ' ' . $monthDoneCount . '회';
        $routine['summaryLabel'] = $this->routineSummaryLabel($routine, $monthStart, $monthDoneCount);
        $routine['isNaturalEnded'] = $isNaturalEnded;
        $routine['canManage'] = $status === 'active';
        $routine['statusLabel'] = $isFuture ? '시작 예정' : ($isNaturalEnded ? '기간 종료' : ($status === 'completed' ? '완료' : ($status === 'stopped' ? '중단' : '진행 중')));

        return $routine;
    }

    private function routineSummaryLabel(array $routine, DateTimeImmutable $monthStart, int $monthDoneCount): string
    {
        $parts = [];
        if (!empty($routine['habitAchieved'])) {
            $parts[] = '✓ 습관화 목표 달성';
        } elseif ((int) ($routine['streakCount'] ?? 0) >= 3) {
            $parts[] = '🔥 ' . (int) $routine['streakCount'] . '일 연속';
        }
        $parts[] = '누적 실천 ' . (int) $routine['doneCount'] . '일';
        $parts[] = $monthStart->format('n월') . ' ' . $monthDoneCount . '회';
        return implode(' · ', $parts);
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
            $monthEnd = $month->modify('last day of this month');
            $gridStart = $month->modify('monday this week');
            for ($offset = 0; $offset < 42; $offset++) {
                $date = $gridStart->modify('+' . $offset . ' days');
                $dateKey = $date->format('Y-m-d');
                $state = $stateMap[$dateKey] ?? null;
                $cells[] = [
                    'date' => $dateKey,
                    'day' => $date->format('j'),
                    'inMonth' => $date >= $month && $date <= $monthEnd,
                    'inPeriod' => $date >= $startDate && $date <= $endDate,
                    'canEdit' => $date >= $month && $date <= $monthEnd && $date >= $startDate && $date <= $endDate && $date <= $today,
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

    private function normalizeMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m', $month);
            if ($parsed instanceof DateTimeImmutable && $parsed->format('Y-m') === $month) {
                return $month;
            }
        }
        return date('Y-m');
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
