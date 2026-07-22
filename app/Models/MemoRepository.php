<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class MemoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(int $userId, string $type, string $query = ''): array
    {
        $length = $this->lengthExpression();
        $sql = 'SELECT id, content, created_at, updated_at
                FROM notes
                WHERE user_id = :user_id
                    AND deleted_at IS NULL
                    AND ' . $length . ($type === 'long' ? ' >= 300' : ' < 300');
        $params = ['user_id' => $userId];

        if ($query !== '') {
            $sql .= ' AND content LIKE :query';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY updated_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function listTrash(int $userId, string $query = ''): array
    {
        $sql = 'SELECT id, content, created_at, updated_at, deleted_at
                FROM notes
                WHERE user_id = :user_id
                    AND deleted_at IS NOT NULL';
        $params = ['user_id' => $userId];

        if ($query !== '') {
            $sql .= ' AND content LIKE :query';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY deleted_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array{short: int, long: int} */
    public function counts(int $userId): array
    {
        $length = $this->lengthExpression();
        $sql = 'SELECT
                    SUM(CASE WHEN ' . $length . ' < 300 THEN 1 ELSE 0 END) AS short_count,
                    SUM(CASE WHEN ' . $length . ' >= 300 THEN 1 ELSE 0 END) AS long_count
                FROM notes
                WHERE user_id = :user_id
                    AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch() ?: [];

        return [
            'short' => (int) ($row['short_count'] ?? 0),
            'long' => (int) ($row['long_count'] ?? 0),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listForDate(int $userId, string $date): array
    {
        $koreaTimezone = new DateTimeZone('Asia/Seoul');
        $utcTimezone = new DateTimeZone('UTC');
        $startAt = (new DateTimeImmutable($date . ' 00:00:00', $koreaTimezone))->setTimezone($utcTimezone);
        $endAt = $startAt->modify('+1 day');
        $stmt = $this->db->prepare(
            'SELECT id, content, created_at
             FROM notes
             WHERE user_id = :user_id
                AND deleted_at IS NULL
                AND created_at >= :created_start_at
                AND created_at < :created_end_at
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([
            'user_id' => $userId,
            'created_start_at' => $startAt->format('Y-m-d H:i:s'),
            'created_end_at' => $endAt->format('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchAll();
    }

    public function create(int $userId, string $content): ?int
    {
        $sql = 'INSERT INTO notes (user_id, content, deleted_at, created_at, updated_at)
                VALUES (:user_id, :content, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute(['user_id' => $userId, 'content' => $content]);

        return $ok ? (int) $this->db->lastInsertId() : null;
    }

    public function update(int $userId, int $memoId, string $content): bool
    {
        if (!$this->activeExists($userId, $memoId)) {
            return false;
        }

        $sql = 'UPDATE notes
                SET content = :content, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['content' => $content, 'id' => $memoId, 'user_id' => $userId]);
    }

    public function softDelete(int $userId, int $memoId): bool
    {
        $sql = 'UPDATE notes
                SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $memoId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function restore(int $userId, int $memoId): bool
    {
        $sql = 'UPDATE notes
                SET deleted_at = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NOT NULL';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $memoId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function purgeExpiredTrash(int $userId, string $cutoffUtc): int
    {
        $sql = 'DELETE FROM notes
                WHERE user_id = :user_id
                    AND deleted_at IS NOT NULL
                    AND deleted_at <= :cutoff_utc';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'cutoff_utc' => $cutoffUtc,
        ]);

        return $stmt->rowCount();
    }

    public function emptyTrash(int $userId): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM notes WHERE user_id = :user_id AND deleted_at IS NOT NULL'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }

    private function lengthExpression(): string
    {
        return Database::configuredDriver() === 'mysql'
            ? 'CHAR_LENGTH(TRIM(content))'
            : 'LENGTH(TRIM(content))';
    }

    private function activeExists(int $userId, int $memoId): bool
    {
        $sql = 'SELECT id FROM notes
                WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $memoId, 'user_id' => $userId]);

        return $stmt->fetchColumn() !== false;
    }
}
