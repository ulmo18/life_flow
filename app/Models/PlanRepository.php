<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class PlanRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listGroups(int $userId): array
    {
        $sql = 'SELECT
                    pg.id,
                    pg.name,
                    pg.version_no,
                    pg.source_plan_group_id,
                    pg.created_at,
                    pg.updated_at,
                    COUNT(pb.id) AS block_count,
                    MIN(pb.start_index) AS first_start_index,
                    MAX(pb.end_index) AS last_end_index,
                    GROUP_CONCAT(DISTINCT g.title) AS goal_titles
                FROM plan_groups pg
                LEFT JOIN plan_blocks pb ON pb.plan_group_id = pg.id
                LEFT JOIN plan_templates pt ON pt.id = pb.plan_template_id
                    AND pt.deleted_at IS NULL
                LEFT JOIN goals g ON g.id = pt.goal_id
                    AND g.user_id = pg.user_id
                    AND g.deleted_at IS NULL
                WHERE pg.user_id = :user_id
                    AND pg.deleted_at IS NULL
                GROUP BY
                    pg.id,
                    pg.name,
                    pg.version_no,
                    pg.source_plan_group_id,
                    pg.created_at,
                    pg.updated_at
                ORDER BY pg.updated_at DESC, pg.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findGroupWithBlocks(int $userId, int $groupId, bool $visibleOnly = true): ?array
    {
        $group = $this->findGroup($userId, $groupId, $visibleOnly);
        if ($group === null) {
            return null;
        }

        $group['blocks'] = $this->findBlocks($groupId);

        return $group;
    }

    /**
     * @param array<int, array{title: string, importance: string, start_index: int, end_index: int}> $blocks
     */
    public function createGroup(int $userId, string $name, array $blocks): ?int
    {
        try {
            $this->db->beginTransaction();

            $groupId = $this->insertGroup($userId, $name, null, 1);
            $this->insertBlocksWithNewTemplates($userId, $groupId, $blocks);

            $this->db->commit();
            return $groupId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[plan] create failed: ' . $exception->getMessage());
            return null;
        }
    }

    /**
     * @param array<int, array{title: string, importance: string, start_index: int, end_index: int}> $blocks
     */
    public function createEditedVersion(int $userId, int $sourceGroupId, string $name, array $blocks): ?int
    {
        $source = $this->findGroup($userId, $sourceGroupId, true);
        if ($source === null) {
            return null;
        }

        $rootGroupId = (int) ($source['source_plan_group_id'] ?? 0);
        if ($rootGroupId <= 0) {
            $rootGroupId = (int) $source['id'];
        }

        $versionNo = ((int) ($source['version_no'] ?? 1)) + 1;

        try {
            $this->db->beginTransaction();

            $newGroupId = $this->insertGroup($userId, $name, $rootGroupId, $versionNo);
            $this->insertBlocksWithNewTemplates($userId, $newGroupId, $blocks);
            $this->markDeleted($userId, $sourceGroupId);

            $this->db->commit();
            return $newGroupId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[plan] edit failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function copyGroup(int $userId, int $groupId): ?int
    {
        $group = $this->findGroup($userId, $groupId, true);
        if ($group === null) {
            return null;
        }

        $blocks = $this->findBlocks($groupId);
        if ($blocks === []) {
            return null;
        }

        try {
            $this->db->beginTransaction();

            $newGroupId = $this->insertGroup($userId, (string) $group['name'] . '_복사', null, 1);
            $this->copyBlocks($newGroupId, $blocks);

            $this->db->commit();
            return $newGroupId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[plan] copy failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function softDeleteGroup(int $userId, int $groupId): bool
    {
        return $this->markDeleted($userId, $groupId);
    }

    /** @return array<string, mixed>|null */
    private function findGroup(int $userId, int $groupId, bool $visibleOnly): ?array
    {
        $sql = 'SELECT id, user_id, name, source_plan_group_id, version_no, deleted_at, created_at, updated_at
                FROM plan_groups
                WHERE id = :id
                    AND user_id = :user_id';

        if ($visibleOnly) {
            $sql .= ' AND deleted_at IS NULL';
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $groupId,
            'user_id' => $userId,
        ]);

        $group = $stmt->fetch();

        return $group !== false ? $group : null;
    }

    private function insertGroup(int $userId, string $name, ?int $sourceGroupId, int $versionNo): int
    {
        $sql = 'INSERT INTO plan_groups (
                    user_id, name, source_plan_group_id, version_no, deleted_at, created_at, updated_at
                ) VALUES (
                    :user_id, :name, :source_plan_group_id, :version_no, NULL,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'source_plan_group_id' => $sourceGroupId,
            'version_no' => $versionNo,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function createTemplate(int $userId, string $title, string $importance, ?int $goalId): int
    {
        $sql = 'INSERT INTO plan_templates (user_id, goal_id, title, importance, deleted_at, created_at, updated_at)
                VALUES (:user_id, :goal_id, :title, :importance, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'title' => $title,
            'importance' => $importance,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<int, array{title: string, importance: string, start_index: int, end_index: int}> $blocks
     */
    private function insertBlocksWithNewTemplates(int $userId, int $groupId, array $blocks): void
    {
        $sql = 'INSERT INTO plan_blocks (
                    plan_group_id, plan_template_id, start_index, end_index, sort_order, created_at, updated_at
                ) VALUES (
                    :plan_group_id, :plan_template_id, :start_index, :end_index, :sort_order,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        $stmt = $this->db->prepare($sql);

        foreach ($blocks as $sortOrder => $block) {
            $templateId = $this->createTemplate(
                $userId,
                $block['title'],
                $block['importance'],
                isset($block['goal_id']) ? (int) $block['goal_id'] : null
            );
            $stmt->execute([
                'plan_group_id' => $groupId,
                'plan_template_id' => $templateId,
                'start_index' => $block['start_index'],
                'end_index' => $block['end_index'],
                'sort_order' => $sortOrder + 1,
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function findBlocks(int $groupId): array
    {
        $sql = 'SELECT
                    pb.id,
                    pb.plan_group_id,
                    pb.plan_template_id,
                    pb.start_index,
                    pb.end_index,
                    pb.sort_order,
                    pt.title,
                    pt.importance,
                    pt.goal_id,
                    g.title AS goal_title,
                    g.goal_type AS goal_type
                FROM plan_blocks pb
                INNER JOIN plan_templates pt ON pt.id = pb.plan_template_id
                LEFT JOIN goals g ON g.id = pt.goal_id
                    AND g.user_id = pt.user_id
                    AND g.deleted_at IS NULL
                WHERE pb.plan_group_id = :plan_group_id
                ORDER BY pb.sort_order ASC, pb.start_index ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['plan_group_id' => $groupId]);

        return $stmt->fetchAll();
    }

    /** @param array<int, array<string, mixed>> $blocks */
    private function copyBlocks(int $newGroupId, array $blocks): void
    {
        $sql = 'INSERT INTO plan_blocks (
                    plan_group_id, plan_template_id, start_index, end_index, sort_order, created_at, updated_at
                ) VALUES (
                    :plan_group_id, :plan_template_id, :start_index, :end_index, :sort_order,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        $stmt = $this->db->prepare($sql);

        foreach ($blocks as $block) {
            $stmt->execute([
                'plan_group_id' => $newGroupId,
                'plan_template_id' => (int) $block['plan_template_id'],
                'start_index' => (int) $block['start_index'],
                'end_index' => (int) $block['end_index'],
                'sort_order' => (int) $block['sort_order'],
            ]);
        }
    }

    private function markDeleted(int $userId, int $groupId): bool
    {
        $sql = 'UPDATE plan_groups
                SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $groupId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }
}
