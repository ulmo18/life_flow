<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class CalendarRepository
{
    private PDO $db;
    private ?bool $dailyPlanBlocksSupported = null;
    private ?bool $todoStatusSupported = null;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed> */
    public function getOrCreateDay(int $userId, string $date): array
    {
        $day = $this->findDay($userId, $date);
        if ($day !== null) {
            return $day;
        }

        $sql = 'INSERT INTO calendar_days (user_id, calendar_date, plan_group_id, created_at, updated_at)
                VALUES (:user_id, :calendar_date, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_date' => $date,
        ]);

        return $this->findDay($userId, $date) ?? [
            'id' => (int) $this->db->lastInsertId(),
            'user_id' => $userId,
            'calendar_date' => $date,
            'plan_group_id' => null,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findDay(int $userId, string $date): ?array
    {
        $sql = 'SELECT id, user_id, calendar_date, plan_group_id, created_at, updated_at
                FROM calendar_days
                WHERE user_id = :user_id
                    AND calendar_date = :calendar_date
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_date' => $date,
        ]);

        $day = $stmt->fetch();

        return $day !== false ? $day : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getActualEvents(int $userId, int $calendarDayId): array
    {
        $blockColumn = $this->supportsDailyPlanBlocks()
            ? 'ce.daily_plan_block_id'
            : 'NULL AS daily_plan_block_id';
        $sql = 'SELECT
                    ce.id,
                    ce.calendar_day_id,
                    ce.user_id,
                    ce.title,
                    ce.schedule_type,
                    ce.start_index,
                    ce.end_index,
                    ce.plan_template_id,
                    ce.daily_plan_item_id,
                    ' . $blockColumn . ',
                    ce.calendar_tag_id,
                    ce.memo,
                    ' . ($this->supportsDailyPlanBlocks() ? 'COALESCE(dpb.title_snapshot, dpi.title, pt.title)' : 'COALESCE(dpi.title, pt.title)') . ' AS plan_title,
                    ' . ($this->supportsDailyPlanBlocks() ? 'COALESCE(dpb.importance_snapshot, dpi.importance, pt.importance)' : 'COALESCE(dpi.importance, pt.importance)') . ' AS plan_importance,
                    ct.name AS tag_name,
                    ct.color_hex AS tag_color,
                    ees.group_id_snapshot AS execution_energy_group_id,
                    ees.group_name_snapshot AS execution_energy_group_name,
                    ees.effect_type AS execution_energy_type,
                    ees.energy_value AS execution_energy_value,
                    ees.is_override AS execution_energy_is_override,
                    we.entry_type AS wellness_type,
                    we.detail AS wellness_detail,
                    we.note AS wellness_note,
                    we.rating AS wellness_rating
                FROM calendar_events ce
                LEFT JOIN plan_templates pt ON pt.id = ce.plan_template_id
                LEFT JOIN daily_plan_items dpi ON dpi.id = ce.daily_plan_item_id
                ' . ($this->supportsDailyPlanBlocks() ? 'LEFT JOIN daily_plan_blocks dpb ON dpb.id = ce.daily_plan_block_id' : '') . '
                LEFT JOIN calendar_tags ct ON ct.id = ce.calendar_tag_id
                    AND ct.deleted_at IS NULL
                LEFT JOIN execution_energy_event_settings ees ON ees.calendar_event_id = ce.id
                LEFT JOIN wellness_entries we ON we.calendar_event_id = ce.id AND we.user_id = ce.user_id
                WHERE ce.user_id = :user_id
                    AND ce.calendar_day_id = :calendar_day_id
                    AND ce.deleted_at IS NULL
                ORDER BY CASE WHEN ce.schedule_type = \'unscheduled\' THEN 0 ELSE 1 END ASC,
                    ce.start_index ASC,
                    ce.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_day_id' => $calendarDayId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listCalendarTags(int $userId): array
    {
        $sql = 'SELECT ct.id, ct.user_id, ct.palette_id, ct.slug, ct.name, ct.color_hex,
                       ct.sort_order, ct.is_system,
                       CASE WHEN ct.is_system = 1 THEN COALESCE(ctp.is_enabled, 1) ELSE 1 END AS is_enabled
                FROM calendar_tags ct
                LEFT JOIN calendar_tag_preferences ctp
                    ON ctp.tag_id = ct.id AND ctp.user_id = :preference_user_id
                WHERE ct.deleted_at IS NULL
                    AND (ct.is_system = 1 OR ct.user_id = :tag_user_id)
                ORDER BY ct.sort_order ASC, ct.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'preference_user_id' => $userId,
            'tag_user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listPlanGroups(int $userId): array
    {
        $sql = 'SELECT id, name, version_no
                FROM plan_groups
                WHERE user_id = :user_id
                    AND deleted_at IS NULL
                ORDER BY updated_at DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getPlanBlocksForGroup(int $userId, ?int $planGroupId): array
    {
        if ($planGroupId === null || $planGroupId <= 0) {
            return [];
        }

        $sql = 'SELECT
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
                ORDER BY pb.sort_order ASC, pb.start_index ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'plan_group_id' => $planGroupId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function getDailyPlanForDay(int $userId, int $calendarDayId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, calendar_day_id, source_plan_group_id, name, created_at, updated_at
             FROM daily_plans
             WHERE user_id = :user_id AND calendar_day_id = :calendar_day_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);
        $plan = $stmt->fetch();

        return $plan !== false ? $plan : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDailyPlanItems(int $userId, int $calendarDayId, bool $includeBlockItems = false): array
    {
        $stmt = $this->db->prepare(
            'SELECT dpi.id AS daily_plan_item_id, dpi.id AS plan_block_id,
                    dp.source_plan_group_id AS plan_group_id,
                    dpi.source_plan_template_id AS plan_template_id,
                    dpi.source_plan_block_id, dpi.goal_id, dpi.title, dpi.importance,
                    ' . ($this->supportsTodoStatus() ? 'dpi.todo_status' : "'pending'") . ' AS todo_status,
                    dpi.start_index, dpi.end_index, dpi.estimated_duration_minutes, dpi.sort_order, dpi.created_at,
                    g.title AS goal_title,
                    linked_event.id AS linked_event_id,
                    linked_event.start_index AS linked_event_start_index,
                    linked_event.end_index AS linked_event_end_index
             FROM daily_plan_items dpi
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             LEFT JOIN goals g ON g.id = dpi.goal_id AND g.user_id = dp.user_id AND g.deleted_at IS NULL
             LEFT JOIN calendar_events linked_event ON linked_event.id = (
                 SELECT ce.id FROM calendar_events ce
                 WHERE ce.daily_plan_item_id = dpi.id
                   AND ce.user_id = dp.user_id
                   AND ce.deleted_at IS NULL
                 ORDER BY ce.id ASC LIMIT 1
             )
             WHERE dp.user_id = :user_id
               AND dp.calendar_day_id = :calendar_day_id
               ' . ($includeBlockItems
                    ? 'AND (dpi.deleted_at IS NULL OR EXISTS (SELECT 1 FROM daily_plan_blocks existing_block WHERE existing_block.daily_plan_item_id = dpi.id))'
                    : 'AND dpi.deleted_at IS NULL AND dpi.source_block_template_id IS NULL') . '
             ORDER BY dpi.sort_order ASC, dpi.start_index ASC, dpi.id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getTodoLinkedActualEvents(int $userId, int $calendarDayId): array
    {
        $blockColumn = $this->supportsDailyPlanBlocks() ? 'daily_plan_block_id' : 'NULL AS daily_plan_block_id';
        $stmt = $this->db->prepare(
            'SELECT daily_plan_item_id, ' . $blockColumn . ', title, start_index, end_index
             FROM calendar_events
             WHERE user_id = :user_id AND calendar_day_id = :calendar_day_id AND deleted_at IS NULL
             ORDER BY start_index ASC, id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function getTodoTreeActualEvents(int $userId, int $calendarDayId): array
    {
        if (!$this->supportsDailyPlanBlocks()) return $this->getTodoLinkedActualEvents($userId, $calendarDayId);
        $stmt = $this->db->prepare(
            'SELECT ce.daily_plan_item_id, ce.daily_plan_block_id, ce.title, ce.start_index, ce.end_index, cd.calendar_date AS actual_date
             FROM calendar_events ce
             LEFT JOIN daily_plan_blocks dpb ON dpb.id = ce.daily_plan_block_id
             INNER JOIN daily_plan_items dpi ON dpi.id = COALESCE(dpb.daily_plan_item_id, ce.daily_plan_item_id)
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             INNER JOIN calendar_days cd ON cd.id = ce.calendar_day_id
             WHERE ce.user_id = :user_id AND dp.user_id = :owner_id AND dp.calendar_day_id = :calendar_day_id AND ce.deleted_at IS NULL
             ORDER BY cd.calendar_date, ce.start_index, ce.id'
        );
        $stmt->execute(['user_id' => $userId, 'owner_id' => $userId, 'calendar_day_id' => $calendarDayId]);
        return $stmt->fetchAll();
    }

    public function supportsTodoStatus(): bool
    {
        if ($this->todoStatusSupported !== null) return $this->todoStatusSupported;
        if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $columns = $this->db->query('PRAGMA table_info(daily_plan_items)')->fetchAll();
            return $this->todoStatusSupported = in_array('todo_status', array_column($columns, 'name'), true);
        }
        $column = $this->db->query("SHOW COLUMNS FROM daily_plan_items LIKE 'todo_status'");
        return $this->todoStatusSupported = $column !== false && $column->fetchColumn() !== false;
    }

    public function updateTodoStatus(int $userId, int $itemId, string $status): bool
    {
        if (!$this->supportsTodoStatus() || !in_array($status, ['pending', 'completed', 'cancelled', 'postponed', 'in_progress', 'external'], true)) return false;
        $stmt = $this->db->prepare('UPDATE daily_plan_items SET todo_status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL AND daily_plan_id IN (SELECT id FROM daily_plans WHERE user_id = :user_id)');
        $stmt->execute(['status' => $status, 'id' => $itemId, 'user_id' => $userId]);
        return $stmt->rowCount() > 0 || $this->getDailyPlanItemForCompletion($userId, $itemId) !== null;
    }

    public function supportsDailyPlanBlocks(): bool
    {
        if ($this->dailyPlanBlocksSupported !== null) {
            return $this->dailyPlanBlocksSupported;
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $table = $this->db->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'daily_plan_blocks' LIMIT 1");
            $columns = $this->db->query('PRAGMA table_info(calendar_events)');
            $names = $columns === false ? [] : array_column($columns->fetchAll(), 'name');
            return $this->dailyPlanBlocksSupported = $table !== false
                && $table->fetchColumn() !== false
                && in_array('daily_plan_block_id', $names, true);
        }

        $table = $this->db->query("SHOW TABLES LIKE 'daily_plan_blocks'");
        if ($table === false || $table->fetchColumn() === false) {
            return $this->dailyPlanBlocksSupported = false;
        }
        $column = $this->db->query("SHOW COLUMNS FROM calendar_events LIKE 'daily_plan_block_id'");

        return $this->dailyPlanBlocksSupported = $column !== false && $column->fetchColumn() !== false;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDailyPlanBlocksForDay(int $userId, int $calendarDayId): array
    {
        if (!$this->supportsDailyPlanBlocks()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT dpb.id AS daily_plan_block_id, dpi.id AS daily_plan_item_id,
                    dp.source_plan_group_id AS plan_group_id,
                    dpi.source_plan_template_id AS plan_template_id,
                    dpi.source_plan_block_id, dpi.source_block_template_id, CASE WHEN dpb.title_snapshot IS NULL THEN dpi.goal_id ELSE dpb.goal_id_snapshot END AS goal_id, COALESCE(dpb.title_snapshot, dpi.title) AS title, COALESCE(dpb.importance_snapshot, dpi.importance) AS importance,
                    dpb.start_index, dpb.end_index, dpb.sort_order, dpb.manual_status,
                    dpi.deleted_at AS source_deleted_at, dpi.importance AS source_importance,
                    ' . ($this->supportsTodoStatus() ? 'dpi.todo_status' : "'pending'") . ' AS source_todo_status,
                    g.title AS goal_title,
                    EXISTS (
                        SELECT 1 FROM calendar_events ce
                        WHERE ce.daily_plan_block_id = dpb.id
                          AND ce.user_id = dp.user_id
                          AND ce.deleted_at IS NULL
                    ) AS is_linked
             FROM daily_plan_blocks dpb
             INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             LEFT JOIN goals g ON g.id = CASE WHEN dpb.title_snapshot IS NULL THEN dpi.goal_id ELSE dpb.goal_id_snapshot END AND g.user_id = dp.user_id AND g.deleted_at IS NULL
             WHERE dp.user_id = :user_id
               AND dp.calendar_day_id = :calendar_day_id
             ORDER BY dpb.start_index ASC, dpb.sort_order ASC, dpb.id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function getDailyPlanItemForCompletion(int $userId, int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dpi.id, dpi.title, dpi.start_index, dpi.end_index,
                    cd.calendar_date,
                    (SELECT ce.id FROM calendar_events ce
                     WHERE ce.daily_plan_item_id = dpi.id
                       AND ce.user_id = dp.user_id
                       AND ce.deleted_at IS NULL
                     LIMIT 1) AS linked_event_id
             FROM daily_plan_items dpi
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             INNER JOIN calendar_days cd ON cd.id = dp.calendar_day_id
             WHERE dpi.id = :id AND dp.user_id = :user_id AND dpi.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $itemId, 'user_id' => $userId]);
        $item = $stmt->fetch();

        return $item !== false ? $item : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getDailyPlanBlocks(int $userId, int $dailyPlanItemId): array
    {
        if (!$this->supportsDailyPlanBlocks()) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT dpb.id, dpb.daily_plan_item_id, dpb.start_index, dpb.end_index, dpb.sort_order
             FROM daily_plan_blocks dpb
             INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             WHERE dpb.daily_plan_item_id = :daily_plan_item_id
               AND dp.user_id = :user_id
               AND dpi.deleted_at IS NULL
             ORDER BY dpb.sort_order ASC, dpb.start_index ASC, dpb.id ASC'
        );
        $stmt->execute(['daily_plan_item_id' => $dailyPlanItemId, 'user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function createDailyPlanBlock(
        int $userId,
        int $dailyPlanItemId,
        int $startIndex,
        int $endIndex
    ): ?int {
        if (!$this->supportsDailyPlanBlocks() || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) {
            return null;
        }

        try {
            $this->db->beginTransaction();
            if ($this->findOwnedDailyPlanItem($userId, $dailyPlanItemId) === null
                || $this->hasOverlappingDailyPlanBlock($dailyPlanItemId, $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }
            $stmt = $this->db->prepare(
                'INSERT INTO daily_plan_blocks (
                    daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at
                 ) VALUES (
                    :daily_plan_item_id, :start_index, :end_index,
                    (SELECT COALESCE(MAX(existing.sort_order), 0) + 1
                     FROM daily_plan_blocks existing WHERE existing.daily_plan_item_id = :sort_item_id),
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                 )'
            );
            $stmt->execute([
                'daily_plan_item_id' => $dailyPlanItemId,
                'sort_item_id' => $dailyPlanItemId,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
            ]);
            $blockId = (int) $this->db->lastInsertId();
            $this->snapshotDailyPlanBlock($blockId, $dailyPlanItemId);
            $this->db->commit();

            return $blockId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] create daily plan block failed: ' . $exception->getMessage());
            return null;
        }
    }

    /** @return array{itemId:int, blockId:int, item:array<string, mixed>}|null */
    public function placePlanBlockTemplate(int $userId, string $date, int $templateId, int $startIndex, int $endIndex): ?array
    {
        if (!$this->supportsDailyPlanBlocks() || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) return null;
        return $this->useRecurringTemplate($userId, $date, $templateId, $startIndex, $endIndex);
    }

    /** @return array{itemId:int, blockId:?int, item:array<string, mixed>}|null */
    public function useRecurringTemplate(int $userId, string $date, int $templateId, ?int $startIndex = null, ?int $endIndex = null): ?array
    {
        if (($startIndex === null) !== ($endIndex === null)) return null;
        if ($startIndex !== null && (!$this->supportsDailyPlanBlocks() || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex)) return null;
        try {
            $this->db->beginTransaction();
            $lock = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $template = $this->db->prepare('SELECT title, importance, goal_id FROM plan_block_templates WHERE id = :id AND user_id = :user_id AND is_enabled = 1 AND deleted_at IS NULL' . $lock);
            $template->execute(['id' => $templateId, 'user_id' => $userId]);
            $source = $template->fetch();
            if (!$source) { $this->db->rollBack(); return null; }
            $day = $this->getOrCreateDay($userId, $date);
            $dayId = (int) $day['id'];
            if ($startIndex !== null && $this->hasOverlappingDailyPlanBlockForDay($dayId, $startIndex, $endIndex)) { $this->db->rollBack(); return null; }
            $plan = $this->getDailyPlanForDay($userId, $dayId);
            if ($plan === null) {
                $stmt = $this->db->prepare('INSERT INTO daily_plans (user_id, calendar_day_id, source_plan_group_id, name, created_at, updated_at) VALUES (:user_id, :day_id, NULL, :name, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
                $stmt->execute(['user_id' => $userId, 'day_id' => $dayId, 'name' => '[' . $date . ']의 계획']);
                $planId = (int) $this->db->lastInsertId();
            } else $planId = (int) $plan['id'];
            $goalId = $source['goal_id'] === null ? null : (int) $source['goal_id'];
            if ($goalId !== null && !$this->userOwnsGoal($userId, $goalId)) $goalId = null;
            $existing = $this->db->prepare('SELECT id FROM daily_plan_items WHERE daily_plan_id = :plan_id AND recurring_template_id = :template_id AND deleted_at IS NULL ORDER BY id LIMIT 1');
            $existing->execute(['plan_id' => $planId, 'template_id' => $templateId]);
            $itemId = (int) $existing->fetchColumn();
            if ($itemId === 0) {
                $stmt = $this->db->prepare('INSERT INTO daily_plan_items (daily_plan_id, source_plan_block_id, source_plan_template_id, source_block_template_id, recurring_template_id, goal_id, title, importance, start_index, end_index, estimated_duration_minutes, sort_order, deleted_at, created_at, updated_at) VALUES (:plan_id, NULL, NULL, NULL, :template_id, :goal_id, :title, :importance, NULL, NULL, NULL, (SELECT COALESCE(MAX(existing.sort_order), 0) + 1 FROM daily_plan_items existing WHERE existing.daily_plan_id = :sort_plan_id), NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
                $stmt->execute(['plan_id' => $planId, 'sort_plan_id' => $planId, 'template_id' => $templateId, 'goal_id' => $goalId, 'title' => $source['title'], 'importance' => $source['importance']]);
                $itemId = (int) $this->db->lastInsertId();
            }
            $blockId = $startIndex === null ? null : $this->createInitialDailyPlanBlock($itemId, $startIndex, $endIndex);
            $item = $this->db->prepare('SELECT title, importance, todo_status, goal_id FROM daily_plan_items WHERE id = :id');
            $item->execute(['id' => $itemId]);
            $savedItem = $item->fetch();
            $this->db->commit();
            return ['itemId' => $itemId, 'blockId' => $blockId, 'item' => $savedItem];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] template placement failed: ' . $exception->getMessage());
            return null;
        }
    }

    /** @return int[] */
    public function getUsedRecurringTemplateIds(int $userId, int $dayId): array
    {
        $stmt = $this->db->prepare('SELECT DISTINCT dpi.recurring_template_id FROM daily_plan_items dpi INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id WHERE dp.user_id = :user_id AND dp.calendar_day_id = :day_id AND dpi.deleted_at IS NULL AND dpi.recurring_template_id IS NOT NULL');
        $stmt->execute(['user_id' => $userId, 'day_id' => $dayId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function updateDailyPlanBlock(int $userId, int $blockId, int $startIndex, int $endIndex, ?string $date = null): bool
    {
        if (!$this->supportsDailyPlanBlocks() || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) {
            return false;
        }
        try {
            $this->db->beginTransaction();
            $block = $this->findOwnedDailyPlanBlock($userId, $blockId);
            $day = $date === null ? null : $this->findDay($userId, $date);
            if ($block === null
                || ($day !== null && (int) $block['calendar_day_id'] !== (int) $day['id'])
                || ($date !== null && $day === null)
                || $this->hasOverlappingDailyPlanBlockForDay(
                    (int) $block['calendar_day_id'], $startIndex, $endIndex, $blockId
                )) {
                $this->db->rollBack();
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE daily_plan_blocks
                 SET start_index = :start_index, end_index = :end_index, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute(['start_index' => $startIndex, 'end_index' => $endIndex, 'id' => $blockId]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] update daily plan block failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function deleteDailyPlanBlock(int $userId, int $blockId): bool
    {
        if (!$this->supportsDailyPlanBlocks()) {
            return false;
        }
        try {
            $this->db->beginTransaction();
            if ($this->findOwnedDailyPlanBlock($userId, $blockId) === null) {
                $this->db->rollBack();
                return false;
            }
            // Preserve Actual records as unplanned when their Plan block is deleted.
            $clear = $this->db->prepare(
                'UPDATE calendar_events SET daily_plan_block_id = NULL, daily_plan_item_id = NULL,
                     plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND daily_plan_block_id = :daily_plan_block_id AND deleted_at IS NULL'
            );
            $clear->execute(['user_id' => $userId, 'daily_plan_block_id' => $blockId]);
            $delete = $this->db->prepare('DELETE FROM daily_plan_blocks WHERE id = :id');
            $delete->execute(['id' => $blockId]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] delete daily plan block failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function updateDailyPlanBlockDetails(int $userId, int $blockId, string $importance, bool $detachActuals, ?string $manualStatus = null): bool
    {
        if (!$this->supportsDailyPlanBlocks()) return false;
        if ($manualStatus !== null && !in_array($manualStatus, ['pending', 'in_progress', 'completed', 'postponed', 'cancelled', 'external'], true)) return false;
        try {
            $this->db->beginTransaction();
            $block = $this->findOwnedDailyPlanBlock($userId, $blockId);
            if ($block === null) { $this->db->rollBack(); return false; }
            if ($manualStatus === 'completed') {
                $linked = $this->db->prepare('SELECT 1 FROM calendar_events WHERE user_id = :user_id AND daily_plan_block_id = :block_id AND deleted_at IS NULL LIMIT 1');
                $linked->execute(['user_id' => $userId, 'block_id' => $blockId]);
                if ($detachActuals || $linked->fetchColumn() === false) { $this->db->rollBack(); return false; }
            }
            $statusSql = $manualStatus === null ? '' : ', manual_status = :manual_status';
            $stmt = $this->db->prepare('UPDATE daily_plan_blocks SET importance_snapshot = :importance' . $statusSql . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $params = ['importance' => $importance, 'id' => $blockId];
            if ($manualStatus !== null) $params['manual_status'] = $manualStatus;
            $stmt->execute($params);
            if ($detachActuals) {
                $clear = $this->db->prepare(
                    'UPDATE calendar_events SET daily_plan_block_id = NULL, daily_plan_item_id = NULL, plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                     WHERE user_id = :user_id AND daily_plan_block_id = :block_id AND deleted_at IS NULL'
                );
                $clear->execute(['user_id' => $userId, 'block_id' => $blockId]);
                if ($clear->rowCount() > 0) $this->resetManualStatusIfUnlinked($userId, $blockId);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] update daily plan block details failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function updateDailyPlanBlockStatus(int $userId, int $blockId, string $status): bool
    {
        if (!$this->supportsDailyPlanBlocks() || !in_array($status, ['pending', 'in_progress', 'completed', 'postponed', 'cancelled', 'external'], true)) return false;
        try {
            $this->db->beginTransaction();
            $block = $this->findOwnedDailyPlanBlock($userId, $blockId);
            if ($block === null) { $this->db->rollBack(); return false; }
            if ($status === 'completed') {
                $linked = $this->db->prepare('SELECT 1 FROM calendar_events WHERE user_id = :user_id AND daily_plan_block_id = :block_id AND deleted_at IS NULL LIMIT 1');
                $linked->execute(['user_id' => $userId, 'block_id' => $blockId]);
                if ($linked->fetchColumn() === false) { $this->db->rollBack(); return false; }
            }
            $stmt = $this->db->prepare('UPDATE daily_plan_blocks SET manual_status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute(['status' => $status, 'id' => $blockId]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] update plan block status failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function detachDailyPlanBlockActuals(int $userId, int $blockId): bool
    {
        if (!$this->supportsDailyPlanBlocks()) return false;
        try {
            $this->db->beginTransaction();
            if ($this->findOwnedDailyPlanBlock($userId, $blockId) === null) { $this->db->rollBack(); return false; }
            $stmt = $this->db->prepare(
                'UPDATE calendar_events SET daily_plan_block_id = NULL, daily_plan_item_id = NULL,
                 plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND daily_plan_block_id = :block_id AND deleted_at IS NULL'
            );
            $stmt->execute(['user_id' => $userId, 'block_id' => $blockId]);
            if ($stmt->rowCount() === 0) { $this->db->rollBack(); return false; }
            $this->resetManualStatusIfUnlinked($userId, $blockId);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] detach plan block actuals failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function hasOverlappingActualEvent(int $userId, string $date, int $startIndex, int $endIndex): bool
    {
        $day = $this->findDay($userId, $date);

        return $day !== null
            && $this->hasOverlappingEvent($userId, (int) $day['id'], $startIndex, $endIndex);
    }

    /** @return array<int, int> */
    public function getUsedPlanTemplateIds(int $userId, int $calendarDayId): array
    {
        $sql = 'SELECT plan_template_id
                FROM calendar_events
                WHERE user_id = :user_id
                    AND calendar_day_id = :calendar_day_id
                    AND plan_template_id IS NOT NULL
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_day_id' => $calendarDayId,
        ]);

        return array_map('intval', array_column($stmt->fetchAll(), 'plan_template_id'));
    }

    /** @return array<int, int> */
    public function getUsedDailyPlanItemIds(int $userId, int $calendarDayId): array
    {
        $stmt = $this->db->prepare(
            'SELECT daily_plan_item_id
             FROM calendar_events
             WHERE user_id = :user_id AND calendar_day_id = :calendar_day_id
               AND daily_plan_item_id IS NOT NULL AND deleted_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'daily_plan_item_id'));
    }

    /** @return array<string, mixed>|null */
    public function getDateMeta(string $date, string $localeCode = 'KR'): ?array
    {
        $sql = 'SELECT id, calendar_date, locale_code, date_type, holiday_name, is_holiday, is_substitute_holiday
                FROM calendar_date_meta
                WHERE calendar_date = :calendar_date
                    AND locale_code = :locale_code
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'calendar_date' => $date,
            'locale_code' => $localeCode,
        ]);

        $meta = $stmt->fetch();

        return $meta !== false ? $meta : null;
    }

    public function setDayPlanGroup(int $userId, string $date, ?int $planGroupId, bool $requireEmpty = false): bool
    {
        if ($planGroupId !== null && !$this->userOwnsPlanGroup($userId, $planGroupId)) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            if ($requireEmpty) {
                if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    $lock = $this->db->prepare('SELECT id FROM calendar_days WHERE id = :id AND user_id = :user_id FOR UPDATE');
                    $lock->execute(['id' => (int) $day['id'], 'user_id' => $userId]);
                }
                $existing = $this->db->prepare('SELECT dpi.id FROM daily_plan_items dpi INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id WHERE dp.user_id = :user_id AND dp.calendar_day_id = :day_id AND dpi.deleted_at IS NULL LIMIT 1');
                $existing->execute(['user_id' => $userId, 'day_id' => (int) $day['id']]);
                if ($existing->fetchColumn() !== false) { $this->db->rollBack(); return false; }
            }
            $dailyPlan = $this->getDailyPlanForDay($userId, (int) $day['id']);
            $oldPlanGroupId = $dailyPlan !== null && $dailyPlan['source_plan_group_id'] !== null
                ? (int) $dailyPlan['source_plan_group_id']
                : null;

            if (!$requireEmpty && $oldPlanGroupId === $planGroupId && $dailyPlan !== null) {
                $this->db->commit();
                return true;
            }

            if (!$requireEmpty) $this->clearEventPlanLinks($userId, (int) $day['id']);
            if ($dailyPlan !== null && !$requireEmpty) {
                $stmt = $this->db->prepare('DELETE FROM daily_plans WHERE id = :id AND user_id = :user_id');
                $stmt->execute(['id' => (int) $dailyPlan['id'], 'user_id' => $userId]);
            }

            $sql = 'UPDATE calendar_days
                    SET plan_group_id = :plan_group_id,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                        AND user_id = :user_id';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'plan_group_id' => $planGroupId,
                'id' => (int) $day['id'],
                'user_id' => $userId,
            ]);

            if ($planGroupId !== null) {
                $groupStmt = $this->db->prepare(
                    'SELECT name FROM plan_groups
                     WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL LIMIT 1'
                );
                $groupStmt->execute(['id' => $planGroupId, 'user_id' => $userId]);
                $group = $groupStmt->fetch();
                if ($group === false) {
                    $this->db->rollBack();
                    return false;
                }

                if ($requireEmpty && $dailyPlan !== null) {
                    $dailyPlanId = (int) $dailyPlan['id'];
                    $stmt = $this->db->prepare('UPDATE daily_plans SET source_plan_group_id = :group_id, name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id');
                    $stmt->execute(['group_id' => $planGroupId, 'name' => (string) $group['name'], 'id' => $dailyPlanId, 'user_id' => $userId]);
                } else {
                $stmt = $this->db->prepare(
                    'INSERT INTO daily_plans (
                        user_id, calendar_day_id, source_plan_group_id, name, created_at, updated_at
                     ) VALUES (
                        :user_id, :calendar_day_id, :source_plan_group_id, :name,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                     )'
                );
                $stmt->execute([
                    'user_id' => $userId,
                    'calendar_day_id' => (int) $day['id'],
                    'source_plan_group_id' => $planGroupId,
                    'name' => (string) $group['name'],
                ]);
                $dailyPlanId = (int) $this->db->lastInsertId();
                }

                $stmt = $this->db->prepare(
                    'INSERT INTO daily_plan_items (
                        daily_plan_id, source_plan_block_id, source_plan_template_id, goal_id,
                        title, importance, start_index, end_index, sort_order,
                        deleted_at, created_at, updated_at
                     )
                     SELECT :daily_plan_id, pb.id, pt.id, pt.goal_id,
                            pt.title, pt.importance, pb.start_index, pb.end_index, pb.sort_order,
                            NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                     FROM plan_blocks pb
                     INNER JOIN plan_templates pt ON pt.id = pb.plan_template_id
                     WHERE pb.plan_group_id = :plan_group_id
                     ORDER BY pb.sort_order ASC, pb.start_index ASC'
                );
                $stmt->execute(['daily_plan_id' => $dailyPlanId, 'plan_group_id' => $planGroupId]);

                $stmt = $this->db->prepare(
                    'INSERT INTO daily_plan_blocks (
                        daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at
                     )
                     SELECT dpi.id, dpi.start_index, dpi.end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                     FROM daily_plan_items dpi
                     WHERE dpi.daily_plan_id = :daily_plan_id
                        AND dpi.deleted_at IS NULL
                       AND dpi.start_index IS NOT NULL
                       AND dpi.end_index IS NOT NULL'
                );
                if ($this->supportsDailyPlanBlocks()) {
                    $stmt->execute(['daily_plan_id' => $dailyPlanId]);
                    $this->db->prepare('UPDATE daily_plan_blocks SET title_snapshot = (SELECT title FROM daily_plan_items WHERE id = daily_plan_item_id), importance_snapshot = (SELECT importance FROM daily_plan_items WHERE id = daily_plan_item_id), goal_id_snapshot = (SELECT goal_id FROM daily_plan_items WHERE id = daily_plan_item_id) WHERE title_snapshot IS NULL AND daily_plan_item_id IN (SELECT id FROM daily_plan_items WHERE daily_plan_id = :plan_id)')->execute(['plan_id' => $dailyPlanId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar] set day plan failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function createDailyPlanItem(
        int $userId,
        string $date,
        string $title,
        string $importance,
        ?int $goalId,
        ?int $startIndex,
        ?int $endIndex,
        ?int $estimatedDurationMinutes,
        ?array $placementRange = null
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            if ($placementRange !== null && (!$this->supportsDailyPlanBlocks()
                || $placementRange[0] < 0 || $placementRange[1] > 144 || $placementRange[0] >= $placementRange[1]
                || $this->hasOverlappingDailyPlanBlockForDay((int) $day['id'], $placementRange[0], $placementRange[1]))) {
                $this->db->rollBack();
                return null;
            }
            $dailyPlan = $this->getDailyPlanForDay($userId, (int) $day['id']);

            if ($dailyPlan === null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO daily_plans (
                        user_id, calendar_day_id, source_plan_group_id, name, created_at, updated_at
                     ) VALUES (
                        :user_id, :calendar_day_id, NULL, :name, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                     )'
                );
                $stmt->execute([
                    'user_id' => $userId,
                    'calendar_day_id' => (int) $day['id'],
                    'name' => '[' . $date . ']의 계획',
                ]);
                $dailyPlanId = (int) $this->db->lastInsertId();
            } else {
                $dailyPlanId = (int) $dailyPlan['id'];
            }

            if ($startIndex !== null && $endIndex !== null
                && $this->hasOverlappingDailyPlanItem($dailyPlanId, $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }

            if ($goalId !== null && !$this->userOwnsGoal($userId, $goalId)) {
                $this->db->rollBack();
                return null;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO daily_plan_items (
                    daily_plan_id, source_plan_block_id, source_plan_template_id, goal_id,
                    title, importance, start_index, end_index, estimated_duration_minutes, sort_order,
                    deleted_at, created_at, updated_at
                 ) VALUES (
                    :daily_plan_id, NULL, NULL, :goal_id, :title, :importance,
                    :start_index, :end_index, :estimated_duration_minutes,
                    (SELECT COALESCE(MAX(existing.sort_order), 0) + 1
                     FROM daily_plan_items existing WHERE existing.daily_plan_id = :sort_daily_plan_id),
                    NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                 )'
            );
            $stmt->execute([
                'daily_plan_id' => $dailyPlanId,
                'sort_daily_plan_id' => $dailyPlanId,
                'goal_id' => $goalId,
                'title' => $title,
                'importance' => $importance,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'estimated_duration_minutes' => $estimatedDurationMinutes,
            ]);
            $itemId = (int) $this->db->lastInsertId();
            if ($this->supportsDailyPlanBlocks() && $startIndex !== null && $endIndex !== null) {
                $this->createInitialDailyPlanBlock($itemId, $startIndex, $endIndex);
            } elseif ($placementRange !== null) {
                $this->createInitialDailyPlanBlock($itemId, $placementRange[0], $placementRange[1]);
            }
            $this->db->commit();

            return $itemId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] create daily plan item failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function updateDailyPlanItem(
        int $userId,
        int $itemId,
        string $title,
        string $importance,
        ?int $goalId,
        ?int $startIndex,
        ?int $endIndex,
        ?int $estimatedDurationMinutes,
        string $linkAction
    ): bool {
        try {
            $this->db->beginTransaction();
            $item = $this->findOwnedDailyPlanItem($userId, $itemId);
            if ($item === null
                || ($goalId !== null && !$this->userOwnsGoal($userId, $goalId))
                || ($startIndex !== null && $endIndex !== null
                    && $this->hasOverlappingDailyPlanItem((int) $item['daily_plan_id'], $startIndex, $endIndex, $itemId))) {
                $this->db->rollBack();
                return false;
            }

            $linkedEventId = $item['linked_event_id'] === null ? null : (int) $item['linked_event_id'];
            if ($startIndex === null || $endIndex === null) {
                if ($estimatedDurationMinutes === null && $item['start_index'] !== null && $item['end_index'] !== null) {
                    $estimatedDurationMinutes = ((int) $item['end_index'] - (int) $item['start_index']) * 10;
                } elseif ($estimatedDurationMinutes === null && $item['estimated_duration_minutes'] !== null) {
                    $estimatedDurationMinutes = (int) $item['estimated_duration_minutes'];
                }
            } else {
                $estimatedDurationMinutes = null;
            }
            if ($linkedEventId !== null && $linkAction === 'detach') {
                $oldBlockId = null;
                if ($this->supportsDailyPlanBlocks()) {
                    $previousLink = $this->db->prepare('SELECT daily_plan_block_id FROM calendar_events WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
                    $previousLink->execute(['id' => $linkedEventId, 'user_id' => $userId]);
                    $oldBlockId = (int) $previousLink->fetchColumn();
                }
                $stmt = $this->db->prepare(
                    'UPDATE calendar_events
                     SET daily_plan_item_id = NULL' . ($this->supportsDailyPlanBlocks() ? ', daily_plan_block_id = NULL' : '') . ',
                         plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND user_id = :user_id'
                );
                $stmt->execute(['id' => $linkedEventId, 'user_id' => $userId]);
                if ($oldBlockId > 0) $this->resetManualStatusIfUnlinked($userId, $oldBlockId);
            } elseif ($linkedEventId !== null && $linkAction === 'sync') {
                if ($startIndex === null || $endIndex === null) {
                    $stmt = $this->db->prepare(
                        'UPDATE calendar_events
                         SET title = :title, updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
                    );
                    $stmt->execute(['title' => $title, 'id' => $linkedEventId, 'user_id' => $userId]);
                } else {
                    if ($this->hasOverlappingEvent(
                        $userId,
                        (int) $item['calendar_day_id'],
                        $startIndex,
                        $endIndex,
                        $linkedEventId
                    )) {
                        $this->db->rollBack();
                        return false;
                    }
                    $stmt = $this->db->prepare(
                        'UPDATE calendar_events
                         SET title = :title, start_index = :start_index, end_index = :end_index,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
                    );
                    $stmt->execute([
                        'title' => $title,
                        'start_index' => $startIndex,
                        'end_index' => $endIndex,
                        'id' => $linkedEventId,
                        'user_id' => $userId,
                    ]);
                }
            }

            $stmt = $this->db->prepare(
                'UPDATE daily_plan_items
                 SET title = :title, importance = :importance, goal_id = :goal_id,
                     start_index = :start_index, end_index = :end_index,
                     estimated_duration_minutes = :estimated_duration_minutes,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'title' => $title,
                'importance' => $importance,
                'goal_id' => $goalId,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'estimated_duration_minutes' => $estimatedDurationMinutes,
                'id' => $itemId,
            ]);
            if ($this->supportsDailyPlanBlocks() && $this->getDailyPlanBlocks($userId, $itemId) === [] && $startIndex !== null && $endIndex !== null) {
                $this->createInitialDailyPlanBlock($itemId, $startIndex, $endIndex);
            }
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] update daily plan item failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function updateDailyPlanItemDetails(
        int $userId,
        int $itemId,
        string $title,
        string $importance,
        ?int $goalId
    ): bool {
        try {
            $this->db->beginTransaction();
            if ($this->findOwnedDailyPlanItem($userId, $itemId) === null
                || ($goalId !== null && !$this->userOwnsGoal($userId, $goalId))) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE daily_plan_items
                 SET title = :title, importance = :importance, goal_id = :goal_id,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'title' => $title,
                'importance' => $importance,
                'goal_id' => $goalId,
                'id' => $itemId,
            ]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] update daily plan item details failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function deleteDailyPlanItem(int $userId, int $itemId): bool
    {
        try {
            $this->db->beginTransaction();
            if ($this->findOwnedDailyPlanItem($userId, $itemId) === null) {
                $this->db->rollBack();
                return false;
            }
            $stmt = $this->db->prepare(
                'UPDATE calendar_events
                 SET daily_plan_item_id = NULL' . ($this->supportsDailyPlanBlocks() ? ', daily_plan_block_id = NULL' : '') . ',
                     plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND daily_plan_item_id = :item_id AND deleted_at IS NULL' . ($this->supportsDailyPlanBlocks() ? ' AND daily_plan_block_id IS NULL' : '')
            );
            $stmt->execute(['user_id' => $userId, 'item_id' => $itemId]);
            $stmt = $this->db->prepare(
                'UPDATE daily_plan_items
                 SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute(['id' => $itemId]);
            $ok = $stmt->rowCount() === 1;
            $this->db->commit();
            return $ok;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] delete daily plan item failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function createActualEvent(
        int $userId,
        string $date,
        string $title,
        string $scheduleType,
        ?int $startIndex,
        ?int $endIndex,
        ?int $dailyPlanItemId,
        ?int $calendarTagId,
        string $memo,
        ?int $dailyPlanBlockId = null
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            if ($scheduleType === 'unscheduled') {
                $startIndex = null;
                $endIndex = null;
                $dailyPlanItemId = null;
                $dailyPlanBlockId = null;
            }

            // The legacy item-only link remains one-to-one. A block link is the
            // explicitly opt-in relationship that may have multiple Actuals.
            if ($dailyPlanItemId !== null && $dailyPlanBlockId === null
                && !$this->canUseDailyPlanItem($userId, (int) $day['id'], $dailyPlanItemId)) {
                $this->db->rollBack();
                return null;
            }
            if (!$this->canUseDailyPlanBlock($userId, (int) $day['id'], $dailyPlanItemId, $dailyPlanBlockId)) {
                $this->db->rollBack();
                return null;
            }
            $planData = $dailyPlanItemId === null ? null : $this->dailyPlanItemLinkData($dailyPlanItemId);
            $planTemplateId = $planData['source_plan_template_id'] ?? null;
            if ($planData !== null) {
                $title = (string) $planData['title'];
            }

            if ($scheduleType === 'timed' && $startIndex !== null && $endIndex !== null
                && $this->hasOverlappingEvent($userId, (int) $day['id'], $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }

            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId)) {
                $this->db->rollBack();
                return null;
            }

            $hasBlocks = $this->supportsDailyPlanBlocks();
            $sql = 'INSERT INTO calendar_events (
                        user_id, calendar_day_id, title, schedule_type, start_index, end_index,
                        plan_template_id, daily_plan_item_id' . ($hasBlocks ? ', daily_plan_block_id' : '') . ', calendar_tag_id, memo, deleted_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :calendar_day_id, :title, :schedule_type, :start_index, :end_index,
                        :plan_template_id, :daily_plan_item_id' . ($hasBlocks ? ', :daily_plan_block_id' : '') . ', :calendar_tag_id, :memo, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )';

            $stmt = $this->db->prepare($sql);
            $params = [
                'user_id' => $userId,
                'calendar_day_id' => (int) $day['id'],
                'title' => $title,
                'schedule_type' => $scheduleType,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'plan_template_id' => $planTemplateId,
                'daily_plan_item_id' => $dailyPlanItemId,
                'calendar_tag_id' => $calendarTagId,
                'memo' => $memo,
            ];
            if ($hasBlocks) {
                $params['daily_plan_block_id'] = $dailyPlanBlockId;
            }
            $stmt->execute($params);

            $eventId = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $eventId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar] create event failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function updateActualEvent(
        int $userId,
        int $eventId,
        string $date,
        string $title,
        string $scheduleType,
        ?int $startIndex,
        ?int $endIndex,
        ?int $dailyPlanItemId,
        ?int $calendarTagId,
        string $memo,
        ?int $dailyPlanBlockId = null
    ): bool {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            $hasBlocks = $this->supportsDailyPlanBlocks();
            $stmt = $this->db->prepare(
                'SELECT daily_plan_item_id' . ($hasBlocks ? ', daily_plan_block_id' : ', NULL AS daily_plan_block_id') . ' FROM calendar_events
                 WHERE id = :id AND user_id = :user_id AND calendar_day_id = :calendar_day_id
                   AND deleted_at IS NULL LIMIT 1'
            );
            $stmt->execute(['id' => $eventId, 'user_id' => $userId, 'calendar_day_id' => (int) $day['id']]);
            $currentEvent = $stmt->fetch();
            if ($currentEvent === false) {
                $this->db->rollBack();
                return false;
            }
            if ($scheduleType === 'timed' && ($startIndex === null || $endIndex === null
                || $this->hasOverlappingEvent($userId, (int) $day['id'], $startIndex, $endIndex, $eventId))) {
                $this->db->rollBack();
                return false;
            }
            $currentPlanItemId = $currentEvent['daily_plan_item_id'] === null
                ? null
                : (int) $currentEvent['daily_plan_item_id'];
            if ($dailyPlanBlockId === null
                && $dailyPlanItemId !== null
                && $dailyPlanItemId === $currentPlanItemId
                && $currentEvent['daily_plan_block_id'] !== null) {
                $dailyPlanBlockId = (int) $currentEvent['daily_plan_block_id'];
            }

            if ($dailyPlanItemId !== null && $dailyPlanBlockId === null
                && !$this->canUseDailyPlanItem($userId, (int) $day['id'], $dailyPlanItemId, $eventId)) {
                $this->db->rollBack();
                return false;
            }
            if (!$this->canUseDailyPlanBlock($userId, (int) $day['id'], $dailyPlanItemId, $dailyPlanBlockId)) {
                $this->db->rollBack();
                return false;
            }
            $planData = $dailyPlanItemId === null ? null : $this->dailyPlanItemLinkData($dailyPlanItemId);
            $planTemplateId = $planData['source_plan_template_id'] ?? null;
            if ($dailyPlanItemId !== null && $dailyPlanItemId !== $currentPlanItemId && $planData !== null) {
                $title = (string) $planData['title'];
            }

            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId, $eventId)) {
                $this->db->rollBack();
                return false;
            }

            $sql = 'UPDATE calendar_events
                    SET title = :title,
                        schedule_type = :schedule_type,
                        start_index = :start_index,
                        end_index = :end_index,
                        plan_template_id = :plan_template_id,
                        daily_plan_item_id = :daily_plan_item_id' . ($hasBlocks ? ', daily_plan_block_id = :daily_plan_block_id' : '') . ',
                        calendar_tag_id = :calendar_tag_id,
                        memo = :memo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                        AND user_id = :user_id
                        AND calendar_day_id = :calendar_day_id
                        AND deleted_at IS NULL';

            $stmt = $this->db->prepare($sql);
            $params = [
                'title' => $title,
                'schedule_type' => $scheduleType,
                'start_index' => $scheduleType === 'timed' ? $startIndex : null,
                'end_index' => $scheduleType === 'timed' ? $endIndex : null,
                'plan_template_id' => $planTemplateId,
                'daily_plan_item_id' => $dailyPlanItemId,
                'calendar_tag_id' => $calendarTagId,
                'memo' => $memo,
                'id' => $eventId,
                'user_id' => $userId,
                'calendar_day_id' => (int) $day['id'],
            ];
            if ($hasBlocks) {
                $params['daily_plan_block_id'] = $dailyPlanBlockId;
            }
            $stmt->execute($params);

            $oldBlockId = (int) ($currentEvent['daily_plan_block_id'] ?? 0);
            if ($oldBlockId > 0 && $oldBlockId !== (int) ($dailyPlanBlockId ?? 0)) {
                $this->resetManualStatusIfUnlinked($userId, $oldBlockId);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar] update event failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function updateActualEventTag(int $userId, int $eventId, ?int $calendarTagId): bool
    {
        try {
            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId, $eventId)) {
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE calendar_events
                 SET calendar_tag_id = :calendar_tag_id, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'calendar_tag_id' => $calendarTagId,
                'id' => $eventId,
                'user_id' => $userId,
            ]);

            return $stmt->rowCount() === 1;
        } catch (Throwable $exception) {
            error_log('[calendar] update event tag failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function createActualFromDailyPlanBlock(
        int $userId,
        string $date,
        int $dailyPlanBlockId,
        int $startIndex,
        int $endIndex,
        ?array $energySetting = null
    ): ?int {
        if (!$this->supportsDailyPlanBlocks() || $startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) {
            return null;
        }

        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            $dayId = (int) $day['id'];
            if ($this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $lock = $this->db->prepare('SELECT id FROM calendar_days WHERE id = :id AND user_id = :user_id FOR UPDATE');
                $lock->execute(['id' => $dayId, 'user_id' => $userId]);
            }
            $block = $this->findCopyableDailyPlanBlock($userId, $dayId, $dailyPlanBlockId);
            if ($block === null || $this->hasOverlappingEvent($userId, $dayId, $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO calendar_events (
                    user_id, calendar_day_id, title, schedule_type, start_index, end_index,
                    plan_template_id, daily_plan_item_id, daily_plan_block_id, calendar_tag_id, memo,
                    deleted_at, created_at, updated_at
                ) VALUES (
                    :user_id, :calendar_day_id, :title, \'timed\', :start_index, :end_index,
                    :plan_template_id, :daily_plan_item_id, :daily_plan_block_id, NULL, \'\',
                    NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )'
            );
            $stmt->execute([
                'user_id' => $userId,
                'calendar_day_id' => $dayId,
                'title' => $block['title'],
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'plan_template_id' => $block['source_plan_template_id'],
                'daily_plan_item_id' => $block['daily_plan_item_id'],
                'daily_plan_block_id' => $dailyPlanBlockId,
            ]);

            $eventId = (int) $this->db->lastInsertId();
            if ($energySetting !== null) {
                (new ExecutionEnergyRepository())->upsertEventSetting($userId, $eventId, [
                    'group_id_snapshot' => null,
                    'group_name_snapshot' => '직접 설정',
                    'effect_type' => (string) $energySetting['effectType'],
                    'energy_value' => (int) $energySetting['value'],
                    'is_override' => 1,
                ]);
            }
            $this->db->commit();
            return $eventId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] copy plan block to actual failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function updateActualEventTimeRange(
        int $userId,
        int $eventId,
        string $date,
        int $startIndex,
        int $endIndex
    ): bool {
        if ($startIndex < 0 || $endIndex > 144 || $startIndex >= $endIndex) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            $dayId = (int) $day['id'];
            $event = $this->db->prepare(
                'SELECT id FROM calendar_events
                 WHERE id = :id AND user_id = :user_id AND calendar_day_id = :calendar_day_id
                   AND schedule_type = \'timed\' AND deleted_at IS NULL LIMIT 1'
            );
            $event->execute(['id' => $eventId, 'user_id' => $userId, 'calendar_day_id' => $dayId]);
            if ($event->fetchColumn() === false || $this->hasOverlappingEvent($userId, $dayId, $startIndex, $endIndex, $eventId)) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE calendar_events
                 SET start_index = :start_index, end_index = :end_index, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND user_id = :user_id AND calendar_day_id = :calendar_day_id
                   AND schedule_type = \'timed\' AND deleted_at IS NULL'
            );
            $stmt->execute([
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'id' => $eventId,
                'user_id' => $userId,
                'calendar_day_id' => $dayId,
            ]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[calendar] resize actual event failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function scheduleUnscheduledEvent(
        int $userId,
        int $eventId,
        string $date,
        string $title,
        int $startIndex,
        int $endIndex,
        ?int $dailyPlanItemId,
        ?int $calendarTagId,
        string $memo
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            $dayId = (int) $day['id'];

            if ($this->hasOverlappingEvent($userId, $dayId, $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }

            if ($dailyPlanItemId !== null && !$this->canUseDailyPlanItem($userId, $dayId, $dailyPlanItemId, $eventId)) {
                $this->db->rollBack();
                return null;
            }
            $planData = $dailyPlanItemId === null ? null : $this->dailyPlanItemLinkData($dailyPlanItemId);
            $planTemplateId = $planData['source_plan_template_id'] ?? null;
            if ($planData !== null) {
                $title = (string) $planData['title'];
            }

            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId, $eventId)) {
                $this->db->rollBack();
                return null;
            }

            $sql = 'UPDATE calendar_events
                    SET title = :title,
                        schedule_type = \'timed\',
                        start_index = :start_index,
                        end_index = :end_index,
                        plan_template_id = :plan_template_id,
                        daily_plan_item_id = :daily_plan_item_id,
                        calendar_tag_id = :calendar_tag_id,
                        memo = :memo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                        AND user_id = :user_id
                        AND calendar_day_id = :calendar_day_id
                        AND schedule_type = \'unscheduled\'
                        AND deleted_at IS NULL';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'plan_template_id' => $planTemplateId,
                'daily_plan_item_id' => $dailyPlanItemId,
                'calendar_tag_id' => $calendarTagId,
                'memo' => $memo,
                'id' => $eventId,
                'user_id' => $userId,
                'calendar_day_id' => $dayId,
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return null;
            }

            $this->db->commit();
            return $eventId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar] schedule untimed event failed: ' . $exception->getMessage());
            return null;
        }
    }

    public function softDeleteEvent(int $userId, int $eventId): bool
    {
        try {
            $this->db->beginTransaction();
            $blockColumn = $this->supportsDailyPlanBlocks() ? 'daily_plan_block_id' : 'NULL AS daily_plan_block_id';
            $event = $this->db->prepare('SELECT ' . $blockColumn . ' FROM calendar_events WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL');
            $event->execute(['id' => $eventId, 'user_id' => $userId]);
            $row = $event->fetch();
            if ($row === false) { $this->db->rollBack(); return false; }
            $sql = 'UPDATE calendar_events
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $eventId, 'user_id' => $userId]);
            if ($stmt->rowCount() === 0) { $this->db->rollBack(); return false; }
            if ((int) ($row['daily_plan_block_id'] ?? 0) > 0) $this->resetManualStatusIfUnlinked($userId, (int) $row['daily_plan_block_id']);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('[calendar] delete event failed: ' . $exception->getMessage());
            return false;
        }
    }

    private function resetManualStatusIfUnlinked(int $userId, int $blockId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE daily_plan_blocks SET manual_status = 'pending', updated_at = CURRENT_TIMESTAMP
             WHERE id = :block_id
               AND EXISTS (SELECT 1 FROM daily_plan_items dpi INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
                           WHERE dpi.id = daily_plan_blocks.daily_plan_item_id AND dp.user_id = :owner_id)
               AND NOT EXISTS (SELECT 1 FROM calendar_events ce WHERE ce.daily_plan_block_id = :linked_block_id
                               AND ce.user_id = :event_user_id AND ce.deleted_at IS NULL)"
        );
        $stmt->execute(['block_id' => $blockId, 'owner_id' => $userId, 'linked_block_id' => $blockId, 'event_user_id' => $userId]);
    }

    private function clearEventPlanLinks(int $userId, int $calendarDayId): void
    {
        $sql = 'UPDATE calendar_events
                SET plan_template_id = NULL,
                    daily_plan_item_id = NULL' . ($this->supportsDailyPlanBlocks() ? ',
                    daily_plan_block_id = NULL' : '') . ',
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
                    AND calendar_day_id = :calendar_day_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_day_id' => $calendarDayId,
        ]);
    }

    private function hasOverlappingEvent(
        int $userId,
        int $calendarDayId,
        int $startIndex,
        int $endIndex,
        ?int $excludeEventId = null
    ): bool
    {
        $sql = 'SELECT id
                FROM calendar_events
                WHERE user_id = :user_id
                    AND calendar_day_id = :calendar_day_id
                    AND deleted_at IS NULL
                    AND schedule_type = \'timed\'
                    AND :start_index < end_index
                    AND :end_index > start_index
                    ' . ($excludeEventId !== null ? 'AND id <> :exclude_event_id' : '') . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = [
            'user_id' => $userId,
            'calendar_day_id' => $calendarDayId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ];
        if ($excludeEventId !== null) {
            $params['exclude_event_id'] = $excludeEventId;
        }
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function hasOverlappingDailyPlanItem(
        int $dailyPlanId,
        int $startIndex,
        int $endIndex,
        ?int $excludeItemId = null
    ): bool {
        $sql = 'SELECT id FROM daily_plan_items
                WHERE daily_plan_id = :daily_plan_id
                  AND deleted_at IS NULL
                  AND :start_index < end_index
                  AND :end_index > start_index
                  ' . ($excludeItemId !== null ? 'AND id <> :exclude_item_id' : '') . '
                LIMIT 1';
        $params = [
            'daily_plan_id' => $dailyPlanId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ];
        if ($excludeItemId !== null) {
            $params['exclude_item_id'] = $excludeItemId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function hasOverlappingDailyPlanBlock(
        int $dailyPlanItemId,
        int $startIndex,
        int $endIndex,
        ?int $excludeBlockId = null
    ): bool {
        $sql = 'SELECT id FROM daily_plan_blocks
                WHERE daily_plan_item_id = :daily_plan_item_id
                  AND :start_index < end_index
                  AND :end_index > start_index
                  ' . ($excludeBlockId !== null ? 'AND id <> :exclude_block_id' : '') . '
                LIMIT 1';
        $params = [
            'daily_plan_item_id' => $dailyPlanItemId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ];
        if ($excludeBlockId !== null) {
            $params['exclude_block_id'] = $excludeBlockId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function hasOverlappingDailyPlanBlockForDay(
        int $calendarDayId,
        int $startIndex,
        int $endIndex,
        ?int $excludeBlockId = null
    ): bool {
        $sql = 'SELECT dpb.id
                FROM daily_plan_blocks dpb
                INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
                INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
                WHERE dp.calendar_day_id = :calendar_day_id
                  AND :start_index < dpb.end_index
                  AND :end_index > dpb.start_index
                  ' . ($excludeBlockId !== null ? 'AND dpb.id <> :exclude_block_id' : '') . '
                LIMIT 1';
        $params = [
            'calendar_day_id' => $calendarDayId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ];
        if ($excludeBlockId !== null) {
            $params['exclude_block_id'] = $excludeBlockId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function createInitialDailyPlanBlock(int $dailyPlanItemId, int $startIndex, int $endIndex): ?int
    {
        if (!$this->supportsDailyPlanBlocks()) {
            return null;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO daily_plan_blocks (
                daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at
             ) VALUES (:daily_plan_item_id, :start_index, :end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'daily_plan_item_id' => $dailyPlanItemId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ]);
        $blockId = (int) $this->db->lastInsertId();
        $this->snapshotDailyPlanBlock($blockId, $dailyPlanItemId);
        return $blockId;
    }

    private function snapshotDailyPlanBlock(int $blockId, int $itemId): void
    {
        $stmt = $this->db->prepare('UPDATE daily_plan_blocks SET title_snapshot = (SELECT title FROM daily_plan_items WHERE id = :item_title), importance_snapshot = (SELECT importance FROM daily_plan_items WHERE id = :item_importance), goal_id_snapshot = (SELECT goal_id FROM daily_plan_items WHERE id = :item_goal) WHERE id = :block_id');
        $stmt->execute(['item_title' => $itemId, 'item_importance' => $itemId, 'item_goal' => $itemId, 'block_id' => $blockId]);
    }

    private function synchronizeLegacyDailyPlanBlock(
        int $dailyPlanItemId,
        ?int $startIndex,
        ?int $endIndex
    ): void {
        if (!$this->supportsDailyPlanBlocks()) {
            return;
        }
        $stmt = $this->db->prepare(
            'SELECT id FROM daily_plan_blocks
             WHERE daily_plan_item_id = :daily_plan_item_id
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['daily_plan_item_id' => $dailyPlanItemId]);
        $blocks = $stmt->fetchAll();

        if (count($blocks) > 1) {
            return;
        }
        if ($startIndex !== null && $endIndex !== null) {
            if ($blocks === []) {
                $this->createInitialDailyPlanBlock($dailyPlanItemId, $startIndex, $endIndex);
                return;
            }
            $update = $this->db->prepare(
                'UPDATE daily_plan_blocks
                 SET start_index = :start_index, end_index = :end_index, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $update->execute([
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'id' => (int) $blocks[0]['id'],
            ]);
            return;
        }
        if ($blocks === []) {
            return;
        }

        $blockId = (int) $blocks[0]['id'];
        $clear = $this->db->prepare(
            'UPDATE calendar_events
             SET daily_plan_block_id = NULL, updated_at = CURRENT_TIMESTAMP
             WHERE daily_plan_block_id = :daily_plan_block_id AND deleted_at IS NULL'
        );
        $clear->execute(['daily_plan_block_id' => $blockId]);
        $delete = $this->db->prepare('DELETE FROM daily_plan_blocks WHERE id = :id');
        $delete->execute(['id' => $blockId]);
    }

    /** @return array<string, mixed>|null */
    private function findOwnedDailyPlanItem(int $userId, int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dpi.*, dp.calendar_day_id,
                    (SELECT ce.id FROM calendar_events ce
                     WHERE ce.daily_plan_item_id = dpi.id
                       AND ce.user_id = dp.user_id
                       AND ce.deleted_at IS NULL
                     LIMIT 1) AS linked_event_id
             FROM daily_plan_items dpi
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             WHERE dpi.id = :id AND dp.user_id = :user_id AND dpi.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $itemId, 'user_id' => $userId]);
        $item = $stmt->fetch();

        return $item !== false ? $item : null;
    }

    /** @return array<string, mixed>|null */
    private function findOwnedDailyPlanBlock(int $userId, int $blockId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dpb.id, dpb.daily_plan_item_id, dp.calendar_day_id, dpi.source_block_template_id
             FROM daily_plan_blocks dpb
             INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             WHERE dpb.id = :id AND dp.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $blockId, 'user_id' => $userId]);
        $block = $stmt->fetch();

        return $block !== false ? $block : null;
    }

    public function planBlockFollowsTodo(int $userId, int $blockId): bool
    {
        if (!$this->supportsDailyPlanBlocks() || !$this->supportsTodoStatus()) return false;
        $block = $this->findOwnedDailyPlanBlock($userId, $blockId);
        return $block !== null && $block['source_block_template_id'] === null;
    }

    private function userOwnsGoal(int $userId, int $goalId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM goals
             WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL AND status = :status
             LIMIT 1'
        );
        $stmt->execute(['id' => $goalId, 'user_id' => $userId, 'status' => 'active']);

        return $stmt->fetchColumn() !== false;
    }

    private function userOwnsPlanGroup(int $userId, int $planGroupId): bool
    {
        $sql = 'SELECT id
                FROM plan_groups
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $planGroupId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function canUseDailyPlanItem(int $userId, int $calendarDayId, int $dailyPlanItemId, ?int $excludeEventId = null): bool
    {
        $sql = 'SELECT dpi.id
                FROM daily_plan_items dpi
                INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
                WHERE dp.calendar_day_id = :calendar_day_id
                    AND dp.user_id = :day_user_id
                    AND dpi.id = :daily_plan_item_id
                    AND dpi.deleted_at IS NULL
                    AND NOT EXISTS (
                        SELECT 1
                        FROM calendar_events ce
                        WHERE ce.calendar_day_id = dp.calendar_day_id
                            AND ce.user_id = :event_user_id
                            AND ce.daily_plan_item_id = dpi.id
                            AND ce.deleted_at IS NULL
                            ' . ($excludeEventId !== null ? 'AND ce.id <> :exclude_event_id' : '') . '
                    )
                LIMIT 1';

        $params = [
            'calendar_day_id' => $calendarDayId,
            'day_user_id' => $userId,
            'event_user_id' => $userId,
            'daily_plan_item_id' => $dailyPlanItemId,
        ];

        if ($excludeEventId !== null) {
            $params['exclude_event_id'] = $excludeEventId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function canUseDailyPlanBlock(
        int $userId,
        int $calendarDayId,
        ?int $dailyPlanItemId,
        ?int $dailyPlanBlockId
    ): bool {
        if ($dailyPlanBlockId === null) {
            return true;
        }
        if ($dailyPlanItemId === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT dpb.id
             FROM daily_plan_blocks dpb
             INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             WHERE dpb.id = :daily_plan_block_id
               AND dpb.daily_plan_item_id = :daily_plan_item_id
               AND dp.calendar_day_id = :calendar_day_id
               AND dp.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'daily_plan_block_id' => $dailyPlanBlockId,
            'daily_plan_item_id' => $dailyPlanItemId,
            'calendar_day_id' => $calendarDayId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array{daily_plan_item_id: int, title: string, source_plan_template_id: int|null}|null */
    private function findCopyableDailyPlanBlock(int $userId, int $calendarDayId, int $dailyPlanBlockId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT dpb.daily_plan_item_id, COALESCE(dpb.title_snapshot, dpi.title) AS title, dpi.source_plan_template_id
             FROM daily_plan_blocks dpb
             INNER JOIN daily_plan_items dpi ON dpi.id = dpb.daily_plan_item_id
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             WHERE dpb.id = :daily_plan_block_id
               AND dp.calendar_day_id = :calendar_day_id
               AND dp.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            'daily_plan_block_id' => $dailyPlanBlockId,
            'calendar_day_id' => $calendarDayId,
            'user_id' => $userId,
        ]);
        $block = $stmt->fetch();

        if ($block === false) {
            return null;
        }

        return [
            'daily_plan_item_id' => (int) $block['daily_plan_item_id'],
            'title' => (string) $block['title'],
            'source_plan_template_id' => $block['source_plan_template_id'] === null
                ? null
                : (int) $block['source_plan_template_id'],
        ];
    }

    /** @return array{title: string, source_plan_template_id: int|null}|null */
    private function dailyPlanItemLinkData(int $dailyPlanItemId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT title, source_plan_template_id
             FROM daily_plan_items
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $dailyPlanItemId]);
        $item = $stmt->fetch();

        if ($item === false) {
            return null;
        }

        return [
            'title' => (string) $item['title'],
            'source_plan_template_id' => $item['source_plan_template_id'] === null
                ? null
                : (int) $item['source_plan_template_id'],
        ];
    }

    private function calendarTagExists(int $userId, int $calendarTagId, ?int $currentEventId = null): bool
    {
        $sql = 'SELECT ct.id
                FROM calendar_tags ct
                LEFT JOIN calendar_tag_preferences ctp
                    ON ctp.tag_id = ct.id AND ctp.user_id = :preference_user_id
                WHERE ct.id = :id
                    AND ct.deleted_at IS NULL
                    AND (ct.is_system = 1 OR ct.user_id = :tag_user_id)
                    AND (
                        ct.is_system = 0
                        OR COALESCE(ctp.is_enabled, 1) = 1';

        $params = [
            'preference_user_id' => $userId,
            'id' => $calendarTagId,
            'tag_user_id' => $userId,
        ];

        if ($currentEventId !== null) {
            $sql .= ' OR EXISTS (
                            SELECT 1 FROM calendar_events ce
                            WHERE ce.id = :current_event_id
                                AND ce.user_id = :event_user_id
                                AND ce.calendar_tag_id = ct.id
                                AND ce.deleted_at IS NULL
                        )';
            $params['current_event_id'] = $currentEventId;
            $params['event_user_id'] = $userId;
        }

        $sql .= ')
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }
}
