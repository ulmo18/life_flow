<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class GoalRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(int $userId, ?string $status = null, ?string $goalType = null): array
    {
        $sql = 'SELECT
                    g.id,
                    g.user_id,
                    g.parent_goal_id,
                    g.goal_type,
                    g.title,
                    g.behavior_when,
                    g.behavior_where,
                    g.behavior_how,
                    g.period_start_date,
                    g.period_end_date,
                    g.status,
                    g.sort_order,
                    g.completed_at,
                    g.created_at,
                    g.updated_at,
                    parent.title AS parent_title,
                    parent.goal_type AS parent_goal_type
                FROM goals g
                LEFT JOIN goals parent ON parent.id = g.parent_goal_id
                    AND parent.user_id = g.user_id
                    AND parent.deleted_at IS NULL
                WHERE g.user_id = :user_id
                    AND g.deleted_at IS NULL';
        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= ' AND g.status = :status';
            $params['status'] = $status;
        }

        if ($goalType !== null) {
            $sql .= ' AND g.goal_type = :goal_type';
            $params['goal_type'] = $goalType;
        }

        $sql .= ' ORDER BY ' . $this->goalTypeOrderExpression('g.goal_type') . ',
                    g.sort_order ASC,
                    g.updated_at DESC,
                    g.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listPotentialParents(int $userId, ?int $excludeGoalId = null): array
    {
        $sql = 'SELECT id, parent_goal_id, goal_type, title
                FROM goals
                WHERE user_id = :user_id
                    AND deleted_at IS NULL';
        $params = ['user_id' => $userId];

        if ($excludeGoalId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeGoalId;
        }

        $sql .= ' ORDER BY ' . $this->goalTypeOrderExpression('goal_type') . ', sort_order ASC, updated_at DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveOptions(int $userId): array
    {
        $sql = 'SELECT id, parent_goal_id, goal_type, title
                FROM goals
                WHERE user_id = :user_id
                    AND status = :status
                    AND deleted_at IS NULL
                ORDER BY ' . $this->goalTypeOrderExpression('goal_type') . ', sort_order ASC, updated_at DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'status' => 'active',
        ]);

        return $stmt->fetchAll();
    }

    public function activeGoalExists(int $userId, int $goalId): bool
    {
        $sql = 'SELECT id
                FROM goals
                WHERE id = :id
                    AND user_id = :user_id
                    AND status = :status
                    AND deleted_at IS NULL
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $goalId,
            'user_id' => $userId,
            'status' => 'active',
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function listDeadlineReminderTargets(int $userId, string $today): array
    {
        $sql = 'SELECT id, title, goal_type, period_end_date
                FROM goals
                WHERE user_id = :user_id
                    AND status = :status
                    AND period_end_date IS NOT NULL
                    AND period_end_date >= :today
                    AND deleted_at IS NULL
                ORDER BY period_end_date ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'status' => 'active',
            'today' => $today,
        ]);

        return $stmt->fetchAll();
    }

    /** @param array<int, int> $goalIds @return array<int, array<int, array<string, mixed>>> */
    public function listLinkedPlansByGoalIds(int $userId, array $goalIds): array
    {
        if ($goalIds === []) {
            return [];
        }

        $params = [];
        $placeholders = $this->buildInPlaceholders('goal_id', $goalIds, $params);
        $sql = 'SELECT
                    pt.goal_id,
                    pg.id AS plan_group_id,
                    pg.name AS plan_name,
                    pt.title AS block_title,
                    pt.importance,
                    pb.start_index,
                    pb.end_index
                FROM plan_templates pt
                INNER JOIN plan_blocks pb ON pb.plan_template_id = pt.id
                INNER JOIN plan_groups pg ON pg.id = pb.plan_group_id
                    AND pg.user_id = pt.user_id
                    AND pg.deleted_at IS NULL
                WHERE pt.user_id = :user_id
                    AND pt.deleted_at IS NULL
                    AND pt.goal_id IN (' . $placeholders . ')
                ORDER BY pt.goal_id ASC, pg.updated_at DESC, pb.start_index ASC';

        $params['user_id'] = $userId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $plans = [];
        foreach ($stmt->fetchAll() as $row) {
            $plans[(int) $row['goal_id']][] = $row;
        }

        return $plans;
    }

    /** @param array<int, int> $goalIds @return array<int, array<int, array<string, mixed>>> */
    public function listLinkedRoutinesByGoalIds(int $userId, array $goalIds): array
    {
        if ($goalIds === []) {
            return [];
        }

        $params = [];
        $placeholders = $this->buildInPlaceholders('goal_id', $goalIds, $params);
        $sql = 'SELECT goal_id, id, name, start_date, duration_days
                FROM routines
                WHERE user_id = :user_id
                    AND deleted_at IS NULL
                    AND goal_id IN (' . $placeholders . ')
                ORDER BY goal_id ASC, updated_at DESC, id DESC';

        $params['user_id'] = $userId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $routines = [];
        foreach ($stmt->fetchAll() as $row) {
            $routines[(int) $row['goal_id']][] = $row;
        }

        return $routines;
    }

    /** @return array<string, mixed>|null */
    public function findActive(int $userId, int $goalId): ?array
    {
        $sql = 'SELECT
                    id,
                    user_id,
                    parent_goal_id,
                    goal_type,
                    title,
                    behavior_when,
                    behavior_where,
                    behavior_how,
                    period_start_date,
                    period_end_date,
                    status,
                    sort_order,
                    completed_at,
                    created_at,
                    updated_at
                FROM goals
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $goalId,
            'user_id' => $userId,
        ]);

        $goal = $stmt->fetch();

        return $goal !== false ? $goal : null;
    }

    public function parentExists(int $userId, int $parentGoalId): bool
    {
        return $this->findActive($userId, $parentGoalId) !== null;
    }

    public function wouldCreateCycle(int $userId, int $goalId, int $parentGoalId): bool
    {
        if ($goalId === $parentGoalId) {
            return true;
        }

        $visited = [];
        $currentParentId = $parentGoalId;

        while ($currentParentId > 0) {
            if ($currentParentId === $goalId || isset($visited[$currentParentId])) {
                return true;
            }

            $visited[$currentParentId] = true;
            $parent = $this->findActive($userId, $currentParentId);
            if ($parent === null || $parent['parent_goal_id'] === null) {
                return false;
            }

            $currentParentId = (int) $parent['parent_goal_id'];
        }

        return false;
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): ?int
    {
        $sql = 'INSERT INTO goals (
                    user_id,
                    parent_goal_id,
                    goal_type,
                    title,
                    behavior_when,
                    behavior_where,
                    behavior_how,
                    period_start_date,
                    period_end_date,
                    status,
                    sort_order,
                    completed_at,
                    deleted_at,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :parent_goal_id,
                    :goal_type,
                    :title,
                    :behavior_when,
                    :behavior_where,
                    :behavior_how,
                    :period_start_date,
                    :period_end_date,
                    :status,
                    :sort_order,
                    :completed_at,
                    NULL,
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'parent_goal_id' => $data['parentGoalId'],
                'goal_type' => $data['goalType'],
                'title' => $data['title'],
                'behavior_when' => $data['behaviorWhen'],
                'behavior_where' => $data['behaviorWhere'],
                'behavior_how' => $data['behaviorHow'],
                'period_start_date' => $data['periodStartDate'],
                'period_end_date' => $data['periodEndDate'],
                'status' => $data['status'],
                'sort_order' => $this->nextSortOrder($userId, (string) $data['goalType']),
                'completed_at' => $data['status'] === 'completed' ? $this->currentTimestampExpressionValue() : null,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Throwable $exception) {
            error_log('[goal] create failed: ' . $exception->getMessage());
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $userId, int $goalId, array $data): bool
    {
        if ($this->findActive($userId, $goalId) === null) {
            return false;
        }

        $sql = 'UPDATE goals
                SET parent_goal_id = :parent_goal_id,
                    goal_type = :goal_type,
                    title = :title,
                    behavior_when = :behavior_when,
                    behavior_where = :behavior_where,
                    behavior_how = :behavior_how,
                    period_start_date = :period_start_date,
                    period_end_date = :period_end_date,
                    status = :status,
                    completed_at = :completed_at,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'parent_goal_id' => $data['parentGoalId'],
            'goal_type' => $data['goalType'],
            'title' => $data['title'],
            'behavior_when' => $data['behaviorWhen'],
            'behavior_where' => $data['behaviorWhere'],
            'behavior_how' => $data['behaviorHow'],
            'period_start_date' => $data['periodStartDate'],
            'period_end_date' => $data['periodEndDate'],
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'completed' ? $this->currentTimestampExpressionValue() : null,
            'id' => $goalId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function softDelete(int $userId, int $goalId): bool
    {
        if ($this->findActive($userId, $goalId) === null) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $clearChildren = $this->db->prepare(
                'UPDATE goals
                 SET parent_goal_id = NULL,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id
                    AND parent_goal_id = :goal_id
                    AND deleted_at IS NULL'
            );
            $clearChildren->execute([
                'user_id' => $userId,
                'goal_id' => $goalId,
            ]);

            $clearPlanTemplates = $this->db->prepare(
                'UPDATE plan_templates
                 SET goal_id = NULL,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id
                    AND goal_id = :goal_id
                    AND deleted_at IS NULL'
            );
            $clearPlanTemplates->execute([
                'user_id' => $userId,
                'goal_id' => $goalId,
            ]);

            $clearRoutines = $this->db->prepare(
                'UPDATE routines
                 SET goal_id = NULL,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id
                    AND goal_id = :goal_id
                    AND deleted_at IS NULL'
            );
            $clearRoutines->execute([
                'user_id' => $userId,
                'goal_id' => $goalId,
            ]);

            $delete = $this->db->prepare(
                'UPDATE goals
                 SET deleted_at = CURRENT_TIMESTAMP,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL'
            );
            $delete->execute([
                'id' => $goalId,
                'user_id' => $userId,
            ]);

            $this->db->commit();

            return $delete->rowCount() > 0;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[goal] delete failed: ' . $exception->getMessage());
            return false;
        }
    }

    private function nextSortOrder(int $userId, string $goalType): int
    {
        $sql = 'SELECT COALESCE(MAX(sort_order), 0) + 1
                FROM goals
                WHERE user_id = :user_id
                    AND goal_type = :goal_type
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'goal_type' => $goalType,
        ]);

        return (int) $stmt->fetchColumn();
    }

    private function goalTypeOrderExpression(string $column): string
    {
        return "CASE " . $column . "
                    WHEN 'bucket' THEN 1
                    WHEN 'yearly' THEN 2
                    WHEN 'half_year' THEN 3
                    WHEN 'quarterly' THEN 4
                    WHEN 'monthly' THEN 5
                    ELSE 9
                END";
    }

    private function currentTimestampExpressionValue(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** @param array<int, int> $values @param array<string, mixed> $params */
    private function buildInPlaceholders(string $prefix, array $values, array &$params): string
    {
        $params = [];
        $placeholders = [];

        foreach (array_values(array_unique(array_map('intval', $values))) as $index => $value) {
            $key = $prefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $value;
        }

        return implode(', ', $placeholders);
    }
}
