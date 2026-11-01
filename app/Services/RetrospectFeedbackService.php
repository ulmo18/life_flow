<?php

declare(strict_types=1);

namespace App\Services;

final class RetrospectFeedbackService
{
    /** Generate at most one fact-based candidate from a published snapshot. */
    public function generate(array $plans, array $actuals, array $routines = [], ?string $reportDate = null): ?array
    {
        foreach ($routines as $routine) {
            $dates = $routine['completedDates'] ?? null;
            if (($routine['state'] ?? '') !== 'O' || (int) ($routine['id'] ?? 0) <= 0 || !is_array($dates) || count($dates) !== 60 || $reportDate === null) continue;
            $previous = '';
            foreach ($dates as $date) {
                if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue 2;
                $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if (!$parsed || $parsed->format('Y-m-d') !== $date || $date <= $previous || $date > $reportDate) continue 2;
                $previous = $date;
            }
            if ($previous !== $reportDate) continue;
            $name = (string) ($routine['name'] ?? '');
            return [
                'rule' => 'routine_60_days',
                'message' => '‘' . $name . '’ 루틴을 누적 60일 완료했어요.',
                'evidenceLabel' => '이 회고를 발행할 때 저장된 루틴 완료 날짜 기준이에요.',
                'evidence' => array_map(static fn(string $date): array => ['timeRange' => $date, 'title' => '완료'], array_slice($dates, -5)),
                'remainingCount' => 55,
                'remainingUnit' => '일',
            ];
        }
        $periodFeedback = $this->routinePeriodFeedback($routines, $reportDate);
        $events = [];
        foreach ($actuals as $actual) {
            $id = (int) ($actual['sourceEventId'] ?? 0);
            $start = $actual['start_index'] ?? null;
            $end = $actual['end_index'] ?? null;
            if ($id <= 0 || !is_int($start) || !is_int($end) || $start < 0 || $end > 144 || $start >= $end || isset($events[$id])) {
                return null;
            }
            $events[$id] = $actual;
        }
        if ($events === []) return $periodFeedback;

        $blocks = [];
        $duplicateBlocks = [];
        foreach ($plans as $plan) {
            $id = (int) ($plan['plan_block_id'] ?? 0);
            if ($id <= 0) continue;
            if (isset($blocks[$id])) $duplicateBlocks[$id] = true;
            $blocks[$id] = $plan;
        }

        $importantEvents = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        $importantBlocks = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        foreach ($events as $event) {
            $blockId = (int) ($event['dailyPlanBlockId'] ?? 0);
            $itemId = (int) ($event['plan_template_id'] ?? 0);
            if ($blockId <= 0 || $itemId <= 0 || isset($duplicateBlocks[$blockId]) || !isset($blocks[$blockId])
                || (int) ($blocks[$blockId]['plan_template_id'] ?? 0) !== $itemId) continue;
            $importance = $blocks[$blockId]['importance'] ?? '';
            if (!isset($importantEvents[$importance])) continue;
            $importantEvents[$importance][] = $event;
            $importantBlocks[$importance][$blockId] = true;
        }
        $priority = null;
        foreach (['A', 'B', 'C', 'D'] as $grade) {
            if ($importantEvents[$grade] !== []) { $priority = $grade; break; }
        }
        $comparison = null;
        $comparisonPlan = null;
        if ($priority !== null) {
            $selected = $importantEvents[$priority];
            $ordered = $selected;
            usort($ordered, static fn(array $a, array $b): int => $a['start_index'] <=> $b['start_index']);
            $lastEnd = -1;
            $minutes = 0;
            $overlap = false;
            foreach ($ordered as $event) {
                if ($event['start_index'] < $lastEnd) $overlap = true;
                $lastEnd = max($lastEnd, $event['end_index']);
                $minutes += ($event['end_index'] - $event['start_index']) * 10;
            }
            $labels = ['A' => '중요·긴급(A)', 'B' => '중요·비긴급(B)', 'C' => '비중요·긴급(C)', 'D' => '비중요·비긴급(D)'];
            $label = $labels[$priority];
            $message = $overlap
                ? $label . ' 계획 ' . count($importantBlocks[$priority]) . '개에 실제 일정 ' . count($selected) . '건이 연결되어 있어요.'
                : $label . ' 계획 ' . count($importantBlocks[$priority]) . '개에 연결된 실제 일정 ' . count($selected) . '건의 기록 합은 ' . $minutes . '분이에요.';
            $rule = 'linked_' . strtolower($priority) . '_records';
            if (!$overlap && count($importantBlocks[$priority]) === 1) {
                $blockId = array_key_first($importantBlocks[$priority]);
                $block = $blocks[$blockId];
                $start = $block['start_index'] ?? null;
                $end = $block['end_index'] ?? null;
                if (is_int($start) && is_int($end) && $start >= 0 && $end <= 144 && $start < $end) {
                    $comparison = '계획 ' . (($end - $start) * 10) . '분 · 연결된 실제 ' . $minutes . '분';
                    $comparisonPlan = ['title' => (string) ($block['title'] ?? ''), 'timeRange' => (string) ($block['timeRange'] ?? '')];
                }
            }
        } else {
            foreach ($blocks as $blockId => $block) {
                $start = $block['start_index'] ?? null;
                $end = $block['end_index'] ?? null;
                $itemId = (int) ($block['plan_template_id'] ?? 0);
                if (isset($duplicateBlocks[$blockId]) || $itemId <= 0 || !is_int($start) || !is_int($end)
                    || $start < 0 || $end > 144 || $start >= $end) continue;
                $linked = array_values(array_filter($events, static fn(array $event): bool =>
                    (int) ($event['dailyPlanBlockId'] ?? 0) === $blockId
                    && (int) ($event['plan_template_id'] ?? 0) === $itemId
                ));
                if ($linked === []) continue;
                usort($linked, static fn(array $a, array $b): int => $a['start_index'] <=> $b['start_index']);
                $lastEnd = -1;
                $actualMinutes = 0;
                foreach ($linked as $event) {
                    if ($event['start_index'] < $lastEnd) continue 2;
                    $actualMinutes += ($event['end_index'] - $event['start_index']) * 10;
                    $lastEnd = $event['end_index'];
                }
                $evidence = [['title' => '계획: ' . (string) ($block['title'] ?? ''), 'timeRange' => (string) ($block['timeRange'] ?? '')]];
                foreach (array_slice($linked, 0, 4) as $event) {
                    $evidence[] = ['title' => '실제: ' . (string) ($event['title'] ?? ''), 'timeRange' => (string) ($event['timeRange'] ?? '')];
                }
                return [
                    'rule' => 'linked_plan_duration',
                    'message' => '계획 ' . (($end - $start) * 10) . '분에 연결된 실제 일정은 총 ' . $actualMinutes . '분이에요.',
                    'evidenceLabel' => '발행 당시 저장된 계획 블록과 직접 연결된 실제 일정의 시간 합이에요.',
                    'evidence' => $evidence,
                    'remainingCount' => max(0, count($linked) - 4),
                ];
            }
            if ($periodFeedback !== null) return $periodFeedback;
            $selected = array_values($events);
            $ordered = $selected;
            usort($ordered, static fn(array $a, array $b): int => $a['start_index'] <=> $b['start_index']);
            $lastEnd = -1;
            $minutes = 0;
            $overlap = false;
            foreach ($ordered as $event) {
                if ($event['start_index'] < $lastEnd) $overlap = true;
                $lastEnd = max($lastEnd, $event['end_index']);
                $minutes += ($event['end_index'] - $event['start_index']) * 10;
            }
            $message = $overlap
                ? '이날 실제 일정 ' . count($selected) . '건을 기록했어요.'
                : '이날 실제 일정 ' . count($selected) . '건의 기록 합은 ' . $minutes . '분이에요.';
            $rule = 'actual_records';
        }

        return [
            'rule' => $rule,
            'message' => $message,
            'comparison' => $comparison,
            'comparisonPlan' => $comparisonPlan,
            'evidence' => array_map(static fn(array $event): array => [
                'title' => (string) ($event['title'] ?? ''),
                'timeRange' => (string) ($event['timeRange'] ?? ''),
            ], array_slice($selected, 0, 5)),
            'remainingCount' => max(0, count($selected) - 5),
        ];
    }

    private function routinePeriodFeedback(array $routines, ?string $reportDate): ?array
    {
        if ($reportDate === null) return null;
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $reportDate);
        if (!$end || $end->format('Y-m-d') !== $reportDate) return null;
        $recentStart = $end->modify('-6 days')->format('Y-m-d');
        $previousEnd = $end->modify('-7 days')->format('Y-m-d');
        $previousStart = $end->modify('-13 days')->format('Y-m-d');
        foreach ($routines as $routine) {
            $start = $routine['completedDatesStart'] ?? null;
            $dates = $routine['completedDates'] ?? null;
            if ((int) ($routine['id'] ?? 0) <= 0 || !is_string($start) || !is_array($dates) || $start > $previousStart) continue;
            $parsedStart = \DateTimeImmutable::createFromFormat('!Y-m-d', $start);
            if (!$parsedStart || $parsedStart->format('Y-m-d') !== $start) continue;
            $last = '';
            $previous = [];
            $recent = [];
            foreach ($dates as $date) {
                if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue 2;
                $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if (!$parsed || $parsed->format('Y-m-d') !== $date || $date <= $last || $date < $start || $date > $reportDate) continue 2;
                $last = $date;
                if ($date >= $previousStart && $date <= $previousEnd) $previous[] = $date;
                if ($date >= $recentStart) $recent[] = $date;
            }
            if (count($previous) < 3 || count($recent) < 3 || count($previous) === count($recent)) continue;
            $name = (string) ($routine['name'] ?? '');
            return [
                'rule' => 'routine_period_comparison',
                'message' => '‘' . $name . '’ 루틴의 완료 기록이 ' . count($previous) . '일에서 ' . count($recent) . '일로 바뀌었어요.',
                'evidenceLabel' => '회고일을 포함한 최근 7일과 그 전 7일의 완료 기록을 발행 당시 기준으로 비교했어요.',
                'evidence' => array_merge([
                    ['timeRange' => $previousStart . ' ~ ' . $previousEnd, 'title' => count($previous) . '일 완료'],
                    ['timeRange' => $recentStart . ' ~ ' . $reportDate, 'title' => count($recent) . '일 완료'],
                ], array_map(static fn(string $date): array => ['timeRange' => $date, 'title' => '완료'], array_merge($previous, $recent))),
                'remainingCount' => 0,
            ];
        }
        return null;
    }
}
