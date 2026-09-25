<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarRepository;
use DateTimeImmutable;

final class CalendarService
{
    private CalendarRepository $calendarRepository;
    private RoutineService $routineService;
    private RetrospectService $retrospectService;
    private GoalService $goalService;
    private CalendarTagService $calendarTagService;
    private PlanService $planService;

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
        $this->routineService = new RoutineService();
        $this->retrospectService = new RetrospectService();
        $this->goalService = new GoalService();
        $this->calendarTagService = new CalendarTagService();
        $this->planService = new PlanService();
    }

    /** @return array<string, mixed> */
    public function getDayViewData(int $userId, ?string $requestedDate, array $notificationSettings = []): array
    {
        $date = $this->normalizeDate($requestedDate);
        $day = $this->calendarRepository->getOrCreateDay($userId, $date);
        $selectedPlanGroupId = isset($day['plan_group_id']) ? (int) $day['plan_group_id'] : null;
        $actualEvents = $this->calendarRepository->getActualEvents($userId, (int) $day['id']);
        $dailyPlan = $this->calendarRepository->getDailyPlanForDay($userId, (int) $day['id']);
        $planBlocks = $this->calendarRepository->getDailyPlanItems($userId, (int) $day['id']);
        $usedPlanItemIds = $this->calendarRepository->getUsedDailyPlanItemIds($userId, (int) $day['id']);
        $dateMeta = $this->buildDateMeta($date);
        $tagData = $this->calendarTagService->getTagPageData($userId);
        $planReminderItems = $this->buildPlanReminderItems($planBlocks, $usedPlanItemIds);
        $planSegments = $this->buildSegmentsFromBlocks($planBlocks, 'plan');
        $actualSegments = $this->buildSegmentsFromEvents($actualEvents);

        return [
            'date' => $date,
            'todayDate' => date('Y-m-d'),
            'canEditRoutines' => $date <= date('Y-m-d'),
            'dateTitle' => $this->formatDateTitle($date),
            'dateSubTitle' => $this->formatDateSubTitle($date, $dateMeta),
            'dateClass' => $this->dateClass($date, $dateMeta),
            'prevDate' => $this->shiftDate($date, '-1 day'),
            'nextDate' => $this->shiftDate($date, '+1 day'),
            'isToday' => $date === date('Y-m-d'),
            'currentIndex' => $date === date('Y-m-d') ? $this->currentTimeIndex() : null,
            'dayId' => (int) $day['id'],
            'selectedPlanGroupId' => $selectedPlanGroupId,
            'dailyPlan' => $dailyPlan,
            'planGroups' => $this->calendarRepository->listPlanGroups($userId),
            'goalOptions' => $this->goalService->activeGoalOptions($userId),
            'calendarTags' => $tagData['tags'],
            'tagPalettes' => $tagData['palettes'],
            'blockTemplates' => $this->planService->getBlockTemplates($userId),
            'planReminderItems' => $planReminderItems,
            'planOptions' => $this->buildPlanOptions($planBlocks, $usedPlanItemIds),
            'planSegments' => $planSegments,
            'actualSegments' => $actualSegments,
            'unscheduledEvents' => $this->buildUnscheduledEvents($actualEvents),
            'hasLinkedActualEvents' => $this->hasLinkedActualEvents($actualEvents),
            'routines' => $this->routineService->getCalendarRoutines($userId, $date),
            'retrospectPreview' => $this->retrospectService->getLatestPublishedPreview($userId),
            'headerGuidance' => $this->buildHeaderGuidance(
                $date,
                $planReminderItems,
                $actualSegments,
                $notificationSettings
            ),
        ];
    }

    /** @param array<string, mixed> $input */
    public function validateEventInput(array $input): array
    {
        $date = $this->normalizeDate((string) ($input['date'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $requestedScheduleType = (string) ($input['schedule_type'] ?? 'timed');
        $scheduleType = 'timed';
        $startIndex = filter_var($input['start_index'] ?? null, FILTER_VALIDATE_INT);
        $endIndex = filter_var($input['end_index'] ?? null, FILTER_VALIDATE_INT);
        $dailyPlanItemId = filter_var($input['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $calendarTagId = filter_var($input['calendar_tag_id'] ?? null, FILTER_VALIDATE_INT);
        $memo = trim((string) ($input['memo'] ?? ''));
        $routineIds = $this->normalizeRoutineIds($input['routine_ids'] ?? []);
        $sourceEventId = filter_var($input['source_event_id'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if ($requestedScheduleType !== 'timed') {
            $errors['time'] = '실제 일정은 시작시간과 종료시간을 선택해야 합니다.';
        }

        if ($title === '') {
            $errors['title'] = '일정명을 입력해주세요.';
        } elseif (mb_strlen($title) > 80) {
            $errors['title'] = '일정명은 80자 이내로 입력해주세요.';
        }

        if (!isset($errors['time']) && ($startIndex === false || $endIndex === false)) {
            $errors['time'] = '일정 시간을 다시 선택해주세요.';
        } elseif (!isset($errors['time']) && ($startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex)) {
            $errors['time'] = '일정 시간 범위가 올바르지 않습니다.';
        }

        if (mb_strlen($memo) > 500) {
            $errors['memo'] = '메모는 500자 이내로 입력해주세요.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'date' => $date,
                'title' => $title,
                'scheduleType' => $scheduleType,
                'startIndex' => $scheduleType === 'timed' && $startIndex !== false ? $startIndex : null,
                'endIndex' => $scheduleType === 'timed' && $endIndex !== false ? $endIndex : null,
                'dailyPlanItemId' => $scheduleType === 'timed' && $dailyPlanItemId !== false && $dailyPlanItemId > 0 ? $dailyPlanItemId : null,
                'calendarTagId' => $calendarTagId === false || $calendarTagId <= 0 ? null : $calendarTagId,
                'memo' => $memo,
                'routineIds' => $scheduleType === 'timed' ? $routineIds : [],
                'sourceEventId' => $scheduleType === 'timed' && $sourceEventId !== false && $sourceEventId > 0
                    ? (int) $sourceEventId
                    : null,
            ],
        ];
    }

    /** @param array<string, mixed> $input */
    public function validateEventUpdateInput(array $input): array
    {
        $date = $this->normalizeDate((string) ($input['date'] ?? ''));
        $eventId = filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT);
        $scheduleType = (string) ($input['schedule_type'] ?? 'timed') === 'unscheduled' ? 'unscheduled' : 'timed';
        $title = trim((string) ($input['title'] ?? ''));
        $dailyPlanItemId = filter_var($input['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $calendarTagId = filter_var($input['calendar_tag_id'] ?? null, FILTER_VALIDATE_INT);
        $memo = trim((string) ($input['memo'] ?? ''));
        $errors = [];

        if ($eventId === false || $eventId <= 0) {
            $errors['general'] = '수정할 일정을 찾을 수 없습니다.';
        }

        if ($title === '') {
            $errors['title'] = '일정명을 입력해주세요.';
        } elseif (mb_strlen($title) > 80) {
            $errors['title'] = '일정명은 80자 이내로 입력해주세요.';
        }

        if (mb_strlen($memo) > 500) {
            $errors['memo'] = '메모는 500자 이내로 입력해주세요.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'date' => $date,
                'eventId' => $eventId === false ? 0 : $eventId,
                'title' => $title,
                'scheduleType' => $scheduleType,
                'dailyPlanItemId' => $scheduleType === 'timed' && $dailyPlanItemId !== false && $dailyPlanItemId > 0 ? $dailyPlanItemId : null,
                'calendarTagId' => $calendarTagId === false || $calendarTagId <= 0 ? null : $calendarTagId,
                'memo' => $memo,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createActualEvent(int $userId, array $data): ?int
    {
        $eventId = ($data['sourceEventId'] ?? null) !== null
            ? $this->calendarRepository->scheduleUnscheduledEvent(
                $userId,
                (int) $data['sourceEventId'],
                (string) $data['date'],
                (string) $data['title'],
                (int) $data['startIndex'],
                (int) $data['endIndex'],
                $data['dailyPlanItemId'] === null ? null : (int) $data['dailyPlanItemId'],
                $data['calendarTagId'] === null ? null : (int) $data['calendarTagId'],
                (string) $data['memo']
            )
            : $this->calendarRepository->createActualEvent(
                $userId,
                (string) $data['date'],
                (string) $data['title'],
                (string) $data['scheduleType'],
                $data['startIndex'] === null ? null : (int) $data['startIndex'],
                $data['endIndex'] === null ? null : (int) $data['endIndex'],
                $data['dailyPlanItemId'] === null ? null : (int) $data['dailyPlanItemId'],
                $data['calendarTagId'] === null ? null : (int) $data['calendarTagId'],
                (string) $data['memo']
            );

        if ($eventId !== null) {
            foreach (($data['routineIds'] ?? []) as $routineId) {
                $this->routineService->markDoneForDate($userId, (int) $routineId, (string) $data['date']);
            }
        }

        return $eventId;
    }

    /** @param array<string, mixed> $data */
    public function updateActualEvent(int $userId, array $data): bool
    {
        return $this->calendarRepository->updateActualEvent(
            $userId,
            (int) $data['eventId'],
            (string) $data['date'],
            (string) $data['title'],
            $data['dailyPlanItemId'] === null ? null : (int) $data['dailyPlanItemId'],
            $data['calendarTagId'] === null ? null : (int) $data['calendarTagId'],
            (string) $data['memo']
        );
    }

    public function deleteActualEvent(int $userId, int $eventId): bool
    {
        return $this->calendarRepository->softDeleteEvent($userId, $eventId);
    }

    public function setDayPlanGroup(int $userId, string $date, ?int $planGroupId): bool
    {
        return $this->calendarRepository->setDayPlanGroup($userId, $this->normalizeDate($date), $planGroupId);
    }

    /** @param array<string, mixed> $input */
    public function validateDailyPlanItemInput(array $input, bool $requireId = false): array
    {
        $date = $this->normalizeDate((string) ($input['date'] ?? ''));
        $itemId = filter_var($input['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string) ($input['title'] ?? ''));
        $importance = $this->normalizeImportance((string) ($input['importance'] ?? 'D'));
        $goalId = filter_var($input['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $scheduleType = (string) ($input['schedule_type'] ?? 'timed') === 'unscheduled' ? 'unscheduled' : 'timed';
        $startIndex = filter_var($input['start_index'] ?? null, FILTER_VALIDATE_INT);
        $endIndex = filter_var($input['end_index'] ?? null, FILTER_VALIDATE_INT);
        $linkActionInput = (string) ($input['link_action'] ?? 'keep');
        $linkAction = in_array($linkActionInput, ['keep', 'sync', 'detach'], true)
            ? $linkActionInput
            : 'keep';
        $errors = [];

        if ($requireId && ($itemId === false || $itemId <= 0)) {
            $errors['general'] = '수정할 계획 일정을 찾을 수 없습니다.';
        }
        if ($title === '' || mb_strlen($title) > 80) {
            $errors['title'] = '계획 일정명은 1자 이상 80자 이내로 입력해주세요.';
        }
        if ($scheduleType === 'timed' && ($startIndex === false || $endIndex === false
            || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex)) {
            $errors['time'] = '계획 일정 시간 범위가 올바르지 않습니다.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'date' => $date,
                'itemId' => $itemId === false ? 0 : (int) $itemId,
                'title' => $title,
                'importance' => $importance,
                'goalId' => $goalId === false || $goalId <= 0 ? null : (int) $goalId,
                'scheduleType' => $scheduleType,
                'startIndex' => $scheduleType === 'timed' && $startIndex !== false ? (int) $startIndex : null,
                'endIndex' => $scheduleType === 'timed' && $endIndex !== false ? (int) $endIndex : null,
                'linkAction' => $linkAction,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createDailyPlanItem(int $userId, array $data): ?int
    {
        return $this->calendarRepository->createDailyPlanItem(
            $userId,
            (string) $data['date'],
            (string) $data['title'],
            (string) $data['importance'],
            $data['goalId'] === null ? null : (int) $data['goalId'],
            $data['startIndex'] === null ? null : (int) $data['startIndex'],
            $data['endIndex'] === null ? null : (int) $data['endIndex']
        );
    }

    /** @param array<string, mixed> $data */
    public function updateDailyPlanItem(int $userId, array $data): bool
    {
        return $this->calendarRepository->updateDailyPlanItem(
            $userId,
            (int) $data['itemId'],
            (string) $data['title'],
            (string) $data['importance'],
            $data['goalId'] === null ? null : (int) $data['goalId'],
            $data['startIndex'] === null ? null : (int) $data['startIndex'],
            $data['endIndex'] === null ? null : (int) $data['endIndex'],
            (string) $data['linkAction']
        );
    }

    public function deleteDailyPlanItem(int $userId, int $itemId): bool
    {
        return $this->calendarRepository->deleteDailyPlanItem($userId, $itemId);
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

    /** @return array<string, mixed> */
    private function buildDateMeta(string $date): array
    {
        $dbMeta = $this->calendarRepository->getDateMeta($date);
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $isWeekend = $weekday >= 6;

        return [
            'isWeekend' => $isWeekend,
            'isHoliday' => $dbMeta !== null ? (bool) $dbMeta['is_holiday'] : false,
            'isSubstituteHoliday' => $dbMeta !== null ? (bool) $dbMeta['is_substitute_holiday'] : false,
            'holidayName' => $dbMeta['holiday_name'] ?? null,
            'dateType' => $dbMeta['date_type'] ?? ($isWeekend ? 'weekend' : 'weekday'),
        ];
    }

    /** @param array<string, mixed> $dateMeta */
    private function formatDateSubTitle(string $date, array $dateMeta): string
    {
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        $label = ['월', '화', '수', '목', '금', '토', '일'][$weekday - 1];
        if (!empty($dateMeta['holidayName'])) {
            $label .= ' · ' . (string) $dateMeta['holidayName'];
        } elseif (!empty($dateMeta['isWeekend'])) {
            $label .= ' · Weekend';
        }

        return $label;
    }

    private function formatDateTitle(string $date): string
    {
        return (new DateTimeImmutable($date))->format('m.d');
    }

    /** @param array<string, mixed> $dateMeta */
    private function dateClass(string $date, array $dateMeta): string
    {
        $weekday = (int) (new DateTimeImmutable($date))->format('N');
        if (!empty($dateMeta['isHoliday']) || !empty($dateMeta['isSubstituteHoliday']) || $weekday === 7) {
            return 'is-rest-day';
        }

        if ($weekday === 6) {
            return 'is-saturday';
        }

        return '';
    }

    private function shiftDate(string $date, string $modifier): string
    {
        return (new DateTimeImmutable($date))->modify($modifier)->format('Y-m-d');
    }

    private function currentTimeIndex(): int
    {
        return ((int) date('G') * 6) + (int) floor(((int) date('i')) / 10);
    }

    /**
     * @param array<int, array<string, mixed>> $planItems
     * @param array<int, array<string, mixed>> $actualSegments
     * @param array<string, mixed> $notificationSettings
     * @return array<string, mixed>|null
     */
    private function buildHeaderGuidance(
        string $date,
        array $planItems,
        array $actualSegments,
        array $notificationSettings
    ): ?array {
        if ($date !== date('Y-m-d')) {
            return null;
        }

        $currentMinutes = ((int) date('G') * 60) + (int) date('i');
        $notificationsEnabled = (int) ($notificationSettings['notification_enabled'] ?? 1) === 1;

        if (
            $notificationsEnabled
            && (int) ($notificationSettings['retrospect_morning_enabled'] ?? 1) === 1
            && $this->isWithinGuidanceWindow(
                $currentMinutes,
                (string) ($notificationSettings['retrospect_morning_time'] ?? '07:00')
            )
        ) {
            return [
                'kind' => 'retrospect',
                'message' => '어제의 흐름을 돌아볼 시간이에요',
                'href' => '/retrospect?date=' . rawurlencode($this->shiftDate($date, '-1 day')),
            ];
        }

        if (
            $notificationsEnabled
            && (int) ($notificationSettings['retrospect_evening_enabled'] ?? 1) === 1
            && $this->isWithinGuidanceWindow(
                $currentMinutes,
                (string) ($notificationSettings['retrospect_evening_time'] ?? '20:00')
            )
        ) {
            return [
                'kind' => 'retrospect',
                'message' => '오늘의 흐름을 회고할 시간이에요',
                'href' => '/retrospect?date=' . rawurlencode($date),
            ];
        }

        $currentIndex = $this->currentTimeIndex();
        $timedPlans = array_values(array_filter(
            $planItems,
            static fn(array $item): bool => $item['scheduleType'] === 'timed' && empty($item['isLinked'])
        ));
        $currentPlan = null;
        $nextPlan = null;

        foreach ($timedPlans as $item) {
            $startIndex = (int) $item['startIndex'];
            $endIndex = (int) $item['endIndex'];
            if ($startIndex <= $currentIndex && $currentIndex < $endIndex) {
                $currentPlan = $item;
                break;
            }
            if ($startIndex > $currentIndex && ($nextPlan === null || $startIndex < (int) $nextPlan['startIndex'])) {
                $nextPlan = $item;
            }
        }

        if ($currentPlan !== null) {
            return $this->planGuidance(
                $currentPlan,
                '지금은 ‘' . (string) $currentPlan['title'] . '’ 계획 시간이에요'
            );
        }

        if ($nextPlan !== null) {
            return $this->planGuidance(
                $nextPlan,
                '다음 계획 · ' . (string) $nextPlan['timeRange'] . ' ' . (string) $nextPlan['title']
            );
        }

        if ($planItems === []) {
            return [
                'kind' => 'plan',
                'message' => '오늘 계획이 비어 있어요 · 계획 추가',
            ];
        }

        if ($actualSegments === []) {
            return [
                'kind' => 'actual',
                'message' => '실행한 일정이 아직 없어요 · 기록하기',
            ];
        }

        return null;
    }

    /** @param array<string, mixed> $item */
    private function planGuidance(array $item, string $message): array
    {
        return [
            'kind' => 'actual',
            'message' => $message,
            'planItemId' => (int) $item['itemId'],
            'planTitle' => (string) $item['title'],
            'startIndex' => (int) $item['startIndex'],
            'endIndex' => (int) $item['endIndex'],
        ];
    }

    private function isWithinGuidanceWindow(int $currentMinutes, string $configuredTime): bool
    {
        if (preg_match('/^(\d{2}):(\d{2})/', $configuredTime, $matches) !== 1) {
            return false;
        }

        $startMinutes = ((int) $matches[1] * 60) + (int) $matches[2];

        return $currentMinutes >= $startMinutes && $currentMinutes < min($startMinutes + 120, 1440);
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildPlanOptions(array $blocks, array $usedPlanItemIds): array
    {
        return array_map(function (array $block) use ($usedPlanItemIds): array {
            $itemId = (int) $block['daily_plan_item_id'];
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
            $isTimed = $block['start_index'] !== null && $block['end_index'] !== null;

            return [
                'itemId' => $itemId,
                'templateId' => $itemId,
                'title' => (string) $block['title'],
                'importance' => $importance,
                'importanceBadge' => $importance,
                'scheduleType' => $isTimed ? 'timed' : 'unscheduled',
                'timeRange' => $isTimed
                    ? $this->formatTimeRange((int) $block['start_index'], (int) $block['end_index'])
                    : '시간 미정',
                'disabled' => in_array($itemId, $usedPlanItemIds, true),
            ];
        }, $blocks);
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildPlanReminderItems(array $blocks, array $usedPlanItemIds): array
    {
        $items = array_map(function (array $block) use ($usedPlanItemIds): array {
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
            $itemId = (int) $block['daily_plan_item_id'];
            $isTimed = $block['start_index'] !== null && $block['end_index'] !== null;

            return [
                'itemId' => $itemId,
                'templateId' => $itemId,
                'title' => (string) $block['title'],
                'importance' => $importance,
                'importanceBadge' => $importance,
                'goalId' => $block['goal_id'] === null ? null : (int) $block['goal_id'],
                'scheduleType' => $isTimed ? 'timed' : 'unscheduled',
                'timeRange' => $isTimed
                    ? $this->formatTimeRange((int) $block['start_index'], (int) $block['end_index'])
                    : '시간 미정',
                'durationMinutes' => $isTimed ? ((int) $block['end_index'] - (int) $block['start_index']) * 10 : null,
                'startIndex' => $isTimed ? (int) $block['start_index'] : null,
                'endIndex' => $isTimed ? (int) $block['end_index'] : null,
                'isLinked' => in_array($itemId, $usedPlanItemIds, true),
            ];
        }, $blocks);

        $importanceRank = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3];
        usort($items, static function (array $left, array $right) use ($importanceRank): int {
            if ($left['isLinked'] !== $right['isLinked']) {
                return $left['isLinked'] ? 1 : -1;
            }

            $leftRank = $importanceRank[$left['importance']] ?? 4;
            $rightRank = $importanceRank[$right['importance']] ?? 4;

            return $leftRank === $rightRank
                ? ($left['startIndex'] ?? 145) <=> ($right['startIndex'] ?? 145)
                : $leftRank <=> $rightRank;
        });

        return $items;
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildSegmentsFromBlocks(array $blocks, string $type): array
    {
        $segments = [];

        foreach ($blocks as $block) {
            if ($block['start_index'] === null || $block['end_index'] === null) {
                continue;
            }
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));

            foreach ($this->splitRange((int) $block['start_index'], (int) $block['end_index']) as $segment) {
                $segments[] = array_merge($segment, [
                    'title' => (string) $block['title'],
                    'type' => $type,
                    'itemId' => (int) $block['daily_plan_item_id'],
                    'goalId' => $block['goal_id'] === null ? null : (int) $block['goal_id'],
                    'goalTitle' => $block['goal_title'] ?? null,
                    'startIndex' => (int) $block['start_index'],
                    'endIndex' => (int) $block['end_index'],
                    'isLinked' => $block['linked_event_id'] !== null,
                    'importance' => $importance,
                    'importanceBadge' => $importance,
                ]);
            }
        }

        return $segments;
    }

    /** @param array<int, array<string, mixed>> $events */
    private function buildSegmentsFromEvents(array $events): array
    {
        $segments = [];

        foreach ($events as $event) {
            if ((string) ($event['schedule_type'] ?? 'timed') !== 'timed') {
                continue;
            }

            $tagColor = $this->normalizeHexColor((string) ($event['tag_color'] ?? ''));
            foreach ($this->splitRange((int) $event['start_index'], (int) $event['end_index']) as $segment) {
                $segments[] = array_merge($segment, [
                    'id' => (int) $event['id'],
                    'title' => (string) $event['title'],
                    'type' => 'actual',
                    'dailyPlanItemId' => $event['daily_plan_item_id'] === null ? null : (int) $event['daily_plan_item_id'],
                    'planTitle' => $event['plan_title'] ?? null,
                    'tagId' => $event['calendar_tag_id'] === null ? null : (int) $event['calendar_tag_id'],
                    'tagName' => $event['tag_name'] ?? null,
                    'tagColor' => $tagColor,
                    'tagTextColor' => $this->contrastTextColor($tagColor),
                    'memo' => (string) ($event['memo'] ?? ''),
                    'startIndex' => (int) $event['start_index'],
                    'endIndex' => (int) $event['end_index'],
                    'scheduleType' => 'timed',
                ]);
            }
        }

        return $segments;
    }

    /** @param array<int, array<string, mixed>> $events */
    private function buildUnscheduledEvents(array $events): array
    {
        $items = [];

        foreach ($events as $event) {
            if ((string) ($event['schedule_type'] ?? 'timed') !== 'unscheduled') {
                continue;
            }

            $tagColor = $this->normalizeHexColor((string) ($event['tag_color'] ?? ''));
            $items[] = [
                'id' => (int) $event['id'],
                'title' => (string) $event['title'],
                'tagId' => $event['calendar_tag_id'] === null ? null : (int) $event['calendar_tag_id'],
                'tagName' => $event['tag_name'] ?? null,
                'tagColor' => $tagColor,
                'tagTextColor' => $this->contrastTextColor($tagColor),
                'memo' => (string) ($event['memo'] ?? ''),
            ];
        }

        return $items;
    }

    /** @return array<int, int> */
    private function normalizeRoutineIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $candidate) {
            $id = filter_var($candidate, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }

    /** @return array<int, array{row: int, col: int, span: int}> */
    private function splitRange(int $startIndex, int $endIndex): array
    {
        $segments = [];

        for ($i = $startIndex; $i < $endIndex;) {
            $row = (int) floor($i / 6);
            $col = $i % 6;
            $span = min(6 - $col, $endIndex - $i);

            $segments[] = ['row' => $row, 'col' => $col, 'span' => $span];
            $i += $span;
        }

        return $segments;
    }

    /** @param array<int, array<string, mixed>> $actualEvents */
    private function hasLinkedActualEvents(array $actualEvents): bool
    {
        foreach ($actualEvents as $event) {
            if ($event['daily_plan_item_id'] !== null) {
                return true;
            }
        }

        return false;
    }

    private function formatTimeRange(int $startIndex, int $endIndex): string
    {
        return $this->indexToTime($startIndex) . ' ~ ' . $this->indexToTime($endIndex);
    }

    private function indexToTime(int $index): string
    {
        $minutes = $index * 10;
        $hour = (int) floor($minutes / 60);
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
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#FF5E5B';
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

}
