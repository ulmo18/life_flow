<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require __DIR__ . '/../../config/database.php';
        $driver = strtolower((string) ($config['driver'] ?? 'sqlite'));

        $dsn = self::buildDsn($driver, $config);
        $username = $driver === 'sqlite' ? null : (string) ($config['username'] ?? '');
        $password = $driver === 'sqlite' ? null : (string) ($config['password'] ?? '');

        try {
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if ($driver === 'mysql') {
                // Keep DATETIME/CURRENT_TIMESTAMP values consistent across hosts.
                // User-facing day boundaries use the signed-in user's IANA time zone in PHP.
                self::$connection->exec("SET time_zone = '+00:00'");
            } elseif ($driver === 'sqlite') {
                self::ensureSqliteSchemaReady(self::$connection);
            }
        } catch (PDOException $exception) {
            http_response_code(500);
            echo 'Database connection failed.';
            exit;
        }

        return self::$connection;
    }

    /** @param array<string, mixed> $config */
    private static function buildDsn(string $driver, array $config): string
    {
        if ($driver === 'mysql') {
            return sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                (string) ($config['host'] ?? '127.0.0.1'),
                (int) ($config['port'] ?? 3306),
                (string) ($config['database'] ?? ''),
                (string) ($config['charset'] ?? 'utf8mb4')
            );
        }

        if ($driver === 'pgsql') {
            return sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                (string) ($config['host'] ?? '127.0.0.1'),
                (int) ($config['port'] ?? 5432),
                (string) ($config['database'] ?? '')
            );
        }

        // sqlite fallback (default when env is not configured)
        $sqlitePath = (string) ($config['sqlite_path'] ?? (__DIR__ . '/../../storage/database.sqlite'));
        $sqliteDir = dirname($sqlitePath);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0775, true);
        }

        return 'sqlite:' . $sqlitePath;
    }

    private static function ensureSqliteSchemaReady(PDO $connection): void
    {
        $exists = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user' LIMIT 1");
        if ($exists !== false && $exists->fetchColumn() !== false) {
            self::ensureSqliteUserConsentColumns($connection);
            self::ensureSqliteRoutineGoalColumn($connection);
            self::ensureSqliteRoutineLifecycle($connection);
            self::ensureSqliteCalendarScheduleType($connection);
            self::ensureSqliteDailyPlans($connection);
            self::ensureSqliteUntimedDailyPlans($connection);
            self::ensureSqliteDailyPlanEstimatedDuration($connection);
            self::ensureSqliteDailyPlanBlocks($connection);
            self::ensureSqliteRetrospectEventMemo($connection);
            self::ensureSqliteRetrospectReviewColumns($connection);
            self::ensureSqliteRetrospectExecutionEnergyColumns($connection);
            self::ensureSqliteRetrospectPlanBlockSnapshot($connection);
            self::ensureSqliteUntimedRetrospectPlans($connection);
            self::ensureSqlitePlanBlockTemplates($connection);
            self::ensureSqliteExecutionEnergy($connection);
            self::ensureSqliteGoalFocusColumns($connection);
            self::ensureSqliteSchemaObjects($connection);
            self::ensureSqliteWellnessEventLink($connection);
            return;
        }

        $schemaPath = __DIR__ . '/../../sql/schema.sqlite.sql';
        if (!is_file($schemaPath)) {
            throw new PDOException('SQLite schema file not found: ' . $schemaPath);
        }

        $schemaSql = file_get_contents($schemaPath);
        if (!is_string($schemaSql) || trim($schemaSql) === '') {
            throw new PDOException('SQLite schema file is empty.');
        }

        $connection->exec($schemaSql);
    }

    private static function ensureSqliteUserConsentColumns(PDO $connection): void
    {
        $columns = $connection->query('PRAGMA table_info(user)');
        $columnNames = $columns !== false
            ? array_column($columns->fetchAll(), 'name')
            : [];

        if (!in_array('terms_agreed_at', $columnNames, true)) {
            $connection->exec('ALTER TABLE user ADD COLUMN terms_agreed_at TEXT NULL');
        }

        if (!in_array('privacy_agreed_at', $columnNames, true)) {
            $connection->exec('ALTER TABLE user ADD COLUMN privacy_agreed_at TEXT NULL');
        }

    }

    private static function ensureSqliteWellnessEventLink(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='wellness_entries' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(wellness_entries)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        if (!in_array('calendar_event_id', $columnNames, true)) {
            $connection->exec('ALTER TABLE wellness_entries ADD COLUMN calendar_event_id INTEGER NULL REFERENCES calendar_events(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
        $connection->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_wellness_calendar_event ON wellness_entries(calendar_event_id)');
    }

    private static function ensureSqliteRoutineGoalColumn(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='routines' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(routines)');
        $columnNames = $columns !== false
            ? array_column($columns->fetchAll(), 'name')
            : [];

        if (!in_array('goal_id', $columnNames, true)) {
            $connection->exec('ALTER TABLE routines ADD COLUMN goal_id INTEGER NULL');
        }
    }

    private static function ensureSqliteCalendarScheduleType(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='calendar_events' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(calendar_events)');
        $columnNames = $columns !== false
            ? array_column($columns->fetchAll(), 'name')
            : [];

        if (in_array('schedule_type', $columnNames, true)) {
            return;
        }

        $migrationPath = __DIR__ . '/../../sql/migration.add_calendar_schedule_type.sqlite.sql';
        $migrationSql = is_file($migrationPath) ? file_get_contents($migrationPath) : false;
        if (!is_string($migrationSql) || trim($migrationSql) === '') {
            throw new PDOException('SQLite calendar migration file not found.');
        }

        $connection->exec($migrationSql);
    }

    private static function ensureSqliteDailyPlans(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='calendar_events' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(calendar_events)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        if (!in_array('daily_plan_item_id', $columnNames, true)) {
            $connection->exec('ALTER TABLE calendar_events ADD COLUMN daily_plan_item_id INTEGER NULL');
        }

        $migrationPath = __DIR__ . '/../../sql/migration.daily_plans.sqlite.sql';
        $migrationSql = is_file($migrationPath) ? file_get_contents($migrationPath) : false;
        if (!is_string($migrationSql) || trim($migrationSql) === '') {
            throw new PDOException('SQLite daily plan migration file not found.');
        }

        $connection->exec($migrationSql);
    }

    private static function ensureSqliteUntimedDailyPlans(PDO $connection): void
    {
        $columns = $connection->query('PRAGMA table_info(daily_plan_items)');
        $columnRows = $columns !== false ? $columns->fetchAll() : [];
        $startColumn = null;
        foreach ($columnRows as $column) {
            if ((string) ($column['name'] ?? '') === 'start_index') {
                $startColumn = $column;
                break;
            }
        }
        if ($startColumn === null || (int) ($startColumn['notnull'] ?? 0) === 0) {
            return;
        }

        self::runSqliteMigration($connection, 'migration.daily_plan_untimed.sqlite.sql');
    }

    private static function ensureSqliteDailyPlanEstimatedDuration(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='daily_plan_items' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(daily_plan_items)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        if (!in_array('estimated_duration_minutes', $columnNames, true)) {
            self::runSqliteMigration($connection, 'migration.daily_plan_estimated_duration.sqlite.sql');
        }
    }

    private static function ensureSqliteDailyPlanBlocks(PDO $connection): void
    {
        $blockTable = $connection->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='daily_plan_blocks' LIMIT 1"
        );
        $hasBlockTable = $blockTable !== false && $blockTable->fetchColumn() !== false;
        $eventColumns = $connection->query('PRAGMA table_info(calendar_events)');
        $eventColumnNames = $eventColumns !== false ? array_column($eventColumns->fetchAll(), 'name') : [];
        $hasBlockColumn = in_array('daily_plan_block_id', $eventColumnNames, true);

        if (!$hasBlockTable && !$hasBlockColumn) {
            self::runSqliteMigration($connection, 'migration.daily_plan_blocks.sqlite.sql');
            return;
        }

        if (!$hasBlockTable) {
            self::ensureSqliteSchemaObjects($connection);
        }
        if (!$hasBlockColumn) {
            $connection->exec('ALTER TABLE calendar_events ADD COLUMN daily_plan_block_id INTEGER NULL');
        }

        $connection->exec('CREATE INDEX IF NOT EXISTS idx_daily_plan_blocks_item_order ON daily_plan_blocks(daily_plan_item_id, sort_order)');
        $connection->exec('CREATE INDEX IF NOT EXISTS idx_calendar_events_daily_plan_block_id ON calendar_events(daily_plan_block_id)');
        self::backfillSqliteDailyPlanBlocks($connection);
    }

    private static function backfillSqliteDailyPlanBlocks(PDO $connection): void
    {
        $connection->exec(
            'INSERT INTO daily_plan_blocks (daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at)
             SELECT dpi.id, dpi.start_index, dpi.end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
             FROM daily_plan_items dpi
             WHERE dpi.deleted_at IS NULL
               AND dpi.start_index IS NOT NULL
               AND dpi.end_index IS NOT NULL
               AND NOT EXISTS (
                 SELECT 1 FROM daily_plan_blocks dpb WHERE dpb.daily_plan_item_id = dpi.id
               )'
        );
        $connection->exec(
            'UPDATE calendar_events
             SET daily_plan_block_id = (
               SELECT dpb.id FROM daily_plan_blocks dpb
               WHERE dpb.daily_plan_item_id = calendar_events.daily_plan_item_id
               ORDER BY dpb.sort_order ASC, dpb.id ASC
               LIMIT 1
             )
             WHERE daily_plan_item_id IS NOT NULL AND daily_plan_block_id IS NULL'
        );
    }

    private static function ensureSqliteUntimedRetrospectPlans(PDO $connection): void
    {
        $columns = $connection->query('PRAGMA table_info(retrospect_report_plan_items)');
        $columnRows = $columns !== false ? $columns->fetchAll() : [];
        $startColumn = null;
        foreach ($columnRows as $column) {
            if ((string) ($column['name'] ?? '') === 'start_index') {
                $startColumn = $column;
                break;
            }
        }
        if ($startColumn === null || (int) ($startColumn['notnull'] ?? 0) === 0) {
            return;
        }

        self::runSqliteMigration($connection, 'migration.retrospect_plan_untimed.sqlite.sql');
    }

    private static function runSqliteMigration(PDO $connection, string $filename): void
    {
        $migrationPath = __DIR__ . '/../../sql/' . $filename;
        $migrationSql = is_file($migrationPath) ? file_get_contents($migrationPath) : false;
        if (!is_string($migrationSql) || trim($migrationSql) === '') {
            throw new PDOException('SQLite migration file not found: ' . $filename);
        }

        $connection->exec($migrationSql);
    }

    private static function ensureSqlitePlanBlockTemplates(PDO $connection): void
    {
        $table = $connection->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='plan_block_templates' LIMIT 1"
        );
        if ($table !== false && $table->fetchColumn() !== false) {
            return;
        }

        self::runSqliteMigration($connection, 'migration.plan_block_templates.sqlite.sql');
    }

    private static function ensureSqliteExecutionEnergy(PDO $connection): void
    {
        $table = $connection->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='execution_energy_profiles' LIMIT 1"
        );
        if ($table !== false && $table->fetchColumn() !== false) {
            $columns = $connection->query('PRAGMA table_info(execution_energy_days)');
            $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
            if (!in_array('burden_feedback', $columnNames, true)) {
                $connection->exec('ALTER TABLE execution_energy_days ADD COLUMN burden_feedback TEXT NULL');
            }
            $noticeTable = $connection->query(
                "SELECT sql FROM sqlite_master WHERE type='table' AND name='execution_energy_notices' LIMIT 1"
            );
            $noticeTableSql = $noticeTable !== false ? (string) ($noticeTable->fetchColumn() ?: '') : '';
            if ($noticeTableSql !== '' && preg_match('/notice_level\s+IN\s*\(\s*0\s*,\s*25\s*,\s*50\s*\)/i', $noticeTableSql) !== 1) {
                self::runSqliteMigration($connection, 'migration.execution_energy_zero_notice.sqlite.sql');
            }
            return;
        }

        self::runSqliteMigration($connection, 'migration.add_execution_energy.sqlite.sql');
    }

    private static function ensureSqliteRoutineLifecycle(PDO $connection): void
    {
        $stmt = $connection->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='routines' LIMIT 1");
        $tableSql = $stmt !== false ? (string) ($stmt->fetchColumn() ?: '') : '';
        if ($tableSql === '') {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(routines)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        $hasLifecycleColumns = in_array('status', $columnNames, true) && in_array('ended_at', $columnNames, true);
        $supportsShortRoutines = stripos($tableSql, 'duration_days >= 1') !== false;
        if ($hasLifecycleColumns && $supportsShortRoutines) {
            return;
        }

        $migrationPath = __DIR__ . '/../../sql/migration.routine_lifecycle.sqlite.sql';
        $migrationSql = is_file($migrationPath) ? file_get_contents($migrationPath) : false;
        if (!is_string($migrationSql) || trim($migrationSql) === '') {
            throw new PDOException('SQLite routine lifecycle migration file not found.');
        }

        $connection->exec($migrationSql);
    }

    private static function ensureSqliteRetrospectEventMemo(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='retrospect_report_actual_items' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }

        $columns = $connection->query('PRAGMA table_info(retrospect_report_actual_items)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        if (!in_array('memo_snapshot', $columnNames, true)) {
            $connection->exec('ALTER TABLE retrospect_report_actual_items ADD COLUMN memo_snapshot TEXT NULL');
        }
    }

    private static function ensureSqliteRetrospectReviewColumns(PDO $connection): void
    {
        foreach ([
            'retrospect_report_plan_items' => 'goal_title_snapshot',
            'retrospect_report_routine_items' => 'goal_title_snapshot',
        ] as $table => $column) {
            $exists = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='" . $table . "' LIMIT 1");
            if ($exists === false || $exists->fetchColumn() === false) {
                continue;
            }

            $columns = $connection->query('PRAGMA table_info(' . $table . ')');
            $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
            if (!in_array($column, $columnNames, true)) {
                $connection->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' TEXT NULL');
            }
        }
    }

    private static function ensureSqliteRetrospectExecutionEnergyColumns(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='retrospect_report_actual_items' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }
        $columns = $connection->query('PRAGMA table_info(retrospect_report_actual_items)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        foreach (['energy_group_name_snapshot' => 'TEXT NULL', 'energy_effect_type_snapshot' => 'TEXT NULL', 'energy_value_snapshot' => 'INTEGER NULL'] as $column => $definition) {
            if (!in_array($column, $columnNames, true)) {
                $connection->exec('ALTER TABLE retrospect_report_actual_items ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    private static function ensureSqliteRetrospectPlanBlockSnapshot(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='retrospect_report_actual_items' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) return;
        $columns = $connection->query('PRAGMA table_info(retrospect_report_actual_items)');
        $names = $columns === false ? [] : array_column($columns->fetchAll(), 'name');
        if (!in_array('daily_plan_block_id_snapshot', $names, true)) {
            $connection->exec('ALTER TABLE retrospect_report_actual_items ADD COLUMN daily_plan_block_id_snapshot INTEGER NULL');
        }
    }

    private static function ensureSqliteGoalFocusColumns(PDO $connection): void
    {
        $table = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='goals' LIMIT 1");
        if ($table === false || $table->fetchColumn() === false) {
            return;
        }
        $columns = $connection->query('PRAGMA table_info(goals)');
        $columnNames = $columns !== false ? array_column($columns->fetchAll(), 'name') : [];
        if (!in_array('is_focused', $columnNames, true)) {
            $connection->exec('ALTER TABLE goals ADD COLUMN is_focused INTEGER NOT NULL DEFAULT 0 CHECK (is_focused IN (0, 1))');
        }
        if (!in_array('focused_at', $columnNames, true)) {
            $connection->exec('ALTER TABLE goals ADD COLUMN focused_at TEXT NULL');
        }
        $connection->exec('CREATE INDEX IF NOT EXISTS idx_goals_user_focus ON goals(user_id, is_focused, focused_at)');
    }

    private static function ensureSqliteSchemaObjects(PDO $connection): void
    {
        $schemaPath = __DIR__ . '/../../sql/schema.sqlite.sql';
        if (!is_file($schemaPath)) {
            return;
        }

        $schemaSql = file_get_contents($schemaPath);
        if (is_string($schemaSql) && trim($schemaSql) !== '') {
            $connection->exec($schemaSql);
        }
    }

    public static function configuredDriver(): string
    {
        $config = require __DIR__ . '/../../config/database.php';

        return strtolower((string) ($config['driver'] ?? 'sqlite'));
    }

    public static function recommendedSchemaFile(): string
    {
        $driver = self::configuredDriver();

        // Driver-to-schema mapping for local setup/bootstrap:
        // mysql/pgsql -> sql/schema.mysql.sql (pgsql uses same logical schema, type tuning may be needed later)
        // sqlite      -> sql/schema.sqlite.sql
        return $driver === 'sqlite' ? 'sql/schema.sqlite.sql' : 'sql/schema.mysql.sql';
    }

}
