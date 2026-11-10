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
    private PlanService $planService;

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
        $this->executionEnergyService = new ExecutionEnergyService();
        $this->calendarService = new CalendarService();
        $this->goalService = new GoalService();
        $this->planService = new PlanService();
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
        $blocksByItem = [];
        $blockItems = [];
        $actualByItem = [];
        if ($day !== null) {
            foreach ($this->calendarRepository->getDailyPlanBlocksForDay($userId, (int) $day['id']) as $block) {
                $itemId = (int) $block['daily_plan_item_id'];
                $blocksByItem[$itemId][] = $block;
                $blockItems[(int) $block['daily_plan_block_id']] = $itemId;
            }
            foreach ($this->calendarRepository->getTodoTreeActualEvents($userId, (int) $day['id']) as $event) {
                $itemId = $event['daily_plan_item_id'] !== null
                    ? (int) $event['daily_plan_item_id']
                    : ($blockItems[(int) ($event['daily_plan_block_id'] ?? 0)] ?? null);
                if ($itemId !== null) $actualByItem[$itemId][] = $event;
            }
            $visibleItemIds = array_fill_keys(array_map(static fn(array $item): int => (int) $item['daily_plan_item_id'], $dayItems), true);
            foreach ($blocksByItem as $itemId => $blocks) {
                if (isset($visibleItemIds[$itemId])) continue;
                $block = $blocks[0];
                $dayItems[] = [
                    'daily_plan_item_id' => $itemId, 'title' => $block['title'], 'importance' => $block['source_deleted_at'] !== null ? $block['source_importance'] : $block['importance'],
                    'todo_status' => $block['source_deleted_at'] !== null ? $block['source_todo_status'] : $block['manual_status'],
                    'goal_id' => $block['goal_id'], 'goal_title' => $block['goal_title'],
                    'start_index' => null, 'end_index' => null, 'estimated_duration_minutes' => null,
                    'linked_event_id' => null, 'source_unavailable' => true,
                ];
            }
        }
        $blocksSupported = $this->calendarRepository->supportsDailyPlanBlocks();
        $reminders = new NotificationService();
        $reminderSettings = $reminders->settings($userId);
        $items = array_map(function (array $item) use ($blocksByItem, $actualByItem, $date, $blocksSupported, $reminders, $reminderSettings): array {
            $itemId = (int) $item['daily_plan_item_id'];
            $blocks = $blocksByItem[$itemId] ?? [];
            $actuals = $actualByItem[$itemId] ?? [];
            $isTimed = $item['start_index'] !== null && $item['end_index'] !== null;
            $isCompleted = ($item['todo_status'] ?? 'pending') === 'completed';
            $ranges = [];
            if ($isCompleted) {
                foreach ($actuals as $actual) {
                    if ($actual['start_index'] !== null && $actual['end_index'] !== null) {
                        $ranges[] = $this->formatTime((int) $actual['start_index']) . '–' . $this->formatTime((int) $actual['end_index']);
                    }
                }
                $timeRange = $ranges === [] ? '' : t('todo.actual_time', ['time' => implode(' · ', $ranges)]);
            } elseif (in_array(($item['todo_status'] ?? 'pending'), ['cancelled', 'postponed', 'external'], true)) {
                $timeRange = '';
            } elseif ($blocks !== []) {
                foreach ($blocks as $block) {
                    $ranges[] = $this->formatTime((int) $block['start_index']) . '–' . $this->formatTime((int) $block['end_index']);
                }
                $timeRange = implode(' · ', $ranges);
            } else {
                $timeRange = $isTimed
                    ? $this->formatTime((int) $item['start_index']) . '–' . $this->formatTime((int) $item['end_index'])
                    : t('todo.untimed');
            }
            $hasLongPlan = count(array_filter($blocks, static fn(array $block): bool => ((int) $block['end_index'] - (int) $block['start_index']) * 10 > 60)) > 0;
            if (!$blocksSupported && $isTimed) $hasLongPlan = ((int) $item['end_index'] - (int) $item['start_index']) * 10 > 60;
            $plannedRanges = array_map(fn(array $block): string => $this->formatTime((int) $block['start_index']) . '–' . $this->formatTime((int) $block['end_index']), $blocks);
            $actualRangesForItem = [];
            foreach ($actuals as $actual) {
                if ($actual['start_index'] !== null && $actual['end_index'] !== null) $actualRangesForItem[] = $this->formatTime((int) $actual['start_index']) . '–' . $this->formatTime((int) $actual['end_index']);
            }

            return [
                'id' => (int) $item['daily_plan_item_id'],
                'title' => (string) $item['title'],
                'importance' => (string) ($item['importance'] ?? 'D'),
                'todoStatus' => (string) ($item['todo_status'] ?? 'pending'),
                'goalId' => $item['goal_id'] === null ? null : (int) $item['goal_id'],
                'goalTitle' => $item['goal_title'] === null ? null : (string) $item['goal_title'],
                'startIndex' => $isTimed ? (int) $item['start_index'] : null,
                'endIndex' => $isTimed ? (int) $item['end_index'] : null,
                'timeRange' => $timeRange,
                'plannedTimeRange' => $plannedRanges !== [] ? implode(' · ', $plannedRanges) : ($isTimed ? $this->formatTime((int) $item['start_index']) . '–' . $this->formatTime((int) $item['end_index']) : t('todo.untimed')),
                'actualTimeRange' => $actualRangesForItem === [] ? '' : t('todo.actual_time', ['time' => implode(' · ', $actualRangesForItem)]),
                'isLongDuration' => $hasLongPlan,
                'isCompleted' => $isCompleted,
                'hasActual' => $actuals !== [] || $item['linked_event_id'] !== null,
                'sourceUnavailable' => !empty($item['source_unavailable']),
                'unassignedActuals' => array_values(array_map(fn(array $actual): array => [
                    'title' => (string) $actual['title'],
                    'date' => ($actual['actual_date'] ?? $date) !== $date ? (string) $actual['actual_date'] : null,
                    'timeRange' => $this->formatTime((int) $actual['start_index']) . '–' . $this->formatTime((int) $actual['end_index']),
                    'minutes' => ((int) $actual['end_index'] - (int) $actual['start_index']) * 10,
                ], array_filter($actuals, static fn(array $actual): bool => empty($actual['daily_plan_block_id']) && $actual['start_index'] !== null && $actual['end_index'] !== null))),
                'planBlocks' => array_map(function (array $block) use ($actuals, $date, $reminders, $reminderSettings): array {
                    $linked = [];
                    $minutes = ((int) $block['end_index'] - (int) $block['start_index']) * 10;
                    foreach ($actuals as $actual) {
                        if ((int) ($actual['daily_plan_block_id'] ?? 0) !== (int) $block['daily_plan_block_id']) continue;
                        $actualMinutes = $actual['start_index'] === null || $actual['end_index'] === null ? null : ((int) $actual['end_index'] - (int) $actual['start_index']) * 10;
                        $linked[] = [
                            'title' => (string) $actual['title'],
                            'date' => ($actual['actual_date'] ?? $date) !== $date ? (string) $actual['actual_date'] : null,
                            'timeRange' => $actualMinutes === null ? '시간 미정' : $this->formatTime((int) $actual['start_index']) . '–' . $this->formatTime((int) $actual['end_index']),
                            'minutes' => $actualMinutes,
                            'differenceMinutes' => $actualMinutes === null ? null : $actualMinutes - $minutes,
                        ];
                    }
                    return [
                        'id' => (int) $block['daily_plan_block_id'],
                        'title' => (string) $block['title'],
                        'timeRange' => $this->formatTime((int) $block['start_index']) . '–' . $this->formatTime((int) $block['end_index']),
                        'reminderEnabled' => (int) ($block['reminder_enabled'] ?? 0) === 1,
                        'reminderMinutesBefore' => (int) ($block['reminder_minutes_before'] ?? 5),
                        'reminderTiming' => $reminders->planReminderTiming($date, (int) $block['start_index'], $reminderSettings),
                        'minutes' => $minutes,
                        'actuals' => $linked,
                        'actualMinutes' => array_sum(array_column($linked, 'minutes')),
                        'status' => (string) ($block['manual_status'] ?? 'pending'),
                        'isLinked' => !empty($block['is_linked']),
                    ];
                }, $blocks),
            ];
        }, $dayItems);

        if (!$blocksSupported) {
            foreach ($items as &$item) {
                if ($item['startIndex'] === null || $item['endIndex'] === null) continue;
                $minutes = ($item['endIndex'] - $item['startIndex']) * 10;
                $actuals = array_map(static fn(array $actual): array => $actual + ['differenceMinutes' => $actual['minutes'] - $minutes], $item['unassignedActuals']);
                $item['planBlocks'] = [[
                    'id' => null,
                    'title' => $item['title'],
                    'timeRange' => $this->formatTime($item['startIndex']) . '–' . $this->formatTime($item['endIndex']),
                    'minutes' => $minutes,
                    'actuals' => $actuals,
                    'actualMinutes' => array_sum(array_column($actuals, 'minutes')),
                    'status' => $item['todoStatus'],
                    'isLinked' => $item['hasActual'],
                ]];
                $item['unassignedActuals'] = [];
            }
            unset($item);
        }

        $importanceRank = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        usort($items, static function (array $left, array $right) use ($importanceRank): int {
            $importanceComparison = ($importanceRank[$left['importance']] ?? 4)
                <=> ($importanceRank[$right['importance']] ?? 4);
            if ($importanceComparison !== 0) {
                return $importanceComparison;
            }

            $timeComparison = ($left['startIndex'] ?? 145) <=> ($right['startIndex'] ?? 145);

            return $timeComparison !== 0 ? $timeComparison : $left['id'] <=> $right['id'];
        });

        $sourceItemsById = [];
        foreach ($items as $item) {
            if (!$item['sourceUnavailable']) $sourceItemsById[$item['id']] = $item;
        }
        $planItems = [];
        foreach ($blocksByItem as $blocks) {
            foreach ($blocks as $block) {
                $source = $sourceItemsById[(int) $block['daily_plan_item_id']] ?? null;
                $blockActuals = array_filter(
                    $actualByItem[(int) $block['daily_plan_item_id']] ?? [],
                    static fn(array $event): bool => (int) ($event['daily_plan_block_id'] ?? 0) === (int) $block['daily_plan_block_id']
                );
                $actualRanges = [];
                foreach ($blockActuals as $actual) {
                    if ($actual['start_index'] !== null && $actual['end_index'] !== null) {
                        $actualRanges[] = $this->formatTime((int) $actual['start_index']) . '–' . $this->formatTime((int) $actual['end_index']);
                    }
                }
                $planItems[] = [
                    'id' => (int) $block['daily_plan_block_id'],
                    'title' => (string) $block['title'],
                    'importance' => $source['importance'] ?? ($block['source_deleted_at'] !== null ? (string) $block['source_importance'] : (string) ($block['importance'] ?? 'D')),
                    'goalTitle' => $block['goal_title'] === null ? null : (string) $block['goal_title'],
                    'startIndex' => (int) $block['start_index'],
                    'timeRange' => $this->formatTime((int) $block['start_index']) . '–' . $this->formatTime((int) $block['end_index']),
                    'actualTimeRanges' => $actualRanges,
                    'status' => $source['todoStatus'] ?? ($block['source_deleted_at'] !== null ? (string) $block['source_todo_status'] : (string) ($block['manual_status'] ?? 'pending')),
                    'sourceId' => $source['id'] ?? null,
                    'isLinked' => !empty($block['is_linked']),
                ];
            }
        }
        if (!$this->calendarRepository->supportsDailyPlanBlocks()) {
            foreach ($items as $item) {
                if ($item['startIndex'] === null || $item['endIndex'] === null) continue;
                $planItems[] = [
                    'id' => null,
                    'sourceId' => $item['id'],
                    'title' => $item['title'],
                    'importance' => $item['importance'],
                    'goalTitle' => $item['goalTitle'],
                    'startIndex' => $item['startIndex'],
                    'timeRange' => $this->formatTime($item['startIndex']) . '–' . $this->formatTime($item['endIndex']),
                    'actualTimeRanges' => [],
                    'status' => $item['todoStatus'],
                    'isLinked' => $item['isCompleted'],
                ];
            }
        }
        usort($planItems, static fn(array $left, array $right): int =>
            ($left['startIndex'] <=> $right['startIndex']) ?: ($left['id'] <=> $right['id'])
        );

        $usedRecurringTemplateIds = $day === null ? [] : $this->calendarRepository->getUsedRecurringTemplateIds($userId, (int) $day['id']);
        return [
            'date' => $date,
            'todoStatusSupported' => $this->calendarRepository->supportsTodoStatus(),
            'planRemindersSupported' => $this->calendarRepository->supportsPlanBlockReminders(),
            'carryovers' => $this->calendarRepository->listTodoCarryovers($userId, $date),
            'carryoverSupported' => $this->calendarRepository->supportsTodoCarryover(),
            'planItems' => $planItems,
            'activityItems' => $items,
            'items' => $items,
            'pendingItems' => array_values(array_filter($items, static fn(array $item): bool => !$item['isCompleted'])),
            'completedItems' => array_values(array_filter($items, static fn(array $item): bool => $item['isCompleted'])),
            'blockTemplates' => array_map(static function (array $template) use ($usedRecurringTemplateIds): array {
                $template['isUsedOnDate'] = in_array((int) $template['id'], $usedRecurringTemplateIds, true);
                return $template;
            }, $this->planService->getBlockTemplates($userId)),
            'goalOptions' => $this->goalService->activeGoalOptions($userId),
        ];
    }

    public function updateStatus(int $userId, int $itemId, string $status): bool
    {
        return $this->calendarRepository->updateTodoStatus($userId, $itemId, $status);
    }

    public function getFollowup(int $userId, int $itemId): array
    {
        $item = $this->calendarRepository->getTodoFollowup($userId, $itemId);
        if ($item === null) return ['ok' => false, 'status' => 404, 'message' => '할 일을 찾을 수 없습니다.'];
        return ['ok' => true, 'itemId' => $itemId, 'note' => (string) ($item['followup_note'] ?? ''), 'sourceId' => $item['source_carryover_item_id'],
            'supported' => $this->calendarRepository->supportsTodoFollowup()];
    }

    public function saveFollowup(int $userId, array $input): array
    {
        $id = filter_var($input['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $note = is_string($input['note'] ?? null) ? trim($input['note']) : null;
        $length = $note === null ? false : preg_match_all('/./us', $note);
        if ($id === false || $id <= 0 || $length === false || $length > 500) {
            return ['ok' => false, 'status' => 422, 'message' => '이어가기 메모는 500자 이내로 입력해 주세요.'];
        }
        $current = $this->getFollowup($userId, (int) $id);
        if (!$current['ok']) return $current;
        if (!$current['supported']) return ['ok' => false, 'status' => 503, 'message' => '메모 저장을 위한 데이터베이스 업데이트가 필요합니다.'];
        if (!$this->calendarRepository->updateTodoFollowup($userId, (int) $id, $note)) return ['ok' => false, 'status' => 409, 'message' => '메모를 저장하지 못했습니다.'];
        return ['ok' => true, 'itemId' => (int) $id, 'note' => $note, 'supported' => true];
    }

    public function registerCarryover(int $userId, array $input): array
    {
        $id = filter_var($input['source_item_id'] ?? null, FILTER_VALIDATE_INT);
        $today = date('Y-m-d');
        if ($id === false || $id <= 0) return ['ok' => false, 'status' => 422, 'message' => '어제의 할 일을 확인해 주세요.'];
        $source = $this->calendarRepository->getTodoFollowup($userId, (int) $id);
        if ($source === null) return ['ok' => false, 'status' => 404, 'message' => '할 일을 찾을 수 없습니다.'];
        if ($source['calendar_date'] !== date('Y-m-d', strtotime('-1 day')) || !in_array($source['todo_status'], ['in_progress', 'postponed'], true)) return ['ok' => false, 'status' => 422, 'message' => '어제의 진행 중·연기한 할 일만 등록할 수 있습니다.'];
        if (!$this->calendarRepository->supportsTodoCarryover()) return ['ok' => false, 'status' => 503, 'message' => '이어 등록을 위한 데이터베이스 업데이트가 필요합니다.'];
        $created = $this->calendarRepository->registerTodoCarryover($userId, (int) $id, $today);
        if ($created === null) return ['ok' => false, 'status' => 409, 'message' => '오늘 할 일로 등록하지 못했습니다. 다시 확인해 주세요.'];
        return ['ok' => true, 'itemId' => $created, 'date' => $today, 'message' => '어제 기록을 유지하고 오늘 할 일로 등록했습니다.'];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createRecurring(int $userId, array $input): array
    {
        $input['duration_index'] = 6;
        $validation = $this->planService->validateBlockTemplateInput($userId, $input);
        if (!$validation['ok']) return ['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '반복 할 일을 확인해 주세요.')];
        try {
            $id = $this->planService->createBlockTemplate($userId, $validation['data']);
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => '반복 할 일을 추가하지 못했습니다.'];
        }
        if ($id === null) return ['ok' => false, 'message' => '반복 할 일을 추가하지 못했습니다.'];
        return ['ok' => true, 'message' => '반복 할 일을 추가했습니다.'];
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

    /** @return array<string, mixed> */
    public function create(int $userId, array $input): array
    {
        if (array_key_exists('plan_block_template_id', $input)) {
            $templateId = filter_var($input['plan_block_template_id'], FILTER_VALIDATE_INT);
            if ($templateId === false || $templateId <= 0) {
                return ['ok' => false, 'message' => '사용할 수 있는 계획 블록을 찾을 수 없습니다.'];
            }
            $result = $this->calendarRepository->useRecurringTemplate($userId, date('Y-m-d'), (int) $templateId);
            if ($result === null) return ['ok' => false, 'message' => '사용할 수 있는 계획 블록을 찾을 수 없습니다.'];
            return [
                'ok' => true,
                'message' => '계획을 추가했습니다.',
                'item' => [
                    'id' => $result['itemId'],
                    'title' => (string) $result['item']['title'],
                    'importance' => (string) $result['item']['importance'],
                    'goalId' => $result['item']['goal_id'] === null ? null : (int) $result['item']['goal_id'],
                    'scheduleType' => 'unscheduled',
                ],
            ];
        }
        $validation = $this->calendarService->validateDailyPlanItemInput([
            'date' => $input['date'] ?? date('Y-m-d'),
            'title' => $input['title'] ?? '',
            'importance' => $input['importance'] ?? 'D',
            'goal_id' => $input['goal_id'] ?? '',
            'schedule_type' => 'unscheduled',
        ]);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '할 일 이름을 확인해주세요.')];
        }
        $itemId = $this->calendarService->createDailyPlanItem($userId, $validation['data']);
        if ($itemId === null) {
            return ['ok' => false, 'message' => '할 일을 추가하지 못했습니다.'];
        }

        return [
            'ok' => true,
            'message' => '계획을 추가했습니다.',
            'item' => [
                'id' => $itemId,
                'title' => (string) $validation['data']['title'],
                'importance' => (string) $validation['data']['importance'],
                'goalId' => $validation['data']['goalId'] === null ? null : (int) $validation['data']['goalId'],
                'scheduleType' => 'unscheduled',
            ],
        ];
    }

    /** @return array{ok: bool, message: string} */
    public function createToday(int $userId, mixed $title): array
    {
        return $this->create($userId, [
            'date' => date('Y-m-d'),
            'title' => $title,
            'importance' => 'D',
            'goal_id' => '',
        ]);
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
        // This editor changes source details only; never accept schedule or link changes here.
        $input['schedule_type'] = 'unscheduled';
        unset($input['estimated_duration_minutes']);
        $validation = $this->calendarService->validateDailyPlanItemInput($input, true);
        if (!$validation['ok']) {
            return ['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '할 일 내용을 확인해주세요.')];
        }

        if (!$this->calendarService->updateDailyPlanItemDetails($userId, $validation['data'])) {
            return ['ok' => false, 'message' => '할 일을 수정하지 못했습니다.'];
        }

        return ['ok' => true, 'message' => '할 일을 수정했습니다.'];
    }

    /** @return array<string, mixed> */
    public function deleteToday(int $userId, int $itemId): array
    {
        $item = $itemId > 0 ? $this->calendarRepository->getDailyPlanItemForCompletion($userId, $itemId) : null;
        if ($item === null || (string) $item['calendar_date'] !== date('Y-m-d')) {
            return ['ok' => false, 'message' => '삭제할 오늘의 할 일을 찾을 수 없습니다.'];
        }
        if (!$this->calendarRepository->deleteDailyPlanItem($userId, $itemId)) {
            return ['ok' => false, 'message' => '할 일을 삭제하지 못했습니다.'];
        }
        return ['ok' => true, 'message' => '할 일을 삭제했습니다.'];
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
