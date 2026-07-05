<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanRepository;

final class PlanService
{
    private PlanRepository $planRepository;

    public function __construct()
    {
        $this->planRepository = new PlanRepository();
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
                'start_index' => $block['startIndex'],
                'end_index' => $block['endIndex'],
            ], $blocks), JSON_UNESCAPED_UNICODE) ?: '[]',
        ];
    }

    /**
     * @param array<int, mixed> $rawBlocks
     * @return array{ok: bool, errors: array<string, string>, blocks: array<int, array{title: string, importance: string, start_index: int, end_index: int}>}
     */
    public function validateInput(string $name, array $rawBlocks): array
    {
        $errors = [];
        $name = trim($name);

        if ($name === '') {
            $errors['name'] = '계획명을 입력해주세요.';
        } elseif (mb_strlen($name) > 80) {
            $errors['name'] = '계획명은 80자 이내로 입력해주세요.';
        }

        $blocks = $this->normalizeBlocks($rawBlocks);
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

    /** @param array<int, array{title: string, importance: string, start_index: int, end_index: int}> $blocks */
    public function createPlanGroup(int $userId, string $name, array $blocks): ?int
    {
        return $this->planRepository->createGroup($userId, trim($name), $blocks);
    }

    /** @param array<int, array{title: string, importance: string, start_index: int, end_index: int}> $blocks */
    public function createEditedPlanGroup(int $userId, int $sourceGroupId, string $name, array $blocks): ?int
    {
        return $this->planRepository->createEditedVersion($userId, $sourceGroupId, trim($name), $blocks);
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
    private function normalizeBlocks(array $rawBlocks): array
    {
        $blocks = [];

        foreach ($rawBlocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $title = trim((string) ($block['title'] ?? ''));
            $importance = $this->normalizeImportance((string) ($block['importance'] ?? 'D'));
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

    private function indexToTime(int $index): string
    {
        $minutes = $index * 10;
        $hour = (int) floor($minutes / 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
