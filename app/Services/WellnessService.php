<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WellnessRepository;

final class WellnessService
{
    private const TYPES = [
        'sleep' => ['label' => '수면', 'icon' => '☾', 'mode' => 'range'],
        'meal' => ['label' => '식사', 'icon' => '🍽', 'mode' => 'point'],
        'snack' => ['label' => '간식', 'icon' => '🍎', 'mode' => 'point'],
        'exercise' => ['label' => '운동', 'icon' => '🏃', 'mode' => 'range'],
        'rest' => ['label' => '휴식', 'icon' => '☕', 'mode' => 'range'],
    ];

    private WellnessRepository $repository;

    public function __construct()
    {
        $this->repository = new WellnessRepository();
    }

    /** @return array{types: array<string, array<string, string>>, entries: array<int, array<string, mixed>>, counts: array<string, int>} */
    public function getDayData(int $userId, string $date): array
    {
        $entries = array_map(function (array $row): array {
            $type = (string) $row['entry_type'];
            return [
                'id' => (int) $row['id'],
                'type' => $type,
                'label' => self::TYPES[$type]['label'] ?? $type,
                'icon' => self::TYPES[$type]['icon'] ?? '•',
                'timeLabel' => $this->timeLabel($row),
                'detail' => (string) ($row['detail'] ?? ''),
                'note' => (string) ($row['note'] ?? ''),
                'rating' => $row['rating'] === null ? null : (int) $row['rating'],
            ];
        }, $this->repository->listForDate($userId, $date));

        $counts = array_fill_keys(array_keys(self::TYPES), 0);
        foreach ($entries as $entry) {
            $counts[$entry['type']]++;
        }

        return ['types' => self::TYPES, 'entries' => $entries, 'counts' => $counts];
    }

    /** @return array<string, array<string, string>> */
    public function types(): array
    {
        return self::TYPES;
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>|null} */
    public function validateEventRecord(array $input): array
    {
        $type = trim((string) ($input['wellness_type'] ?? ''));
        $detail = trim((string) ($input['wellness_detail'] ?? ''));
        $note = trim((string) ($input['wellness_note'] ?? ''));
        $ratingInput = $input['wellness_rating'] ?? null;
        $rating = $ratingInput === '' || $ratingInput === null
            ? null
            : filter_var($ratingInput, FILTER_VALIDATE_INT);
        $errors = [];

        if ($type === '') {
            return ['ok' => true, 'errors' => [], 'data' => null];
        }
        if (!isset(self::TYPES[$type])) {
            $errors['wellness_type'] = '생활 기록 종류를 다시 선택해주세요.';
        }
        if (mb_strlen($detail) > 160) {
            $errors['wellness_detail'] = '생활 기록 내용은 160자 이내로 입력해주세요.';
        }
        if (mb_strlen($note) > 1000) {
            $errors['wellness_note'] = '생활 기록 메모는 1000자 이내로 입력해주세요.';
        }
        if ($rating !== null && ($rating === false || $rating < 1 || $rating > 5)) {
            $errors['wellness_rating'] = '체감 상태를 다시 선택해주세요.';
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'type' => $type,
            'detail' => $detail,
            'note' => $note,
            'rating' => $rating === false ? null : $rating,
        ]];
    }

    /** @param array<string, mixed>|null $data */
    public function syncForEvent(int $userId, int $eventId, string $date, ?array $data): bool
    {
        return $this->repository->syncForEvent($userId, $eventId, $date, $data);
    }

    public function deleteForEvent(int $userId, int $eventId): bool
    {
        return $this->repository->deleteForEvent($userId, $eventId);
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validate(array $input): array
    {
        $type = trim((string) ($input['entry_type'] ?? ''));
        $date = trim((string) ($input['date'] ?? ''));
        $occurredTime = $this->normalizeTime($input['occurred_time'] ?? null);
        $startTime = $this->normalizeTime($input['start_time'] ?? null);
        $endTime = $this->normalizeTime($input['end_time'] ?? null);
        $detail = trim((string) ($input['detail'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if (!isset(self::TYPES[$type])) $errors['entry_type'] = '생활 기록 종류를 선택해주세요.';
        if ($date !== date('Y-m-d')) $errors['date'] = '생활 기록은 오늘 날짜에만 추가할 수 있습니다.';
        if (mb_strlen($detail) > 160) $errors['detail'] = '기록 내용은 160자 이내로 입력해주세요.';
        if (mb_strlen($note) > 1000) $errors['note'] = '메모는 1000자 이내로 입력해주세요.';
        if ($rating === false || $rating < 1 || $rating > 5) $rating = null;

        if (isset(self::TYPES[$type]) && self::TYPES[$type]['mode'] === 'point' && $occurredTime === null) {
            $errors['time'] = '기록 시간을 입력해주세요.';
        }
        if (isset(self::TYPES[$type]) && self::TYPES[$type]['mode'] === 'range' && ($startTime === null || $endTime === null)) {
            $errors['time'] = '시작 시간과 종료 시간을 입력해주세요.';
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'type' => $type, 'date' => date('Y-m-d'), 'occurredTime' => $occurredTime,
            'startTime' => $startTime, 'endTime' => $endTime, 'detail' => $detail,
            'note' => $note, 'rating' => $rating,
        ]];
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): bool
    {
        return $this->repository->create($userId, $data);
    }

    private function normalizeTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1 ? $value : null;
    }

    /** @param array<string, mixed> $row */
    private function timeLabel(array $row): string
    {
        if (!empty($row['occurred_time'])) return substr((string) $row['occurred_time'], 0, 5);
        if (!empty($row['start_time']) && !empty($row['end_time'])) {
            return substr((string) $row['start_time'], 0, 5) . ' ~ ' . substr((string) $row['end_time'], 0, 5);
        }
        return '시간 미정';
    }
}
