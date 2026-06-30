<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SocialAccount
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByProviderAndProviderUserId(string $provider, string $providerUserId): ?array
    {
        $sql = 'SELECT id, user_id, provider, provider_user_id, email FROM social_accounts
                WHERE provider = :provider AND provider_user_id = :provider_user_id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
        ]);

        $account = $stmt->fetch();

        return $account !== false ? $account : null;
    }

    public function findByUserIdAndProvider(int $userId, string $provider): ?array
    {
        $sql = 'SELECT id, user_id, provider, provider_user_id, email FROM social_accounts
                WHERE user_id = :user_id AND provider = :provider LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
        ]);

        $account = $stmt->fetch();

        return $account !== false ? $account : null;
    }

    public function create(int $userId, string $provider, string $providerUserId, string $email): bool
    {
        $sql = 'INSERT INTO social_accounts (user_id, provider, provider_user_id, email, created_at, updated_at)
                VALUES (:user_id, :provider, :provider_user_id, :email, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
            'email' => $email,
        ]);
    }
}
