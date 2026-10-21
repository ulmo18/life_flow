<?php

declare(strict_types=1);

/*
 * STEP 0 regression baseline.
 *
 * Run with: php tests/step0_baseline.php
 *
 * This runner never loads .env. It creates one unique SQLite database under
 * the OS temporary directory and deletes only that exact file on shutdown.
 */

use App\Core\Database;
use App\Models\CalendarRepository;
use App\Models\RetrospectRepository;
use App\Services\RetrospectService;
use App\Services\TodoService;

$root = dirname(__DIR__);
$testDbPath = tempnam(sys_get_temp_dir(), 'lifeflow-step0-');
if ($testDbPath === false) {
    fwrite(STDERR, "Unable to allocate an isolated SQLite test database.\n");
    exit(1);
}

putenv('DB_CONNECTION=sqlite');
putenv('DB_DRIVER=sqlite');
putenv('SQLITE_PATH=' . $testDbPath);
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['SQLITE_PATH'] = $testDbPath;
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['SQLITE_PATH'] = $testDbPath;
date_default_timezone_set('Asia/Seoul');

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
require $root . '/app/Core/helpers.php';

register_shutdown_function(static function () use ($testDbPath): void {
    if (is_file($testDbPath)) {
        @unlink($testDbPath);
    }
});

/** @param mixed $actual @param mixed $expected */
function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
    }
}

function assertTrueValue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function seedUser(PDO $db, string $email): int
{
    $stmt = $db->prepare(
        'INSERT INTO user (email, password_hash, nickname, role, is_active, created_at, updated_at)
         VALUES (:email, :password_hash, :nickname, :role, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
    );
    $stmt->execute([
        'email' => $email,
        'password_hash' => password_hash('test-password', PASSWORD_DEFAULT),
        'nickname' => 'STEP 0 Test',
        'role' => 'user',
    ]);

    return (int) $db->lastInsertId();
}

function findActual(array $events, int $eventId): array
{
    foreach ($events as $event) {
        if ((int) $event['id'] === $eventId) {
            return $event;
        }
    }

    throw new RuntimeException('Expected actual event was not returned.');
}

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "SKIP: pdo_sqlite is required for the isolated STEP 0 baseline.\n");
    exit(2);
}

$tests = [];
$tests['SQLite schema and MySQL parity markers'] = static function () use ($root): void {
    $sqlite = (string) file_get_contents($root . '/sql/schema.sqlite.sql');
    $mysql = (string) file_get_contents($root . '/sql/schema.mysql.sql');

    assertTrueValue(str_contains($sqlite, 'daily_plan_items'), 'SQLite schema must contain daily_plan_items.');
    assertTrueValue(str_contains($sqlite, 'daily_plan_item_id'), 'SQLite schema must retain the Actual-to-Plan link.');
    assertTrueValue(str_contains($sqlite, 'daily_plan_blocks'), 'SQLite schema must contain daily_plan_blocks.');
    assertTrueValue(str_contains($sqlite, 'daily_plan_block_id'), 'SQLite schema must contain the optional Actual-to-Plan-block link.');
    assertTrueValue(str_contains($mysql, 'daily_plan_items'), 'MySQL schema must contain daily_plan_items.');
    assertTrueValue(str_contains($mysql, 'daily_plan_item_id'), 'MySQL schema must retain the Actual-to-Plan link.');
    assertTrueValue(str_contains($mysql, 'daily_plan_blocks'), 'MySQL schema must contain daily_plan_blocks.');
    assertTrueValue(str_contains($mysql, 'daily_plan_block_id'), 'MySQL schema must contain the optional Actual-to-Plan-block link.');
    assertTrueValue(str_contains($sqlite, 'INTEGER PRIMARY KEY AUTOINCREMENT'), 'SQLite uses INTEGER AUTOINCREMENT identifiers.');
    assertTrueValue(str_contains($mysql, 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'), 'MySQL uses BIGINT AUTO_INCREMENT identifiers.');
};

$tests['daily plan block migration backfills timed plans without duplicating them'] = static function () use ($root): void {
    $path = tempnam(sys_get_temp_dir(), 'lifeflow-block-migration-');
    if ($path === false) {
        throw new RuntimeException('Unable to allocate SQLite migration fixture.');
    }
    try {
        $db = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE daily_plan_items (id INTEGER PRIMARY KEY, deleted_at TEXT NULL, start_index INTEGER NULL, end_index INTEGER NULL)');
        $db->exec('CREATE TABLE calendar_events (id INTEGER PRIMARY KEY, daily_plan_item_id INTEGER NULL)');
        $db->exec('INSERT INTO daily_plan_items (id, deleted_at, start_index, end_index) VALUES (1, NULL, 60, 66)');
        $db->exec('INSERT INTO calendar_events (id, daily_plan_item_id) VALUES (1, 1)');
        $migration = (string) file_get_contents($root . '/sql/migration.daily_plan_blocks.sqlite.sql');
        $db->exec($migration);

        assertSameValue(1, (int) $db->query('SELECT COUNT(*) FROM daily_plan_blocks WHERE daily_plan_item_id = 1')->fetchColumn(), 'Timed legacy plan must receive one block.');
        assertSameValue(1, (int) $db->query('SELECT daily_plan_block_id FROM calendar_events WHERE id = 1')->fetchColumn(), 'Legacy Actual must point to the migrated block.');

        $db->exec('INSERT INTO daily_plan_blocks (daily_plan_item_id, start_index, end_index, sort_order, created_at, updated_at)
                   SELECT dpi.id, dpi.start_index, dpi.end_index, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                   FROM daily_plan_items dpi
                   WHERE dpi.deleted_at IS NULL AND dpi.start_index IS NOT NULL AND dpi.end_index IS NOT NULL
                     AND NOT EXISTS (SELECT 1 FROM daily_plan_blocks dpb WHERE dpb.daily_plan_item_id = dpi.id)');
        assertSameValue(1, (int) $db->query('SELECT COUNT(*) FROM daily_plan_blocks WHERE daily_plan_item_id = 1')->fetchColumn(), 'Rerunning the backfill must not duplicate a block.');
    } finally {
        if (is_file($path)) {
            @unlink($path);
        }
    }
};

$tests['untimed To-do creates one date-specific daily plan item'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'untimed@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');

    $result = (new TodoService())->createToday($userId, 'Untimed task');
    assertTrueValue((bool) $result['ok'], 'Untimed To-do item should be created through the To-do service.');

    $day = $repository->findDay($userId, $date);
    assertTrueValue($day !== null, 'A calendar day should be created for the item.');
    $items = $repository->getDailyPlanItems($userId, (int) $day['id']);
    assertSameValue(1, count($items), 'Exactly one daily plan item should exist.');
    assertSameValue(null, $items[0]['start_index'], 'Untimed item start index must remain null.');
    assertSameValue(null, $items[0]['end_index'], 'Untimed item end index must remain null.');
};

$tests['daily plan item can receive and change a timed range'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'plan-edit@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Editable plan', 'B', null, null, null, null);
    assertTrueValue($itemId !== null, 'Plan item should be created.');

    assertTrueValue($repository->updateDailyPlanItem($userId, (int) $itemId, 'Editable plan', 'A', null, 60, 66, null, 'plan_only'), 'Initial time assignment should succeed.');
    assertTrueValue($repository->updateDailyPlanItem($userId, (int) $itemId, 'Edited plan', 'A', null, 63, 68, null, 'plan_only'), 'Timed plan modification should succeed.');

    $day = $repository->findDay($userId, $date);
    $items = $repository->getDailyPlanItems($userId, (int) $day['id']);
    assertSameValue(63, (int) $items[0]['start_index'], 'Modified start index must persist.');
    assertSameValue(68, (int) $items[0]['end_index'], 'Modified end index must persist.');
    assertSameValue('Edited plan', (string) $items[0]['title'], 'Modified title must persist.');
};

$tests['a plan block can be stored and linked to an Actual without replacing the existing plan link'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'block-link@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Multi-block plan', 'B', null, null, null, null);
    assertTrueValue($itemId !== null, 'Untimed plan item should be created for block storage.');

    $blockId = $repository->createDailyPlanBlock($userId, (int) $itemId, 60, 66);
    assertTrueValue($blockId !== null, 'A plan block should be stored under its plan item.');
    $secondBlockId = $repository->createDailyPlanBlock($userId, (int) $itemId, 84, 90);
    assertTrueValue($secondBlockId !== null, 'A second non-overlapping plan block should be supported.');
    assertSameValue(2, count($repository->getDailyPlanBlocks($userId, (int) $itemId)), 'Both blocks should be returned for the same activity.');
    assertTrueValue($repository->updateDailyPlanBlock($userId, (int) $secondBlockId, 84, 93), 'One block should be editable without changing its sibling.');
    $blocks = $repository->getDailyPlanBlocks($userId, (int) $itemId);
    assertSameValue(66, (int) $blocks[0]['end_index'], 'The first block must keep its original range.');
    assertSameValue(93, (int) $blocks[1]['end_index'], 'The edited block range must persist.');

    $eventId = $repository->createActualEvent($userId, $date, 'Multi-block plan', 'timed', 60, 66, (int) $itemId, null, '', (int) $blockId);
    assertTrueValue($eventId !== null, 'Actual event should accept a block belonging to its plan item.');
    $day = $repository->findDay($userId, $date);
    $actual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $eventId);
    assertSameValue((int) $itemId, (int) $actual['daily_plan_item_id'], 'Existing Actual-to-Plan link must remain populated.');
    assertSameValue((int) $blockId, (int) $actual['daily_plan_block_id'], 'Actual-to-Plan-block link must be returned.');

    $secondActualId = $repository->createActualFromDailyPlanBlock($userId, $date, (int) $blockId, 72, 78);
    assertTrueValue($secondActualId !== null, 'One plan block must allow another non-overlapping Actual copy.');
    $secondActual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $secondActualId);
    assertSameValue((int) $itemId, (int) $secondActual['daily_plan_item_id'], 'Copied Actual must preserve its legacy plan-item link.');
    assertSameValue((int) $blockId, (int) $secondActual['daily_plan_block_id'], 'Copied Actual must preserve its plan-block link.');
    assertTrueValue($repository->updateActualEventTimeRange($userId, (int) $secondActualId, $date, 66, 78), 'Actual time resize should preserve linked data.');
    $resizedSecondActual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $secondActualId);
    assertSameValue((int) $blockId, (int) $resizedSecondActual['daily_plan_block_id'], 'Actual resize must not clear its plan-block link.');

    assertTrueValue($repository->updateActualEvent($userId, (int) $eventId, $date, 'Multi-block plan', 'timed', 60, 66, (int) $itemId, null, 'edited'), 'Existing Actual update should preserve its block link when no new block is supplied.');
    $updated = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $eventId);
    assertSameValue((int) $blockId, (int) $updated['daily_plan_block_id'], 'Backward-compatible Actual update must not clear the block link.');
    assertTrueValue($repository->deleteDailyPlanBlock($userId, (int) $blockId), 'A single plan block should be deletable.');
    $preservedActual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $eventId);
    assertSameValue((int) $itemId, (int) $preservedActual['daily_plan_item_id'], 'Deleting a block must retain the Actual-to-Plan item link.');
    assertSameValue(null, $preservedActual['daily_plan_block_id'], 'Deleting a block must only clear its optional Actual block link.');
    assertSameValue(1, count($repository->getDailyPlanBlocks($userId, (int) $itemId)), 'Deleting one block must retain its sibling.');
};

$tests['existing Actual link is one-to-one and independent Actual events remain editable'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'actual-link@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Linked plan', 'C', null, 42, 48, null);
    assertTrueValue($itemId !== null, 'Linked plan should be created.');

    $linkedEventId = $repository->createActualEvent($userId, $date, 'Linked plan', 'timed', 42, 48, (int) $itemId, null, '');
    assertTrueValue($linkedEventId !== null, 'First linked Actual should be created.');
    $secondLink = $repository->createActualEvent($userId, $date, 'Duplicate link', 'timed', 54, 60, (int) $itemId, null, '');
    assertSameValue(null, $secondLink, 'Current baseline must reject a second active Actual link.');

    $independentId = $repository->createActualEvent($userId, $date, 'Independent Actual', 'timed', 66, 72, null, null, 'first');
    assertTrueValue($independentId !== null, 'Independent Actual should be created.');
    assertTrueValue($repository->updateActualEvent($userId, (int) $independentId, $date, 'Edited Actual', 'timed', 67, 73, null, null, 'edited'), 'Independent Actual update should succeed.');

    $day = $repository->findDay($userId, $date);
    $actual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $independentId);
    assertSameValue('Edited Actual', (string) $actual['title'], 'Actual title update must persist.');
    assertSameValue(67, (int) $actual['start_index'], 'Actual range update must persist.');
};

$tests['Retrospect aggregates multiple plan blocks and Actuals without duplicate counting'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'retrospect-blocks@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Development study', 'B', null, null, null, null);
    assertTrueValue($itemId !== null, 'Comparison fixture activity should be created.');
    $firstBlock = $repository->createDailyPlanBlock($userId, (int) $itemId, 60, 66);
    $secondBlock = $repository->createDailyPlanBlock($userId, (int) $itemId, 84, 90);
    assertTrueValue($firstBlock !== null && $secondBlock !== null, 'Comparison fixture blocks should be created.');
    assertTrueValue($repository->createActualFromDailyPlanBlock($userId, $date, (int) $firstBlock, 62, 65) !== null, 'First block Actual should be created.');
    assertTrueValue($repository->createActualFromDailyPlanBlock($userId, $date, (int) $firstBlock, 66, 68) !== null, 'Second Actual for one block should be created.');
    assertTrueValue($repository->createActualFromDailyPlanBlock($userId, $date, (int) $secondBlock, 85, 90) !== null, 'Second block Actual should be created.');
    assertTrueValue($repository->createActualEvent($userId, $date, 'Phone call', 'timed', 96, 99, null, null, '') !== null, 'Unplanned Actual should be created.');

    $page = (new RetrospectService())->getPageData($userId, $date, 'time');
    assertSameValue(120, (int) $page['summary']['plannedMinutes'], 'All plan blocks must be summed.');
    assertSameValue(130, (int) $page['summary']['actualMinutes'], 'All Actual records must be summed once.');
    assertSameValue(100, (int) $page['summary']['linkedActualMinutes'], 'Linked Actual records must be summed once.');
    assertSameValue(30, (int) $page['summary']['unplannedActualMinutes'], 'Unplanned Actual records must stay separate.');
    assertSameValue(-20, (int) $page['activityComparisons'][0]['differenceMinutes'], 'Activity comparison must use actual minus planned minutes.');
};

$tests['To-do completion is derived from a linked Actual event'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-complete@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Complete me', 'A', null, 78, 84, null);
    assertTrueValue($itemId !== null, 'To-do plan should be created.');

    $result = (new TodoService())->complete($userId, (int) $itemId, null, null);
    assertTrueValue((bool) $result['ok'], 'Completing a timed To-do should create a linked Actual event.');
    $todo = (new TodoService())->getTodayData($userId);
    assertSameValue(1, count($todo['completedItems']), 'A linked Actual must place the item in the completed group.');
    assertSameValue((int) $itemId, (int) $todo['completedItems'][0]['id'], 'Completed To-do must be the linked plan item.');
};

$tests['deleting a plan preserves its Actual record and clears the link'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'plan-delete@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Delete plan only', 'D', null, 90, 96, null);
    $eventId = $repository->createActualEvent($userId, $date, 'Delete plan only', 'timed', 90, 96, (int) $itemId, null, 'keep me');
    assertTrueValue($eventId !== null, 'Linked Actual should be created.');
    assertTrueValue($repository->deleteDailyPlanItem($userId, (int) $itemId), 'Plan deletion should succeed.');

    $day = $repository->findDay($userId, $date);
    $actual = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $eventId);
    assertSameValue(null, $actual['daily_plan_item_id'], 'Actual event must remain after plan deletion with a cleared link.');
    assertSameValue('keep me', (string) $actual['memo'], 'Actual event content must remain after plan deletion.');
};

$tests['Retrospect saves KPT and preserves a published snapshot'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'retrospect@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Snapshot plan', 'B', null, 102, 108, null);
    $eventId = $repository->createActualEvent($userId, $date, 'Snapshot plan', 'timed', 102, 108, (int) $itemId, null, 'snapshot memo');
    assertTrueValue($eventId !== null, 'Snapshot fixture Actual should be created.');

    $service = new RetrospectService();
    $texts = ['today_review' => 'Keep text', 'today_thoughts' => 'Problem text', 'tomorrow_plan' => 'Try text'];
    assertTrueValue($service->saveDraft($userId, $date, $texts), 'KPT draft should save for today.');
    $live = $service->getPageData($userId, $date, 'time');
    assertSameValue(1, (int) $live['summary']['linkedActualCount'], 'Live Retrospect must count the linked Actual event.');
    assertSameValue(60, (int) $live['summary']['linkedActualMinutes'], 'Live Retrospect must calculate linked Actual minutes from indexes.');
    assertTrueValue($service->publish($userId, $date, $texts), 'Retrospect publish should create a snapshot.');

    $reportRepository = new RetrospectRepository();
    $report = $reportRepository->findReport($userId, $date);
    assertSameValue('submitted', (string) $report['status'], 'Published report status must persist.');
    assertSameValue('Keep text', (string) $report['today_review'], 'Keep text must persist in the report.');
    $snapshotActuals = $reportRepository->listActualItems((int) $report['id'], 'time');
    assertSameValue('Snapshot plan', (string) $snapshotActuals[0]['title_snapshot'], 'Published snapshot must include the original Actual title.');

    assertTrueValue($repository->updateActualEvent($userId, (int) $eventId, $date, 'Changed after publish', 'timed', 102, 108, (int) $itemId, null, 'changed'), 'Source Actual mutation should succeed after publishing.');
    $preservedSnapshot = $reportRepository->listActualItems((int) $report['id'], 'time');
    assertSameValue('Snapshot plan', (string) $preservedSnapshot[0]['title_snapshot'], 'Published snapshot must not change with source data.');
};

$passed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "PASS  {$name}\n");
    } catch (Throwable $exception) {
        fwrite(STDERR, "FAIL  {$name}\n" . $exception->getMessage() . "\n");
        exit(1);
    }
}

fwrite(STDOUT, "{$passed}/" . count($tests) . " STEP 0 baseline tests passed.\n");
