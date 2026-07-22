<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class RetrospectRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed>|null */
    public function findReport(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT *
             FROM retrospect_reports
             WHERE user_id = :user_id
                AND report_date = :report_date
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'report_date' => $date,
        ]);

        $report = $stmt->fetch();

        return $report !== false ? $report : null;
    }

    /** @return array<string, mixed>|null */
    public function findLatestSubmittedReport(int $userId, ?string $maxDate = null): ?array
    {
        $where = 'WHERE user_id = :user_id AND status = :status';
        $params = [
            'user_id' => $userId,
            'status' => 'submitted',
        ];

        if ($maxDate !== null) {
            $where .= ' AND report_date <= :max_date';
            $params['max_date'] = $maxDate;
        }

        $stmt = $this->db->prepare(
            'SELECT *
             FROM retrospect_reports
             ' . $where . '
             ORDER BY report_date DESC, submitted_at DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute($params);
        $report = $stmt->fetch();

        return $report !== false ? $report : null;
    }

    /** @return array<string, mixed> */
    public function getSettings(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT auto_publish_enabled, auto_publish_time
             FROM retrospect_settings
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $settings = $stmt->fetch();

        if ($settings !== false) {
            return $settings;
        }

        $sql = 'INSERT INTO retrospect_settings (
                    user_id, auto_publish_enabled, auto_publish_time, created_at, updated_at
                ) VALUES (
                    :user_id, 0, :auto_publish_time, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'auto_publish_time' => '22:00',
        ]);

        return [
            'auto_publish_enabled' => 0,
            'auto_publish_time' => '22:00',
        ];
    }

    public function updateSettings(int $userId, bool $enabled, string $time): void
    {
        if ($this->driver() === 'mysql') {
            $sql = 'INSERT INTO retrospect_settings (
                        user_id, auto_publish_enabled, auto_publish_time, created_at, updated_at
                    ) VALUES (
                        :user_id, :enabled, :time, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON DUPLICATE KEY UPDATE
                        auto_publish_enabled = VALUES(auto_publish_enabled),
                        auto_publish_time = VALUES(auto_publish_time),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO retrospect_settings (
                        user_id, auto_publish_enabled, auto_publish_time, created_at, updated_at
                    ) VALUES (
                        :user_id, :enabled, :time, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(user_id) DO UPDATE SET
                        auto_publish_enabled = excluded.auto_publish_enabled,
                        auto_publish_time = excluded.auto_publish_time,
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'enabled' => $enabled ? 1 : 0,
            'time' => $time,
        ]);
    }

    /** @param array{today_review: string, today_thoughts: string, tomorrow_plan: string} $texts */
    public function saveDraft(int $userId, string $date, array $texts): void
    {
        if ($this->driver() === 'mysql') {
            $sql = 'INSERT INTO retrospect_reports (
                        user_id, report_date, status,
                        today_review, today_thoughts, tomorrow_plan,
                        created_at, updated_at
                    ) VALUES (
                        :user_id, :report_date, :status,
                        :today_review, :today_thoughts, :tomorrow_plan,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON DUPLICATE KEY UPDATE
                        today_review = VALUES(today_review),
                        today_thoughts = VALUES(today_thoughts),
                        tomorrow_plan = VALUES(tomorrow_plan),
                        status = IF(status = \'submitted\', \'submitted\', VALUES(status)),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO retrospect_reports (
                        user_id, report_date, status,
                        today_review, today_thoughts, tomorrow_plan,
                        created_at, updated_at
                    ) VALUES (
                        :user_id, :report_date, :status,
                        :today_review, :today_thoughts, :tomorrow_plan,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(user_id, report_date) DO UPDATE SET
                        today_review = excluded.today_review,
                        today_thoughts = excluded.today_thoughts,
                        tomorrow_plan = excluded.tomorrow_plan,
                        status = CASE
                            WHEN retrospect_reports.status = \'submitted\' THEN \'submitted\'
                            ELSE excluded.status
                        END,
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'report_date' => $date,
            'status' => 'draft',
            'today_review' => $texts['today_review'],
            'today_thoughts' => $texts['today_thoughts'],
            'tomorrow_plan' => $texts['tomorrow_plan'],
        ]);
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $planItems
     * @param array<int, array<string, mixed>> $actualItems
     * @param array<int, array<string, mixed>> $routineItems
     * @param array{today_review: string, today_thoughts: string, tomorrow_plan: string} $texts
     */
    public function publishReport(
        int $userId,
        string $date,
        array $summary,
        array $planItems,
        array $actualItems,
        array $routineItems,
        array $texts
    ): bool {
        try {
            $this->db->beginTransaction();

            $reportId = $this->upsertReportForPublish($userId, $date, $summary, $texts);
            $this->deleteSnapshotItems($reportId);
            $this->insertPlanItems($reportId, $planItems);
            $this->insertActualItems($reportId, $actualItems);
            $this->insertRoutineItems($reportId, $routineItems);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[retrospect] publish failed: ' . $exception->getMessage());
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function listPlanItems(int $reportId): array
    {
        return $this->fetchItems(
            'SELECT *
             FROM retrospect_report_plan_items
             WHERE report_id = :report_id
             ORDER BY sort_order ASC, start_index ASC, id ASC',
            $reportId
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listActualItems(int $reportId, string $sort): array
    {
        $orderBy = $sort === 'tag'
            ? 'tag_name_snapshot ASC, start_index ASC, id ASC'
            : 'start_index ASC, end_index ASC, id ASC';

        return $this->fetchItems(
            'SELECT *
             FROM retrospect_report_actual_items
             WHERE report_id = :report_id
             ORDER BY ' . $orderBy,
            $reportId
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listRoutineItems(int $reportId): array
    {
        return $this->fetchItems(
            'SELECT *
             FROM retrospect_report_routine_items
             WHERE report_id = :report_id
             ORDER BY sort_order ASC, id ASC',
            $reportId
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listRecentPublishedReports(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, report_date, plan_achievement_rate, routine_achievement_rate, submitted_at
             FROM retrospect_reports
             WHERE user_id = :user_id
                AND status = :status
             ORDER BY report_date DESC
             LIMIT ' . max(1, min(30, $limit))
        );
        $stmt->execute([
            'user_id' => $userId,
            'status' => 'submitted',
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findCalendarDay(int $userId, string $date): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, calendar_date, plan_group_id
             FROM calendar_days
             WHERE user_id = :user_id
                AND calendar_date = :calendar_date
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'calendar_date' => $date,
        ]);
        $day = $stmt->fetch();

        return $day !== false ? $day : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listPlanBlocksForDay(int $userId, string $date): array
    {
        $day = $this->findCalendarDay($userId, $date);
        $planGroupId = $day['plan_group_id'] ?? null;
        if ($planGroupId === null) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT
                pb.id AS plan_block_id,
                pb.plan_group_id,
                pb.plan_template_id,
                pb.start_index,
                pb.end_index,
                pb.sort_order,
                pt.title,
                pt.importance
             FROM plan_blocks pb
             INNER JOIN plan_groups pg ON pg.id = pb.plan_group_id
             INNER JOIN plan_templates pt ON pt.id = pb.plan_template_id
             WHERE pg.id = :plan_group_id
                AND pg.user_id = :user_id
                AND pg.deleted_at IS NULL
             ORDER BY pb.sort_order ASC, pb.start_index ASC'
        );
        $stmt->execute([
            'plan_group_id' => (int) $planGroupId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActualEventsForDay(int $userId, string $date, string $sort = 'time'): array
    {
        $day = $this->findCalendarDay($userId, $date);
        if ($day === null) {
            return [];
        }

        $orderBy = $sort === 'tag'
            ? 'ct.sort_order ASC, ct.name ASC, ce.start_index ASC, ce.id ASC'
            : 'ce.start_index ASC, ce.end_index ASC, ce.id ASC';

        $stmt = $this->db->prepare(
            'SELECT
                ce.id,
                ce.calendar_day_id,
                ce.title,
                ce.memo,
                ce.start_index,
                ce.end_index,
                ce.plan_template_id,
                ce.calendar_tag_id,
                pt.importance AS plan_importance,
                ct.name AS tag_name,
                ct.color_hex AS tag_color,
                ct.sort_order AS tag_sort_order
             FROM calendar_events ce
             LEFT JOIN plan_templates pt ON pt.id = ce.plan_template_id
             LEFT JOIN calendar_tags ct ON ct.id = ce.calendar_tag_id
                AND ct.deleted_at IS NULL
             WHERE ce.user_id = :user_id
                AND ce.calendar_day_id = :calendar_day_id
                AND ce.schedule_type = \'timed\'
                AND ce.deleted_at IS NULL
             ORDER BY ' . $orderBy
        );
        $stmt->execute([
            'user_id' => $userId,
            'calendar_day_id' => (int) $day['id'],
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listRoutinesForDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                r.id,
                r.name,
                rl.is_done AS state
             FROM routines r
             LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                AND rl.user_id = r.user_id
                AND rl.log_date = :log_date
             WHERE r.user_id = :user_id
                AND r.deleted_at IS NULL
                AND (r.status = :active_status OR (r.ended_at IS NOT NULL AND :target_date_status <= r.ended_at))
                AND :target_date_start >= r.start_date
                AND :target_date_end <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
             ORDER BY r.created_at ASC, r.id ASC'
        );
        $stmt->execute([
            'log_date' => $date,
            'target_date_start' => $date,
            'target_date_end' => $date,
            'target_date_status' => $date,
            'user_id' => $userId,
            'active_status' => 'active',
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listGoalReviewGoals(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT g.id, g.goal_type, g.title, g.period_start_date, g.period_end_date, g.status,
                (SELECT COUNT(*) FROM plan_templates pt
                 WHERE pt.goal_id = g.id AND pt.user_id = g.user_id AND pt.deleted_at IS NULL) AS linked_plan_count
             FROM goals g
             WHERE g.user_id = :user_id AND g.deleted_at IS NULL
             ORDER BY g.updated_at DESC, g.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listGoalPlanProgress(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                pt.goal_id,
                COUNT(*) AS plan_count,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM calendar_events ce
                    WHERE ce.calendar_day_id = cd.id
                        AND ce.plan_template_id = pt.id
                        AND ce.user_id = cd.user_id
                        AND ce.deleted_at IS NULL
                ) THEN 1 ELSE 0 END) AS executed_plan_count,
                SUM((
                    SELECT COUNT(*) FROM calendar_events ce_count
                    WHERE ce_count.calendar_day_id = cd.id
                        AND ce_count.plan_template_id = pt.id
                        AND ce_count.user_id = cd.user_id
                        AND ce_count.deleted_at IS NULL
                )) AS actual_event_count
             FROM calendar_days cd
             INNER JOIN plan_groups pg ON pg.id = cd.plan_group_id AND pg.user_id = cd.user_id AND pg.deleted_at IS NULL
             INNER JOIN plan_blocks pb ON pb.plan_group_id = pg.id
             INNER JOIN plan_templates pt ON pt.id = pb.plan_template_id AND pt.user_id = cd.user_id AND pt.deleted_at IS NULL
             INNER JOIN goals g ON g.id = pt.goal_id AND g.user_id = pt.user_id AND g.deleted_at IS NULL
             WHERE cd.user_id = :user_id
                AND cd.calendar_date <= :today
                AND (g.period_start_date IS NULL OR cd.calendar_date >= g.period_start_date)
                AND (g.period_end_date IS NULL OR cd.calendar_date <= g.period_end_date)
                AND pt.goal_id IS NOT NULL
             GROUP BY pt.goal_id'
        );
        $stmt->execute(['user_id' => $userId, 'today' => date('Y-m-d')]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listGoalRoutineProgress(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                r.goal_id,
                COUNT(DISTINCT r.id) AS routine_count,
                COALESCE(SUM(CASE WHEN rl.is_done = 1 THEN 1 ELSE 0 END), 0) AS done_day_count
             FROM routines r
             INNER JOIN goals g ON g.id = r.goal_id AND g.user_id = r.user_id AND g.deleted_at IS NULL
             LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                AND rl.user_id = r.user_id
                AND rl.log_date >= r.start_date
                AND rl.log_date <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
             WHERE r.user_id = :user_id AND r.deleted_at IS NULL AND r.goal_id IS NOT NULL
             GROUP BY r.goal_id'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listCompletedRoutineHistory(int $userId, string $today): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                r.id, r.name, r.start_date, r.duration_days, r.status, r.ended_at,
                COALESCE(SUM(CASE WHEN rl.is_done = 1 THEN 1 ELSE 0 END), 0) AS done_count
             FROM routines r
             LEFT JOIN routine_logs rl ON rl.routine_id = r.id
                AND rl.user_id = r.user_id
                AND rl.log_date >= r.start_date
                AND rl.log_date <= ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . '
             WHERE r.user_id = :user_id
                AND r.deleted_at IS NULL
                AND (r.status <> :active_status OR ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . ' < :today)
             GROUP BY r.id, r.name, r.start_date, r.duration_days, r.status, r.ended_at
             ORDER BY COALESCE(r.ended_at, ' . $this->dateAddExpression('r.start_date', 'r.duration_days - 1') . ') DESC, r.id DESC'
        );
        $stmt->execute(['user_id' => $userId, 'active_status' => 'active', 'today' => $today]);

        return $stmt->fetchAll();
    }

    /** @return array<string, int> */
    public function listRoutineLogStates(int $userId, int $routineId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT log_date, is_done
             FROM routine_logs
             WHERE user_id = :user_id AND routine_id = :routine_id
                AND log_date >= :start_date AND log_date <= :end_date
             ORDER BY log_date ASC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'routine_id' => $routineId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $states = [];
        foreach ($stmt->fetchAll() as $row) {
            $states[(string) $row['log_date']] = (int) $row['is_done'];
        }

        return $states;
    }

    private function upsertReportForPublish(int $userId, string $date, array $summary, array $texts): int
    {
        if ($this->driver() === 'mysql') {
            $sql = 'INSERT INTO retrospect_reports (
                        user_id, report_date, status,
                        today_review, today_thoughts, tomorrow_plan,
                        plan_total_count, plan_linked_count, plan_unlinked_count, plan_achievement_rate,
                        routine_total_count, routine_done_count, routine_achievement_rate,
                        linked_actual_minutes, linked_actual_count,
                        submitted_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :report_date, :status,
                        :today_review, :today_thoughts, :tomorrow_plan,
                        :plan_total_count, :plan_linked_count, :plan_unlinked_count, :plan_achievement_rate,
                        :routine_total_count, :routine_done_count, :routine_achievement_rate,
                        :linked_actual_minutes, :linked_actual_count,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON DUPLICATE KEY UPDATE
                        status = VALUES(status),
                        today_review = VALUES(today_review),
                        today_thoughts = VALUES(today_thoughts),
                        tomorrow_plan = VALUES(tomorrow_plan),
                        plan_total_count = VALUES(plan_total_count),
                        plan_linked_count = VALUES(plan_linked_count),
                        plan_unlinked_count = VALUES(plan_unlinked_count),
                        plan_achievement_rate = VALUES(plan_achievement_rate),
                        routine_total_count = VALUES(routine_total_count),
                        routine_done_count = VALUES(routine_done_count),
                        routine_achievement_rate = VALUES(routine_achievement_rate),
                        linked_actual_minutes = VALUES(linked_actual_minutes),
                        linked_actual_count = VALUES(linked_actual_count),
                        submitted_at = COALESCE(submitted_at, CURRENT_TIMESTAMP),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO retrospect_reports (
                        user_id, report_date, status,
                        today_review, today_thoughts, tomorrow_plan,
                        plan_total_count, plan_linked_count, plan_unlinked_count, plan_achievement_rate,
                        routine_total_count, routine_done_count, routine_achievement_rate,
                        linked_actual_minutes, linked_actual_count,
                        submitted_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :report_date, :status,
                        :today_review, :today_thoughts, :tomorrow_plan,
                        :plan_total_count, :plan_linked_count, :plan_unlinked_count, :plan_achievement_rate,
                        :routine_total_count, :routine_done_count, :routine_achievement_rate,
                        :linked_actual_minutes, :linked_actual_count,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(user_id, report_date) DO UPDATE SET
                        status = excluded.status,
                        today_review = excluded.today_review,
                        today_thoughts = excluded.today_thoughts,
                        tomorrow_plan = excluded.tomorrow_plan,
                        plan_total_count = excluded.plan_total_count,
                        plan_linked_count = excluded.plan_linked_count,
                        plan_unlinked_count = excluded.plan_unlinked_count,
                        plan_achievement_rate = excluded.plan_achievement_rate,
                        routine_total_count = excluded.routine_total_count,
                        routine_done_count = excluded.routine_done_count,
                        routine_achievement_rate = excluded.routine_achievement_rate,
                        linked_actual_minutes = excluded.linked_actual_minutes,
                        linked_actual_count = excluded.linked_actual_count,
                        submitted_at = COALESCE(retrospect_reports.submitted_at, CURRENT_TIMESTAMP),
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'report_date' => $date,
            'status' => 'submitted',
            'today_review' => $texts['today_review'],
            'today_thoughts' => $texts['today_thoughts'],
            'tomorrow_plan' => $texts['tomorrow_plan'],
            'plan_total_count' => (int) $summary['planTotalCount'],
            'plan_linked_count' => (int) $summary['planLinkedCount'],
            'plan_unlinked_count' => (int) $summary['planUnlinkedCount'],
            'plan_achievement_rate' => (int) $summary['planAchievementRate'],
            'routine_total_count' => (int) $summary['routineTotalCount'],
            'routine_done_count' => (int) $summary['routineDoneCount'],
            'routine_achievement_rate' => (int) $summary['routineAchievementRate'],
            'linked_actual_minutes' => (int) $summary['linkedActualMinutes'],
            'linked_actual_count' => (int) $summary['linkedActualCount'],
        ]);

        $report = $this->findReport($userId, $date);

        return $report === null ? 0 : (int) $report['id'];
    }

    private function deleteSnapshotItems(int $reportId): void
    {
        foreach ([
            'retrospect_report_plan_items',
            'retrospect_report_actual_items',
            'retrospect_report_routine_items',
        ] as $table) {
            $stmt = $this->db->prepare('DELETE FROM ' . $table . ' WHERE report_id = :report_id');
            $stmt->execute(['report_id' => $reportId]);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function insertPlanItems(int $reportId, array $items): void
    {
        $sql = 'INSERT INTO retrospect_report_plan_items (
                    report_id, plan_group_id, plan_block_id, plan_template_id,
                    title_snapshot, start_index, end_index, importance_snapshot,
                    is_linked, sort_order, created_at
                ) VALUES (
                    :report_id, :plan_group_id, :plan_block_id, :plan_template_id,
                    :title_snapshot, :start_index, :end_index, :importance_snapshot,
                    :is_linked, :sort_order, CURRENT_TIMESTAMP
                )';
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                'report_id' => $reportId,
                'plan_group_id' => $item['plan_group_id'],
                'plan_block_id' => $item['plan_block_id'],
                'plan_template_id' => $item['plan_template_id'],
                'title_snapshot' => $item['title'],
                'start_index' => $item['start_index'],
                'end_index' => $item['end_index'],
                'importance_snapshot' => $item['importance'],
                'is_linked' => !empty($item['is_linked']) ? 1 : 0,
                'sort_order' => $item['sort_order'],
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function insertActualItems(int $reportId, array $items): void
    {
        $sql = 'INSERT INTO retrospect_report_actual_items (
                    report_id, calendar_day_id, calendar_event_id, title_snapshot,
                    memo_snapshot, start_index, end_index, tag_name_snapshot, tag_color_snapshot,
                    plan_template_id_snapshot, plan_importance_snapshot,
                    is_linked, sort_order, created_at
                ) VALUES (
                    :report_id, :calendar_day_id, :calendar_event_id, :title_snapshot,
                    :memo_snapshot, :start_index, :end_index, :tag_name_snapshot, :tag_color_snapshot,
                    :plan_template_id_snapshot, :plan_importance_snapshot,
                    :is_linked, :sort_order, CURRENT_TIMESTAMP
                )';
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                'report_id' => $reportId,
                'calendar_day_id' => $item['calendar_day_id'],
                'calendar_event_id' => $item['id'],
                'title_snapshot' => $item['title'],
                'memo_snapshot' => $item['memo'],
                'start_index' => $item['start_index'],
                'end_index' => $item['end_index'],
                'tag_name_snapshot' => $item['tag_name'],
                'tag_color_snapshot' => $item['tag_color'],
                'plan_template_id_snapshot' => $item['plan_template_id'],
                'plan_importance_snapshot' => $item['plan_importance'],
                'is_linked' => !empty($item['is_linked']) ? 1 : 0,
                'sort_order' => $item['sort_order'],
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function insertRoutineItems(int $reportId, array $items): void
    {
        $sql = 'INSERT INTO retrospect_report_routine_items (
                    report_id, routine_id, routine_name_snapshot,
                    state_snapshot, was_active, sort_order, created_at
                ) VALUES (
                    :report_id, :routine_id, :routine_name_snapshot,
                    :state_snapshot, :was_active, :sort_order, CURRENT_TIMESTAMP
                )';
        $stmt = $this->db->prepare($sql);

        foreach ($items as $item) {
            $stmt->execute([
                'report_id' => $reportId,
                'routine_id' => $item['id'],
                'routine_name_snapshot' => $item['name'],
                'state_snapshot' => $item['state'],
                'was_active' => 1,
                'sort_order' => $item['sort_order'],
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchItems(string $sql, int $reportId): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => $reportId]);

        return $stmt->fetchAll();
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
