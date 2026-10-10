<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class WellnessRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listForDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, calendar_event_id, entry_date, entry_type, occurred_time, start_time, end_time, detail, note, rating
             FROM wellness_entries
             WHERE user_id = :user_id AND entry_date = :entry_date
             ORDER BY COALESCE(occurred_time, start_time, end_time, :fallback_time) ASC, id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'entry_date' => $date, 'fallback_time' => '23:59:59']);

        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO wellness_entries (
                user_id, entry_date, entry_type, occurred_time, start_time, end_time,
                detail, note, rating, created_at, updated_at
             ) VALUES (
                :user_id, :entry_date, :entry_type, :occurred_time, :start_time, :end_time,
                :detail, :note, :rating, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
             )'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'entry_date' => $data['date'],
            'entry_type' => $data['type'],
            'occurred_time' => $data['occurredTime'],
            'start_time' => $data['startTime'],
            'end_time' => $data['endTime'],
            'detail' => $data['detail'] !== '' ? $data['detail'] : null,
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'rating' => $data['rating'],
        ]);
    }

    /** @param array<string, mixed>|null $data */
    public function syncForEvent(int $userId, int $eventId, string $date, ?array $data): bool
    {
        $existing = $this->db->prepare(
            'SELECT id FROM wellness_entries
             WHERE user_id = :user_id AND calendar_event_id = :calendar_event_id
             LIMIT 1'
        );
        $existing->execute(['user_id' => $userId, 'calendar_event_id' => $eventId]);
        $entryId = $existing->fetchColumn();

        if ($data === null) {
            if ($entryId === false) {
                return true;
            }
            $delete = $this->db->prepare(
                'DELETE FROM wellness_entries WHERE id = :id AND user_id = :user_id'
            );
            return $delete->execute(['id' => (int) $entryId, 'user_id' => $userId]);
        }

        $params = [
            'user_id' => $userId,
            'calendar_event_id' => $eventId,
            'entry_date' => $date,
            'entry_type' => $data['type'],
            'detail' => $data['detail'] !== '' ? $data['detail'] : null,
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'rating' => $data['rating'],
        ];

        if ($entryId !== false) {
            $update = $this->db->prepare(
                'UPDATE wellness_entries
                 SET entry_date = :entry_date, entry_type = :entry_type,
                     occurred_time = NULL, start_time = NULL, end_time = NULL,
                     detail = :detail, note = :note, rating = :rating, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id AND calendar_event_id = :calendar_event_id'
            );
            return $update->execute($params + ['id' => (int) $entryId]);
        }

        $insert = $this->db->prepare(
            'INSERT INTO wellness_entries (
                user_id, calendar_event_id, entry_date, entry_type, occurred_time, start_time, end_time,
                detail, note, rating, created_at, updated_at
             ) VALUES (
                :user_id, :calendar_event_id, :entry_date, :entry_type, NULL, NULL, NULL,
                :detail, :note, :rating, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
             )'
        );
        return $insert->execute($params);
    }

    public function deleteForEvent(int $userId, int $eventId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM wellness_entries WHERE user_id = :user_id AND calendar_event_id = :calendar_event_id'
        );
        return $stmt->execute(['user_id' => $userId, 'calendar_event_id' => $eventId]);
    }
}
