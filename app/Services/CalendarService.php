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

    public function __construct()
    {
        $this->calendarRepository = new CalendarRepository();
        $this->routineService = new RoutineService();
        $this->retrospectService = new RetrospectService();
    }

    /** @return array<string, mixed> */
    public function getDayViewData(int $userId, ?string $requestedDate): array
    {
        $date = $this->normalizeDate($requestedDate);
        $day = $this->calendarRepository->getOrCreateDay($userId, $date);
        $selectedPlanGroupId = isset($day['plan_group_id']) ? (int) $day['plan_group_id'] : null;
        $actualEvents = $this->calendarRepository->getActualEvents($userId, (int) $day['id']);
        $planBlocks = $this->calendarRepository->getPlanBlocksForGroup($userId, $selectedPlanGroupId);
        $usedTemplateIds = $this->calendarRepository->getUsedPlanTemplateIds($userId, (int) $day['id']);
        $dateMeta = $this->buildDateMeta($date);

        return [
            'date' => $date,
            'dateTitle' => $this->formatDateTitle($date),
            'dateSubTitle' => $this->formatDateSubTitle($date, $dateMeta),
            'dateClass' => $this->dateClass($date, $dateMeta),
            'prevDate' => $this->shiftDate($date, '-1 day'),
            'nextDate' => $this->shiftDate($date, '+1 day'),
            'isToday' => $date === date('Y-m-d'),
            'currentIndex' => $date === date('Y-m-d') ? $this->currentTimeIndex() : null,
            'dayId' => (int) $day['id'],
            'selectedPlanGroupId' => $selectedPlanGroupId,
            'planGroups' => $this->calendarRepository->listPlanGroups($userId),
            'calendarTags' => $this->calendarRepository->listCalendarTags($userId),
            'planReminderItems' => $this->buildPlanReminderItems($planBlocks, $usedTemplateIds),
            'planOptions' => $this->buildPlanOptions($planBlocks, $usedTemplateIds),
            'planSegments' => $this->buildSegmentsFromBlocks($planBlocks, 'plan'),
            'actualSegments' => $this->buildSegmentsFromEvents($actualEvents),
            'unscheduledEvents' => $this->buildUnscheduledEvents($actualEvents),
            'hasLinkedActualEvents' => $this->hasLinkedActualEvents($actualEvents),
            'routines' => $this->routineService->getCalendarRoutines($userId, $date),
            'retrospectPreview' => $this->retrospectService->getLatestPublishedPreview($userId, $date),
        ];
    }

    /** @param array<string, mixed> $input */
    public function validateEventInput(array $input): array
    {
        $date = $this->normalizeDate((string) ($input['date'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $scheduleType = (string) ($input['schedule_type'] ?? 'timed') === 'unscheduled' ? 'unscheduled' : 'timed';
        $startIndex = filter_var($input['start_index'] ?? null, FILTER_VALIDATE_INT);
        $endIndex = filter_var($input['end_index'] ?? null, FILTER_VALIDATE_INT);
        $planTemplateId = filter_var($input['plan_template_id'] ?? null, FILTER_VALIDATE_INT);
        $calendarTagId = filter_var($input['calendar_tag_id'] ?? null, FILTER_VALIDATE_INT);
        $memo = trim((string) ($input['memo'] ?? ''));
        $routineIds = $this->normalizeRoutineIds($input['routine_ids'] ?? []);
        $sourceEventId = filter_var($input['source_event_id'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if ($title === '') {
            $errors['title'] = '일정명을 입력해주세요.';
        } elseif (mb_strlen($title) > 80) {
            $errors['title'] = '일정명은 80자 이내로 입력해주세요.';
        }

        if ($scheduleType === 'timed' && ($startIndex === false || $endIndex === false)) {
            $errors['time'] = '일정 시간을 다시 선택해주세요.';
        } elseif ($scheduleType === 'timed' && ($startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex)) {
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
                'planTemplateId' => $scheduleType === 'timed' && $planTemplateId !== false && $planTemplateId > 0 ? $planTemplateId : null,
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
        $planTemplateId = filter_var($input['plan_template_id'] ?? null, FILTER_VALIDATE_INT);
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
                'planTemplateId' => $scheduleType === 'timed' && $planTemplateId !== false && $planTemplateId > 0 ? $planTemplateId : null,
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
                $data['planTemplateId'] === null ? null : (int) $data['planTemplateId'],
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
                $data['planTemplateId'] === null ? null : (int) $data['planTemplateId'],
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
            $data['planTemplateId'] === null ? null : (int) $data['planTemplateId'],
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
        $label = (new DateTimeImmutable($date))->format('D');
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

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildPlanOptions(array $blocks, array $usedTemplateIds): array
    {
        return array_map(function (array $block) use ($usedTemplateIds): array {
            $templateId = (int) $block['plan_template_id'];
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));

            return [
                'templateId' => $templateId,
                'title' => (string) $block['title'],
                'importance' => $importance,
                'importanceBadge' => $importance,
                'timeRange' => $this->formatTimeRange((int) $block['start_index'], (int) $block['end_index']),
                'disabled' => in_array($templateId, $usedTemplateIds, true),
            ];
        }, $blocks);
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildPlanReminderItems(array $blocks, array $usedTemplateIds): array
    {
        $items = array_map(function (array $block) use ($usedTemplateIds): array {
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
            $templateId = (int) $block['plan_template_id'];

            return [
                'templateId' => $templateId,
                'title' => (string) $block['title'],
                'importance' => $importance,
                'importanceBadge' => $importance,
                'timeRange' => $this->formatTimeRange((int) $block['start_index'], (int) $block['end_index']),
                'durationMinutes' => ((int) $block['end_index'] - (int) $block['start_index']) * 10,
                'startIndex' => (int) $block['start_index'],
                'isLinked' => in_array($templateId, $usedTemplateIds, true),
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
                ? $left['startIndex'] <=> $right['startIndex']
                : $leftRank <=> $rightRank;
        });

        return $items;
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function buildSegmentsFromBlocks(array $blocks, string $type): array
    {
        $segments = [];

        foreach ($blocks as $block) {
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));

            foreach ($this->splitRange((int) $block['start_index'], (int) $block['end_index']) as $segment) {
                $segments[] = array_merge($segment, [
                    'title' => (string) $block['title'],
                    'type' => $type,
                    'templateId' => (int) $block['plan_template_id'],
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

            foreach ($this->splitRange((int) $event['start_index'], (int) $event['end_index']) as $segment) {
                $segments[] = array_merge($segment, [
                    'id' => (int) $event['id'],
                    'title' => (string) $event['title'],
                    'type' => 'actual',
                    'planTemplateId' => $event['plan_template_id'] === null ? null : (int) $event['plan_template_id'],
                    'planTitle' => $event['plan_title'] ?? null,
                    'tagId' => $event['calendar_tag_id'] === null ? null : (int) $event['calendar_tag_id'],
                    'tagName' => $event['tag_name'] ?? null,
                    'tagColor' => $this->normalizeHexColor((string) ($event['tag_color'] ?? '')),
                    'memo' => (string) ($event['memo'] ?? ''),
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

            $items[] = [
                'id' => (int) $event['id'],
                'title' => (string) $event['title'],
                'tagId' => $event['calendar_tag_id'] === null ? null : (int) $event['calendar_tag_id'],
                'tagName' => $event['tag_name'] ?? null,
                'tagColor' => $this->normalizeHexColor((string) ($event['tag_color'] ?? '')),
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
            if ($event['plan_template_id'] !== null) {
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

}
