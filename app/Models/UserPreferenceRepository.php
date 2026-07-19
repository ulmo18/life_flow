<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class UserPreferenceRepository
{
    private const DEFAULTS = [
        'theme' => 'light',
        'notification_enabled' => 1,
        'retrospect_morning_enabled' => 1,
        'retrospect_morning_time' => '07:00',
        'retrospect_evening_enabled' => 1,
        'retrospect_evening_time' => '20:00',
        'routine_reminder_enabled' => 1,
        'routine_reminder_time' => '14:00',
        'calendar_plan_reminder_enabled' => 1,
        'goal_deadline_reminder_enabled' => 1,
        'goal_deadline_time' => '12:00',
        'goal_deadline_day_before_enabled' => 1,
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->ensureNotificationColumns();
    }

    /** @return array<string, mixed> */
    public function get(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                theme,
                notification_enabled,
                retrospect_morning_enabled,
                retrospect_morning_time,
                retrospect_evening_enabled,
                retrospect_evening_time,
                routine_reminder_enabled,
                routine_reminder_time,
                calendar_plan_reminder_enabled,
                goal_deadline_reminder_enabled,
                goal_deadline_time,
                goal_deadline_day_before_enabled
             FROM user_preferences
             WHERE user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $preferences = $stmt->fetch();

        if ($preferences !== false) {
            return $this->withDefaults($preferences);
        }

        $this->updateTheme($userId, 'light');

        return self::DEFAULTS;
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

    /** @param array<string, mixed> $settings */
    public function updateNotificationSettings(int $userId, array $settings): void
    {
        $settings = $this->withDefaults($settings);

        if (Database::configuredDriver() === 'mysql') {
            $sql = 'INSERT INTO user_preferences (
                        user_id,
                        theme,
                        notification_enabled,
                        retrospect_morning_enabled,
                        retrospect_morning_time,
                        retrospect_evening_enabled,
                        retrospect_evening_time,
                        routine_reminder_enabled,
                        routine_reminder_time,
                        calendar_plan_reminder_enabled,
                        goal_deadline_reminder_enabled,
                        goal_deadline_time,
                        goal_deadline_day_before_enabled,
                        created_at,
                        updated_at
                    ) VALUES (
                        :user_id,
                        :theme,
                        :notification_enabled,
                        :retrospect_morning_enabled,
                        :retrospect_morning_time,
                        :retrospect_evening_enabled,
                        :retrospect_evening_time,
                        :routine_reminder_enabled,
                        :routine_reminder_time,
                        :calendar_plan_reminder_enabled,
                        :goal_deadline_reminder_enabled,
                        :goal_deadline_time,
                        :goal_deadline_day_before_enabled,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )
                    ON DUPLICATE KEY UPDATE
                        notification_enabled = VALUES(notification_enabled),
                        retrospect_morning_enabled = VALUES(retrospect_morning_enabled),
                        retrospect_morning_time = VALUES(retrospect_morning_time),
                        retrospect_evening_enabled = VALUES(retrospect_evening_enabled),
                        retrospect_evening_time = VALUES(retrospect_evening_time),
                        routine_reminder_enabled = VALUES(routine_reminder_enabled),
                        routine_reminder_time = VALUES(routine_reminder_time),
                        calendar_plan_reminder_enabled = VALUES(calendar_plan_reminder_enabled),
                        goal_deadline_reminder_enabled = VALUES(goal_deadline_reminder_enabled),
                        goal_deadline_time = VALUES(goal_deadline_time),
                        goal_deadline_day_before_enabled = VALUES(goal_deadline_day_before_enabled),
                        updated_at = CURRENT_TIMESTAMP';
        } else {
            $sql = 'INSERT INTO user_preferences (
                        user_id,
                        theme,
                        notification_enabled,
                        retrospect_morning_enabled,
                        retrospect_morning_time,
                        retrospect_evening_enabled,
                        retrospect_evening_time,
                        routine_reminder_enabled,
                        routine_reminder_time,
                        calendar_plan_reminder_enabled,
                        goal_deadline_reminder_enabled,
                        goal_deadline_time,
                        goal_deadline_day_before_enabled,
                        created_at,
                        updated_at
                    ) VALUES (
                        :user_id,
                        :theme,
                        :notification_enabled,
                        :retrospect_morning_enabled,
                        :retrospect_morning_time,
                        :retrospect_evening_enabled,
                        :retrospect_evening_time,
                        :routine_reminder_enabled,
                        :routine_reminder_time,
                        :calendar_plan_reminder_enabled,
                        :goal_deadline_reminder_enabled,
                        :goal_deadline_time,
                        :goal_deadline_day_before_enabled,
                        CURRENT_TIMESTAMP,
                        CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(user_id) DO UPDATE SET
                        notification_enabled = excluded.notification_enabled,
                        retrospect_morning_enabled = excluded.retrospect_morning_enabled,
                        retrospect_morning_time = excluded.retrospect_morning_time,
                        retrospect_evening_enabled = excluded.retrospect_evening_enabled,
                        retrospect_evening_time = excluded.retrospect_evening_time,
                        routine_reminder_enabled = excluded.routine_reminder_enabled,
                        routine_reminder_time = excluded.routine_reminder_time,
                        calendar_plan_reminder_enabled = excluded.calendar_plan_reminder_enabled,
                        goal_deadline_reminder_enabled = excluded.goal_deadline_reminder_enabled,
                        goal_deadline_time = excluded.goal_deadline_time,
                        goal_deadline_day_before_enabled = excluded.goal_deadline_day_before_enabled,
                        updated_at = CURRENT_TIMESTAMP';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'theme' => (string) $settings['theme'],
            'notification_enabled' => (int) $settings['notification_enabled'],
            'retrospect_morning_enabled' => (int) $settings['retrospect_morning_enabled'],
            'retrospect_morning_time' => (string) $settings['retrospect_morning_time'],
            'retrospect_evening_enabled' => (int) $settings['retrospect_evening_enabled'],
            'retrospect_evening_time' => (string) $settings['retrospect_evening_time'],
            'routine_reminder_enabled' => (int) $settings['routine_reminder_enabled'],
            'routine_reminder_time' => (string) $settings['routine_reminder_time'],
            'calendar_plan_reminder_enabled' => (int) $settings['calendar_plan_reminder_enabled'],
            'goal_deadline_reminder_enabled' => (int) $settings['goal_deadline_reminder_enabled'],
            'goal_deadline_time' => (string) $settings['goal_deadline_time'],
            'goal_deadline_day_before_enabled' => (int) $settings['goal_deadline_day_before_enabled'],
        ]);
    }

    /** @param array<string, mixed> $preferences */
    private function withDefaults(array $preferences): array
    {
        $preferences = array_merge(self::DEFAULTS, $preferences);
        foreach ([
            'retrospect_morning_time',
            'retrospect_evening_time',
            'routine_reminder_time',
            'goal_deadline_time',
        ] as $key) {
            $preferences[$key] = substr((string) $preferences[$key], 0, 5);
        }

        return $preferences;
    }

    private function ensureNotificationColumns(): void
    {
        $columns = $this->columnNames('user_preferences');
        if ($columns === []) {
            return;
        }

        $definitions = Database::configuredDriver() === 'mysql'
            ? [
                'notification_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'retrospect_morning_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'retrospect_morning_time' => "TIME NOT NULL DEFAULT '07:00:00'",
                'retrospect_evening_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'retrospect_evening_time' => "TIME NOT NULL DEFAULT '20:00:00'",
                'routine_reminder_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'routine_reminder_time' => "TIME NOT NULL DEFAULT '14:00:00'",
                'calendar_plan_reminder_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'goal_deadline_reminder_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
                'goal_deadline_time' => "TIME NOT NULL DEFAULT '12:00:00'",
                'goal_deadline_day_before_enabled' => 'TINYINT(1) NOT NULL DEFAULT 1',
            ]
            : [
                'notification_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'retrospect_morning_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'retrospect_morning_time' => "TEXT NOT NULL DEFAULT '07:00'",
                'retrospect_evening_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'retrospect_evening_time' => "TEXT NOT NULL DEFAULT '20:00'",
                'routine_reminder_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'routine_reminder_time' => "TEXT NOT NULL DEFAULT '14:00'",
                'calendar_plan_reminder_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'goal_deadline_reminder_enabled' => 'INTEGER NOT NULL DEFAULT 1',
                'goal_deadline_time' => "TEXT NOT NULL DEFAULT '12:00'",
                'goal_deadline_day_before_enabled' => 'INTEGER NOT NULL DEFAULT 1',
            ];

        foreach ($definitions as $column => $definition) {
            if (!in_array($column, $columns, true)) {
                $this->db->exec('ALTER TABLE user_preferences ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    /** @return array<int, string> */
    private function columnNames(string $table): array
    {
        if (Database::configuredDriver() === 'mysql') {
            $stmt = $this->db->query('SHOW COLUMNS FROM `' . $table . '`');

            return $stmt === false ? [] : array_map('strval', array_column($stmt->fetchAll(), 'Field'));
        }

        $stmt = $this->db->query('PRAGMA table_info(' . $table . ')');

        return $stmt === false ? [] : array_map('strval', array_column($stmt->fetchAll(), 'name'));
    }
}
