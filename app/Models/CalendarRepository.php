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
                    ce.start_index,
                    ce.end_index,
                    ce.plan_template_id,
                    ce.calendar_tag_id,
                    ce.memo,
                    pt.title AS plan_title,
                    pt.importance AS plan_importance,
                    ct.name AS tag_name,
                    ct.color_hex AS tag_color
                FROM calendar_events ce
                LEFT JOIN plan_templates pt ON pt.id = ce.plan_template_id
                LEFT JOIN calendar_tags ct ON ct.id = ce.calendar_tag_id
                    AND ct.deleted_at IS NULL
                WHERE ce.user_id = :user_id
                    AND ce.calendar_day_id = :calendar_day_id
                    AND ce.deleted_at IS NULL
                ORDER BY ce.start_index ASC, ce.id ASC';

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
        $sql = 'SELECT id, user_id, palette_id, slug, name, color_hex, sort_order, is_system
                FROM calendar_tags
                WHERE deleted_at IS NULL
                    AND (is_system = 1 OR user_id = :user_id)
                ORDER BY sort_order ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

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
            $oldPlanGroupId = isset($day['plan_group_id']) ? (int) $day['plan_group_id'] : null;

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

            if ($oldPlanGroupId !== $planGroupId) {
                $this->clearEventPlanLinks($userId, (int) $day['id']);
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

    public function createActualEvent(
        int $userId,
        string $date,
        string $title,
        int $startIndex,
        int $endIndex,
        ?int $planTemplateId,
        ?int $calendarTagId,
        string $memo
    ): ?int {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            if ($planTemplateId !== null && !$this->canUsePlanTemplate($userId, (int) $day['id'], $planTemplateId)) {
                $this->db->rollBack();
                return null;
            }

            if ($this->hasOverlappingEvent($userId, (int) $day['id'], $startIndex, $endIndex)) {
                $this->db->rollBack();
                return null;
            }

            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId)) {
                $this->db->rollBack();
                return null;
            }

            $sql = 'INSERT INTO calendar_events (
                        user_id, calendar_day_id, title, start_index, end_index,
                        plan_template_id, calendar_tag_id, memo, deleted_at, created_at, updated_at
                    ) VALUES (
                        :user_id, :calendar_day_id, :title, :start_index, :end_index,
                        :plan_template_id, :calendar_tag_id, :memo, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'calendar_day_id' => (int) $day['id'],
                'title' => $title,
                'start_index' => $startIndex,
                'end_index' => $endIndex,
                'plan_template_id' => $planTemplateId,
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
        ?int $planTemplateId,
        ?int $calendarTagId,
        string $memo
    ): bool {
        try {
            $this->db->beginTransaction();
            $day = $this->getOrCreateDay($userId, $date);

            if ($planTemplateId !== null && !$this->canUsePlanTemplate($userId, (int) $day['id'], $planTemplateId, $eventId)) {
                $this->db->rollBack();
                return false;
            }

            if ($calendarTagId !== null && !$this->calendarTagExists($userId, $calendarTagId)) {
                $this->db->rollBack();
                return false;
            }

            $sql = 'UPDATE calendar_events
                    SET title = :title,
                        plan_template_id = :plan_template_id,
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

    private function hasOverlappingEvent(int $userId, int $calendarDayId, int $startIndex, int $endIndex): bool
    {
        $sql = 'SELECT id
                FROM calendar_events
                WHERE user_id = :user_id
                    AND calendar_day_id = :calendar_day_id
                    AND deleted_at IS NULL
                    AND :start_index < end_index
                    AND :end_index > start_index
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'calendar_day_id' => $calendarDayId,
            'start_index' => $startIndex,
            'end_index' => $endIndex,
        ]);

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

    private function canUsePlanTemplate(int $userId, int $calendarDayId, int $planTemplateId, ?int $excludeEventId = null): bool
    {
        $sql = 'SELECT pt.id
                FROM calendar_days cd
                INNER JOIN plan_blocks pb ON pb.plan_group_id = cd.plan_group_id
                INNER JOIN plan_templates pt ON pt.id = pb.plan_template_id
                WHERE cd.id = :calendar_day_id
                    AND cd.user_id = :day_user_id
                    AND pt.user_id = :template_user_id
                    AND pt.id = :plan_template_id
                    AND pt.deleted_at IS NULL
                    AND NOT EXISTS (
                        SELECT 1
                        FROM calendar_events ce
                        WHERE ce.calendar_day_id = cd.id
                            AND ce.user_id = :event_user_id
                            AND ce.plan_template_id = pt.id
                            AND ce.deleted_at IS NULL
                            ' . ($excludeEventId !== null ? 'AND ce.id <> :exclude_event_id' : '') . '
                    )
                LIMIT 1';

        $params = [
            'calendar_day_id' => $calendarDayId,
            'day_user_id' => $userId,
            'template_user_id' => $userId,
            'event_user_id' => $userId,
            'plan_template_id' => $planTemplateId,
        ];

        if ($excludeEventId !== null) {
            $params['exclude_event_id'] = $excludeEventId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function calendarTagExists(int $userId, int $calendarTagId): bool
    {
        $sql = 'SELECT id
                FROM calendar_tags
                WHERE id = :id
                    AND deleted_at IS NULL
                    AND (is_system = 1 OR user_id = :user_id)
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $calendarTagId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }
}
