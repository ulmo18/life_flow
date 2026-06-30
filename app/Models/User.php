<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function existsByEmail(string $email): bool
    {
        $sql = 'SELECT id FROM `user` WHERE email = :email LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    public function findById(int $userId): ?array
    {
        $sql = 'SELECT id, email, password_hash, nickname, role, is_active FROM `user` WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT id, email, password_hash, nickname, role, is_active FROM `user` WHERE email = :email LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user !== false ? $user : null;
    }

    public function deactivateById(int $userId): bool
    {
        $sql = 'UPDATE `user` SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $userId]);
    }

    public function create(string $email, string $passwordHash, string $nickname, bool $privacyAgreed): bool
    {
        $sql = 'INSERT INTO `user` (
                    email, password_hash, nickname, role, is_active, terms_agreed_at, privacy_agreed_at, created_at, updated_at
                ) VALUES (
                    :email, :password_hash, :nickname, :role, :is_active, :terms_agreed_at, :privacy_agreed_at,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';

        $stmt = $this->db->prepare($sql);
        $agreedAt = gmdate('Y-m-d H:i:s');

        return $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'nickname' => $nickname,
            'role' => 'user',
            'is_active' => 1,
            'terms_agreed_at' => $agreedAt,
            'privacy_agreed_at' => $privacyAgreed ? $agreedAt : null,
        ]);
    }

    public function createGoogleUser(string $email, string $nickname, bool $privacyAgreed): ?int
    {
        $randomPasswordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        $sql = 'INSERT INTO `user` (
                    email, password_hash, nickname, role, is_active, terms_agreed_at, privacy_agreed_at, created_at, updated_at
                ) VALUES (
                    :email, :password_hash, :nickname, :role, :is_active, :terms_agreed_at, :privacy_agreed_at,
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )';
        $stmt = $this->db->prepare($sql);
        $agreedAt = gmdate('Y-m-d H:i:s');
        $ok = $stmt->execute([
            'email' => $email,
            'password_hash' => $randomPasswordHash,
            'nickname' => $nickname,
            'role' => 'user',
            'is_active' => 1,
            'terms_agreed_at' => $agreedAt,
            'privacy_agreed_at' => $privacyAgreed ? $agreedAt : null,
        ]);

        if (!$ok) {
            return null;
        }

        return (int) $this->db->lastInsertId();
    }
}
