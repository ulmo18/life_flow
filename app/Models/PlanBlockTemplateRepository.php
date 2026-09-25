<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class PlanBlockTemplateRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                pbt.id,
                pbt.title,
                pbt.duration_index,
                pbt.importance,
                pbt.goal_id,
                g.title AS goal_title,
                pbt.created_at,
                pbt.updated_at
             FROM plan_block_templates pbt
             LEFT JOIN goals g ON g.id = pbt.goal_id
                AND g.user_id = pbt.user_id
                AND g.deleted_at IS NULL
             WHERE pbt.user_id = :user_id
                AND pbt.deleted_at IS NULL
             ORDER BY pbt.updated_at DESC, pbt.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function create(int $userId, string $title, int $durationIndex, string $importance, ?int $goalId): ?int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO plan_block_templates (
                user_id, goal_id, title, duration_index, importance, deleted_at, created_at, updated_at
             ) VALUES (
                :user_id, :goal_id, :title, :duration_index, :importance, NULL,
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
             )'
        );
        $stmt->execute([
            'user_id' => $userId,
            'goal_id' => $goalId,
            'title' => $title,
            'duration_index' => $durationIndex,
            'importance' => $importance,
        ]);

        $id = (int) $this->db->lastInsertId();

        return $id > 0 ? $id : null;
    }

    public function update(
        int $userId,
        int $templateId,
        string $title,
        int $durationIndex,
        string $importance,
        ?int $goalId
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE plan_block_templates
             SET title = :title,
                 duration_index = :duration_index,
                 importance = :importance,
                 goal_id = :goal_id,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
                AND user_id = :user_id
                AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $templateId,
            'user_id' => $userId,
            'title' => $title,
            'duration_index' => $durationIndex,
            'importance' => $importance,
            'goal_id' => $goalId,
        ]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        $exists = $this->db->prepare(
            'SELECT 1
             FROM plan_block_templates
             WHERE id = :id
                AND user_id = :user_id
                AND deleted_at IS NULL
             LIMIT 1'
        );
        $exists->execute(['id' => $templateId, 'user_id' => $userId]);

        return $exists->fetchColumn() !== false;
    }

    public function softDelete(int $userId, int $templateId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE plan_block_templates
             SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
                AND user_id = :user_id
                AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $templateId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }
}
