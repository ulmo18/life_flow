<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SearchRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function search(int $userId, string $query, ?string $from, ?string $to): array
    {
        $term = '%' . $query . '%';
        $results = [];
        $definitions = [
            ['goal', 'SELECT id, title, behavior_how AS body, behavior_when AS body_alt, behavior_where AS body_alt2, updated_at AS occurred_at, NULL AS record_date FROM goals WHERE user_id = :user_id AND deleted_at IS NULL AND (title LIKE :term1 OR behavior_when LIKE :term2 OR behavior_where LIKE :term3 OR behavior_how LIKE :term4)', 'updated_at', 4],
            ['routine', 'SELECT id, name AS title, NULL AS body, updated_at AS occurred_at, start_date AS record_date FROM routines WHERE user_id = :user_id AND deleted_at IS NULL AND name LIKE :term1', 'updated_at', 1],
            ['plan', 'SELECT id, title, NULL AS body, updated_at AS occurred_at, NULL AS record_date FROM plan_block_templates WHERE user_id = :user_id AND deleted_at IS NULL AND title LIKE :term1', 'updated_at', 1],
            ['plan', 'SELECT id, name AS title, NULL AS body, updated_at AS occurred_at, NULL AS record_date FROM plan_groups WHERE user_id = :user_id AND deleted_at IS NULL AND name LIKE :term1', 'updated_at', 1],
            ['todo', 'SELECT dpi.id, dpi.title, NULL AS body, dpi.updated_at AS occurred_at, cd.calendar_date AS record_date FROM daily_plan_items dpi INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id INNER JOIN calendar_days cd ON cd.id = dp.calendar_day_id WHERE dp.user_id = :user_id AND dpi.deleted_at IS NULL AND dpi.title LIKE :term1', 'cd.calendar_date', 1],
            ['schedule', 'SELECT ce.id, ce.title, ce.memo AS body, ce.updated_at AS occurred_at, cd.calendar_date AS record_date FROM calendar_events ce INNER JOIN calendar_days cd ON cd.id = ce.calendar_day_id WHERE ce.user_id = :user_id AND ce.deleted_at IS NULL AND (ce.title LIKE :term1 OR ce.memo LIKE :term2)', 'cd.calendar_date', 2],
            ['memo', "SELECT id, '메모' AS title, content AS body, updated_at AS occurred_at, NULL AS record_date FROM notes WHERE user_id = :user_id AND deleted_at IS NULL AND content LIKE :term1", 'updated_at', 1],
            ['retrospect', "SELECT id, '발행 회고' AS title, today_review AS body, today_thoughts AS body_alt, tomorrow_plan AS body_alt2, submitted_at AS occurred_at, report_date AS record_date FROM retrospect_reports WHERE user_id = :user_id AND status = 'submitted' AND (today_review LIKE :term1 OR today_thoughts LIKE :term2 OR tomorrow_plan LIKE :term3)", 'report_date', 3],
            ['wellness', "SELECT id, entry_type AS title, COALESCE(detail, note) AS body, updated_at AS occurred_at, entry_date AS record_date FROM wellness_entries WHERE user_id = :user_id AND (detail LIKE :term1 OR note LIKE :term2 OR CASE entry_type WHEN 'sleep' THEN '수면' WHEN 'meal' THEN '식사' WHEN 'snack' THEN '간식' WHEN 'exercise' THEN '운동' WHEN 'rest' THEN '휴식' END LIKE :term3)", 'entry_date', 3],
        ];

        foreach ($definitions as [$type, $sql, $dateColumn, $termCount]) {
            $params = ['user_id' => $userId];
            for ($index = 1; $index <= $termCount; $index++) $params['term' . $index] = $term;
            if ($from !== null) { $sql .= ' AND ' . $dateColumn . ' >= :date_from'; $params['date_from'] = $from; }
            if ($to !== null) { $sql .= ' AND ' . $dateColumn . ' <= :date_to'; $params['date_to'] = $to . ' 23:59:59'; }
            $sql .= ' ORDER BY ' . $dateColumn . ' DESC LIMIT 20';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $row['type'] = $type;
                $results[] = $row;
            }
        }

        return $results;
    }
}
