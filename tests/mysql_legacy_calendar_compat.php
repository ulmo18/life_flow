<?php

declare(strict_types=1);

/*
 * Runs only against an explicitly supplied disposable MySQL database.
 * Example: DB_CONNECTION=mysql DB_HOST=... php tests/mysql_legacy_calendar_compat.php
 */

use App\Core\Database;
use App\Models\CalendarRepository;
use App\Services\CalendarService;
use App\Services\RetrospectService;
use App\Services\TodoService;

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
require $root . '/app/Core/helpers.php';

if (getenv('DB_CONNECTION') !== 'mysql' || getenv('LIFEFLOW_DISPOSABLE_TEST_DB') !== '1') {
    fwrite(STDERR, "Refusing to run without an explicit disposable MySQL target.\n");
    exit(2);
}

function legacyAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $db = Database::connection();
    $email = 'legacy-' . bin2hex(random_bytes(6)) . '@example.test';
    $user = $db->prepare('INSERT INTO user (email, password_hash, nickname, role, is_active, created_at, updated_at) VALUES (:email, :password_hash, :nickname, :role, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
    $user->execute(['email' => $email, 'password_hash' => password_hash('test-password', PASSWORD_DEFAULT), 'nickname' => 'Legacy test', 'role' => 'user']);
    $userId = (int) $db->lastInsertId();
    $date = '2026-10-19';
    $calendar = new CalendarRepository();

    legacyAssert(!$calendar->supportsDailyPlanBlocks(), 'Legacy schema unexpectedly enabled plan blocks.');
    legacyAssert($calendar->getActualEvents($userId, (int) $calendar->getOrCreateDay($userId, $date)['id']) === [], 'Empty legacy Actual list should load.');
    $calendarPage = (new CalendarService())->getDayViewData($userId, $date);
    legacyAssert(($calendarPage['planBlocksEnabled'] ?? true) === false, 'Legacy Calendar page data must disable plan blocks.');
    legacyAssert(is_array((new TodoService())->getDateData($userId, $date)), 'Legacy To-do read should remain available.');
    $itemId = $calendar->createDailyPlanItem($userId, $date, 'Legacy plan', 'B', null, 60, 66, null);
    legacyAssert($itemId !== null, 'Legacy Plan creation failed.');
    legacyAssert($calendar->updateDailyPlanItem($userId, (int) $itemId, 'Legacy plan edited', 'A', null, 61, 67, null, 'plan_only'), 'Legacy Plan update failed.');
    $eventId = $calendar->createActualEvent($userId, $date, 'Legacy actual', 'timed', 61, 67, (int) $itemId, null, '');
    legacyAssert($eventId !== null, 'Legacy Actual creation failed.');
    legacyAssert($calendar->updateActualEvent($userId, (int) $eventId, $date, 'Legacy actual edited', 'timed', 61, 67, (int) $itemId, null, 'edited'), 'Legacy Actual update failed.');
    $independentEventId = $calendar->createActualEvent($userId, $date, 'Legacy independent actual', 'timed', 72, 78, null, null, '');
    legacyAssert($independentEventId !== null, 'Legacy independent Actual creation failed.');
    legacyAssert((new CalendarService())->deleteActualEvent($userId, (int) $independentEventId), 'Legacy Actual deletion failed.');
    legacyAssert($calendar->deleteDailyPlanItem($userId, (int) $itemId), 'Legacy Plan deletion failed.');
    $events = $calendar->getActualEvents($userId, (int) $calendar->findDay($userId, $date)['id']);
    legacyAssert(count($events) === 1 && $events[0]['daily_plan_item_id'] === null, 'Legacy Actual must remain after Plan deletion.');
    (new RetrospectService())->getPageData($userId, $date, 'time');

    fwrite(STDOUT, "PASS legacy MySQL Calendar, Actual, and Retrospect read compatibility\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "FAIL " . $exception->getMessage() . "\n");
    exit(1);
}
