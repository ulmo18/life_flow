<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

final class CalendarTagRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listPalettes(): array
    {
        $sql = 'SELECT id, slug, color_hex, sort_order
                FROM calendar_tag_palettes
                ORDER BY sort_order ASC, id ASC';

        return $this->db->query($sql)->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listVisibleTags(int $userId): array
    {
        $sql = 'SELECT ct.id, ct.user_id, ct.palette_id, ct.slug, ct.name, ct.color_hex,
                       ct.sort_order, ct.is_system,
                       CASE WHEN ct.is_system = 1 THEN COALESCE(ctp.is_enabled, 1) ELSE 1 END AS is_enabled
                FROM calendar_tags ct
                LEFT JOIN calendar_tag_preferences ctp
                    ON ctp.tag_id = ct.id AND ctp.user_id = :preference_user_id
                WHERE ct.deleted_at IS NULL
                    AND (ct.is_system = 1 OR ct.user_id = :tag_user_id)
                ORDER BY ct.is_system DESC, ct.sort_order ASC, ct.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'preference_user_id' => $userId,
            'tag_user_id' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findUserTag(int $userId, int $tagId): ?array
    {
        $sql = 'SELECT id, user_id, palette_id, slug, name, color_hex, sort_order, is_system
                FROM calendar_tags
                WHERE id = :id
                    AND user_id = :user_id
                    AND is_system = 0
                    AND deleted_at IS NULL
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $tagId,
            'user_id' => $userId,
        ]);

        $tag = $stmt->fetch();

        return $tag !== false ? $tag : null;
    }

    public function createUserTag(int $userId, string $name, int $paletteId, string $colorHex): ?int
    {
        $sql = 'INSERT INTO calendar_tags (
                    user_id, palette_id, slug, name, color_hex, sort_order, is_system,
                    deleted_at, created_at, updated_at
                ) VALUES (
                    :user_id, :palette_id, :slug, :name, :color_hex, :sort_order, 0,
                    NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            'user_id' => $userId,
            'palette_id' => $paletteId,
            'slug' => 'user-' . $userId . '-' . bin2hex(random_bytes(6)),
            'name' => $name,
            'color_hex' => $colorHex,
            'sort_order' => $this->nextUserSortOrder($userId),
        ]);

        return $ok ? (int) $this->db->lastInsertId() : null;
    }

    public function updateUserTag(int $userId, int $tagId, string $name, int $paletteId, string $colorHex): bool
    {
        $sql = 'UPDATE calendar_tags
                SET name = :name,
                    palette_id = :palette_id,
                    color_hex = :color_hex,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                    AND user_id = :user_id
                    AND is_system = 0
                    AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'palette_id' => $paletteId,
            'color_hex' => $colorHex,
            'id' => $tagId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function softDeleteUserTag(int $userId, int $tagId): bool
    {
        try {
            $this->db->beginTransaction();

            $sql = 'UPDATE calendar_tags
                    SET deleted_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                        AND user_id = :user_id
                        AND is_system = 0
                        AND deleted_at IS NULL';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id' => $tagId,
                'user_id' => $userId,
            ]);

            $deleted = $stmt->rowCount() > 0;
            if ($deleted) {
                $clearSql = 'UPDATE calendar_events
                             SET calendar_tag_id = NULL,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE user_id = :user_id
                                AND calendar_tag_id = :tag_id
                                AND deleted_at IS NULL';
                $clearStmt = $this->db->prepare($clearSql);
                $clearStmt->execute([
                    'user_id' => $userId,
                    'tag_id' => $tagId,
                ]);
            }

            $this->db->commit();
            return $deleted;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[calendar-tag] delete failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function setSystemTagEnabled(int $userId, int $tagId, bool $enabled): bool
    {
        $tagStmt = $this->db->prepare(
            'SELECT id FROM calendar_tags
             WHERE id = :id AND is_system = 1 AND deleted_at IS NULL
             LIMIT 1'
        );
        $tagStmt->execute(['id' => $tagId]);
        if ($tagStmt->fetchColumn() === false) {
            return false;
        }

        $findStmt = $this->db->prepare(
            'SELECT tag_id FROM calendar_tag_preferences
             WHERE user_id = :user_id AND tag_id = :tag_id
             LIMIT 1'
        );
        $findStmt->execute(['user_id' => $userId, 'tag_id' => $tagId]);

        if ($findStmt->fetchColumn() !== false) {
            $stmt = $this->db->prepare(
                'UPDATE calendar_tag_preferences
                 SET is_enabled = :is_enabled, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :user_id AND tag_id = :tag_id'
            );
        } else {
            $stmt = $this->db->prepare(
                'INSERT INTO calendar_tag_preferences
                    (user_id, tag_id, is_enabled, created_at, updated_at)
                 VALUES (:user_id, :tag_id, :is_enabled, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            );
        }

        return $stmt->execute([
            'user_id' => $userId,
            'tag_id' => $tagId,
            'is_enabled' => $enabled ? 1 : 0,
        ]);
    }

    public function paletteExists(int $paletteId): bool
    {
        $sql = 'SELECT id FROM calendar_tag_palettes WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $paletteId]);

        return $stmt->fetchColumn() !== false;
    }

    /** @return array<string, mixed>|null */
    public function findPalette(int $paletteId): ?array
    {
        $sql = 'SELECT id, slug, color_hex, sort_order
                FROM calendar_tag_palettes
                WHERE id = :id
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $paletteId]);
        $palette = $stmt->fetch();

        return $palette !== false ? $palette : null;
    }

    public function isPaletteColorUsed(int $userId, int $paletteId, ?int $excludeTagId = null): bool
    {
        $sql = 'SELECT id
                FROM calendar_tags
                WHERE palette_id = :palette_id
                    AND deleted_at IS NULL
                    AND (is_system = 1 OR user_id = :user_id)';

        $params = [
            'palette_id' => $paletteId,
            'user_id' => $userId,
        ];

        if ($excludeTagId !== null) {
            $sql .= ' AND id <> :exclude_tag_id';
            $params['exclude_tag_id'] = $excludeTagId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public function userTagNameExists(int $userId, string $name, ?int $excludeTagId = null): bool
    {
        $sql = 'SELECT id
                FROM calendar_tags
                WHERE user_id = :user_id
                    AND name = :name
                    AND deleted_at IS NULL';

        $params = [
            'user_id' => $userId,
            'name' => $name,
        ];

        if ($excludeTagId !== null) {
            $sql .= ' AND id <> :exclude_tag_id';
            $params['exclude_tag_id'] = $excludeTagId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    private function nextUserSortOrder(int $userId): int
    {
        $sql = 'SELECT COALESCE(MAX(sort_order), 100) + 1
                FROM calendar_tags
                WHERE user_id = :user_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }
}
