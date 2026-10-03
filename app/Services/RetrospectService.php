<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MemoRepository;
use App\Models\RetrospectRepository;
use DateTimeImmutable;

final class RetrospectService
{
    private RetrospectRepository $retrospectRepository;
    private MemoRepository $memoRepository;

    public function __construct()
    {
        $this->retrospectRepository = new RetrospectRepository();
        $this->memoRepository = new MemoRepository();
    }

    /** @return array<string, mixed> */
    public function getPageData(int $userId, ?string $requestedDate, ?string $requestedSort): array
    {
        $date = $this->normalizeDate($requestedDate);
        $sort = $this->normalizeSort($requestedSort);
        $isToday = $date === date('Y-m-d');
        $canEdit = $this->isEditableDate($date);
        $settings = $this->formatSettings($this->retrospectRepository->getSettings($userId));

        if ($isToday && $settings['autoPublishEnabled'] && $this->shouldAutoPublish($settings['autoPublishTime'])) {
            $report = $this->retrospectRepository->findReport($userId, $date);
            if ($report === null || (string) $report['status'] !== 'submitted') {
                $this->publish($userId, $date, $this->textsFromReport($report));
            }
        }

        $report = $this->retrospectRepository->findReport($userId, $date);
        $useSnapshots = $report !== null && (string) $report['status'] === 'submitted';
        $isEmptyHistory = !$canEdit && !$useSnapshots;

        if ($useSnapshots) {
            $snapshot = $this->buildSnapshotFromReport($report, $sort);
        } elseif ($isEmptyHistory) {
            $snapshot = $this->emptySnapshot();
        } else {
            $snapshot = $this->buildLiveSnapshot($userId, $date, $sort);
        }

        $memoItems = $useSnapshots
            ? $this->formatSnapshotMemoItems($this->retrospectRepository->listMemoItems((int) $report['id']))
            : $this->formatMemoItems($this->memoRepository->listForDate($userId, $date));
        $routineItems = $this->withRoutineDetails($userId, $snapshot['routineItems'], $date);
        $scheduleItems = $this->buildScheduleItems($snapshot['planItems'], $snapshot['actualItems'], $sort);
        $recordComposition = $this->buildRecordComposition(
            count($routineItems),
            count($snapshot['planItems']),
            count($snapshot['actualItems']),
            count($memoItems)
        );
        $isFuture = $date > date('Y-m-d');

        return [
            'date' => $date,
            'dateTitle' => $this->formatDateTitle($date),
            'dateSubTitle' => $this->formatDateSubTitle($date),
            'prevDate' => $this->shiftDate($date, '-1 day'),
            'nextDate' => $this->shiftDate($date, '+1 day'),
            'todayDate' => date('Y-m-d'),
            'isToday' => $isToday,
            'canEdit' => $canEdit,
            'isFuture' => $isFuture,
            'futureMessageKey' => $isFuture ? 'retrospect.future_message_' . random_int(1, 4) : null,
            'isEmptyHistory' => $isEmptyHistory,
            'sort' => $sort,
            'report' => $report,
            'texts' => $this->textsFromReport($report),
            'summary' => $snapshot['summary'],
            'planItems' => $snapshot['planItems'],
            'actualItems' => $snapshot['actualItems'],
            'scheduleItems' => $scheduleItems,
            'routineItems' => $routineItems,
            'memoItems' => $memoItems,
            'recordComposition' => $recordComposition,
            'settings' => $settings,
            'statusLabel' => $useSnapshots ? '발행됨' : ($canEdit ? '작성중' : '회고 없음'),
        ];
    }

    /** @return array<string, mixed> */
    public function getHistoryData(int $userId, int $requestedPage): array
    {
        $perPage = 20;
        $page = max(1, $requestedPage);
        $rows = $this->retrospectRepository->listPublishedReports($userId, $perPage + 1, ($page - 1) * $perPage);
        $hasNext = count($rows) > $perPage;
        if ($hasNext) {
            array_pop($rows);
        }

        return [
            'items' => array_map(function (array $row): array {
                $texts = $this->textsFromReport($row);
                $snippet = $texts['today_review'] !== ''
                    ? $texts['today_review']
                    : ($texts['today_thoughts'] !== '' ? $texts['today_thoughts'] : $texts['tomorrow_plan']);

                return [
                    'date' => (string) $row['report_date'],
                    'dateTitle' => $this->formatDateTitle((string) $row['report_date']),
                    'dateSubTitle' => $this->formatDateSubTitle((string) $row['report_date']),
                    'snippet' => mb_strlen($snippet) > 90 ? mb_substr($snippet, 0, 90) . '…' : $snippet,
                    'planAchievementRate' => (int) $row['plan_achievement_rate'],
                    'routineAchievementRate' => (int) $row['routine_achievement_rate'],
                    'submittedAt' => (string) ($row['submitted_at'] ?? ''),
                ];
            }, $rows),
            'page' => $page,
            'hasPrevious' => $page > 1,
            'hasNext' => $hasNext,
        ];
    }

    /** @return array<string, mixed> */
    public function getGoalReviewData(int $userId): array
    {
        $planMap = [];
        foreach ($this->retrospectRepository->listGoalPlanProgress($userId) as $row) {
            $planMap[(int) $row['goal_id']] = $row;
        }

        $routineMap = [];
        foreach ($this->retrospectRepository->listGoalRoutineProgress($userId) as $row) {
            $routineMap[(int) $row['goal_id']] = $row;
        }

        $goals = [];
        foreach ($this->retrospectRepository->listGoalReviewGoals($userId) as $goal) {
            $goalId = (int) $goal['id'];
            $plan = $planMap[$goalId] ?? [];
            $routine = $routineMap[$goalId] ?? [];
            $planCount = (int) ($plan['plan_count'] ?? 0);
            $linkedPlanCount = max((int) ($goal['linked_plan_count'] ?? 0), $planCount);
            $executedPlanCount = (int) ($plan['executed_plan_count'] ?? 0);
            $routineCount = (int) ($routine['routine_count'] ?? 0);
            $doneDayCount = (int) ($routine['done_day_count'] ?? 0);

            if ($linkedPlanCount === 0 && $routineCount === 0) {
                $feedback = '목표를 실행할 계획이나 루틴을 연결해보세요.';
            } elseif ($linkedPlanCount > 0 && $planCount === 0 && $routineCount === 0) {
                $feedback = '연결된 계획을 캘린더 기준 계획으로 선택해 실행을 시작해보세요.';
            } elseif ($executedPlanCount === 0 && $doneDayCount === 0) {
                $feedback = '연결된 실행 항목은 있지만 아직 완료 기록이 없습니다.';
            } elseif ($planCount > 0 && $executedPlanCount < $planCount) {
                $feedback = '아직 실행 기록이 없는 계획을 다음 일정에 배치해보세요.';
            } else {
                $feedback = '실행 기록이 이어지고 있습니다. 다음 기간에도 유지할 행동을 확인해보세요.';
            }

            $goalType = (string) $goal['goal_type'];
            $goalTypeLabels = [
                'bucket' => '버킷',
                'yearly' => '연간',
                'half_year' => '반기',
                'quarterly' => '분기',
                'monthly' => '월간',
            ];
            $goalStatus = (string) $goal['status'];
            $goalStatusLabels = [
                'active' => '진행 중',
                'completed' => '완료',
                'paused' => '보류',
                'archived' => '보관',
            ];
            $goals[] = [
                'id' => $goalId,
                'type' => $goalTypeLabels[$goalType] ?? $goalType,
                'title' => (string) $goal['title'],
                'status' => $goalStatus,
                'statusLabel' => $goalStatusLabels[$goalStatus] ?? $goalStatus,
                'periodStartDate' => $goal['period_start_date'] === null ? null : (string) $goal['period_start_date'],
                'periodEndDate' => $goal['period_end_date'] === null ? null : (string) $goal['period_end_date'],
                'planCount' => $planCount,
                'linkedPlanCount' => $linkedPlanCount,
                'executedPlanCount' => $executedPlanCount,
                'planExecutionRate' => $this->percentage($executedPlanCount, $planCount),
                'actualEventCount' => (int) ($plan['actual_event_count'] ?? 0),
                'routineCount' => $routineCount,
                'routineDoneDayCount' => $doneDayCount,
                'feedback' => $feedback,
            ];
        }

        $routineHistory = [];
        foreach ($this->retrospectRepository->listCompletedRoutineHistory($userId, date('Y-m-d')) as $routine) {
            $durationDays = max(1, (int) $routine['duration_days']);
            $plannedEndDate = (new DateTimeImmutable((string) $routine['start_date']))
                ->modify('+' . ($durationDays - 1) . ' days')
                ->format('Y-m-d');
            $status = (string) ($routine['status'] ?? 'active');
            $effectiveEndDate = $routine['ended_at'] === null ? $plannedEndDate : (string) $routine['ended_at'];
            $statusLabel = $status === 'stopped'
                ? '중단'
                : ($effectiveEndDate < $plannedEndDate ? '조기 완료' : '기간 완료');
            $stateMap = $this->retrospectRepository->listRoutineLogStates(
                $userId,
                (int) $routine['id'],
                (string) $routine['start_date'],
                $effectiveEndDate
            );
            $periodGroups = [];
            for (
                $logDate = new DateTimeImmutable((string) $routine['start_date']);
                $logDate <= new DateTimeImmutable($effectiveEndDate);
                $logDate = $logDate->modify('+1 day')
            ) {
                $dateKey = $logDate->format('Y-m-d');
                $state = $stateMap[$dateKey] ?? null;
                $monthKey = $logDate->format('Y-m');
                if (!isset($periodGroups[$monthKey])) {
                    $periodGroups[$monthKey] = [
                        'key' => $monthKey,
                        'label' => $logDate->format('Y년 n월'),
                        'cells' => [],
                    ];
                }
                $periodGroups[$monthKey]['cells'][] = [
                    'date' => $dateKey,
                    'day' => $logDate->format('j'),
                    'state' => $state === 1 ? 'O' : ($state === 0 ? 'X' : ''),
                ];
            }

            $routineHistory[] = [
                'id' => (int) $routine['id'],
                'name' => (string) $routine['name'],
                'startDate' => (string) $routine['start_date'],
                'endDate' => $effectiveEndDate,
                'durationDays' => $durationDays,
                'doneCount' => (int) $routine['done_count'],
                'achievementRate' => $this->percentage((int) $routine['done_count'], $durationDays),
                'statusLabel' => $statusLabel,
                'periodGroups' => array_values($periodGroups),
            ];
        }

        return ['goals' => $goals, 'routineHistory' => $routineHistory];
    }

    /** @return array<string, mixed>|null */
    public function getPublishedPreview(int $userId, string $date): ?array
    {
        $date = $this->normalizeDate($date);
        $report = $this->retrospectRepository->findReport($userId, $date);
        if ($report === null || (string) $report['status'] !== 'submitted') {
            return null;
        }

        return [
            'date' => $date,
            'dateTitle' => $this->formatDateTitle($date),
            'dateSubTitle' => $this->formatDateSubTitle($date),
            'planAchievementRate' => (int) $report['plan_achievement_rate'],
            'routineAchievementRate' => (int) $report['routine_achievement_rate'],
            'linkedActualTimeLabel' => $this->formatMinutes((int) $report['linked_actual_minutes']),
            'texts' => $this->textsFromReport($report),
            'submittedAt' => (string) ($report['submitted_at'] ?? ''),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getLatestPublishedPreview(int $userId, ?string $maxDate = null): ?array
    {
        $date = $maxDate === null ? null : $this->normalizeDate($maxDate);
        $report = $this->retrospectRepository->findLatestSubmittedReport($userId, $date);
        if ($report === null) {
            return null;
        }

        return [
            'date' => (string) $report['report_date'],
            'dateTitle' => $this->formatDateTitle((string) $report['report_date']),
            'dateSubTitle' => $this->formatDateSubTitle((string) $report['report_date']),
            'planAchievementRate' => (int) $report['plan_achievement_rate'],
            'routineAchievementRate' => (int) $report['routine_achievement_rate'],
            'linkedActualTimeLabel' => $this->formatMinutes((int) $report['linked_actual_minutes']),
            'texts' => $this->textsFromReport($report),
            'submittedAt' => (string) ($report['submitted_at'] ?? ''),
        ];
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, string>} */
    public function validateTextInput(array $input): array
    {
        $data = [
            'today_review' => trim((string) ($input['today_review'] ?? '')),
            'today_thoughts' => trim((string) ($input['today_thoughts'] ?? '')),
            'tomorrow_plan' => trim((string) ($input['tomorrow_plan'] ?? '')),
        ];
        $errors = [];

        foreach ($data as $field => $value) {
            if (mb_strlen($value) > 2000) {
                $errors[$field] = '각 회고 입력은 2000자 이내로 입력해주세요.';
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => $data,
        ];
    }

    /** @param array{today_review: string, today_thoughts: string, tomorrow_plan: string} $texts */
    public function saveDraft(int $userId, string $date, array $texts): bool
    {
        $date = $this->normalizeDate($date);
        if (!$this->isEditableDate($date)) {
            return false;
        }

        $this->retrospectRepository->saveDraft($userId, $date, $texts);

        return true;
    }

    /** @param array{today_review: string, today_thoughts: string, tomorrow_plan: string} $texts */
    public function publish(int $userId, string $date, array $texts): bool
    {
        $date = $this->normalizeDate($date);
        if (!$this->isEditableDate($date)) {
            return false;
        }

        $snapshot = $this->buildLiveSnapshot($userId, $date, 'time');
        $memoItems = $this->memoRepository->listForDate($userId, $date);

        return $this->retrospectRepository->publishReport(
            $userId,
            $date,
            $snapshot['summary'],
            $snapshot['planItems'],
            $snapshot['actualItems'],
            $snapshot['routineItems'],
            $memoItems,
            $texts
        );
    }

    public function republish(int $userId, string $date): bool
    {
        $date = $this->normalizeDate($date);
        if (!$this->isEditableDate($date)) {
            return false;
        }

        $report = $this->retrospectRepository->findReport($userId, $date);
        if ($report === null || (string) $report['status'] !== 'submitted') {
            return false;
        }

        return $this->publish($userId, $date, $this->textsFromReport($report));
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array{enabled: bool, time: string}} */
    public function validateSettingsInput(array $input): array
    {
        $enabled = isset($input['auto_publish_enabled']) && (string) $input['auto_publish_enabled'] === '1';
        $time = trim((string) ($input['auto_publish_time'] ?? '22:00'));
        $errors = [];

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
            $errors['auto_publish_time'] = '자동 발행 시간은 HH:MM 형식으로 입력해주세요.';
            $time = '22:00';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'enabled' => $enabled,
                'time' => $time,
            ],
        ];
    }

    public function updateSettings(int $userId, bool $enabled, string $time): void
    {
        $this->retrospectRepository->updateSettings($userId, $enabled, $time);
    }

    public function normalizeDate(?string $date): string
    {
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date) {
                return $date;
            }
        }

        return date('Y-m-d');
    }

    private function normalizeSort(?string $sort): string
    {
        return $sort === 'time' ? 'time' : 'importance';
    }

    /** @return array<string, mixed> */
    private function emptySnapshot(): array
    {
        return [
            'summary' => [
                'planTotalCount' => 0,
                'planLinkedCount' => 0,
                'planUnlinkedCount' => 0,
                'planAchievementRate' => 0,
                'routineTotalCount' => 0,
                'routineDoneCount' => 0,
                'routineAchievementRate' => 0,
                'linkedActualMinutes' => 0,
                'linkedActualCount' => 0,
                'linkedActualTimeLabel' => '0분',
            ],
            'planItems' => [],
            'actualItems' => [],
            'routineItems' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function buildLiveSnapshot(int $userId, string $date, string $sort): array
    {
        $planBlocks = $this->formatPlanItems($this->retrospectRepository->listPlanBlocksForDay($userId, $date));
        $actualItems = $this->formatActualItems($this->retrospectRepository->listActualEventsForDay($userId, $date, $sort));
        $routineItems = $this->formatRoutineItems($this->retrospectRepository->listRoutinesForDate($userId, $date));
        $linkedTemplateIds = [];
        $linkedActualMinutes = 0;
        $linkedActualCount = 0;

        foreach ($actualItems as $item) {
            if ($item['is_linked'] && $item['plan_template_id'] !== null) {
                $linkedTemplateIds[(int) $item['plan_template_id']] = true;
                $linkedActualMinutes += (int) $item['durationMinutes'];
                $linkedActualCount++;
            }
        }

        foreach ($planBlocks as &$item) {
            $item['is_linked'] = isset($linkedTemplateIds[(int) $item['plan_template_id']]);
        }
        unset($item);

        $planTotal = count($planBlocks);
        $planLinked = 0;
        foreach ($planBlocks as $item) {
            if (!empty($item['is_linked'])) {
                $planLinked++;
            }
        }

        $routineDone = 0;
        foreach ($routineItems as $item) {
            if ($item['state'] === 'O') {
                $routineDone++;
            }
        }

        return [
            'summary' => [
                'planTotalCount' => $planTotal,
                'planLinkedCount' => $planLinked,
                'planUnlinkedCount' => max(0, $planTotal - $planLinked),
                'planAchievementRate' => $this->percentage($planLinked, $planTotal),
                'routineTotalCount' => count($routineItems),
                'routineDoneCount' => $routineDone,
                'routineAchievementRate' => $this->percentage($routineDone, count($routineItems)),
                'linkedActualMinutes' => $linkedActualMinutes,
                'linkedActualCount' => $linkedActualCount,
                'linkedActualTimeLabel' => $this->formatMinutes($linkedActualMinutes),
            ],
            'planItems' => $planBlocks,
            'actualItems' => $actualItems,
            'routineItems' => $routineItems,
        ];
    }

    /** @param array<string, mixed> $report */
    private function buildSnapshotFromReport(array $report, string $sort): array
    {
        $reportId = (int) $report['id'];
        $summary = [
            'planTotalCount' => (int) $report['plan_total_count'],
            'planLinkedCount' => (int) $report['plan_linked_count'],
            'planUnlinkedCount' => (int) $report['plan_unlinked_count'],
            'planAchievementRate' => (int) $report['plan_achievement_rate'],
            'routineTotalCount' => (int) $report['routine_total_count'],
            'routineDoneCount' => (int) $report['routine_done_count'],
            'routineAchievementRate' => (int) $report['routine_achievement_rate'],
            'linkedActualMinutes' => (int) $report['linked_actual_minutes'],
            'linkedActualCount' => (int) $report['linked_actual_count'],
            'linkedActualTimeLabel' => $this->formatMinutes((int) $report['linked_actual_minutes']),
        ];

        return [
            'summary' => $summary,
            'planItems' => $this->formatSnapshotPlanItems($this->retrospectRepository->listPlanItems($reportId)),
            'actualItems' => $this->formatSnapshotActualItems($this->retrospectRepository->listActualItems($reportId, $sort)),
            'routineItems' => $this->formatSnapshotRoutineItems($this->retrospectRepository->listRoutineItems($reportId)),
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatPlanItems(array $items): array
    {
        return array_map(function (array $item): array {
            $isTimed = $item['start_index'] !== null && $item['end_index'] !== null;
            return [
                'plan_group_id' => (int) $item['plan_group_id'],
                'plan_block_id' => (int) $item['plan_block_id'],
                'plan_template_id' => (int) $item['plan_template_id'],
                'title' => (string) $item['title'],
                'start_index' => $isTimed ? (int) $item['start_index'] : null,
                'end_index' => $isTimed ? (int) $item['end_index'] : null,
                'importance' => $this->normalizeImportance((string) ($item['importance'] ?? 'D')),
                'goalTitle' => (string) ($item['goal_title'] ?? ''),
                'is_linked' => false,
                'sort_order' => (int) $item['sort_order'],
                'timeRange' => $isTimed
                    ? $this->formatTimeRange((int) $item['start_index'], (int) $item['end_index'])
                    : '시간 미정',
            ];
        }, $items);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatActualItems(array $items): array
    {
        return array_map(function (array $item): array {
            $start = (int) $item['start_index'];
            $end = (int) $item['end_index'];
            $tagColor = $this->normalizeHexColor((string) ($item['tag_color'] ?? ''));

            return [
                'id' => (int) $item['id'],
                'calendar_day_id' => (int) $item['calendar_day_id'],
                'title' => (string) $item['title'],
                'memo' => (string) ($item['memo'] ?? ''),
                'start_index' => $start,
                'end_index' => $end,
                'plan_template_id' => $item['plan_template_id'] === null ? null : (int) $item['plan_template_id'],
                'plan_importance' => $item['plan_importance'] === null ? null : $this->normalizeImportance((string) $item['plan_importance']),
                'tag_name' => (string) ($item['tag_name'] ?? '태그 없음'),
                'tag_color' => $tagColor,
                'tag_text_color' => $this->contrastTextColor($tagColor),
                'is_linked' => $item['plan_template_id'] !== null,
                'durationMinutes' => ($end - $start) * 10,
                'durationLabel' => $this->formatMinutes(($end - $start) * 10),
                'timeRange' => $this->formatTimeRange($start, $end),
                'sort_order' => (int) ($item['tag_sort_order'] ?? 999),
            ];
        }, $items);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatRoutineItems(array $items): array
    {
        $formatted = [];
        foreach ($items as $index => $item) {
            $state = $item['state'] === null ? 'blank' : ((int) $item['state'] === 1 ? 'O' : 'X');
            $formatted[] = [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'goalTitle' => (string) ($item['goal_title'] ?? ''),
                'startDate' => (string) ($item['start_date'] ?? ''),
                'durationDays' => (int) ($item['duration_days'] ?? 0),
                'endedAt' => $item['ended_at'] === null ? null : (string) $item['ended_at'],
                'state' => $state,
                'stateLabel' => $this->routineStateLabel($state),
                'sort_order' => $index + 1,
            ];
        }

        return $formatted;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatSnapshotPlanItems(array $items): array
    {
        return array_map(function (array $item): array {
            $isTimed = $item['start_index'] !== null && $item['end_index'] !== null;
            return [
                'plan_template_id' => (int) ($item['plan_template_id'] ?? 0),
                'title' => (string) $item['title_snapshot'],
                'start_index' => $isTimed ? (int) $item['start_index'] : null,
                'end_index' => $isTimed ? (int) $item['end_index'] : null,
                'importance' => $this->normalizeImportance((string) ($item['importance_snapshot'] ?? 'D')),
                'goalTitle' => (string) ($item['goal_title_snapshot'] ?? ''),
                'is_linked' => (int) $item['is_linked'] === 1,
                'timeRange' => $isTimed
                    ? $this->formatTimeRange((int) $item['start_index'], (int) $item['end_index'])
                    : '시간 미정',
            ];
        }, $items);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatSnapshotActualItems(array $items): array
    {
        return array_map(function (array $item): array {
            $start = (int) $item['start_index'];
            $end = (int) $item['end_index'];
            $importance = $item['plan_importance_snapshot'] === null
                ? null
                : $this->normalizeImportance((string) $item['plan_importance_snapshot']);
            $tagColor = $this->normalizeHexColor((string) ($item['tag_color_snapshot'] ?? ''));

            return [
                'title' => (string) $item['title_snapshot'],
                'memo' => (string) ($item['memo_snapshot'] ?? ''),
                'start_index' => $start,
                'end_index' => $end,
                'tag_name' => (string) ($item['tag_name_snapshot'] ?? '태그 없음'),
                'tag_color' => $tagColor,
                'tag_text_color' => $this->contrastTextColor($tagColor),
                'plan_importance' => $importance,
                'plan_template_id' => $item['plan_template_id_snapshot'] === null ? null : (int) $item['plan_template_id_snapshot'],
                'is_linked' => (int) $item['is_linked'] === 1,
                'durationMinutes' => ($end - $start) * 10,
                'durationLabel' => $this->formatMinutes(($end - $start) * 10),
                'timeRange' => $this->formatTimeRange($start, $end),
            ];
        }, $items);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatSnapshotRoutineItems(array $items): array
    {
        return array_map(function (array $item): array {
            $state = (string) $item['state_snapshot'];

            return [
                'id' => (int) $item['routine_id'],
                'name' => (string) $item['routine_name_snapshot'],
                'goalTitle' => (string) ($item['goal_title_snapshot'] ?? ''),
                'startDate' => '',
                'durationDays' => 0,
                'endedAt' => null,
                'state' => $state,
                'stateLabel' => $this->routineStateLabel($state),
            ];
        }, $items);
    }

    /** @param array<int, array<string, mixed>> $items
     *  @return array<int, array<string, mixed>>
     */
    private function withRoutineDetails(int $userId, array $items, string $date): array
    {
        foreach ($items as &$item) {
            $detail = $this->retrospectRepository->findRoutineDetail($userId, (int) $item['id']);
            if ($detail !== null) {
                if ((string) ($item['goalTitle'] ?? '') === '') {
                    $item['goalTitle'] = (string) ($detail['goal_title'] ?? '');
                }
                $item['startDate'] = (string) $detail['start_date'];
                $item['durationDays'] = (int) $detail['duration_days'];
                $item['endedAt'] = $detail['ended_at'] === null ? null : (string) $detail['ended_at'];
            }

            $startDate = (string) ($item['startDate'] ?? '');
            $durationDays = (int) ($item['durationDays'] ?? 0);
            if ($startDate === '' || $durationDays < 1) {
                $item['periodGroups'] = [];
                $item['streakCount'] = 0;
                continue;
            }

            $plannedEnd = (new DateTimeImmutable($startDate))->modify('+' . ($durationDays - 1) . ' days')->format('Y-m-d');
            $endDate = !empty($item['endedAt']) && (string) $item['endedAt'] < $plannedEnd
                ? (string) $item['endedAt']
                : $plannedEnd;
            $states = $this->retrospectRepository->listRoutineLogStates($userId, (int) $item['id'], $startDate, $endDate);
            $groups = [];
            for ($cursor = new DateTimeImmutable($startDate); $cursor <= new DateTimeImmutable($endDate); $cursor = $cursor->modify('+1 day')) {
                $key = $cursor->format('Y-m');
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'label' => $cursor->format('Y년 n월'),
                        'leadingBlankCount' => (int) $cursor->modify('first day of this month')->format('N') - 1,
                        'cells' => [],
                    ];
                }
                $cellDate = $cursor->format('Y-m-d');
                $state = $states[$cellDate] ?? null;
                $groups[$key]['cells'][] = [
                    'date' => $cellDate,
                    'day' => $cursor->format('j'),
                    'state' => $state === 1 ? 'O' : ($state === 0 ? 'X' : ''),
                ];
            }

            $streak = 0;
            if ($date >= $startDate && $date <= $endDate) {
                for ($cursor = new DateTimeImmutable($date); $cursor >= new DateTimeImmutable($startDate); $cursor = $cursor->modify('-1 day')) {
                    if (($states[$cursor->format('Y-m-d')] ?? null) !== 1) {
                        break;
                    }
                    $streak++;
                }
            }

            $item['endDate'] = $endDate;
            $item['periodGroups'] = array_values($groups);
            $item['streakCount'] = $streak;
        }
        unset($item);

        return $items;
    }

    /** @param array<int, array<string, mixed>> $plans
     *  @param array<int, array<string, mixed>> $actuals
     *  @return array<int, array<string, mixed>>
     */
    private function buildScheduleItems(array $plans, array $actuals, string $sort): array
    {
        $planIds = [];
        foreach ($plans as $plan) {
            $planIds[(int) ($plan['plan_template_id'] ?? 0)] = true;
        }
        $actualByPlan = [];
        $unplanned = [];
        foreach ($actuals as $actual) {
            $planId = $actual['plan_template_id'] ?? null;
            if ($planId !== null && (int) $planId > 0 && isset($planIds[(int) $planId])) {
                $actualByPlan[(int) $planId] = $actual;
            } else {
                $unplanned[] = $actual;
            }
        }

        $rows = [];
        foreach ($plans as $plan) {
            $planId = (int) ($plan['plan_template_id'] ?? 0);
            $actual = $actualByPlan[$planId] ?? null;
            $planStart = $plan['start_index'] ?? null;
            $planEnd = $plan['end_index'] ?? null;
            $actualStart = is_array($actual) ? (int) $actual['start_index'] : null;
            $actualEnd = is_array($actual) ? (int) $actual['end_index'] : null;
            $planDuration = $planStart === null || $planEnd === null ? null : ((int) $planEnd - (int) $planStart) * 10;
            $actualDuration = $actualStart === null || $actualEnd === null ? null : ($actualEnd - $actualStart) * 10;
            $hasDifference = $actual !== null && ($planStart === null || $planEnd === null
                || (int) $planStart !== $actualStart || (int) $planEnd !== $actualEnd);

            $rows[] = [
                'kind' => 'plan',
                'title' => (string) $plan['title'],
                'importance' => (string) $plan['importance'],
                'goalTitle' => (string) ($plan['goalTitle'] ?? ''),
                'planTimeRange' => (string) $plan['timeRange'],
                'actualTitle' => $actual === null ? '' : (string) $actual['title'],
                'actualTimeRange' => $actual === null ? '' : (string) $actual['timeRange'],
                'actualMemo' => $actual === null ? '' : (string) ($actual['memo'] ?? ''),
                'tagName' => $actual === null ? '' : (string) ($actual['tag_name'] ?? ''),
                'tagColor' => $actual === null ? '' : (string) ($actual['tag_color'] ?? ''),
                'isLinked' => $actual !== null,
                'hasDifference' => $hasDifference,
                'durationDifferenceLabel' => $actual === null || $planDuration === null
                    ? ''
                    : $this->formatDurationDifference($actualDuration - $planDuration),
                'startDifferenceLabel' => $actual === null || $planStart === null
                    ? ''
                    : $this->formatPointDifference(($actualStart - (int) $planStart) * 10),
                'endDifferenceLabel' => $actual === null || $planEnd === null
                    ? ''
                    : $this->formatPointDifference(($actualEnd - (int) $planEnd) * 10),
                'sortTime' => $actualStart ?? ($planStart === null ? 1000 : (int) $planStart),
            ];
        }

        foreach ($unplanned as $actual) {
            $rows[] = [
                'kind' => 'actual',
                'title' => (string) $actual['title'],
                'importance' => '',
                'goalTitle' => '',
                'planTimeRange' => '',
                'actualTitle' => (string) $actual['title'],
                'actualTimeRange' => (string) $actual['timeRange'],
                'actualMemo' => (string) ($actual['memo'] ?? ''),
                'tagName' => (string) ($actual['tag_name'] ?? ''),
                'tagColor' => (string) ($actual['tag_color'] ?? ''),
                'isLinked' => false,
                'hasDifference' => false,
                'durationDifferenceLabel' => '',
                'startDifferenceLabel' => '',
                'endDifferenceLabel' => '',
                'sortTime' => (int) $actual['start_index'],
            ];
        }

        usort($rows, static function (array $left, array $right) use ($sort): int {
            if ($sort === 'time') {
                return [$left['sortTime'], $left['kind'] === 'actual' ? 1 : 0]
                    <=> [$right['sortTime'], $right['kind'] === 'actual' ? 1 : 0];
            }
            $leftRank = $left['kind'] === 'plan' ? ord((string) $left['importance']) - ord('A') : 10;
            $rightRank = $right['kind'] === 'plan' ? ord((string) $right['importance']) - ord('A') : 10;
            return [$leftRank, $left['sortTime']] <=> [$rightRank, $right['sortTime']];
        });

        return $rows;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatMemoItems(array $items): array
    {
        return array_map(fn (array $item): array => [
            'content' => (string) $item['content'],
            'createdAt' => (string) $item['created_at'],
            'timeLabel' => $this->formatMemoTime((string) $item['created_at']),
        ], $items);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function formatSnapshotMemoItems(array $items): array
    {
        return array_map(fn (array $item): array => [
            'content' => (string) $item['content_snapshot'],
            'createdAt' => (string) $item['created_at_snapshot'],
            'timeLabel' => $this->formatMemoTime((string) $item['created_at_snapshot']),
        ], $items);
    }

    private function formatMemoTime(string $createdAt): string
    {
        if ($createdAt === '') {
            return '';
        }

        return (new DateTimeImmutable($createdAt, new \DateTimeZone('UTC')))
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format('H:i');
    }

    private function formatDurationDifference(int $minutes): string
    {
        if ($minutes === 0) {
            return '소요시간 동일';
        }

        return '계획보다 ' . abs($minutes) . '분 ' . ($minutes > 0 ? '길게' : '짧게');
    }

    private function formatPointDifference(int $minutes): string
    {
        if ($minutes === 0) {
            return '같음';
        }

        return abs($minutes) . '분 ' . ($minutes > 0 ? '늦음' : '빠름');
    }

    private function isEditableDate(string $date): bool
    {
        $today = date('Y-m-d');
        $yesterday = (new DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d');

        return $date === $today || $date === $yesterday;
    }

    /** @param array<string, mixed>|null $report */
    private function textsFromReport(?array $report): array
    {
        return [
            'today_review' => (string) ($report['today_review'] ?? ''),
            'today_thoughts' => (string) ($report['today_thoughts'] ?? ''),
            'tomorrow_plan' => (string) ($report['tomorrow_plan'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $settings */
    private function formatSettings(array $settings): array
    {
        $time = substr((string) ($settings['auto_publish_time'] ?? '22:00'), 0, 5);
        $enabled = (int) ($settings['auto_publish_enabled'] ?? 0) === 1;

        return [
            'autoPublishEnabled' => $enabled,
            'autoPublishTime' => $time,
            'autoPublishLabel' => $enabled ? '자동 발행 ' . $time : '자동 발행 끔',
        ];
    }

    private function shouldAutoPublish(string $time): bool
    {
        return date('H:i') >= $time;
    }

    private function percentage(int $count, int $total): int
    {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    }

    /** @return array{total: int, routine: int, plan: int, actual: int, memo: int} */
    private function buildRecordComposition(
        int $routineCount,
        int $planCount,
        int $actualCount,
        int $memoCount
    ): array
    {
        $counts = [
            'routine' => max(0, $routineCount),
            'plan' => max(0, $planCount),
            'actual' => max(0, $actualCount),
            'memo' => max(0, $memoCount),
        ];
        $total = array_sum($counts);
        $percentages = ['routine' => 0, 'plan' => 0, 'actual' => 0, 'memo' => 0];

        if ($total > 0) {
            $remainders = [];
            foreach ($counts as $key => $count) {
                $exact = ($count / $total) * 100;
                $percentages[$key] = (int) floor($exact);
                $remainders[$key] = $exact - $percentages[$key];
            }

            arsort($remainders, SORT_NUMERIC);
            $pointsToDistribute = 100 - array_sum($percentages);
            foreach (array_keys($remainders) as $key) {
                if ($pointsToDistribute <= 0) {
                    break;
                }
                $percentages[$key]++;
                $pointsToDistribute--;
            }
        }

        return ['total' => $total] + $percentages;
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours <= 0) {
            return $remaining . '분';
        }

        return $hours . '시간 ' . $remaining . '분';
    }

    private function formatDateTitle(string $date): string
    {
        return (new DateTimeImmutable($date))->format('m.d');
    }

    private function formatDateSubTitle(string $date): string
    {
        return (new DateTimeImmutable($date))->format('D');
    }

    private function shiftDate(string $date, string $modifier): string
    {
        return (new DateTimeImmutable($date))->modify($modifier)->format('Y-m-d');
    }

    private function formatTimeRange(int $startIndex, int $endIndex): string
    {
        return $this->indexToTime($startIndex) . ' ~ ' . $this->indexToTime($endIndex);
    }

    private function indexToTime(int $index): string
    {
        $minutes = $index * 10;
        $hour = intdiv($minutes, 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function normalizeImportance(string $importance): string
    {
        $importance = strtoupper(trim($importance));

        return in_array($importance, ['A', 'B', 'C', 'D'], true) ? $importance : 'D';
    }

    private function normalizeHexColor(string $color): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#8EA4A2';
    }

    private function contrastTextColor(string $color): string
    {
        $channels = array_map(static function (string $channel): float {
            $value = hexdec($channel) / 255;
            return $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }, [substr($color, 1, 2), substr($color, 3, 2), substr($color, 5, 2)]);
        $luminance = (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);

        return $luminance >= 0.21 ? '#1D1D18' : '#FFFFFF';
    }

    private function routineStateLabel(string $state): string
    {
        if ($state === 'O') {
            return '완료';
        }

        if ($state === 'X') {
            return '미완료';
        }

        return '미기록';
    }
}
