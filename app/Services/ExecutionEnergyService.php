<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExecutionEnergyRepository;
use DateTimeImmutable;

final class ExecutionEnergyService
{
    private const DEFAULT_CAPACITY = 500;
    private const MIN_CAPACITY = 5;
    private const MAX_CAPACITY = 1000;
    private const DEFAULT_EVENT_VALUE = 25;
    private const EVENT_PRESETS = [10, 25, 50, 75, 100];
    private const STATE_ADJUSTMENTS = [-50, -25, 0, 25, 50];

    private ExecutionEnergyRepository $repository;

    public function __construct()
    {
        $this->repository = new ExecutionEnergyRepository();
    }

    /** @return array<string, mixed> */
    public function getManagementData(int $userId): array
    {
        $profile = $this->ensureProfile($userId);

        return [
            'profile' => $profile,
        ];
    }

    /** @return array<string, mixed> */
    public function getDayData(int $userId, ?string $requestedDate): array
    {
        $date = $this->normalizeDate($requestedDate);
        $profile = $this->ensureProfile($userId);
        $day = $this->repository->findDay($userId, $date)
            ?? $this->repository->createDay($userId, $date, (int) $profile['base_capacity']);
        $startCapacity = max(self::MIN_CAPACITY, $this->roundToFive(
            (int) $day['base_capacity_snapshot'] + (int) $day['morning_adjustment']
        ));
        $activities = $this->repository->listEventActivities($userId, $date);
        $timeline = [];

        foreach ($activities as $activity) {
            $effectType = (string) ($activity['effect_type'] ?? '');
            $value = (int) ($activity['energy_value'] ?? 0);
            if (!in_array($effectType, ['consume', 'recover'], true) || !$this->isValidUnit($value)) {
                continue;
            }
            $timeline[] = [
                'kind' => 'activity',
                'timeIndex' => (int) ($activity['end_index'] ?? 144),
                'id' => (int) $activity['id'],
                'title' => (string) $activity['title'],
                'effectType' => $effectType,
                'value' => $value,
                'groupName' => (string) ($activity['group_name_snapshot'] ?? '직접 설정'),
            ];
        }
        usort($timeline, static fn (array $left, array $right): int => [
            $left['timeIndex'], $left['id'],
        ] <=> [
            $right['timeIndex'], $right['id'],
        ]);

        $capacity = $startCapacity;
        $overflow = 0;
        $overConsumed = 0;
        $consumed = 0;
        $recovered = 0;
        foreach ($timeline as &$entry) {
            $before = $capacity;
            if ($entry['effectType'] === 'recover') {
                $recovered += (int) $entry['value'];
                $recoveredCapacity = $capacity + (int) $entry['value'];
                if ($recoveredCapacity > $startCapacity) {
                    $overflow += $recoveredCapacity - $startCapacity;
                }
                $capacity = min($startCapacity, $recoveredCapacity);
            } else {
                $consumed += (int) $entry['value'];
                $overConsumed += max(0, (int) $entry['value'] - $capacity);
                $capacity = max(0, $capacity - (int) $entry['value']);
            }
            $entry['beforeCapacity'] = $before;
            $entry['afterCapacity'] = $capacity;
        }
        unset($entry);

        $predictedCapacity = $capacity;
        foreach ($this->repository->listRemainingPlanItems($userId, $date) as $plan) {
            $predictedCapacity = max(0, $predictedCapacity - self::DEFAULT_EVENT_VALUE);
        }

        return [
            'date' => $date,
            'day' => $day,
            'baseCapacity' => (int) $day['base_capacity_snapshot'],
            'startCapacity' => $startCapacity,
            'currentCapacity' => $capacity,
            'predictedCapacity' => $predictedCapacity,
            'overflow' => $overflow,
            'overConsumed' => $overConsumed,
            'consumed' => $consumed,
            'recovered' => $recovered,
            'percent' => (int) round(($capacity / $startCapacity) * 100),
            'conditionApplied' => (int) $day['morning_adjustment'] !== 0 || $this->hasMorningSelection($day),
            'morningAdjustment' => (int) $day['morning_adjustment'],
            'timeline' => $timeline,
            'feedbackMessage' => $this->buildFeedbackMessage(
                $day,
                $capacity,
                $predictedCapacity,
                $startCapacity,
                $overConsumed
            ),
        ];
    }

    /** @param array<string, mixed> $input */
    public function validateProfileInput(array $input): array
    {
        $capacity = filter_var($input['base_capacity'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];
        if ($capacity === false || !$this->isValidUnit((int) $capacity)) {
            $errors['base_capacity'] = '기본 실행력은 5~1000 사이의 5단위 값으로 입력해주세요.';
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'base_capacity' => $capacity === false ? self::DEFAULT_CAPACITY : (int) $capacity,
            'effective_from' => (new DateTimeImmutable('tomorrow'))->format('Y-m-d'),
            'body_state_note' => $this->shortText($input['body_state_note'] ?? ''),
            'mood_state_note' => $this->shortText($input['mood_state_note'] ?? ''),
            'stress_state_note' => $this->shortText($input['stress_state_note'] ?? ''),
            'injury_note' => $this->shortText($input['injury_note'] ?? ''),
            'injury_until' => $this->normalizeOptionalDate($input['injury_until'] ?? null),
            'inbody_note' => $this->shortText($input['inbody_note'] ?? ''),
        ]];
    }

    /** @param array<string, mixed> $data */
    public function saveProfile(int $userId, array $data): void
    {
        $existing = $this->repository->findProfile($userId);
        if (is_array($existing)) {
            foreach (['body_state_note', 'mood_state_note', 'stress_state_note', 'injury_note', 'injury_until', 'inbody_note'] as $field) {
                $data[$field] = $existing[$field] ?? null;
            }
        }
        $this->repository->saveProfile($userId, $data);
        $this->repository->refreshFutureDaySnapshots(
            $userId,
            (string) $data['effective_from'],
            (int) $data['base_capacity']
        );
    }

    /** @param array<string, mixed> $input */
    public function validateMorningInput(array $input): array
    {
        $states = [];
        $errors = [];
        foreach (['sleep_state', 'fatigue_state', 'mood_state', 'stress_state'] as $field) {
            $value = $input[$field] ?? '';
            if ($value === '') {
                $states[$field] = null;
                continue;
            }
            $parsed = filter_var($value, FILTER_VALIDATE_INT);
            if ($parsed === false || $parsed < 1 || $parsed > 5) {
                $errors[$field] = '각 컨디션은 5단계로 선택해주세요.';
                $states[$field] = null;
                continue;
            }
            $states[$field] = (int) $parsed;
        }
        $adjustment = 0;
        foreach ($states as $field => $state) {
            if ($state === null) continue;
            $adjustment += self::STATE_ADJUSTMENTS[$this->normalizeStateDirection($field, $state) - 1];
        }
        $burdenFeedback = (string) ($input['burden_feedback'] ?? '');
        if (!in_array($burdenFeedback, ['', 'lower', 'similar', 'higher'], true)) {
            $errors['burden_feedback'] = '체감 부담을 다시 선택해주세요.';
            $burdenFeedback = '';
        }
        return ['ok' => $errors === [], 'errors' => $errors, 'data' => $states + [
            'burden_feedback' => $burdenFeedback !== '' ? $burdenFeedback : null,
            'morning_adjustment' => $this->roundToFive($adjustment),
        ]];
    }

    /** @param array<string, int|null> $data */
    public function saveMorningCheck(int $userId, string $date, array $data): void
    {
        $date = $this->normalizeDate($date);
        $profile = $this->ensureProfile($userId);
        $this->repository->findDay($userId, $date) ?? $this->repository->createDay($userId, $date, (int) $profile['base_capacity']);
        $this->repository->saveMorningCheck($userId, $date, $data);
    }

    /** @param array<string, mixed> $input */
    public function validateEventSetting(int $userId, array $input): array
    {
        $effectType = (string) ($input['execution_energy_type'] ?? 'consume');
        $amount = (string) ($input['execution_energy_amount'] ?? (string) self::DEFAULT_EVENT_VALUE);
        $errors = [];

        if (!in_array($effectType, ['none', 'consume', 'recover'], true)) {
            $errors['execution_energy_type'] = '실행력 변화를 다시 선택해주세요.';
            $effectType = 'consume';
        }

        $value = self::DEFAULT_EVENT_VALUE;
        if ($effectType !== 'none') {
            if ($amount === 'custom') {
                $customValue = filter_var($input['execution_energy_custom_value'] ?? null, FILTER_VALIDATE_INT);
                if ($customValue === false || !$this->isValidUnit((int) $customValue)) {
                    $errors['execution_energy_value'] = '직접 입력 변화량은 5~1000 사이의 5단위로 입력해주세요.';
                } else {
                    $value = (int) $customValue;
                }
            } else {
                $presetValue = filter_var($amount, FILTER_VALIDATE_INT);
                if ($presetValue === false || !in_array((int) $presetValue, self::EVENT_PRESETS, true)) {
                    $errors['execution_energy_value'] = '실행력 변화량을 다시 선택해주세요.';
                } else {
                    $value = (int) $presetValue;
                }
            }
        }

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'effectType' => $effectType,
            'value' => $effectType === 'none' ? 0 : $value,
        ]];
    }

    /** @param array<string, mixed> $setting */
    public function saveEventSetting(int $userId, int $eventId, array $setting): void
    {
        $effectType = (string) ($setting['effectType'] ?? 'consume');
        $value = (int) ($setting['value'] ?? self::DEFAULT_EVENT_VALUE);
        if ($effectType === 'none') {
            $this->repository->deleteEventSetting($userId, $eventId);
            return;
        }
        if (!in_array($effectType, ['consume', 'recover'], true) || !$this->isValidUnit($value)) {
            return;
        }

        $this->repository->upsertEventSetting($userId, $eventId, [
            'group_id_snapshot' => null,
            'group_name_snapshot' => '직접 설정',
            'effect_type' => $effectType,
            'energy_value' => $value,
            'is_override' => 1,
        ]);
    }

    public function saveDefaultEventSetting(int $userId, int $eventId): void
    {
        $this->ensureProfile($userId);
        $this->saveEventSetting($userId, $eventId, [
            'effectType' => 'consume',
            'value' => self::DEFAULT_EVENT_VALUE,
        ]);
    }

    public function claimMilestoneFeedback(int $userId, string $date): ?string
    {
        $date = $this->normalizeDate($date);
        $percent = (int) $this->getDayData($userId, $date)['percent'];
        $level = $percent <= 0 ? 0 : ($percent <= 25 ? 25 : ($percent <= 50 ? 50 : null));
        if ($level === null) {
            return null;
        }

        foreach ([50, 25, 0] as $passedLevel) {
            if ($percent <= $passedLevel) {
                $claimed = $this->repository->claimNotice($userId, $date, $passedLevel);
                if ($passedLevel === $level && !$claimed) {
                    return null;
                }
            }
        }

        return $this->milestoneFeedbackMessage($level);
    }

    private function ensureProfile(int $userId): array
    {
        $profile = $this->repository->findProfile($userId);
        if ($profile === null) {
            $this->repository->saveProfile($userId, [
                'base_capacity' => self::DEFAULT_CAPACITY, 'effective_from' => date('Y-m-d'),
                'body_state_note' => null, 'mood_state_note' => null, 'stress_state_note' => null,
                'injury_note' => null, 'injury_until' => null, 'inbody_note' => null,
            ]);
            $profile = $this->repository->findProfile($userId) ?? [];
        }
        return $profile;
    }

    private function normalizeDate(?string $date): string
    {
        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
    }

    private function isValidUnit(int $value): bool
    {
        return $value >= self::MIN_CAPACITY && $value <= self::MAX_CAPACITY && $value % 5 === 0;
    }

    private function roundToFive(int $value): int
    {
        return (int) (round($value / 5) * 5);
    }

    private function normalizeStateDirection(string $field, int $state): int
    {
        return $state;
    }

    /** @param array<string, mixed> $day */
    private function hasMorningSelection(array $day): bool
    {
        return $day['sleep_state'] !== null || $day['fatigue_state'] !== null || $day['mood_state'] !== null || $day['stress_state'] !== null;
    }

    private function shortText(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    private function normalizeOptionalDate(mixed $date): ?string
    {
        $date = is_string($date) ? $date : '';
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null;
    }

    /** @param array<string, mixed> $day */
    private function buildFeedbackMessage(
        array $day,
        int $currentCapacity,
        int $predictedCapacity,
        int $startCapacity,
        int $overConsumed
    ): string {
        return match ((string) ($day['burden_feedback'] ?? '')) {
            'higher' => '예상보다 부담이 컸어요. 다음 계획은 실행력 한 단계를 낮춰 잡아보세요.',
            'lower' => '예상보다 여유가 있었어요. 비슷한 일정의 실행력 값을 조금 낮춰볼 수 있어요.',
            'similar' => '예상과 실제 부담이 비슷했어요. 현재 기준을 다음 계획에도 활용해 보세요.',
            default => $currentCapacity <= 0 || $overConsumed > 0
                ? $this->milestoneFeedbackMessage(0)
                : ($currentCapacity <= ($startCapacity * 0.25)
                    ? $this->milestoneFeedbackMessage(25)
                    : ($currentCapacity <= ($startCapacity * 0.5)
                        ? $this->milestoneFeedbackMessage(50)
                        : ($predictedCapacity <= ($startCapacity * 0.25)
                            ? '남은 계획까지 진행하면 실행력이 크게 줄 수 있어요. 우선순위나 휴식 시간을 확인해 보세요.'
                            : '아직 절반 이상의 실행력이 남아 있어요. 가장 중요한 다음 한 가지에 집중해 보세요.'))),
        };
    }

    private function milestoneFeedbackMessage(int $level): string
    {
        return match ($level) {
            0 => '오늘의 실행력을 계획에 충분히 사용했어요. 완료한 일을 응원하며, 이제 잠시 회복해도 괜찮아요.',
            25 => '오늘 계획을 많이 실행했어요. 남은 실행력은 꼭 마무리하고 싶은 계획에 사용해 보세요.',
            default => '오늘 계획을 차근차근 실행하고 있어요. 남은 계획도 지금의 흐름대로 이어가 보세요.',
        };
    }
}
