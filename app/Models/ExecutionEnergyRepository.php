<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ExecutionEnergyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findProfile(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM execution_energy_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function saveProfile(int $userId, array $data): void
    {
        $existing = $this->findProfile($userId);
        if ($existing === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO execution_energy_profiles (
                    user_id, base_capacity, effective_from, body_state_note, mood_state_note, stress_state_note,
                    injury_note, injury_until, inbody_note, created_at, updated_at
                ) VALUES (
                    :user_id, :base_capacity, :effective_from, :body_state_note, :mood_state_note, :stress_state_note,
                    :injury_note, :injury_until, :inbody_note, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )'
            );
        } else {
            $stmt = $this->db->prepare(
                'UPDATE execution_energy_profiles
                 SET base_capacity = :base_capacity, effective_from = :effective_from,
                     body_state_note = :body_state_note, mood_state_note = :mood_state_note,
                     stress_state_note = :stress_state_note, injury_note = :injury_note,
                     injury_until = :injury_until, inbody_note = :inbody_note, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id'
            );
        }
        $stmt->execute($data + ['user_id' => $userId]);
    }

    public function refreshFutureDaySnapshots(int $userId, string $effectiveFrom, int $baseCapacity): void
    {
        $stmt = $this->db->prepare(
            'UPDATE execution_energy_days
             SET base_capacity_snapshot = :base_capacity, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND energy_date >= :effective_from'
        );
        $stmt->execute([
            'base_capacity' => $baseCapacity,
            'user_id' => $userId,
            'effective_from' => $effectiveFrom,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function findDay(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM execution_energy_days WHERE user_id = :user_id AND energy_date = :energy_date LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'energy_date' => $date]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function createDay(int $userId, string $date, int $baseCapacity): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO execution_energy_days (user_id, energy_date, base_capacity_snapshot, created_at, updated_at)
             VALUES (:user_id, :energy_date, :base_capacity_snapshot, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['user_id' => $userId, 'energy_date' => $date, 'base_capacity_snapshot' => $baseCapacity]);

        return $this->findDay($userId, $date) ?? [];
    }

    /** @param array<string, mixed> $data */
    public function saveMorningCheck(int $userId, string $date, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE execution_energy_days
             SET sleep_state = :sleep_state, fatigue_state = :fatigue_state, mood_state = :mood_state,
                 stress_state = :stress_state, burden_feedback = :burden_feedback,
                 morning_adjustment = :morning_adjustment, updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id AND energy_date = :energy_date'
        );
        $stmt->execute($data + ['user_id' => $userId, 'energy_date' => $date]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listEventActivities(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT ce.id, ce.title, ce.end_index, ce.start_index, ce.daily_plan_item_id,
                    ees.group_name_snapshot, ees.effect_type, ees.energy_value, ees.is_override
             FROM calendar_events ce
             INNER JOIN calendar_days cd ON cd.id = ce.calendar_day_id
             LEFT JOIN execution_energy_event_settings ees ON ees.calendar_event_id = ce.id
             WHERE ce.user_id = :user_id AND cd.calendar_date = :energy_date
               AND ce.deleted_at IS NULL AND ce.schedule_type = \'timed\'
             ORDER BY ce.end_index ASC, ce.id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'energy_date' => $date]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listRemainingPlanItems(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT dpi.id, dpi.title, dpi.start_index, dpi.end_index
             FROM daily_plan_items dpi
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             INNER JOIN calendar_days cd ON cd.id = dp.calendar_day_id
             WHERE dp.user_id = :user_id AND cd.calendar_date = :energy_date AND dpi.deleted_at IS NULL
               AND NOT EXISTS (
                    SELECT 1 FROM calendar_events ce
                    WHERE ce.daily_plan_item_id = dpi.id AND ce.user_id = dp.user_id AND ce.deleted_at IS NULL
               )
             ORDER BY CASE WHEN dpi.start_index IS NULL THEN 1 ELSE 0 END, dpi.start_index ASC, dpi.id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'energy_date' => $date]);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function upsertEventSetting(int $userId, int $eventId, array $data): void
    {
        $exists = $this->db->prepare('SELECT id FROM calendar_events WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
        $exists->execute(['id' => $eventId, 'user_id' => $userId]);
        if ($exists->fetchColumn() === false) {
            return;
        }

        $delete = $this->db->prepare('DELETE FROM execution_energy_event_settings WHERE calendar_event_id = :event_id');
        $delete->execute(['event_id' => $eventId]);
        $stmt = $this->db->prepare(
            'INSERT INTO execution_energy_event_settings (
                calendar_event_id, user_id, group_id_snapshot, group_name_snapshot, effect_type, energy_value,
                is_override, created_at, updated_at
             ) VALUES (
                :calendar_event_id, :user_id, :group_id_snapshot, :group_name_snapshot, :effect_type, :energy_value,
                :is_override, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
             )'
        );
        $stmt->execute($data + ['calendar_event_id' => $eventId, 'user_id' => $userId]);
    }

    public function deleteEventSetting(int $userId, int $eventId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM execution_energy_event_settings
             WHERE calendar_event_id = :event_id AND user_id = :user_id'
        );
        $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
    }

    public function claimNotice(int $userId, string $date, int $level): bool
    {
        $params = ['user_id' => $userId, 'energy_date' => $date, 'notice_level' => $level];
        $exists = $this->db->prepare(
            'SELECT 1 FROM execution_energy_notices
             WHERE user_id = :user_id AND energy_date = :energy_date AND notice_level = :notice_level
             LIMIT 1'
        );
        $exists->execute($params);
        if ($exists->fetchColumn() !== false) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO execution_energy_notices (user_id, energy_date, notice_level, created_at)
                 VALUES (:user_id, :energy_date, :notice_level, CURRENT_TIMESTAMP)'
            );
            $stmt->execute($params);
        } catch (\PDOException) {
            return false;
        }

        return true;
    }

}
