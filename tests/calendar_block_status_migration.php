<?php

declare(strict_types=1);

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE daily_plan_blocks (id INTEGER PRIMARY KEY, daily_plan_item_id INTEGER NOT NULL)');
$db->exec('INSERT INTO daily_plan_blocks (id, daily_plan_item_id) VALUES (1, 11), (2, 11)');
$db->exec((string) file_get_contents(__DIR__ . '/../sql/migration.daily_plan_block_status.sqlite.sql'));
$rows = $db->query('SELECT id, daily_plan_item_id, manual_status FROM daily_plan_blocks ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
if ($rows !== [
    ['id' => 1, 'daily_plan_item_id' => 11, 'manual_status' => 'pending'],
    ['id' => 2, 'daily_plan_item_id' => 11, 'manual_status' => 'pending'],
]) {
    throw new RuntimeException('Existing blocks must retain their data and receive pending status.');
}
fwrite(STDOUT, "PASS SQLite block status migration preserves existing blocks\n");
