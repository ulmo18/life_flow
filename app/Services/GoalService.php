<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoalRepository;
use DateTimeImmutable;

final class GoalService
{
    private const GOAL_TYPES = [
        'bucket' => '버킷리스트',
        'yearly' => '연간',
        'half_year' => '반기',
        'quarterly' => '분기',
        'monthly' => '한달',
    ];

    private const STATUSES = [
        'active' => '진행 중',
        'completed' => '완료',
        'paused' => '보류',
        'archived' => '보관',
    ];

    private GoalRepository $goalRepository;

    public function __construct()
    {
        $this->goalRepository = new GoalRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function getGoalList(int $userId, ?string $status = 'active', mixed $goalType = null): array
    {
        $statusFilter = $this->normalizeStatusFilter($status);
        $goalTypeFilter = $this->normalizeGoalTypeFilter($goalType);
        $goals = array_map(
            fn (array $goal): array => $this->formatGoal($goal),
            $this->goalRepository->listActive($userId, $statusFilter, $goalTypeFilter)
        );
        $goalIds = array_map(static fn (array $goal): int => (int) $goal['id'], $goals);
        $linkedPlans = $this->goalRepository->listLinkedPlansByGoalIds($userId, $goalIds);
        $linkedRoutines = $this->goalRepository->listLinkedRoutinesByGoalIds($userId, $goalIds);

        return array_map(function (array $goal) use ($linkedPlans, $linkedRoutines): array {
            $goalId = (int) $goal['id'];
            $goal['linkedPlans'] = array_map(
                fn (array $plan): array => $this->formatLinkedPlan($plan),
                $linkedPlans[$goalId] ?? []
            );
            $goal['linkedRoutines'] = array_map(
                fn (array $routine): array => $this->formatLinkedRoutine($routine),
                $linkedRoutines[$goalId] ?? []
            );

            return $goal;
        }, $goals);
    }

    /** @return array<string, string> */
    public function goalTypeOptions(): array
    {
        return self::GOAL_TYPES;
    }

    /** @return array<string, string> */
    public function statusOptions(): array
    {
        return self::STATUSES;
    }

    public function normalizeStatusFilter(mixed $status): string
    {
        $status = trim((string) $status);

        return array_key_exists($status, self::STATUSES) ? $status : 'active';
    }

    public function normalizeViewMode(mixed $viewMode): string
    {
        return trim((string) $viewMode) === 'tree' ? 'tree' : 'cards';
    }

    public function normalizeGoalTypeFilter(mixed $goalType): ?string
    {
        $goalType = trim((string) $goalType);

        return array_key_exists($goalType, self::GOAL_TYPES) ? $goalType : null;
    }

    /**
     * @param array<int, array<string, mixed>> $goals
     * @return array<int, array<string, mixed>>
     */
    public function buildGoalTree(array $goals): array
    {
        $goalIds = [];
        foreach ($goals as $goal) {
            $goalIds[(int) $goal['id']] = true;
        }

        $childrenByParentId = [];
        foreach ($goals as $goal) {
            $parentGoalId = $goal['parentGoalId'] ?? null;
            $parentKey = $parentGoalId !== null && isset($goalIds[(int) $parentGoalId])
                ? (int) $parentGoalId
                : 0;
            $childrenByParentId[$parentKey][] = $goal;
        }

        $buildBranch = static function (int $parentGoalId) use (&$buildBranch, &$childrenByParentId): array {
            $branch = [];
            foreach ($childrenByParentId[$parentGoalId] ?? [] as $goal) {
                $goal['children'] = $buildBranch((int) $goal['id']);
                $branch[] = $goal;
            }

            return $branch;
        };

        return $buildBranch(0);
    }

    /** @return array<int, array<string, mixed>> */
    public function parentOptions(int $userId, ?int $excludeGoalId = null): array
    {
        return array_map(
            fn (array $goal): array => [
                'id' => (int) $goal['id'],
                'title' => (string) $goal['title'],
                'goalType' => (string) $goal['goal_type'],
                'goalTypeLabel' => self::GOAL_TYPES[(string) $goal['goal_type']] ?? '목표',
            ],
            $this->goalRepository->listPotentialParents($userId, $excludeGoalId)
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeGoalOptions(int $userId): array
    {
        return array_map(
            fn (array $goal): array => [
                'id' => (int) $goal['id'],
                'title' => (string) $goal['title'],
                'goalType' => (string) $goal['goal_type'],
                'goalTypeLabel' => self::GOAL_TYPES[(string) $goal['goal_type']] ?? '목표',
                'label' => (self::GOAL_TYPES[(string) $goal['goal_type']] ?? '목표') . ' · ' . (string) $goal['title'],
            ],
            $this->goalRepository->listActiveOptions($userId)
        );
    }

    public function activeGoalExists(int $userId, int $goalId): bool
    {
        return $goalId > 0 && $this->goalRepository->activeGoalExists($userId, $goalId);
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validateInput(array $input, int $userId, ?int $goalId = null): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $goalType = trim((string) ($input['goal_type'] ?? 'monthly'));
        $status = trim((string) ($input['status'] ?? 'active'));
        $parentGoalId = filter_var($input['parent_goal_id'] ?? null, FILTER_VALIDATE_INT);
        $behaviorNote = $this->normalizeNullableText((string) ($input['behavior_note'] ?? ($input['behavior_how'] ?? '')), 300);
        $periodStartDate = $this->normalizeNullableDate((string) ($input['period_start_date'] ?? ''));
        $periodEndDate = $this->normalizeNullableDate((string) ($input['period_end_date'] ?? ''));
        $errors = [];

        if ($title === '') {
            $errors['title'] = '목표명을 입력해주세요.';
        } elseif (mb_strlen($title) > 80) {
            $errors['title'] = '목표명은 80자 이내로 입력해주세요.';
        }

        if (!array_key_exists($goalType, self::GOAL_TYPES)) {
            $errors['goal_type'] = '목표 구분을 다시 선택해주세요.';
            $goalType = 'monthly';
        }

        if (!array_key_exists($status, self::STATUSES)) {
            $errors['status'] = '목표 상태를 다시 선택해주세요.';
            $status = 'active';
        }

        if ($parentGoalId === false || $parentGoalId === 0) {
            $parentGoalId = null;
        }

        if ($parentGoalId !== null) {
            if (!$this->goalRepository->parentExists($userId, (int) $parentGoalId)) {
                $errors['parent_goal_id'] = '연결할 상위 목표를 찾을 수 없습니다.';
            } elseif ($goalId !== null && $this->goalRepository->wouldCreateCycle($userId, $goalId, (int) $parentGoalId)) {
                $errors['parent_goal_id'] = '하위 목표를 상위 목표로 연결할 수 없습니다.';
            }
        }

        if ($goalType === 'bucket') {
            $periodStartDate = null;
            $periodEndDate = null;
        } else {
            $periodStartDate ??= date('Y-m-d');
            $periodEndDate ??= $this->defaultEndDate($goalType, $periodStartDate);

            if ($periodStartDate === null) {
                $errors['period_start_date'] = '기간 목표는 시작일을 입력해주세요.';
            }

            if ($periodEndDate === null) {
                $errors['period_end_date'] = '기간 목표는 종료일을 입력해주세요.';
            }

            if ($periodStartDate !== null && $periodEndDate !== null && $periodEndDate < $periodStartDate) {
                $errors['period_end_date'] = '종료일은 시작일 이후로 입력해주세요.';
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'title' => $title,
                'goalType' => $goalType,
                'status' => $status,
                'parentGoalId' => $parentGoalId === null ? null : (int) $parentGoalId,
                'behaviorWhen' => null,
                'behaviorWhere' => null,
                'behaviorHow' => $behaviorNote,
                'periodStartDate' => $periodStartDate,
                'periodEndDate' => $periodEndDate,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createGoal(int $userId, array $data): ?int
    {
        return $this->goalRepository->create($userId, $data);
    }

    /** @param array<string, mixed> $data */
    public function updateGoal(int $userId, int $goalId, array $data): bool
    {
        return $this->goalRepository->update($userId, $goalId, $data);
    }

    public function deleteGoal(int $userId, int $goalId): bool
    {
        return $this->goalRepository->softDelete($userId, $goalId);
    }

    private function normalizeNullableText(string $value, int $maxLength): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function normalizeNullableDate(string $date): ?string
    {
        $date = trim($date);
        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date
            ? $date
            : null;
    }

    private function defaultEndDate(string $goalType, string $startDate): ?string
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
        if (!$parsed instanceof DateTimeImmutable) {
            return null;
        }

        $modifier = match ($goalType) {
            'yearly' => '+1 year',
            'half_year' => '+6 months',
            'quarterly' => '+3 months',
            'monthly' => '+1 month',
            default => null,
        };

        return $modifier === null ? null : $parsed->modify($modifier)->format('Y-m-d');
    }

    /** @return array<string, mixed> */
    private function formatGoal(array $goal): array
    {
        $goalType = (string) $goal['goal_type'];
        $status = (string) $goal['status'];
        $startDate = $goal['period_start_date'] === null ? null : (string) $goal['period_start_date'];
        $endDate = $goal['period_end_date'] === null ? null : (string) $goal['period_end_date'];
        $periodProgress = $this->periodProgress($startDate, $endDate, $status);

        return [
            'id' => (int) $goal['id'],
            'parentGoalId' => $goal['parent_goal_id'] === null ? null : (int) $goal['parent_goal_id'],
            'parentTitle' => $goal['parent_title'] === null ? null : (string) $goal['parent_title'],
            'parentGoalTypeLabel' => $goal['parent_goal_type'] === null
                ? null
                : (self::GOAL_TYPES[(string) $goal['parent_goal_type']] ?? '목표'),
            'goalType' => $goalType,
            'goalTypeLabel' => self::GOAL_TYPES[$goalType] ?? '목표',
            'title' => (string) $goal['title'],
            'behaviorWhen' => $goal['behavior_when'] === null ? '' : (string) $goal['behavior_when'],
            'behaviorWhere' => $goal['behavior_where'] === null ? '' : (string) $goal['behavior_where'],
            'behaviorHow' => $goal['behavior_how'] === null ? '' : (string) $goal['behavior_how'],
            'behaviorNote' => $goal['behavior_how'] === null ? '' : (string) $goal['behavior_how'],
            'periodStartDate' => $startDate,
            'periodEndDate' => $endDate,
            'periodLabel' => $startDate === null || $endDate === null ? '기간 없음' : $startDate . ' ~ ' . $endDate,
            'periodProgress' => $periodProgress,
            'status' => $status,
            'statusLabel' => self::STATUSES[$status] ?? '진행 중',
            'completedAt' => $goal['completed_at'] === null ? null : (string) $goal['completed_at'],
            'linkedPlans' => [],
            'linkedRoutines' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function formatLinkedPlan(array $plan): array
    {
        return [
            'planGroupId' => (int) $plan['plan_group_id'],
            'planName' => (string) $plan['plan_name'],
            'blockTitle' => (string) $plan['block_title'],
            'importance' => (string) $plan['importance'],
            'timeRange' => $this->indexToTime((int) $plan['start_index']) . ' ~ ' . $this->indexToTime((int) $plan['end_index']),
        ];
    }

    /** @return array<string, mixed> */
    private function formatLinkedRoutine(array $routine): array
    {
        $startDate = (string) $routine['start_date'];
        $durationDays = (int) $routine['duration_days'];
        $endDate = (new DateTimeImmutable($startDate))->modify('+' . max(0, $durationDays - 1) . ' days')->format('Y-m-d');

        return [
            'id' => (int) $routine['id'],
            'name' => (string) $routine['name'],
            'periodLabel' => $startDate . ' ~ ' . $endDate,
        ];
    }

    private function indexToTime(int $index): string
    {
        $minutes = $index * 10;
        $hour = (int) floor($minutes / 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    /** @return array{visible: bool, percent: int, label: string, state: string} */
    private function periodProgress(?string $startDate, ?string $endDate, string $status): array
    {
        $empty = [
            'visible' => false,
            'percent' => 0,
            'label' => '',
            'state' => 'none',
        ];

        if ($startDate === null || $endDate === null) {
            return $empty;
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
        if (!$start instanceof DateTimeImmutable || !$end instanceof DateTimeImmutable || $end < $start) {
            return $empty;
        }

        if ($status === 'completed') {
            return [
                'visible' => true,
                'percent' => 100,
                'label' => '완료',
                'state' => 'completed',
            ];
        }

        $today = new DateTimeImmutable('today');
        $totalDays = max(1, ((int) $start->diff($end)->days) + 1);

        if ($today < $start) {
            return [
                'visible' => true,
                'percent' => 0,
                'label' => '시작 전',
                'state' => 'upcoming',
            ];
        }

        if ($today > $end) {
            return [
                'visible' => true,
                'percent' => 100,
                'label' => '마감 지남',
                'state' => 'overdue',
            ];
        }

        $elapsedDays = ((int) $start->diff($today)->days) + 1;
        $percent = (int) min(100, max(0, round(($elapsedDays / $totalDays) * 100)));

        return [
            'visible' => true,
            'percent' => $percent,
            'label' => '기간 ' . $percent . '% 진행',
            'state' => 'active',
        ];
    }
}
