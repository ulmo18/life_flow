<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class RoutineRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveWithDoneCounts(int $userId, string $today): array
    {
        $sql = 'SELECT
                    r.id,
                    r.user_id,
                    r.goal_id,
                    r.name,
                    r.start_date,
                    r.duration_days,
                    r.reminder_enabled,
                    r.reminder_time,
                    r.created_at,
                    r.updated_at,
                    g.title AS goal_title,
                    g.goal_type AS goal_type,
                    COALESCE(SUM(CASE WHEN rl.is_done = 1 THEN 1 ELSE 0 END), 0) AS done_count,
                    today_log.is_done AS today_state
                FROM routines r
                LEFT JOIN goals g ON g.id = r.goal_id
                    AND g.user_id = r.user_id
                    AND g.deleted_at IS NULL
                LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                    AND rl.user_id = r.user_id
                    AND rl.log_date >= r.start_date
                    AND rl.log_date <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
                LEFT JOIN routine_logs today_log ON today_log.routine_id = r.id
                    AND today_log.user_id = r.user_id
                    AND today_log.log_date = :today
                WHERE r.user_id = :user_id
                    AND r.deleted_at IS NULL
                    AND :target_date_start >= r.start_date
                    AND :target_date_end <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
                GROUP BY
                    r.id,
                    r.user_id,
                    r.goal_id,
                    r.name,
                    r.start_date,
                    r.duration_days,
                    r.reminder_enabled,
                    r.reminder_time,
                    r.created_at,
                    r.updated_at,
                    g.title,
                    g.goal_type,
                    today_log.is_done
                ORDER BY r.updated_at DESC, r.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'today' => $today,
            'target_date_start' => $today,
            'target_date_end' => $today,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActiveWithDoneCount(int $userId, int $routineId, string $today): ?array
    {
        $sql = 'SELECT
                    r.id,
                    r.user_id,
                    r.goal_id,
                    r.name,
                    r.start_date,
                    r.duration_days,
                    r.reminder_enabled,
                    r.reminder_time,
                    r.created_at,
                    r.updated_at,
                    g.title AS goal_title,
                    g.goal_type AS goal_type,
                    COALESCE(SUM(CASE WHEN rl.is_done = 1 THEN 1 ELSE 0 END), 0) AS done_count,
                    today_log.is_done AS today_state
                FROM routines r
                LEFT JOIN goals g ON g.id = r.goal_id
                    AND g.user_id = r.user_id
                    AND g.deleted_at IS NULL
                LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                    AND rl.user_id = r.user_id
                    AND rl.log_date >= r.start_date
                    AND rl.log_date <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
                LEFT JOIN routine_logs today_log ON today_log.routine_id = r.id
                    AND today_log.user_id = r.user_id
                    AND today_log.log_date = :today
                WHERE r.id = :id
                    AND r.user_id = :user_id
                    AND r.deleted_at IS NULL
                GROUP BY
                    r.id,
                    r.user_id,
                    r.goal_id,
                    r.name,
                    r.start_date,
                    r.duration_days,
                    r.reminder_enabled,
                    r.reminder_time,
                    r.created_at,
                    r.updated_at,
                    g.title,
                    g.goal_type,
                    today_log.is_done
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'today' => $today,
            'id' => $routineId,
            'user_id' => $userId,
        ]);

        $routine = $stmt->fetch();

        return $routine !== false ? $routine : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveForDate(int $userId, string $date): array
    {
        $sql = 'SELECT
                    r.id,
                    r.name,
                    r.start_date,
                    r.duration_days,
                    rl.is_done AS state
                FROM routines r
                LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                    AND rl.user_id = r.user_id
                    AND rl.log_date = :log_date
                WHERE r.user_id = :user_id
                    AND r.deleted_at IS NULL
                    AND :target_date_start >= r.start_date
                    AND :target_date_end <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
                ORDER BY r.created_at ASC, r.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'log_date' => $date,
            'target_date_start' => $date,
            'target_date_end' => $date,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActive(int $userId, int $routineId): ?array
    {
        $sql = 'SELECT id, user_id, goal_id, name, start_date, duration_days, reminder_enabled, reminder_time, created_at, updated_at
                FROM routines
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $routineId,
            'user_id' => $userId,
        ]);

        $routine = $stmt->fetch();

        return $routine !== false ? $routine : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listNotificationEnabled(int $userId): array
    {
        $sql = 'SELECT id, name, start_date, duration_days, reminder_enabled, reminder_time
                FROM routines
                WHERE user_id = :user_id
                    AND reminder_enabled = 1
                    AND deleted_at IS NULL
                ORDER BY updated_at DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function create(int $userId, ?int $goalId, string $name, string $startDate, int $durationDays, bool $reminderEnabled, ?string $reminderTime): ?int
    {
        $sql = 'INSERT INTO routines (
                    user_id, goal_id, name, start_date, duration_days, reminder_enabled, reminder_time, deleted_at, created_at, updated_at
                ) VALUES (
                    :user_id, :goal_id, :name, :start_date, :duration_days, :reminder_enabled, :reminder_time,
                    NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'goal_id' => $goalId,
                'name' => $name,
                'start_date' => $startDate,
                'duration_days' => $durationDays,
                'reminder_enabled' => $reminderEnabled ? 1 : 0,
                'reminder_time' => $reminderTime,
            ]);

            return (int) $this->db->lastInsertId();
        } catch (Throwable $exception) {
            error_log('[routine] create failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function update(int $userId, int $routineId, ?int $goalId, string $name, string $startDate, int $durationDays, bool $reminderEnabled, ?string $reminderTime): bool
    {
        if ($this->findActive($userId, $routineId) === null) {
            return false;
        }

        $sql = 'UPDATE routines
                SET name = :name,
                    goal_id = :goal_id,
                    start_date = :start_date,
                    duration_days = :duration_days,
                    reminder_enabled = :reminder_enabled,
                    reminder_time = :reminder_time,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'goal_id' => $goalId,
            'start_date' => $startDate,
            'duration_days' => $durationDays,
            'reminder_enabled' => $reminderEnabled ? 1 : 0,
            'reminder_time' => $reminderTime,
            'id' => $routineId,
            'user_id' => $userId,
        ]);

        return true;
    }

    public function softDelete(int $userId, int $routineId): bool
    {
        $sql = 'UPDATE routines
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $routineId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function cycleLogState(int $userId, int $routineId, string $date): ?string
    {
        if (!$this->isRoutineActiveOnDate($userId, $routineId, $date)) {
            return null;
        }

        $currentState = $this->findLogState($userId, $routineId, $date);
        if ($currentState === null) {
            $this->upsertLogState($userId, $routineId, $date, true);
            return 'O';
        }

        if ($currentState === 1) {
            $this->upsertLogState($userId, $routineId, $date, false);
            return 'X';
        }

        $this->deleteLogState($userId, $routineId, $date);
        return '';
    }

    public function markDoneForDate(int $userId, int $routineId, string $date): bool
    {
        if (!$this->isRoutineActiveOnDate($userId, $routineId, $date)) {
            return false;
        }

        $this->upsertLogState($userId, $routineId, $date, true);
        return true;
    }

    /** @return array<string, int> */
    public function listLogStates(int $userId, int $routineId, string $startDate, string $endDate): array
    {
        $sql = 'SELECT log_date, is_done
                FROM routine_logs
                WHERE routine_id = :routine_id
                    AND user_id = :user_id
                    AND log_date >= :start_date
                    AND log_date <= :end_date
                ORDER BY log_date ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'routine_id' => $routineId,
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $states = [];
        foreach ($stmt->fetchAll() as $row) {
            $states[(string) $row['log_date']] = (int) $row['is_done'];
        }

        return $states;
    }

    private function upsertLogState(int $userId, int $routineId, string $date, bool $isDone): void
    {
        if ($this->driver() === 'mysql') {
            $sql = 'INSERT INTO routine_logs (routine_id, user_id, log_date, is_done, created_at, updated_at)
                    VALUES (:routine_id, :user_id, :log_date, :is_done, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                        is_done = VALUES(is_done),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO routine_logs (routine_id, user_id, log_date, is_done, created_at, updated_at)
                    VALUES (:routine_id, :user_id, :log_date, :is_done, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ON CONFLICT(routine_id, log_date) DO UPDATE SET
                        is_done = excluded.is_done,
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'routine_id' => $routineId,
            'user_id' => $userId,
            'log_date' => $date,
            'is_done' => $isDone ? 1 : 0,
        ]);
    }

    private function isRoutineActiveOnDate(int $userId, int $routineId, string $date): bool
    {
        $sql = 'SELECT id
                FROM routines
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL
                    AND :target_date_start >= start_date
                    AND :target_date_end <= ' . $this->dateAddExpression('start_date', 'duration_days - 1') . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $routineId,
            'user_id' => $userId,
            'target_date_start' => $date,
            'target_date_end' => $date,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function findLogState(int $userId, int $routineId, string $date): ?int
    {
        $sql = 'SELECT is_done
                FROM routine_logs
                WHERE routine_id = :routine_id
                    AND user_id = :user_id
                    AND log_date = :log_date
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'routine_id' => $routineId,
            'user_id' => $userId,
            'log_date' => $date,
        ]);

        $state = $stmt->fetchColumn();

        return $state === false ? null : (int) $state;
    }

    private function deleteLogState(int $userId, int $routineId, string $date): void
    {
        $sql = 'DELETE FROM routine_logs
                WHERE routine_id = :routine_id
                    AND user_id = :user_id
                    AND log_date = :log_date';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'routine_id' => $routineId,
            'user_id' => $userId,
            'log_date' => $date,
        ]);
    }

    private function dateAddExpression(string $dateColumn, string $daysExpression): string
    {
        if ($this->driver() === 'mysql') {
            return 'DATE_ADD(' . $dateColumn . ', INTERVAL (' . $daysExpression . ') DAY)';
        }

        return "date(" . $dateColumn . ", '+' || (" . $daysExpression . ") || ' days')";
    }

    private function driver(): string
    {
        return Database::configuredDriver();
    }
}
