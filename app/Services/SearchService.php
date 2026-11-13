<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SearchRepository;
use DateTimeImmutable;

final class SearchService
{
    private const LABELS = [
        'goal' => '목표', 'routine' => '루틴', 'plan' => '계획', 'todo' => '할 일',
        'schedule' => '일정', 'memo' => '메모', 'retrospect' => '회고', 'wellness' => '생활 기록',
    ];

    private SearchRepository $repository;

    public function __construct()
    {
        $this->repository = new SearchRepository();
    }

    /** @return array<string, mixed> */
    public function search(int $userId, mixed $query, mixed $type, mixed $from, mixed $to): array
    {
        $query = trim((string) $query);
        $selectedType = array_key_exists((string) $type, self::LABELS) ? (string) $type : 'all';
        $fromDate = $this->dateOrNull($from);
        $toDate = $this->dateOrNull($to);
        $error = mb_strlen($query) === 1 ? '검색어를 두 글자 이상 입력해주세요.' : '';
        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            $error = '시작일은 종료일보다 늦을 수 없습니다.';
        }
        $rows = mb_strlen($query) >= 2 && $error === ''
            ? $this->repository->search($userId, $query, $fromDate, $toDate)
            : [];

        $items = [];
        foreach ($rows as $row) {
            $rowType = (string) $row['type'];
            if ($selectedType !== 'all' && $selectedType !== $rowType) continue;
            $bodyCandidates = [(string) ($row['body'] ?? ''), (string) ($row['body_alt'] ?? ''), (string) ($row['body_alt2'] ?? '')];
            $body = trim((string) ($bodyCandidates[0] ?? ''));
            foreach ($bodyCandidates as $candidate) {
                if ($candidate !== '' && mb_stripos($candidate, $query) !== false) { $body = trim($candidate); break; }
            }
            $items[] = [
                'type' => $rowType,
                'typeLabel' => self::LABELS[$rowType] ?? $rowType,
                'title' => $this->title($rowType, (string) $row['title']),
                'snippet' => $this->snippet($body, $query),
                'details' => $this->details($rowType, $row),
                'date' => substr((string) ($row['record_date'] ?: $row['occurred_at']), 0, 10),
                'href' => $this->href($rowType, (string) ($row['record_date'] ?? '')),
            ];
        }
        usort($items, static fn(array $a, array $b): int => strcmp($b['date'], $a['date']));

        return ['query' => $query, 'selectedType' => $selectedType, 'types' => self::LABELS,
            'filterSummary' => $this->filterSummary($selectedType, $fromDate, $toDate),
            'from' => $fromDate ?? '', 'to' => $toDate ?? '', 'error' => $error, 'items' => array_slice($items, 0, 60)];
    }

    private function filterSummary(string $type, ?string $from, ?string $to): string
    {
        $period = $from === null && $to === null ? '전체 기간'
            : str_replace('-', '.', $from ?? '시작일 미지정') . ' ~ ' . str_replace('-', '.', $to ?? '종료일 미지정');
        return (self::LABELS[$type] ?? '전체') . ' · ' . $period;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $value ? $value : null;
    }

    /** @return array<int, array{label:string, value:string}> */
    private function details(string $type, array $row): array
    {
        $labels = match ($type) {
            'goal' => ['body' => '어떻게', 'body_alt' => '언제', 'body_alt2' => '어디서', 'period_start_date' => '시작일', 'period_end_date' => '마감일'],
            'retrospect' => ['body' => '오늘의 회고', 'body_alt' => '오늘의 생각', 'body_alt2' => '내일의 계획'],
            'routine' => ['record_date' => '시작일', 'duration_days' => '기간(일)'],
            'schedule' => ['body' => '메모'],
            'wellness' => ['body' => '상세 내용', 'body_alt' => '메모'],
            default => ['body' => '내용', 'importance' => '중요도'],
        };
        $details = [];
        foreach ($labels as $key => $label) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') $details[] = ['label' => $label, 'value' => $value];
        }
        if ($type === 'schedule') {
            $clock = static fn(int $index): string => sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10);
            array_unshift($details, ['label' => '시간', 'value' => isset($row['start_index'], $row['end_index'])
                ? $clock((int) $row['start_index']) . ' – ' . $clock((int) $row['end_index']) : '시간 미설정']);
        }
        return $details;
    }

    private function title(string $type, string $title): string
    {
        if ($type !== 'wellness') return $title;
        return ['sleep' => '수면', 'meal' => '식사', 'snack' => '간식', 'exercise' => '운동', 'rest' => '휴식'][$title] ?? '생활 기록';
    }

    private function snippet(string $body, string $query): string
    {
        if ($body === '') return '일치하는 제목 또는 기록';
        $position = mb_stripos($body, $query);
        $start = $position === false ? 0 : max(0, $position - 24);
        $snippet = mb_substr(preg_replace('/\s+/u', ' ', $body) ?? $body, $start, 90);
        return ($start > 0 ? '…' : '') . $snippet . (mb_strlen($body) > $start + 90 ? '…' : '');
    }

    private function href(string $type, string $date): string
    {
        return match ($type) {
            'goal' => '/goal?mode=all', 'routine' => '/manage/routines', 'plan' => '/plan',
            'memo' => '/memo', 'retrospect' => '/retrospect/report?date=' . rawurlencode($date),
            'todo', 'schedule', 'wellness' => '/calendar?date=' . rawurlencode($date),
            default => '/',
        };
    }
}
