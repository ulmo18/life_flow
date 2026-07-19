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

            if ($driver === 'sqlite') {
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
            self::ensureSqliteCalendarScheduleType($connection);
            self::ensureSqliteSchemaObjects($connection);
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
