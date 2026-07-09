<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserPreferenceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed> */
    public function get(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT theme
             FROM user_preferences
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $preferences = $stmt->fetch();

        if ($preferences !== false) {
            return $preferences;
        }

        $this->updateTheme($userId, 'light');

        return ['theme' => 'light'];
    }

    public function updateTheme(int $userId, string $theme): void
    {
        $theme = $theme === 'dark' ? 'dark' : 'light';

        if (Database::configuredDriver() === 'mysql') {
            $sql = 'INSERT INTO user_preferences (
                        user_id, theme, created_at, updated_at
                    ) VALUES (
                        :user_id, :theme, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON DUPLICATE KEY UPDATE
                        theme = VALUES(theme),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO user_preferences (
                        user_id, theme, created_at, updated_at
                    ) VALUES (
                        :user_id, :theme, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(user_id) DO UPDATE SET
                        theme = excluded.theme,
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'theme' => $theme,
        ]);
    }
}
