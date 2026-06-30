<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class RememberToken
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId, string $selector, string $tokenHash, string $expiresAt): bool
    {
        $sql = 'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at, created_at, updated_at)
                VALUES (:user_id, :selector, :token_hash, :expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findBySelector(string $selector): ?array
    {
        $sql = 'SELECT id, user_id, selector, token_hash, expires_at FROM remember_tokens WHERE selector = :selector LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['selector' => $selector]);

        $token = $stmt->fetch();

        return $token !== false ? $token : null;
    }

    public function deleteBySelector(string $selector): bool
    {
        $sql = 'DELETE FROM remember_tokens WHERE selector = :selector';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['selector' => $selector]);
    }

    public function deleteByUserId(int $userId): bool
    {
        $sql = 'DELETE FROM remember_tokens WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['user_id' => $userId]);
    }
}
