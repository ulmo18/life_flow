<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class CalendarRepository
{
    private PDO $db;

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
                    ce.calendar_tag_id,
                    ce.memo,
                    COALESCE(dpi.title, pt.title) AS plan_title,
                    COALESCE(dpi.importance, pt.importance) AS plan_importance,
                    ct.name AS tag_name,
                    ct.color_hex AS tag_color
                FROM calendar_events ce
                LEFT JOIN plan_templates pt ON pt.id = ce.plan_template_id
                LEFT JOIN daily_plan_items dpi ON dpi.id = ce.daily_plan_item_id
                LEFT JOIN calendar_tags ct ON ct.id = ce.calendar_tag_id
                    AND ct.deleted_at IS NULL
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
    public function getDailyPlanItems(int $userId, int $calendarDayId): array
    {
        $stmt = $this->db->prepare(
            'SELECT dpi.id AS daily_plan_item_id, dpi.id AS plan_block_id,
                    dp.source_plan_group_id AS plan_group_id,
                    dpi.source_plan_template_id AS plan_template_id,
                    dpi.source_plan_block_id, dpi.goal_id, dpi.title, dpi.importance,
                    dpi.start_index, dpi.end_index, dpi.sort_order,
                    g.title AS goal_title,
                    (SELECT ce.id FROM calendar_events ce
                     WHERE ce.daily_plan_item_id = dpi.id
                       AND ce.user_id = dp.user_id
                       AND ce.deleted_at IS NULL
                     LIMIT 1) AS linked_event_id
             FROM daily_plan_items dpi
             INNER JOIN daily_plans dp ON dp.id = dpi.daily_plan_id
             LEFT JOIN goals g ON g.id = dpi.goal_id AND g.user_id = dp.user_id AND g.deleted_at IS NULL
             WHERE dp.user_id = :user_id
               AND dp.calendar_day_id = :calendar_day_id
               AND dpi.deleted_at IS NULL
             ORDER BY dpi.sort_order ASC, dpi.start_index ASC, dpi.id ASC'
        );
        $stmt->execute(['user_id' => $userId, 'calendar_day_id' => $calendarDayId]);

        return $stmt->fetchAll();
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

    public function setDayPlanGroup(int $userId, string $date, ?int $planGroupId): bool
    {
        if ($planGroupId !== null && !$this->userOwnsPlanGroup($userId, $planGroupId)) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
            $dailyPlan = $this->getDailyPlanForDay($userId, (int) $day['id']);
            $oldPlanGroupId = $dailyPlan !== null && $dailyPlan['source_plan_group_id'] !== null
                ? (int) $dailyPlan['source_plan_group_id']
                : null;

            if ($oldPlanGroupId === $planGroupId && $dailyPlan !== null) {
                $this->db->commit();
                return true;
            }

            $this->clearEventPlanLinks($userId, (int) $day['id']);
            if ($dailyPlan !== null) {
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
        ?int $endIndex
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);
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
                    title, importance, start_index, end_index, sort_order,
                    deleted_at, created_at, updated_at
                 ) VALUES (
                    :daily_plan_id, NULL, NULL, :goal_id, :title, :importance,
                    :start_index, :end_index,
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
            ]);
            $itemId = (int) $this->db->lastInsertId();
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
            if ($linkedEventId !== null && $linkAction === 'detach') {
                $stmt = $this->db->prepare(
                    'UPDATE calendar_events
                     SET daily_plan_item_id = NULL, plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id AND user_id = :user_id'
                );
                $stmt->execute(['id' => $linkedEventId, 'user_id' => $userId]);
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
                     start_index = :start_index, end_index = :end_index, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'title' => $title,
                'importance' => $importance,
                'goal_id' => $goalId,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'id' => $itemId,
            ]);
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
                 SET daily_plan_item_id = NULL, plan_template_id = NULL, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND daily_plan_item_id = :item_id AND deleted_at IS NULL'
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
        string $memo
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            if ($scheduleType === 'unscheduled') {
                $startIndex = null;
                $endIndex = null;
                $dailyPlanItemId = null;
            }

            if ($dailyPlanItemId !== null && !$this->canUseDailyPlanItem($userId, (int) $day['id'], $dailyPlanItemId)) {
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

            $sql = 'INSERT INTO calendar_events (
                        user_id, calendar_day_id, title, schedule_type, start_index, end_index,
                        plan_template_id, daily_plan_item_id, calendar_tag_id, memo, deleted_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :calendar_day_id, :title, :schedule_type, :start_index, :end_index,
                        :plan_template_id, :daily_plan_item_id, :calendar_tag_id, :memo, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
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
            ]);

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
        ?int $dailyPlanItemId,
        ?int $calendarTagId,
        string $memo
    ): bool {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            $stmt = $this->db->prepare(
                'SELECT daily_plan_item_id FROM calendar_events
                 WHERE id = :id AND user_id = :user_id AND calendar_day_id = :calendar_day_id
                   AND deleted_at IS NULL LIMIT 1'
            );
            $stmt->execute(['id' => $eventId, 'user_id' => $userId, 'calendar_day_id' => (int) $day['id']]);
            $currentEvent = $stmt->fetch();
            if ($currentEvent === false) {
                $this->db->rollBack();
                return false;
            }
            $currentPlanItemId = $currentEvent['daily_plan_item_id'] === null
                ? null
                : (int) $currentEvent['daily_plan_item_id'];

            if ($dailyPlanItemId !== null && !$this->canUseDailyPlanItem($userId, (int) $day['id'], $dailyPlanItemId, $eventId)) {
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
                        plan_template_id = CASE WHEN schedule_type = \'unscheduled\' THEN NULL ELSE :plan_template_id END,
                        daily_plan_item_id = CASE WHEN schedule_type = \'unscheduled\' THEN NULL ELSE :daily_plan_item_id END,
                        calendar_tag_id = :calendar_tag_id,
                        memo = :memo,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                        AND user_id = :user_id
                        AND calendar_day_id = :calendar_day_id
                        AND deleted_at IS NULL';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'title' => $title,
                'plan_template_id' => $planTemplateId,
                'daily_plan_item_id' => $dailyPlanItemId,
                'calendar_tag_id' => $calendarTagId,
                'memo' => $memo,
                'id' => $eventId,
                'user_id' => $userId,
                'calendar_day_id' => (int) $day['id'],
            ]);

            $this->db->commit();
            return $stmt->rowCount() > 0;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar] update event failed: ' . $exception->getMessage());
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
        $sql = 'UPDATE calendar_events
                SET deleted_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $eventId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function clearEventPlanLinks(int $userId, int $calendarDayId): void
    {
        $sql = 'UPDATE calendar_events
                SET plan_template_id = NULL,
                    daily_plan_item_id = NULL,
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
