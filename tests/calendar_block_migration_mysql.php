<?php

declare(strict_types=1);

// Temporary tables are connection-local; this test never edits persistent tables.
$config = require __DIR__ . '/../config/database.php';
if (($config['driver'] ?? '') !== 'mysql') {
    fwrite(STDERR, "SKIP: MySQL configuration required.\n");
    exit(2);
}
$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']);
$db = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('CREATE TEMPORARY TABLE daily_plan_items (id BIGINT UNSIGNED PRIMARY KEY, title VARCHAR(80), importance CHAR(1), goal_id BIGINT UNSIGNED NULL)');
$db->exec('CREATE TEMPORARY TABLE daily_plan_blocks (id BIGINT UNSIGNED PRIMARY KEY, daily_plan_item_id BIGINT UNSIGNED NOT NULL)');
$db->exec("INSERT INTO daily_plan_items VALUES (1, 'Original', 'B', NULL), (2, 'Other', 'A', 42)");
$db->exec('INSERT INTO daily_plan_blocks VALUES (1, 1), (2, 1), (3, 2)');
$migration = (string) file_get_contents(__DIR__ . '/../sql/migration.calendar_block_snapshots.mysql.sql');
foreach (explode(';', $migration) as $statement) {
    $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement) ?? '');
    if ($statement !== '') $db->exec($statement);
}
$rows = $db->query('SELECT title_snapshot, importance_snapshot, goal_id_snapshot FROM daily_plan_blocks ORDER BY id')->fetchAll();
if (count($rows) !== 3 || $rows[0]['title_snapshot'] !== 'Original' || $rows[0]['importance_snapshot'] !== 'B' || $rows[0]['goal_id_snapshot'] !== null || (int) $rows[2]['goal_id_snapshot'] !== 42) {
    throw new RuntimeException('MySQL migration did not preserve block snapshots.');
}
$db->exec("UPDATE daily_plan_items SET title = 'Changed', importance = 'D' WHERE id = 1");
$row = $db->query('SELECT title_snapshot, importance_snapshot FROM daily_plan_blocks WHERE id = 1')->fetch();
if ($row['title_snapshot'] !== 'Original' || $row['importance_snapshot'] !== 'B') throw new RuntimeException('Snapshot changed with source.');
fwrite(STDOUT, "PASS MySQL draft migrates temporary block snapshots independently\n");
