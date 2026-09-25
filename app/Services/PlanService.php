<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoalRepository;
use App\Models\PlanRepository;
use App\Models\PlanBlockTemplateRepository;

final class PlanService
{
    private PlanRepository $planRepository;
    private GoalRepository $goalRepository;
    private PlanBlockTemplateRepository $blockTemplateRepository;

    public function __construct()
    {
        $this->planRepository = new PlanRepository();
        $this->goalRepository = new GoalRepository();
        $this->blockTemplateRepository = new PlanBlockTemplateRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function getBlockTemplates(int $userId): array
    {
        return array_map(function (array $template): array {
            $durationIndex = (int) $template['duration_index'];

            return [
                'id' => (int) $template['id'],
                'title' => (string) $template['title'],
                'durationIndex' => $durationIndex,
                'durationMinutes' => $durationIndex * 10,
                'durationLabel' => $this->formatDuration($durationIndex),
                'importance' => $this->normalizeImportance((string) $template['importance']),
                'goalId' => $template['goal_id'] === null ? null : (int) $template['goal_id'],
                'goalTitle' => (string) ($template['goal_title'] ?? ''),
            ];
        }, $this->blockTemplateRepository->listForUser($userId));
    }

    /** @param array<string, mixed> $input */
    public function validateBlockTemplateInput(int $userId, array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $durationIndex = filter_var($input['duration_index'] ?? null, FILTER_VALIDATE_INT);
        $importance = $this->normalizeImportance((string) ($input['importance'] ?? 'D'));
        $goalId = filter_var($input['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if ($title === '' || mb_strlen($title) > 80) {
            $errors['title'] = '계획 블록명은 1자 이상 80자 이내로 입력해주세요.';
        }
        if ($durationIndex === false || $durationIndex < 1 || $durationIndex > 143) {
            $errors['duration'] = '기본 소요 시간은 10분부터 23시간 50분까지 선택해주세요.';
        }

        $normalizedGoalId = $goalId !== false && $goalId > 0
            && $this->goalRepository->activeGoalExists($userId, (int) $goalId)
            ? (int) $goalId
            : null;

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => [
                'title' => $title,
                'durationIndex' => $durationIndex === false ? 0 : (int) $durationIndex,
                'importance' => $importance,
                'goalId' => $normalizedGoalId,
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createBlockTemplate(int $userId, array $data): ?int
    {
        return $this->blockTemplateRepository->create(
            $userId,
            (string) $data['title'],
            (int) $data['durationIndex'],
            (string) $data['importance'],
            $data['goalId'] === null ? null : (int) $data['goalId']
        );
    }

    /** @param array<string, mixed> $data */
    public function updateBlockTemplate(int $userId, int $templateId, array $data): bool
    {
        return $this->blockTemplateRepository->update(
            $userId,
            $templateId,
            (string) $data['title'],
            (int) $data['durationIndex'],
            (string) $data['importance'],
            $data['goalId'] === null ? null : (int) $data['goalId']
        );
    }

    public function deleteBlockTemplate(int $userId, int $templateId): bool
    {
        return $this->blockTemplateRepository->softDelete($userId, $templateId);
    }

    /** @return array<int, array<string, mixed>> */
    public function getPlanList(int $userId): array
    {
        return array_map(function (array $group): array {
            return [
                'id' => (int) $group['id'],
                'name' => (string) $group['name'],
                'versionNo' => (int) ($group['version_no'] ?? 1),
                'blockCount' => (int) $group['block_count'],
                'timeRange' => $this->formatTimeRange($group['first_start_index'], $group['last_end_index']),
                'goalTitles' => $this->splitGoalTitles($group['goal_titles'] ?? null),
            ];
        }, $this->planRepository->listGroups($userId));
    }

    /** @return array<string, mixed>|null */
    public function getPlanDetail(int $userId, int $groupId): ?array
    {
        $group = $this->planRepository->findGroupWithBlocks($userId, $groupId);
        if ($group === null) {
            return null;
        }

        $blocks = [];
        foreach (($group['blocks'] ?? []) as $block) {
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
            $importanceMeta = $this->importanceMeta($importance);

            $blocks[] = [
                'id' => (int) $block['id'],
                'templateId' => (int) $block['plan_template_id'],
                'title' => (string) $block['title'],
                'importance' => $importance,
                'importanceLabel' => $importanceMeta['label'],
                'importanceBadge' => $importanceMeta['badge'],
                'goalId' => $block['goal_id'] === null ? null : (int) $block['goal_id'],
                'goalTitle' => $block['goal_title'] === null ? '' : (string) $block['goal_title'],
                'goalType' => $block['goal_type'] === null ? '' : (string) $block['goal_type'],
                'startIndex' => (int) $block['start_index'],
                'endIndex' => (int) $block['end_index'],
                'timeRange' => $this->formatTimeRange($block['start_index'], $block['end_index']),
            ];
        }

        return [
            'id' => (int) $group['id'],
            'name' => (string) $group['name'],
            'versionNo' => (int) ($group['version_no'] ?? 1),
            'createdAt' => (string) $group['created_at'],
            'updatedAt' => (string) $group['updated_at'],
            'blocks' => $blocks,
            'blocksJson' => json_encode(array_map(static fn (array $block): array => [
                'title' => $block['title'],
                'importance' => $block['importance'],
                'goal_id' => $block['goalId'],
                'start_index' => $block['startIndex'],
                'end_index' => $block['endIndex'],
            ], $blocks), JSON_UNESCAPED_UNICODE) ?: '[]',
        ];
    }

    /**
     * @param array<int, mixed> $rawBlocks
     * @return array{ok: bool, errors: array<string, string>, blocks: array<int, array{title: string, importance: string, goal_id: int|null, start_index: int, end_index: int}>}
     */
    public function validateInput(int $userId, string $name, array $rawBlocks): array
    {
        $errors = [];
        $name = trim($name);

        if ($name === '') {
            $errors['name'] = '계획명을 입력해주세요.';
        } elseif (mb_strlen($name) > 80) {
            $errors['name'] = '계획명은 80자 이내로 입력해주세요.';
        }

        $blocks = $this->normalizeBlocks($userId, $rawBlocks);
        if ($blocks === []) {
            $errors['blocks'] = '계획 블록을 1개 이상 추가해주세요.';
        }

        if ($this->hasOverlap($blocks)) {
            $errors['blocks'] = '서로 겹치는 계획 블록은 저장할 수 없습니다.';
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'blocks' => $blocks,
        ];
    }

    /** @param array<int, array{title: string, importance: string, goal_id: int|null, start_index: int, end_index: int}> $blocks */
    public function createPlanGroup(int $userId, string $name, array $blocks): ?int
    {
        return $this->planRepository->createGroup($userId, trim($name), $blocks);
    }

    /** @param array<int, array{title: string, importance: string, goal_id: int|null, start_index: int, end_index: int}> $blocks */
    public function updatePlanGroup(int $userId, int $groupId, string $name, array $blocks): ?int
    {
        return $this->planRepository->updateGroup($userId, $groupId, trim($name), $blocks);
    }

    public function copyPlanGroup(int $userId, int $groupId): ?int
    {
        return $this->planRepository->copyGroup($userId, $groupId);
    }

    public function deletePlanGroup(int $userId, int $groupId): bool
    {
        return $this->planRepository->softDeleteGroup($userId, $groupId);
    }

    private function importanceMeta(string $importance): array
    {
        return match ($this->normalizeImportance($importance)) {
            'A' => ['badge' => 'A', 'label' => '중요하고 긴급'],
            'B' => ['badge' => 'B', 'label' => '중요하지만 긴급하지 않음'],
            'C' => ['badge' => 'C', 'label' => '긴급하지만 중요하지 않음'],
            default => ['badge' => 'D', 'label' => '중요하지도 긴급하지도 않음'],
        };
    }

    private function normalizeImportance(string $importance): string
    {
        $importance = strtoupper(trim($importance));

        return in_array($importance, ['A', 'B', 'C', 'D'], true) ? $importance : 'D';
    }

    /** @param array<int, mixed> $rawBlocks */
    private function normalizeBlocks(int $userId, array $rawBlocks): array
    {
        $blocks = [];

        foreach ($rawBlocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $title = trim((string) ($block['title'] ?? ''));
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
            $goalId = filter_var($block['goal_id'] ?? null, FILTER_VALIDATE_INT);
            $start = filter_var($block['start_index'] ?? null, FILTER_VALIDATE_INT);
            $end = filter_var($block['end_index'] ?? null, FILTER_VALIDATE_INT);

            if ($title === '' || mb_strlen($title) > 80) {
                continue;
            }

            if ($start === false || $end === false) {
                continue;
            }

            if ($start < 0 || $end > 144 || $start >= $end) {
                continue;
            }

            $blocks[] = [
                'title' => $title,
                'importance' => $importance,
                'goal_id' => $goalId !== false && $goalId > 0 && $this->goalRepository->activeGoalExists($userId, (int) $goalId)
                    ? (int) $goalId
                    : null,
                'start_index' => $start,
                'end_index' => $end,
            ];
        }

        usort($blocks, static fn (array $a, array $b): int => $a['start_index'] <=> $b['start_index']);

        return $blocks;
    }

    /** @param array<int, array{start_index: int, end_index: int}> $blocks */
    private function hasOverlap(array $blocks): bool
    {
        $previousEnd = null;

        foreach ($blocks as $block) {
            if ($previousEnd !== null && $block['start_index'] < $previousEnd) {
                return true;
            }

            $previousEnd = $block['end_index'];
        }

        return false;
    }

    private function formatTimeRange(mixed $startIndex, mixed $endIndex): string
    {
        if ($startIndex === null || $endIndex === null) {
            return '일정 없음';
        }

        return $this->indexToTime((int) $startIndex) . ' ~ ' . $this->indexToTime((int) $endIndex);
    }

    /** @return array<int, string> */
    private function splitGoalTitles(mixed $goalTitles): array
    {
        if (!is_string($goalTitles) || trim($goalTitles) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $goalTitles))));
    }

    private function indexToTime(int $index): string
    {
        $minutes = $index * 10;
        $hour = (int) floor($minutes / 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function formatDuration(int $durationIndex): string
    {
        $minutes = $durationIndex * 10;
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours === 0) {
            return $remainingMinutes . '분';
        }
        if ($remainingMinutes === 0) {
            return $hours . '시간';
        }

        return $hours . '시간 ' . $remainingMinutes . '분';
    }
}
