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
            'groups' => $this->repository->listGroups($userId),
        ];
    }

    /** @return array<string, mixed> */
    public function getDayData(int $userId, ?string $requestedDate): array
    {
        $date = $this->normalizeDate($requestedDate);
        $profile = $this->ensureProfile($userId);
        $groups = $this->repository->listGroups($userId, true);
        $defaultGroup = $this->repository->findDefaultGroup($userId) ?? $groups[0] ?? null;
        $day = $this->repository->findDay($userId, $date)
            ?? $this->repository->createDay($userId, $date, (int) $profile['base_capacity']);
        $startCapacity = max(self::MIN_CAPACITY, $this->roundToFive(
            (int) $day['base_capacity_snapshot'] + (int) $day['morning_adjustment']
        ));
        $activities = $this->repository->listEventActivities($userId, $date);
        $timeline = [];

        foreach ($activities as $activity) {
            if (!$this->isValidUnit((int) ($activity['energy_value'] ?? 0)) && is_array($defaultGroup)) {
                $this->saveEventSetting($userId, (int) $activity['id'], [
                    'group' => $defaultGroup,
                    'overrideType' => '',
                    'overrideValue' => null,
                ]);
                $activity['group_name_snapshot'] = $defaultGroup['name'];
                $activity['effect_type'] = $defaultGroup['effect_type'];
                $activity['energy_value'] = $defaultGroup['energy_value'];
            }
            $effectType = in_array((string) ($activity['effect_type'] ?? ''), ['consume', 'recover'], true)
                ? (string) $activity['effect_type']
                : (string) ($defaultGroup['effect_type'] ?? 'consume');
            $value = $this->isValidUnit((int) ($activity['energy_value'] ?? 0))
                ? (int) $activity['energy_value']
                : (int) ($defaultGroup['energy_value'] ?? 50);
            $timeline[] = [
                'kind' => 'activity',
                'timeIndex' => (int) ($activity['end_index'] ?? 144),
                'id' => (int) $activity['id'],
                'title' => (string) $activity['title'],
                'effectType' => $effectType,
                'value' => $value,
                'groupName' => (string) ($activity['group_name_snapshot'] ?? $defaultGroup['name'] ?? '일반 활동'),
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
            $projectedValue = (int) ($defaultGroup['energy_value'] ?? 50);
            $predictedCapacity = ($defaultGroup['effect_type'] ?? 'consume') === 'recover'
                ? min($startCapacity, $predictedCapacity + $projectedValue)
                : max(0, $predictedCapacity - $projectedValue);
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
            'groups' => $groups,
            'defaultGroup' => $defaultGroup,
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
    public function validateGroupInput(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $type = (string) ($input['effect_type'] ?? 'consume');
        $value = filter_var($input['energy_value'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];
        if ($name === '' || mb_strlen($name) > 60) $errors['name'] = '그룹 이름은 1~60자로 입력해주세요.';
        if (!in_array($type, ['consume', 'recover'], true)) $errors['effect_type'] = '그룹 유형을 선택해주세요.';
        if ($value === false || !$this->isValidUnit((int) $value)) $errors['energy_value'] = '실행력 값은 5~1000 사이의 5단위 값으로 입력해주세요.';

        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'name' => $name, 'effect_type' => $type, 'energy_value' => $value === false ? 50 : (int) $value,
            'is_default' => !empty($input['is_default']) ? 1 : 0, 'is_active' => !empty($input['is_active']) ? 1 : 0,
        ]];
    }

    /** @param array<string, mixed> $data */
    public function createGroup(int $userId, array $data): ?int
    {
        if ((int) $data['is_default'] === 1) $this->clearDefaultGroups($userId);
        return $this->repository->createGroup($userId, $data);
    }

    /** @param array<string, mixed> $data */
    public function updateGroup(int $userId, int $groupId, array $data): bool
    {
        if ((int) $data['is_default'] === 1) $this->clearDefaultGroups($userId);
        return $this->repository->updateGroup($userId, $groupId, $data);
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
        $groupId = filter_var($input['execution_energy_group_id'] ?? null, FILTER_VALIDATE_INT);
        $overrideType = (string) ($input['execution_energy_type'] ?? '');
        $overrideValue = trim((string) ($input['execution_energy_value'] ?? ''));
        $group = $groupId !== false && $groupId > 0 ? $this->repository->findGroup($userId, (int) $groupId, false) : null;
        $errors = [];
        if ($groupId !== false && $groupId > 0 && $group === null) $errors['execution_energy_group_id'] = '사용할 수 없는 실행력 그룹입니다.';
        if ($overrideValue !== '') {
            $value = filter_var($overrideValue, FILTER_VALIDATE_INT);
            if (!in_array($overrideType, ['consume', 'recover'], true) || $value === false || !$this->isValidUnit((int) $value)) {
                $errors['execution_energy_value'] = '개별 실행력 값은 5~1000 사이의 5단위 값으로 입력해주세요.';
            }
        } elseif (in_array($overrideType, ['consume', 'recover'], true) && is_array($group)) {
            $overrideValue = (string) $group['energy_value'];
        }
        return ['ok' => $errors === [], 'errors' => $errors, 'data' => [
            'group' => $group,
            'overrideType' => $overrideType,
            'overrideValue' => $overrideValue === '' ? null : (int) $overrideValue,
            'preserveExisting' => (string) ($input['execution_energy_preserve'] ?? '') === '1',
        ]];
    }

    /** @param array<string, mixed> $setting */
    public function saveEventSetting(int $userId, int $eventId, array $setting): void
    {
        $group = $setting['group'] ?? $this->repository->findDefaultGroup($userId);
        if (!is_array($group)) return;
        $isOverride = $setting['overrideValue'] !== null;
        $this->repository->upsertEventSetting($userId, $eventId, [
            'group_id_snapshot' => (int) $group['id'],
            'group_name_snapshot' => (string) $group['name'],
            'effect_type' => $isOverride ? (string) $setting['overrideType'] : (string) $group['effect_type'],
            'energy_value' => $isOverride ? (int) $setting['overrideValue'] : (int) $group['energy_value'],
            'is_override' => $isOverride ? 1 : 0,
        ]);
    }

    public function saveDefaultEventSetting(int $userId, int $eventId): void
    {
        $this->ensureProfile($userId);
        $this->saveEventSetting($userId, $eventId, [
            'group' => $this->repository->findDefaultGroup($userId),
            'overrideType' => '',
            'overrideValue' => null,
        ]);
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
        if ($this->repository->listGroups($userId) === []) {
            foreach ([['가벼운 활동', 'consume', 25, 0], ['일반 활동', 'consume', 50, 1], ['집중·부담 활동', 'consume', 100, 0], ['고부담 활동', 'consume', 150, 0], ['짧은 휴식', 'recover', 25, 0], ['충분한 휴식', 'recover', 50, 0]] as [$name, $type, $value, $default]) {
                $this->repository->createGroup($userId, ['name' => $name, 'effect_type' => $type, 'energy_value' => $value, 'is_default' => $default]);
            }
        }
        return $profile;
    }

    private function clearDefaultGroups(int $userId): void
    {
        foreach ($this->repository->listGroups($userId) as $group) {
            if ((int) $group['is_default'] !== 1) continue;
            $this->repository->updateGroup($userId, (int) $group['id'], [
                'name' => (string) $group['name'], 'effect_type' => (string) $group['effect_type'],
                'energy_value' => (int) $group['energy_value'], 'is_default' => 0, 'is_active' => (int) $group['is_active'],
            ]);
        }
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
        return in_array($field, ['fatigue_state', 'stress_state'], true) ? 6 - $state : $state;
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
            default => $overConsumed > 0
                ? '시작 실행력보다 더 많이 사용했어요. 다음에는 회복 일정을 먼저 확보해 보세요.'
                : ($predictedCapacity <= ($startCapacity * 0.25)
                    ? '남은 계획까지 진행하면 실행력이 크게 줄 수 있어요. 우선순위나 휴식 시간을 확인해 보세요.'
                    : ($currentCapacity >= ($startCapacity * 0.5)
                        ? '아직 절반 이상의 실행력이 남아 있어요. 가장 중요한 다음 한 가지에 집중해 보세요.'
                        : '오늘 사용량을 확인하고 남은 계획을 가볍게 조정해 보세요.')),
        };
    }
}
