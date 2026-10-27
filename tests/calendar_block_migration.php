<?php

declare(strict_types=1);

// Exercise the draft migration on an isolated, disposable database only.
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE daily_plan_items (id INTEGER PRIMARY KEY, title TEXT, importance TEXT, goal_id INTEGER)');
$db->exec('CREATE TABLE daily_plan_blocks (id INTEGER PRIMARY KEY, daily_plan_item_id INTEGER NOT NULL)');
$db->exec("INSERT INTO daily_plan_items VALUES (1, 'Original', 'B', NULL), (2, 'Other', 'A', 42)");
$db->exec('INSERT INTO daily_plan_blocks VALUES (1, 1), (2, 1), (3, 2)');
$db->exec((string) file_get_contents(__DIR__ . '/../sql/migration.calendar_block_snapshots.sqlite.sql'));
$rows = $db->query('SELECT title_snapshot, importance_snapshot, goal_id_snapshot FROM daily_plan_blocks ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if ($rows !== [
    ['title_snapshot' => 'Original', 'importance_snapshot' => 'B', 'goal_id_snapshot' => null],
    ['title_snapshot' => 'Original', 'importance_snapshot' => 'B', 'goal_id_snapshot' => null],
    ['title_snapshot' => 'Other', 'importance_snapshot' => 'A', 'goal_id_snapshot' => 42],
]) {
    throw new RuntimeException('Existing block snapshots were not preserved.');
}
$db->exec("UPDATE daily_plan_items SET title = 'Changed', importance = 'D', goal_id = 12 WHERE id = 1");
$row = $db->query('SELECT title_snapshot, importance_snapshot, goal_id_snapshot FROM daily_plan_blocks WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
if ($row !== $rows[0]) throw new RuntimeException('Snapshot changed with source item.');
fwrite(STDOUT, "PASS SQLite draft migrates existing block snapshots independently\n");
