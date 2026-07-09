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
        $sql = 'SELECT id, user_id, palette_id, slug, name, color_hex, sort_order, is_system
                FROM calendar_tags
                WHERE deleted_at IS NULL
                    AND (is_system = 1 OR user_id = :user_id)
                ORDER BY is_system DESC, sort_order ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

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
