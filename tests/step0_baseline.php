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
use App\Services\CalendarService;
use App\Services\ExecutionEnergyService;

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
$tests['legacy automatic publishing setting no longer publishes on page access'] = static function (): void {
    $db = Database::connection();
    $user = seedUser($db, 'disabled-auto-publish@example.test');
    $db->prepare('INSERT INTO retrospect_settings (user_id, auto_publish_enabled, auto_publish_time) VALUES (?, 1, ?)')->execute([$user, '00:00']);
    $service = new RetrospectService();
    $view = $service->getPageData($user, date('Y-m-d'), 'time');
    assertSameValue(null, $view['report'], 'Visiting the page must not publish even with an old enabled setting.');
    assertSameValue(null, $service->getPublishedReportData($user, date('Y-m-d')), 'No published report should be created.');
    $texts = ['today_review' => 'Keep', 'today_thoughts' => '', 'tomorrow_plan' => ''];
    assertTrueValue($service->publish($user, date('Y-m-d'), $texts), 'Manual publishing must still work.');
    assertTrueValue($service->getPublishedReportData($user, date('Y-m-d')) !== null, 'Manually published report must remain available.');
};
$tests['editing published daily review shows current records while report stays frozen'] = static function (): void {
    $db = Database::connection();
    $user = seedUser($db, 'edit-published-live@example.test');
    $date = date('Y-m-d');
    $calendar = new CalendarRepository();
    $service = new RetrospectService();
    $texts = ['today_review' => 'Keep', 'today_thoughts' => 'Problem', 'tomorrow_plan' => 'Try'];
    $check = $calendar->createActualEvent($user, $date, 'Before', 'timed', 24, 27, null, null, '');
    assertTrueValue($check !== null && $service->publish($user, $date, $texts), 'Initial report must publish.');
    $later = $calendar->createActualEvent($user, $date, 'After', 'timed', 30, 33, null, null, '');
    assertTrueValue($later !== null, 'Later actual record should save.');
    $view = $service->getPageData($user, $date, 'time');
    $edit = $service->getPageData($user, $date, 'time', true);
    assertSameValue(false, $view['isEditingPublished'], 'Published review defaults to the published card.');
    assertSameValue(1, count($view['actualItems']), 'Published view retains its original snapshot.');
    assertSameValue(true, $edit['isEditingPublished'], 'Edit mode must be explicit.');
    assertSameValue(['Before', 'After'], array_column($edit['actualItems'], 'title'), 'Edit mode must include actuals recorded after publication.');
    assertSameValue($texts, $edit['texts'], 'Edit mode retains KPT text.');
    assertSameValue(1, count($service->getPublishedReportData($user, $date)['actualItems']), 'Opening edit mode must not change the published report.');
    assertTrueValue($service->republish($user, $date), 'Republication should save the current records.');
    assertSameValue(2, count($service->getPublishedReportData($user, $date)['actualItems']), 'Republication captures the later actual.');
};
$tests['Try links require explicit owned later events and survive republishing'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'try-link@example.test');
    $other = seedUser($db, 'try-link-other@example.test');
    $date = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    $repo = new CalendarRepository();
    $service = new RetrospectService();
    $texts = ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => '산책하기'];
    assertTrueValue($service->publish($userId, $date, $texts), 'Try report must publish.');
    $event = $repo->createActualEvent($userId, $today, '산책', 'timed', 24, 30, null, null, '');
    $sameDay = $repo->createActualEvent($userId, $date, '이전', 'timed', 24, 30, null, null, '');
    $otherEvent = $repo->createActualEvent($other, $today, '타인', 'timed', 24, 30, null, null, '');
    assertTrueValue(!$service->changeTryEventLink($userId, $date, (int) $sameDay, false), 'Same-day records must not be linked as later records.');
    assertTrueValue(!$service->changeTryEventLink($userId, $date, (int) $otherEvent, false), 'Another user’s event must not be linked.');
    assertTrueValue(!$service->changeTryEventLink($other, $date, (int) $event, false), 'Another user cannot link to the report.');
    assertTrueValue($service->changeTryEventLink($userId, $date, (int) $event, false), 'Own later event should link.');
    assertTrueValue($service->changeTryEventLink($userId, $date, (int) $event, false), 'Repeated linking must be idempotent.');
    $links = $service->getTryLinkData($userId, $date);
    assertSameValue(1, count($links['linked']), 'Only one link should exist.');
    assertSameValue(false, $links['linked'][0]['tryChanged'], 'Unchanged Try should match the original.');
    $texts['tomorrow_plan'] = '산책하고 쉬기';
    assertTrueValue($service->publish($userId, $date, $texts), 'Revised report should publish.');
    $links = $service->getTryLinkData($userId, $date);
    assertSameValue(1, count($links['linked']), 'Republishing must preserve the explicit link.');
    assertSameValue(true, $links['linked'][0]['tryChanged'], 'Changed Try must be identified.');
    assertSameValue('산책하기', $links['linked'][0]['try_text_snapshot'], 'Original Try text remains available.');
    $linkId = (int) $links['linked'][0]['link_id'];
    assertTrueValue(!$service->changeTryEventLink($other, $date, $linkId, true), 'Another user cannot unlink.');
    assertTrueValue($service->changeTryEventLink($userId, $date, $linkId, true), 'Owner may unlink.');
    assertSameValue(0, count($service->getTryLinkData($userId, $date)['linked']), 'Unlinked records disappear.');
    assertTrueValue($service->changeTryEventLink($userId, $date, (int) $event, false), 'Event can be linked again.');
    assertTrueValue($repo->softDeleteEvent($userId, (int) $event), 'Linked event may be deleted.');
    assertSameValue(0, count($service->getTryLinkData($userId, $date)['linked']), 'Deleted events must not be shown as active links.');
    $unavailable = $service->getTryLinkData($userId, $date)['unavailable'];
    assertSameValue(1, count($unavailable), 'Deleted events must remain visible as unavailable links.');
    assertTrueValue($service->changeTryEventLink($userId, $date, (int) $unavailable[0]['link_id'], true), 'Unavailable links can be removed.');
    assertSameValue([], $service->getTryLinkData($userId, $date)['unavailable'], 'Removed unavailable links disappear.');
    assertTrueValue(!$service->changeTryEventLink($userId, '2026-02-30', (int) $event, false), 'Invalid dates must be rejected.');
    $db->exec('ALTER TABLE retrospect_try_event_links RENAME TO test_hidden_try_links');
    try {
        assertTrueValue(!empty($service->getTryLinkData($userId, $date)['error']), 'A missing link table must produce a safe visible error.');
        assertTrueValue($service->getPublishedReportData($userId, $date) !== null, 'Link lookup failure must not prevent reading the report.');
        assertTrueValue(!$service->changeTryEventLink($userId, $date, 1, true), 'A link database error must return a safe failure.');
    } finally {
        $db->exec('ALTER TABLE test_hidden_try_links RENAME TO retrospect_try_event_links');
    }
};
$tests['published C and D plan feedback uses their direct links and priority'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'feedback-cd@example.test');
    $repo = new CalendarRepository();
    $date = date('Y-m-d');
    $cItem = $repo->createDailyPlanItem($userId, $date, 'C activity', 'C', null, null, null, null);
    $dItem = $repo->createDailyPlanItem($userId, $date, 'D activity', 'D', null, null, null, null);
    $cBlock = $repo->createDailyPlanBlock($userId, (int) $cItem, 24, 30);
    $dBlock = $repo->createDailyPlanBlock($userId, (int) $dItem, 36, 42);
    $cActual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $cBlock, 24, 27);
    $dActual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $dBlock, 36, 42);
    assertTrueValue($cActual !== null && $dActual !== null, 'C and D actual records must save.');
    $service = new RetrospectService();
    $texts = ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
    assertTrueValue($service->publish($userId, $date, $texts), 'Report must publish.');
    $feedback = $service->getPublishedReportData($userId, $date)['feedback'];
    assertSameValue('linked_c_records', $feedback['rule'] ?? null, 'C must take priority over D.');
    assertSameValue('계획 60분 · 연결된 실제 30분', $feedback['comparison'] ?? null, 'C comparison must use snapshot times.');
    assertTrueValue($repo->softDeleteEvent($userId, (int) $cActual), 'C source actual can be deleted.');
    assertSameValue('linked_c_records', $service->getPublishedReportData($userId, $date)['feedback']['rule'] ?? null, 'Published evidence remains unchanged.');
    assertTrueValue($service->republish($userId, $date), 'Report can be republished.');
    $feedback = $service->getPublishedReportData($userId, $date)['feedback'];
    assertSameValue('linked_d_records', $feedback['rule'] ?? null, 'D must appear when C no longer has a linked actual.');
    assertSameValue('계획 60분 · 연결된 실제 60분', $feedback['comparison'] ?? null, 'D comparison must use snapshot times.');
};
$tests['routine 60-day feedback uses a frozen published snapshot'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'routine-feedback@example.test');
    $other = seedUser($db, 'routine-feedback-other@example.test');
    $today = new DateTimeImmutable('today');
    $date = $today->format('Y-m-d');
    $repo = new \App\Models\RoutineRepository();
    $id = $repo->create($userId, null, '걷기', $today->modify('-120 days')->format('Y-m-d'), 121, false, null);
    assertTrueValue($id !== null, 'Routine must be created.');
    $insert = $db->prepare('INSERT INTO routine_logs (routine_id, user_id, log_date, is_done) VALUES (?, ?, ?, 1)');
    $db->beginTransaction();
    for ($i = 118; $i >= 0; $i -= 2) $insert->execute([$id, $userId, $today->modify('-' . $i . ' days')->format('Y-m-d')]);
    $db->commit();
    $service = new RetrospectService();
    $texts = ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
    assertTrueValue($service->publish($userId, $date, $texts), 'Report must publish.');
    $report = $service->getPublishedReportData($userId, $date);
    assertSameValue('routine_60_days', $report['feedback']['rule'] ?? null, '60 nonconsecutive days must be recognized.');
    assertSameValue(null, $service->getPublishedReportData($other, $date), 'Another user cannot read the report.');
    $db->prepare('DELETE FROM routine_logs WHERE routine_id = ? AND log_date = ?')->execute([$id, $today->modify('-118 days')->format('Y-m-d')]);
    $report = $service->getPublishedReportData($userId, $date);
    assertSameValue('routine_60_days', $report['feedback']['rule'] ?? null, 'Published evidence must remain frozen.');
    assertTrueValue($service->republish($userId, $date), 'Report can be republished.');
    $report = $service->getPublishedReportData($userId, $date);
    assertSameValue('routine_period_comparison', $report['feedback']['rule'] ?? null, 'Republishing should recalculate the 59-day snapshot and use the two-week comparison.');
    assertSameValue('‘걷기’ 루틴의 완료 기록이 3일에서 4일로 바뀌었어요.', $report['feedback']['message'] ?? null, 'The two complete seven-day windows should be counted separately.');
    $reportId = (int) (new RetrospectRepository())->findReport($userId, $date)['id'];
    $snapshot = (new RetrospectRepository())->listRoutineItems($reportId)[0];
    $data = json_decode((string) $snapshot['completed_dates_snapshot'], true, 512, JSON_THROW_ON_ERROR);
    assertSameValue(2, $data['version'] ?? null, 'New published routine evidence must identify its snapshot version.');
    $legacyJson = json_encode($data['dates'], JSON_THROW_ON_ERROR);
    $db->prepare('UPDATE retrospect_report_routine_items SET completed_dates_snapshot = ? WHERE report_id = ?')->execute([$legacyJson, $reportId]);
    assertSameValue(null, $service->getPublishedReportData($userId, $date)['feedback'], 'Legacy arrays must not be interpreted as complete period evidence.');
    $oldDates = $data['dates'];
    array_unshift($oldDates, $today->modify('-118 days')->format('Y-m-d'));
    $db->prepare('UPDATE retrospect_report_routine_items SET completed_dates_snapshot = ? WHERE report_id = ?')->execute([json_encode($oldDates, JSON_THROW_ON_ERROR), $reportId]);
    assertSameValue('routine_60_days', $service->getPublishedReportData($userId, $date)['feedback']['rule'] ?? null, 'Legacy arrays must retain their supported 60-day milestone.');
};
$tests['plan block manual status remains independent and completion follows actual links'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'block-status@example.test');
    $otherUser = seedUser($db, 'block-status-other@example.test');
    $repo = new CalendarRepository();
    $service = new CalendarService();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Status activity', 'B', null, null, null, null);
    $first = $repo->createDailyPlanBlock($userId, (int) $item, 24, 30);
    $second = $repo->createDailyPlanBlock($userId, (int) $item, 36, 42);
    assertTrueValue($first !== null && $second !== null, 'Two blocks must exist.');
    foreach (['cancelled', 'external', 'pending'] as $status) {
        assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $second, 'B', false, $status), 'Each manual status should save.');
    }
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $first, 'B', false, 'postponed'), 'Manual status should save.');
    assertTrueValue(!$service->updateDailyPlanBlockDetails($userId, (int) $second, 'B', false, 'completed'), 'Completed must not be manually selectable.');
    assertTrueValue(!$service->updateDailyPlanBlockDetails($otherUser, (int) $first, 'B', false, 'cancelled'), 'Other users cannot update status.');
    $day = $repo->findDay($userId, $date);
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue(['postponed', 'pending'], array_column($blocks, 'manual_status'), 'Each block must retain its own manual status.');
    $segments = $service->getDayViewData($userId, $date)['planSegments'];
    assertSameValue('pending', $segments[0]['manualStatus'], 'Calendar follows the To-do state rather than the stored legacy block state.');
    $actual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $first, 24, 27);
    assertTrueValue($actual !== null, 'Linked actual should save.');
    $secondActual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $first, 27, 30);
    assertTrueValue($secondActual !== null, 'A second Actual can link to the same block.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertTrueValue((bool) $blocks[0]['is_linked'], 'Actual linkage indicates completion.');
    $segments = $service->getDayViewData($userId, $date)['planSegments'];
    assertTrueValue($segments[0]['isLinked'], 'Calendar must receive completion from the linked actual.');
    assertSameValue(2, $segments[0]['actualCount'], 'Calendar detail should show both Actual records.');
    assertSameValue(60, $segments[0]['actualMinutes'], 'Calendar detail should show total Actual minutes.');
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $first, 'B', false, 'in_progress'), 'A linked block can be in progress.');
    assertTrueValue($service->updateDailyPlanBlockStatus($userId, (int) $first, 'completed'), 'A linked block can be completed.');
    assertTrueValue($repo->softDeleteEvent($userId, (int) $actual), 'One Actual can be deleted.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertTrueValue((bool) $blocks[0]['is_linked'], 'Remaining Actual keeps the block completed.');
    assertSameValue('completed', $blocks[0]['manual_status'], 'Completed remains while a link remains.');
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $first, 'B', true), 'Actual links can be removed.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue('pending', $blocks[0]['manual_status'], 'Removing the final link resets manual status.');
    assertTrueValue(!(bool) $blocks[0]['is_linked'], 'Unlink removes completion.');
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $second, 'B', false, 'cancelled'), 'Second block status should save.');
    $deletedActual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $second, 36, 39);
    assertTrueValue($deletedActual !== null && $repo->softDeleteEvent($userId, (int) $deletedActual), 'Last Actual can be deleted.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue('pending', $blocks[1]['manual_status'], 'Deleting the last Actual resets the state.');
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $second, 'B', false, 'external'), 'Status should be editable after deletion.');
    $editedActual = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $second, 39, 42);
    assertTrueValue($editedActual !== null && $repo->updateActualEvent($userId, (int) $editedActual, $date, 'Actual', 'timed', 39, 42, null, null, ''), 'Editing an Actual can remove the plan link.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue('pending', $blocks[1]['manual_status'], 'Changing an Actual link resets the state.');
    $thirdItem = $repo->createDailyPlanItem($userId, $date, 'Detach from item', 'C', null, 54, 60, null);
    $thirdBlocks = $repo->getDailyPlanBlocks($userId, (int) $thirdItem);
    $thirdBlock = (int) $thirdBlocks[0]['id'];
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, $thirdBlock, 'C', false, 'external'), 'Third block state should save.');
    assertTrueValue($repo->createActualFromDailyPlanBlock($userId, $date, $thirdBlock, 54, 60) !== null, 'Third block Actual should save.');
    assertTrueValue($repo->updateDailyPlanItem($userId, (int) $thirdItem, 'Detach from item', 'C', null, 54, 60, null, 'detach'), 'Editing the source can detach an Actual.');
    $blocks = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    $thirdRows = array_values(array_filter($blocks, static fn(array $block): bool => (int) $block['daily_plan_block_id'] === $thirdBlock));
    assertSameValue('pending', $thirdRows[0]['manual_status'], 'Detaching resets the state.');
};
$tests['retrospect distinguishes no plans and ambiguous item-only links'] = static function (): void {
    $db = Database::connection();
    $repo = new CalendarRepository();
    $service = new RetrospectService();
    $date = date('Y-m-d');
    $texts = ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
    $freeUser = seedUser($db, 'feedback-no-plan@example.test');
    assertTrueValue($repo->createActualEvent($freeUser, $date, 'Actual', 'timed', 12, 15, null, null, '') !== null, 'Unplanned Actual fixture should save.');
    assertSameValue('계획 없음', $service->getPageData($freeUser, $date, 'time')['summary']['planAchievementLabel'], 'No plans must not appear as 0%.');
    assertTrueValue($service->publish($freeUser, $date, $texts), 'Plan-free report should publish.');
    assertSameValue('actual_records', $service->getPublishedReportData($freeUser, $date)['feedback']['rule'] ?? null, 'Plan-free published Actuals should generate feedback.');
    assertSameValue('이날 실제 일정 1건의 기록 합은 30분이에요.', $service->getPublishedReportData($freeUser, $date)['feedback']['message'] ?? null, 'Published plan-free feedback must use the actual snapshot duration.');
    assertSameValue('계획 없음', $service->getPublishedReportData($freeUser, $date)['summary']['planAchievementLabel'], 'Published report must show no plans.');
    assertSameValue('계획 없음', $service->getHistoryData($freeUser, 1)['items'][0]['planAchievementLabel'], 'History must show no plans.');
    $userId = seedUser($db, 'feedback-ambiguous@example.test');
    $item = $repo->createDailyPlanItem($userId, $date, 'Plan', 'B', null, null, null, null);
    $repo->createDailyPlanBlock($userId, (int) $item, 24, 30);
    $repo->createDailyPlanBlock($userId, (int) $item, 36, 42);
    assertTrueValue($repo->createActualEvent($userId, $date, 'Actual', 'timed', 24, 27, (int) $item, null, '') !== null, 'Item-only Actual fixture should save.');
    $page = $service->getPageData($userId, $date, 'time');
    assertSameValue(0, $page['summary']['planLinkedCount'], 'Ambiguous item link must not count for either block.');
    assertSameValue('블록 미확인', $page['summary']['planAchievementLabel'], 'An ambiguous rate must not be presented as certain.');
    assertSameValue(30, $page['summary']['linkedActualMinutes'], 'Item-linked actual time must remain recorded once.');
    assertSameValue(3, count($page['scheduleItems']), 'Both plans and the actual must remain visible once.');
    assertSameValue(1, count(array_filter($page['scheduleItems'], static fn(array $row): bool => !empty($row['linkAmbiguous']))), 'The actual must show its ambiguous block link.');
    assertTrueValue($service->publish($userId, $date, $texts), 'Ambiguous report should publish.');
    $db->prepare('UPDATE retrospect_reports SET plan_linked_count = 2, plan_achievement_rate = 100 WHERE user_id = ? AND report_date = ?')->execute([$userId, $date]);
    assertSameValue('블록 미확인', $service->getPublishedReportData($userId, $date)['summary']['planAchievementLabel'], 'Old incorrect stored rate must not be displayed as certain.');
    assertSameValue('블록 미확인', $service->getHistoryData($userId, 1)['items'][0]['planAchievementLabel'], 'History must detect ambiguous snapshots.');
};
$tests['empty daily review offers schedule while preserving saved reviews'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'retrospect-empty@example.test');
    $date = date('Y-m-d');
    $service = new RetrospectService();
    assertSameValue(true, $service->getPageData($userId, $date, null)['showEmptyGuide'], 'Empty editable day should show the schedule guide.');
    $calendar = new CalendarRepository();
    $calendar->createDailyPlanItem($userId, $date, 'Scheduled plan', 'B', null, 12, 18, null);
    assertSameValue(false, $service->getPageData($userId, $date, null)['showEmptyGuide'], 'A scheduled plan is a record.');
    $actualUser = seedUser($db, 'retrospect-empty-actual@example.test');
    assertTrueValue($calendar->createActualEvent($actualUser, $date, 'Actual', 'timed', 12, 18, null, null, '') !== null, 'Actual fixture should save.');
    assertSameValue(false, $service->getPageData($actualUser, $date, null)['showEmptyGuide'], 'An Actual is a record.');
    $routineUser = seedUser($db, 'retrospect-empty-routine@example.test');
    assertTrueValue((new \App\Models\RoutineRepository())->create($routineUser, null, 'Routine', $date, 7, false, null) !== null, 'Routine fixture should save.');
    assertSameValue(false, $service->getPageData($routineUser, $date, null)['showEmptyGuide'], 'A routine is a record.');
    $memoUser = seedUser($db, 'retrospect-empty-memo@example.test');
    assertTrueValue((new \App\Models\MemoRepository())->create($memoUser, 'Memo') !== null, 'Memo fixture should save.');
    assertSameValue(false, $service->getPageData($memoUser, $date, null)['showEmptyGuide'], 'A memo is a record.');
    $draftUser = seedUser($db, 'retrospect-empty-draft@example.test');
    $texts = ['today_review' => 'Saved thought', 'today_thoughts' => '', 'tomorrow_plan' => ''];
    assertTrueValue($service->saveDraft($draftUser, $date, $texts), 'Draft fixture should save.');
    assertSameValue(false, $service->getPageData($draftUser, $date, null)['showEmptyGuide'], 'Existing KPT text must remain accessible.');
    assertTrueValue($service->publish($draftUser, $date, $texts), 'Published fixture should save.');
    assertSameValue(false, $service->getPageData($draftUser, $date, null)['showEmptyGuide'], 'Published reviews must remain accessible.');
};
$tests['published history filters and paginates by month'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'history-month@example.test');
    $otherUser = seedUser($db, 'history-month-other@example.test');
    $insert = $db->prepare('INSERT INTO retrospect_reports (user_id, report_date, status, today_review) VALUES (?, ?, ?, ?)');
    for ($day = 1; $day <= 22; $day++) {
        $insert->execute([$userId, sprintf('2026-10-%02d', $day), 'submitted', 'October']);
    }
    $insert->execute([$userId, '2026-09-30', 'submitted', 'September']);
    $insert->execute([$userId, '2026-11-01', 'submitted', 'November']);
    $insert->execute([$userId, '2026-10-23', 'draft', 'Draft']);
    $insert->execute([$otherUser, '2026-10-24', 'submitted', 'Other user']);
    $service = new RetrospectService();
    $first = $service->getHistoryData($userId, 1, '2026-10');
    $second = $service->getHistoryData($userId, 2, '2026-10');
    assertSameValue(20, count($first['items']), 'First month page must contain 20 reports.');
    assertSameValue('2026-10-22', $first['items'][0]['date'], 'Month reports must be newest first.');
    assertTrueValue($first['hasNext'], 'Additional monthly reports must be reachable.');
    assertSameValue(2, count($second['items']), 'Second month page must contain only remaining reports.');
    assertSameValue('2026-10-01', $second['items'][1]['date'], 'First day of the month must be included.');
    assertSameValue(0, count($service->getHistoryData($userId, 1, '2026-08')['items']), 'An empty month must return no reports.');
    assertSameValue('', $service->getHistoryData($userId, 1, '2026-13')['month'], 'Invalid month must not be applied.');
};
$tests['daily date colors use weekdays and registered holidays consistently'] = static function (): void {
    $db = Database::connection();
    $insert = $db->prepare('INSERT INTO calendar_date_meta (calendar_date, locale_code, is_holiday, is_substitute_holiday) VALUES (?, ?, ?, ?)');
    $insert->execute(['2026-10-26', 'KR', 1, 0]);
    $insert->execute(['2026-10-31', 'KR', 0, 1]);
    $appearance = new \App\Services\DailyDateAppearanceService();
    assertSameValue('is-saturday', $appearance->getClass('2026-10-24'), 'Saturday should be blue.');
    assertSameValue('is-holiday', $appearance->getClass('2026-10-25'), 'Sunday should be red.');
    assertSameValue('is-holiday', $appearance->getClass('2026-10-26'), 'Registered holiday should be red.');
    assertSameValue('is-holiday', $appearance->getClass('2026-10-31'), 'Substitute holiday takes precedence on Saturday.');
    assertSameValue('', $appearance->getClass('2026-10-27'), 'Ordinary weekdays should use the normal color.');
};
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

$tests['direct grid plan creation is atomic and leaves the source untimed'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'direct-grid-plan@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Direct plan', 'B', null, null, null, null, [48, 54]);
    assertTrueValue($itemId !== null, 'The source and its block should be created together.');
    $day = $repository->findDay($userId, $date);
    $items = $repository->getDailyPlanItems($userId, (int) $day['id']);
    assertSameValue(null, $items[0]['start_index'], 'The source must remain independent of the placed block.');
    $stmt = $db->prepare('SELECT start_index, end_index FROM daily_plan_blocks WHERE daily_plan_item_id = :item');
    $stmt->execute(['item' => $itemId]);
    $block = $stmt->fetch(PDO::FETCH_ASSOC);
    assertSameValue(48, (int) $block['start_index'], 'The block start must match the selected range.');
    assertSameValue(54, (int) $block['end_index'], 'The block end must match the selected range.');
    $conflict = $repository->createDailyPlanItem($userId, $date, 'Conflict', 'D', null, null, null, null, [50, 55]);
    assertSameValue(null, $conflict, 'An overlapping range must fail.');
    assertSameValue(1, count($repository->getDailyPlanItems($userId, (int) $day['id'])), 'A failed block must not leave a source item behind.');
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
    assertTrueValue($repository->updateDailyPlanItemDetails($userId, (int) $itemId, 'Renamed multi-block plan', 'A', null), 'Shared activity details should update without changing blocks.');
    $blocksAfterDetails = $repository->getDailyPlanBlocks($userId, (int) $itemId);
    assertSameValue(66, (int) $blocksAfterDetails[0]['end_index'], 'Shared activity details must not change the first block range.');
    assertSameValue(93, (int) $blocksAfterDetails[1]['end_index'], 'Shared activity details must not change the second block range.');

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
    assertSameValue(null, $preservedActual['daily_plan_block_id'], 'Deleting a block must clear its Actual block link.');
    assertSameValue(null, $preservedActual['daily_plan_item_id'], 'Deleting a block must detach the Actual from the plan item too.');
    assertSameValue(null, $preservedActual['plan_template_id'], 'Deleting a block must detach the legacy plan link too.');
    $preservedSecond = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $secondActualId);
    assertSameValue(null, $preservedSecond['daily_plan_item_id'], 'Every linked Actual must become unplanned.');
    assertSameValue(null, $preservedSecond['daily_plan_block_id'], 'Every linked Actual block link must be cleared.');
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
    $multi = array_values(array_filter($page['scheduleItems'], static fn(array $row): bool => ($row['linkedActualCount'] ?? 0) === 2));
    assertSameValue(1, count($multi), 'One plan row must retain both linked Actuals.');
    assertSameValue(['10:20 ~ 10:50', '11:00 ~ 11:20'], array_column($multi[0]['linkedActuals'], 'timeRange'), 'Both Actual ranges must be available in detail.');
    $service = new RetrospectService();
    assertTrueValue($service->publish($userId, $date, ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => '']), 'Snapshot should publish.');
    $report = $service->getPageData($userId, $date, 'time');
    $published = $service->getPublishedReportData($userId, $date);
    assertSameValue('linked_b_records', $published['feedback']['rule'] ?? null, 'Published explicit B links should generate feedback.');
    assertSameValue('중요·비긴급(B) 계획 2개에 연결된 실제 일정 3건의 기록 합은 100분이에요.', $published['feedback']['message'] ?? null, 'Multiple Actuals and blocks must be counted and summed correctly.');
    assertSameValue(null, $service->getPublishedReportData(seedUser($db, 'feedback-other-user@example.test'), $date), 'Another user must not read the report or feedback.');
    $snapshotMulti = array_values(array_filter($report['scheduleItems'], static fn(array $row): bool => ($row['linkedActualCount'] ?? 0) === 2));
    assertSameValue(2, count($snapshotMulti[0]['linkedActuals'] ?? []), 'Published snapshots must retain both linked Actuals.');
};

$tests['Retrospect tag ordering preserves multi-tag and published snapshots'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'retrospect-tag-sort@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $tags = $repository->listCalendarTags($userId);
    assertTrueValue(count($tags) >= 2, 'Two system tags are needed for sorting.');
    $item = $repository->createDailyPlanItem($userId, $date, 'Multiple', 'A', null, null, null, null);
    $block = $repository->createDailyPlanBlock($userId, (int) $item, 30, 36);
    $first = $repository->createActualFromDailyPlanBlock($userId, $date, (int) $block, 30, 33);
    $second = $repository->createActualFromDailyPlanBlock($userId, $date, (int) $block, 33, 36);
    $single = $repository->createActualEvent($userId, $date, 'Single', 'timed', 40, 42, null, null, '');
    $untagged = $repository->createActualEvent($userId, $date, 'No tag', 'timed', 45, 47, null, null, '');
    assertTrueValue($first !== null && $second !== null && $single !== null && $untagged !== null, 'Actual fixtures should be created.');
    assertTrueValue($repository->updateActualEventTag($userId, (int) $first, (int) $tags[0]['id']), 'First tag should save.');
    assertTrueValue($repository->updateActualEventTag($userId, (int) $second, (int) $tags[1]['id']), 'Second tag should save.');
    assertTrueValue($repository->updateActualEventTag($userId, (int) $single, (int) $tags[0]['id']), 'Single tag should save.');
    $service = new RetrospectService();
    $live = $service->getPageData($userId, $date, 'tag');
    assertSameValue(['tag', 'multiple', 'none'], array_column($live['scheduleItems'], 'tagGroupKind'), 'Tag groups should follow the agreed order.');
    assertSameValue(['Single', 'Multiple', 'No tag'], array_column($live['scheduleItems'], 'title'), 'Rows should remain unique and grouped.');
    assertSameValue([50, 60, 20], array_column($live['scheduleItems'], 'tagTotalMinutes'), 'Tag totals must include the matching Actual in multi-tag blocks, with a separate multiple-group total.');
    assertTrueValue($service->publish($userId, $date, ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => '']), 'Snapshot should publish.');
    $feedback = $service->getPublishedReportData($userId, $date)['feedback'];
    assertSameValue('linked_a_records', $feedback['rule'] ?? null, 'A directly linked A plan should produce A feedback.');
    assertSameValue('중요·긴급(A) 계획 1개에 연결된 실제 일정 2건의 기록 합은 60분이에요.', $feedback['message'] ?? null, 'Both directly linked Actual durations should be summed.');
    assertSameValue(2, count($feedback['evidence'] ?? []), 'Published A feedback evidence must contain both Actuals.');
    $publishedSchedule = $service->getPublishedReportData($userId, $date)['scheduleItems'];
    $planRow = array_values(array_filter($publishedSchedule, static fn(array $row): bool => $row['kind'] === 'plan'))[0];
    assertSameValue('1시간 0분', $planRow['planDurationLabel'], 'Published plan duration should be readable.');
    assertSameValue('1시간 0분', $planRow['linkedActualTimeLabel'], 'Multiple actuals must retain a readable combined duration.');
    assertSameValue(['30분', '30분'], array_column($planRow['linkedActuals'], 'durationLabel'), 'Each linked actual must have its own duration.');
    assertTrueValue($planRow['linkedActuals'][0]['tagPresent'] && $planRow['linkedActuals'][0]['tagColor'] !== $planRow['linkedActuals'][1]['tagColor'], 'Each linked actual must preserve its own tag color.');
    assertTrueValue($repository->updateActualEventTag($userId, (int) $second, (int) $tags[0]['id']), 'Live tags may change after publication.');
    $snapshot = $service->getPageData($userId, $date, 'tag');
    assertSameValue(['tag', 'multiple', 'none'], array_column($snapshot['scheduleItems'], 'tagGroupKind'), 'Published tag grouping must use saved snapshots.');
    assertSameValue([50, 60, 20], array_column($snapshot['scheduleItems'], 'tagTotalMinutes'), 'Published tag totals must use saved Actual durations and tags.');
    assertSameValue(['05:00 ~ 05:30', '05:30 ~ 06:00'], array_column($snapshot['scheduleItems'][1]['linkedActuals'], 'timeRange'), 'Linked Actuals must remain in chronological order when grouped by tag.');
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
    assertSameValue(0, count($todo['completedItems']), 'An item-only Actual must not complete a specific block.');
    assertSameValue((int) $itemId, (int) $todo['pendingItems'][0]['id'], 'The plan remains pending until its block is explicitly completed.');
    assertSameValue('13:00–14:00', $todo['pendingItems'][0]['timeRange'], 'An unfinished block displays its planned time.');
};

$tests['todo displays pending block ranges and hides other block states'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-block-times@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $item = $repository->createDailyPlanItem($userId, $date, 'Several blocks', 'D', null, null, null, null);
    $first = $repository->createDailyPlanBlock($userId, (int) $item, 54, 60);
    $second = $repository->createDailyPlanBlock($userId, (int) $item, 84, 90);
    $service = new TodoService();
    assertSameValue('09:00–10:00 · 14:00–15:00', $service->getTodayData($userId)['pendingItems'][0]['timeRange'], 'Pending block ranges should be listed in order.');
    assertTrueValue($repository->updateDailyPlanBlockDetails($userId, (int) $first, 'D', false, 'cancelled'), 'First block should cancel.');
    assertSameValue('09:00–10:00 · 14:00–15:00', $service->getTodayData($userId)['pendingItems'][0]['timeRange'], 'Source plan times do not depend on block status.');
    assertTrueValue($repository->updateDailyPlanBlockDetails($userId, (int) $second, 'D', false, 'postponed'), 'Second block should postpone.');
    assertSameValue('09:00–10:00 · 14:00–15:00', $service->getTodayData($userId)['pendingItems'][0]['timeRange'], 'Block states do not change source status.');
    assertTrueValue($repository->updateDailyPlanBlockDetails($userId, (int) $second, 'D', false, 'external'), 'Second block should be externally handled.');
    assertSameValue('09:00–10:00 · 14:00–15:00', $service->getTodayData($userId)['pendingItems'][0]['timeRange'], 'Block states do not hide source times.');
    assertTrueValue($repository->updateDailyPlanBlockDetails($userId, (int) $second, 'D', false, 'pending'), 'Second block should return to pending.');
    assertTrueValue($repository->createActualEvent($userId, $date, 'Actual one', 'timed', 60, 63, (int) $item, null, '', (int) $second) !== null, 'First Actual should link.');
    assertTrueValue($repository->createActualEvent($userId, $date, 'Actual two', 'timed', 66, 69, (int) $item, null, '', (int) $second) !== null, 'Second Actual should link.');
    $tree = $service->getTodayData($userId)['activityItems'][0]['planBlocks'];
    assertSameValue(60, $tree[1]['minutes'], 'Each plan shows its planned duration.');
    assertSameValue(2, count($tree[1]['actuals']), 'Two Actual events remain under the correct plan.');
    assertSameValue(60, $tree[1]['actualMinutes'], 'Plan difference uses the sum of its Actual durations.');
    assertSameValue([], $tree[0]['actuals'], 'Actual events do not leak to a different plan.');
    assertTrueValue($repository->updateDailyPlanBlockStatus($userId, (int) $first, 'pending'), 'First block should return to pending.');
    assertTrueValue($repository->createActualEvent($userId, $date, 'Actual three', 'timed', 72, 75, (int) $item, null, '', (int) $first) !== null, 'First block Actual should link.');
    assertTrueValue($repository->updateDailyPlanBlockStatus($userId, (int) $first, 'completed'), 'First block should complete.');
    assertTrueValue($repository->updateDailyPlanBlockStatus($userId, (int) $second, 'completed'), 'Second block should complete.');
    assertTrueValue($service->updateStatus($userId, (int) $item, 'completed'), 'Source completion is selected separately.');
    assertSameValue(t('todo.actual_time', ['time' => '10:00–10:30 · 11:00–11:30 · 12:00–12:30']), $service->getTodayData($userId)['completedItems'][0]['timeRange'], 'Completed item should show every linked Actual range.');
};

$tests['untimed completed todo displays linked actual time without changing its plan'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-actual-time@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Untimed', 'D', null, null, null, null);
    $service = new TodoService();
    assertSameValue(t('todo.untimed'), $service->getTodayData($userId)['pendingItems'][0]['timeRange'], 'Unlinked item must stay time-unspecified.');
    $result = $service->complete($userId, (int) $itemId, 84, 90);
    assertTrueValue($result['ok'], 'Untimed item must complete.');
    assertTrueValue($service->updateStatus($userId, (int) $itemId, 'completed'), 'Source completion is selected separately.');
    $completed = $service->getTodayData($userId)['completedItems'][0];
    assertSameValue(t('todo.actual_time', ['time' => '14:00–15:00']), $completed['timeRange'], 'Linked Actual time must display.');
    assertSameValue(null, $completed['startIndex'], 'Displaying Actual time must not alter the planned start.');
    assertSameValue(null, $completed['endIndex'], 'Displaying Actual time must not alter the planned end.');
    assertTrueValue($repository->updateActualEventTimeRange($userId, (int) $result['eventId'], $date, 90, 96), 'Actual time should update.');
    assertSameValue(t('todo.actual_time', ['time' => '15:00–16:00']), $service->getTodayData($userId)['completedItems'][0]['timeRange'], 'Display must reflect later Actual changes.');
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
    assertSameValue($date, $service->getPublishedReportData($userId, $date)['date'] ?? null, 'Published report should be found by its exact date.');
    assertSameValue(null, $service->getPublishedReportData($userId, '1900-01-01'), 'A missing date must not resolve to another report.');
    $history = $service->getHistoryData($userId, 1);
    assertSameValue($texts, $history['items'][0]['texts'], 'History must show each published KPT field separately.');
    assertSameValue((new DateTimeImmutable($date))->format('Y년 n월'), $history['items'][0]['monthLabel'], 'History must group by report month.');

    $reportRepository = new RetrospectRepository();
    $report = $reportRepository->findReport($userId, $date);
    assertSameValue('submitted', (string) $report['status'], 'Published report status must persist.');
    assertSameValue('Keep text', (string) $report['today_review'], 'Keep text must persist in the report.');
    $snapshotActuals = $reportRepository->listActualItems((int) $report['id'], 'time');
    assertSameValue('Snapshot plan', (string) $snapshotActuals[0]['title_snapshot'], 'Published snapshot must include the original Actual title.');

    assertTrueValue($repository->updateActualEvent($userId, (int) $eventId, $date, 'Changed after publish', 'timed', 102, 108, (int) $itemId, null, 'changed'), 'Source Actual mutation should succeed after publishing.');
    $editing = $service->getPageData($userId, $date, 'time', true);
    assertSameValue(true, $editing['isEditingPublished'], 'Editable published reports should enter editing mode.');
    assertSameValue('Changed after publish', $editing['actualItems'][0]['title'], 'Editing mode must show current actual records.');
    assertSameValue($texts, $editing['texts'], 'Editing mode must preserve the published KPT text.');
    $feedback = $service->getPublishedReportData($userId, $date)['feedback'];
    assertSameValue('Snapshot plan', $feedback['evidence'][0]['title'], 'Feedback evidence must use the published snapshot after live edits.');
    $preservedSnapshot = $reportRepository->listActualItems((int) $report['id'], 'time');
    assertSameValue('Snapshot plan', (string) $preservedSnapshot[0]['title_snapshot'], 'Published snapshot must not change with source data.');
    assertTrueValue($repository->softDeleteEvent($userId, (int) $eventId), 'Source Actual can be deleted after publication.');
    assertSameValue('Snapshot plan', $service->getPublishedReportData($userId, $date)['feedback']['evidence'][0]['title'], 'Published feedback survives deletion until republish.');
    assertTrueValue($service->publish($userId, $date, $texts), 'Report can be republished.');
    assertSameValue(null, $service->getPublishedReportData($userId, $date)['feedback'], 'Republishing without Actuals removes their feedback.');
    assertSameValue($texts, $service->getPublishedReportData($userId, $date)['texts'], 'Republishing must retain user KPT text.');
};

$tests['plan block copy saves one default energy setting and rolls back on failure'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'copy-energy@example.test');
    $repository = new CalendarRepository();
    $service = new CalendarService();
    $date = date('Y-m-d');
    $item = $repository->createDailyPlanItem($userId, $date, 'Multi-range', 'B', null, null, null, null);
    $block = $repository->createDailyPlanBlock($userId, (int) $item, 54, 60);
    $repository->createDailyPlanBlock($userId, (int) $item, 84, 90);
    $untimed = $repository->createDailyPlanItem($userId, $date, 'Unplaced', 'D', null, null, null, null);
    $legacy = $repository->createActualEvent($userId, $date, 'Existing', 'timed', 30, 36, null, null, '');
    assertTrueValue($legacy !== null, 'Existing event fixture should be created without an energy setting.');
    $view = $service->getDayViewData($userId, $date);
    $option = array_values(array_filter($view['planOptions'], static fn(array $entry): bool => $entry['itemId'] === (int) $item))[0];
    assertSameValue('09:00 ~ 10:00, 14:00 ~ 15:00', $option['timeRange'], 'All placed ranges must be displayed.');
    $untimedOption = array_values(array_filter($view['planOptions'], static fn(array $entry): bool => $entry['itemId'] === (int) $untimed))[0];
    assertSameValue('시간 미정', $untimedOption['timeRange'], 'Unplaced plans must remain untimed.');
    $input = ['date' => $date, 'blockId' => (int) $block, 'startIndex' => 60, 'endIndex' => 66];
    $eventId = $service->copyDailyPlanBlockToActual($userId, $input);
    assertTrueValue($eventId !== null, 'Copy should succeed.');
    $energy = (new ExecutionEnergyService())->getDayData($userId, $date);
    assertSameValue(25, $energy['consumed'], 'Copy must consume exactly 25.');
    $legacyEnergy = $db->prepare('SELECT COUNT(*) FROM execution_energy_event_settings WHERE calendar_event_id = :id');
    $legacyEnergy->execute(['id' => $legacy]);
    assertSameValue(0, (int) $legacyEnergy->fetchColumn(), 'Existing events must not be changed.');
    assertSameValue(null, $service->copyDailyPlanBlockToActual($userId, $input), 'Duplicate overlapping copy must fail.');
    assertSameValue(25, (new ExecutionEnergyService())->getDayData($userId, $date)['consumed'], 'Duplicate must not consume energy again.');
    $day = $repository->findDay($userId, $date);
    $before = count($repository->getActualEvents($userId, (int) $day['id']));
    $db->exec("CREATE TRIGGER test_reject_energy BEFORE INSERT ON execution_energy_event_settings BEGIN SELECT RAISE(ABORT, 'test failure'); END");
    try {
        $input['startIndex'] = 66;
        $input['endIndex'] = 72;
        assertSameValue(null, $service->copyDailyPlanBlockToActual($userId, $input), 'Energy failure must reject copy.');
        assertSameValue($before, count($repository->getActualEvents($userId, (int) $day['id'])), 'Energy failure must roll back event.');
        assertSameValue(25, (new ExecutionEnergyService())->getDayData($userId, $date)['consumed'], 'Failed copy must not consume energy.');
    } finally {
        $db->exec('DROP TRIGGER test_reject_energy');
    }
};

$tests['placed blocks remain independent of source and preserve Actual linkage'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'independent-blocks@example.test');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $itemId = $repository->createDailyPlanItem($userId, $date, 'Original', 'B', null, null, null, null);
    $first = $repository->createDailyPlanBlock($userId, (int) $itemId, 48, 54);
    $second = $repository->createDailyPlanBlock($userId, (int) $itemId, 72, 78);
    assertTrueValue($first !== null && $second !== null, 'Both blocks must be created.');
    $actual = (new CalendarService())->copyDailyPlanBlockToActual($userId, ['date' => $date, 'blockId' => $first, 'startIndex' => 48, 'endIndex' => 54]);
    assertTrueValue($actual !== null, 'Actual linked to first block must be created.');
    assertTrueValue($repository->updateDailyPlanItemDetails($userId, (int) $itemId, 'Edited source', 'D', null), 'Source edit must succeed.');
    assertTrueValue($repository->updateDailyPlanBlockDetails($userId, (int) $first, 'A', false), 'Individual block importance must be editable.');
    $day = $repository->findDay($userId, $date);
    $blocks = $repository->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue(['Original', 'Original'], array_column($blocks, 'title'), 'Source edits must not alter block titles.');
    assertSameValue(['A', 'B'], array_column($blocks, 'importance'), 'Block importance must be independent.');
    $tabs = (new TodoService())->getDateData($userId, $date);
    assertSameValue(['Original', 'Original'], array_column($tabs['planItems'], 'title'), 'Plan tab shows each block snapshot.');
    assertSameValue(['D', 'D'], array_column($tabs['planItems'], 'importance'), 'To-do plan tab follows source importance.');
    assertSameValue(['08:00–09:00'], $tabs['planItems'][0]['actualTimeRanges'], 'Actual time is attached only to its linked block.');
    assertSameValue([], $tabs['planItems'][1]['actualTimeRanges'], 'An unrelated block must not inherit Actual time.');
    assertSameValue('Edited source', $tabs['activityItems'][0]['title'], 'To-do tab shows the independently edited source.');
    assertSameValue(2, count($tabs['activityItems'][0]['planBlocks']), 'To-do tab counts both placements.');
    $todoService = new TodoService();
    assertTrueValue($todoService->updateStatus($userId, (int) $itemId, 'completed'), 'A To-do can be completed independently of Actual links.');
    assertSameValue(['completed', 'completed'], array_column($todoService->getDateData($userId, $date)['planItems'], 'status'), 'Both planned blocks follow the source status in To-do.');
    assertTrueValue(!$todoService->updateStatus($userId + 999999, (int) $itemId, 'pending'), 'Another user cannot change the source status.');
    assertTrueValue(!$todoService->updateStatus($userId, (int) $itemId, 'invalid'), 'Unknown status must be rejected.');
    assertTrueValue($repository->deleteDailyPlanItem($userId, (int) $itemId), 'Source deletion must succeed.');
    assertSameValue(2, count($repository->getDailyPlanBlocksForDay($userId, (int) $day['id'])), 'Blocks must survive source deletion.');
    $todo = (new TodoService())->getDateData($userId, $date);
    assertSameValue(2, count($todo['planItems']), 'Plan tab retains blocks after source deletion.');
    assertSameValue(['completed', 'completed'], array_column($todo['planItems'], 'status'), 'Deleted source retains its last status in the To-do plan tab.');
    assertSameValue(['D', 'D'], array_column($todo['planItems'], 'importance'), 'Deleted source retains its last importance in the To-do plan tab.');
    assertTrueValue($todo['activityItems'][0]['sourceUnavailable'], 'Deleted sources remain visible as read-only plan records.');
    assertSameValue(2, count($todo['activityItems'][0]['planBlocks']), 'To-do retains blocks whose source was deleted.');
    $event = findActual($repository->getActualEvents($userId, (int) $day['id']), (int) $actual);
    assertSameValue((int) $first, (int) $event['daily_plan_block_id'], 'Actual link must survive source deletion.');
    $retrospect = (new RetrospectService())->getPageData($userId, $date, 'time');
    assertSameValue(2, count(array_filter($retrospect['scheduleItems'], static fn(array $row): bool => $row['kind'] === 'plan')), 'Retrospect must retain independent blocks.');
    assertSameValue(1, (int) $retrospect['summary']['linkedActualCount'], 'Retrospect must retain Actual linkage.');
};

$tests['untimed todo status is explicit and user scoped'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-own-status@example.test');
    $repo = new CalendarRepository();
    $date = date('Y-m-d', strtotime('-1 day'));
    $itemId = $repo->createDailyPlanItem($userId, $date, 'Untimed', 'A', null, null, null, null);
    $service = new TodoService();
    assertSameValue('pending', $service->getDateData($userId, $date)['activityItems'][0]['todoStatus'], 'New item starts pending.');
    assertTrueValue($service->updateStatus($userId, (int) $itemId, 'completed'), 'Untimed item can be completed without an Actual.');
    assertSameValue('completed', $service->getDateData($userId, $date)['activityItems'][0]['todoStatus'], 'Status survives reload.');
    assertTrueValue(!$service->updateStatus($userId + 999999, (int) $itemId, 'cancelled'), 'Other user cannot change status.');
};

$tests['todo detail edit preserves plan times and actual links'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-edit-preserves-links@example.test');
    $repo = new CalendarRepository();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Before', 'B', null, 48, 54, null);
    $blocks = $repo->getDailyPlanBlocks($userId, (int) $item);
    $blockId = (int) $blocks[0]['id'];
    $event = $repo->createActualEvent($userId, $date, 'Before', 'timed', 48, 54, (int) $item, null, '', $blockId);
    assertTrueValue($event !== null, 'Linked Actual fixture must exist.');
    $result = (new TodoService())->updateToday($userId, [
        'daily_plan_item_id' => $item, 'title' => 'After', 'importance' => 'A', 'goal_id' => '',
        'schedule_type' => 'unscheduled', 'link_action' => 'detach', 'estimated_duration_minutes' => '0',
    ]);
    assertTrueValue($result['ok'], 'Source detail update should succeed and ignore time/link fields.');
    $day = $repo->findDay($userId, $date);
    $source = $repo->getDailyPlanItems($userId, (int) $day['id'])[0];
    assertSameValue('After', $source['title'], 'Title changes.');
    assertSameValue('A', $source['importance'], 'Importance changes.');
    assertSameValue(48, (int) $source['start_index'], 'Source start remains unchanged.');
    assertSameValue(54, (int) $source['end_index'], 'Source end remains unchanged.');
    $afterBlock = $repo->getDailyPlanBlocks($userId, (int) $item)[0];
    assertSameValue(48, (int) $afterBlock['start_index'], 'Plan block remains unchanged.');
    $afterEvent = findActual($repo->getActualEvents($userId, (int) $day['id']), (int) $event);
    assertSameValue($blockId, (int) $afterEvent['daily_plan_block_id'], 'Actual remains linked to its plan.');
    assertSameValue(48, (int) $afterEvent['start_index'], 'Actual time remains unchanged.');
    $other = (new TodoService())->updateToday($userId + 999999, ['daily_plan_item_id' => $item, 'title' => 'Forbidden']);
    assertTrueValue(!$other['ok'], 'Another user cannot edit the item.');
};

$tests['todo delete preserves placed blocks and actuals'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-delete-preserves-block@example.test');
    $repo = new CalendarRepository();
    $service = new TodoService();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Delete source', 'B', null, null, null, null);
    $block = $repo->createDailyPlanBlock($userId, (int) $item, 48, 54);
    $event = $repo->createActualEvent($userId, $date, 'Linked', 'timed', 48, 54, (int) $item, null, '', (int) $block);
    assertTrueValue($event !== null, 'Linked Actual fixture must exist.');
    assertTrueValue(!$service->deleteToday($userId + 999999, (int) $item)['ok'], 'Another user cannot delete the item.');
    assertTrueValue($service->deleteToday($userId, (int) $item)['ok'], 'Owner can delete today source.');
    $day = $repo->findDay($userId, $date);
    assertSameValue([], $repo->getDailyPlanItems($userId, (int) $day['id']), 'Source is hidden after deletion.');
    assertSameValue(1, count($repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])), 'Placed plan remains.');
    $actual = findActual($repo->getActualEvents($userId, (int) $day['id']), (int) $event);
    assertSameValue((int) $block, (int) $actual['daily_plan_block_id'], 'Actual remains linked to the placed plan.');
    assertTrueValue(!$service->deleteToday($userId, (int) $item)['ok'], 'Repeated deletion is rejected.');
    $past = $repo->createDailyPlanItem($userId, date('Y-m-d', strtotime('-1 day')), 'Past', 'D', null, null, null, null);
    assertTrueValue(!$service->deleteToday($userId, (int) $past)['ok'], 'Other dates remain read-only.');
};

$tests['todo recurring creation uses owned template library'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-recurring-create@example.test');
    $other = seedUser($db, 'todo-recurring-other@example.test');
    $service = new TodoService();
    $bad = $service->createRecurring($userId, ['title' => '', 'importance' => 'A']);
    assertTrueValue(!$bad['ok'], 'Empty title must be rejected.');
    $result = $service->createRecurring($userId, ['title' => 'Recurring', 'importance' => 'B', 'duration_index' => 99]);
    assertTrueValue($result['ok'], 'Recurring item should be created.');
    $templates = (new \App\Services\PlanService())->getBlockTemplates($userId);
    assertSameValue(1, count($templates), 'One recurring template is created.');
    assertSameValue('Recurring', $templates[0]['title'], 'Template name is preserved.');
    assertSameValue('B', $templates[0]['importance'], 'Importance is preserved.');
    assertSameValue(6, $templates[0]['durationIndex'], 'Existing hidden default duration is used.');
    assertSameValue([], (new \App\Services\PlanService())->getBlockTemplates($other), 'Other users do not see this template.');
};

$tests['calendar todo blocks follow source state and importance'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'calendar-todo-inheritance@example.test');
    $repo = new CalendarRepository();
    $calendar = new CalendarService();
    $todo = new TodoService();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Source', 'B', null, null, null, null);
    $first = $repo->createDailyPlanBlock($userId, (int) $item, 48, 54);
    $second = $repo->createDailyPlanBlock($userId, (int) $item, 84, 90);
    assertTrueValue($todo->updateStatus($userId, (int) $item, 'completed'), 'A To-do can be completed without Actual.');
    assertTrueValue($repo->updateDailyPlanItemDetails($userId, (int) $item, 'Changed', 'A', null), 'Source importance can change.');
    $segments = $calendar->getDayViewData($userId, $date)['planSegments'];
    $segments = array_values(array_filter($segments, static fn(array $segment): bool => in_array($segment['blockId'], [$first, $second], true)));
    assertTrueValue(count($segments) >= 2, 'Both plans are rendered.');
    foreach ($segments as $segment) {
        assertSameValue('A', $segment['importance'], 'Calendar plan follows To-do importance.');
        assertSameValue('completed', $segment['manualStatus'], 'Calendar plan follows To-do status without Actual.');
        assertTrueValue($segment['followsTodo'], 'Calendar marks the plan inherited.');
    }
    assertTrueValue($calendar->planBlockFollowsTodo($userId, (int) $first), 'Owned plan is classified as inherited.');
    assertTrueValue(!$calendar->planBlockFollowsTodo($userId + 999999, (int) $first), 'Another user cannot claim the plan.');
    $actual = $repo->createActualEvent($userId, $date, 'Actual', 'timed', 48, 54, (int) $item, null, '', (int) $first);
    assertTrueValue($actual !== null, 'Actual links to the plan.');
    assertTrueValue($calendar->detachDailyPlanBlockActuals($userId, (int) $first), 'Actual can be detached.');
    assertSameValue('completed', $todo->getDateData($userId, $date)['activityItems'][0]['todoStatus'], 'Detachment leaves To-do state unchanged.');
    $plans = new \App\Services\PlanService();
    $templateId = $plans->createBlockTemplate($userId, ['title' => 'Reusable', 'durationIndex' => 6, 'importance' => 'C', 'goalId' => null]);
    $templateBlock = $repo->placePlanBlockTemplate($userId, $date, (int) $templateId, 102, 108);
    assertTrueValue($templateBlock !== null, 'Reusable template block can be placed.');
    assertTrueValue(!$calendar->planBlockFollowsTodo($userId, (int) $templateBlock['blockId']), 'Template-only block keeps its independent controls.');
    assertTrueValue($repo->deleteDailyPlanItem($userId, (int) $item), 'Source can be deleted.');
    $segments = $calendar->getDayViewData($userId, $date)['planSegments'];
    $segments = array_values(array_filter($segments, static fn(array $segment): bool => $segment['blockId'] === $first));
    assertSameValue('A', $segments[0]['importance'], 'Deleted source keeps last importance.');
    assertSameValue('completed', $segments[0]['manualStatus'], 'Deleted source keeps last status.');
};

$tests['todo tree totals actual minutes per plan'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-tree-totals@example.test');
    $repo = new CalendarRepository();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Tree task', 'A', null, null, null, null);
    $first = $repo->createDailyPlanBlock($userId, (int) $item, 48, 54);
    $second = $repo->createDailyPlanBlock($userId, (int) $item, 84, 90);
    $repo->createActualEvent($userId, $date, 'Part one', 'timed', 48, 51, (int) $item, null, '', (int) $first);
    $repo->createActualEvent($userId, $date, 'Part two', 'timed', 52, 54, (int) $item, null, '', (int) $first);
    $tree = (new TodoService())->getDateData($userId, $date)['activityItems'][0]['planBlocks'];
    assertSameValue(50, $tree[0]['actualMinutes'], 'Two Actual intervals add to 50 minutes.');
    assertSameValue([-30, -40], array_column($tree[0]['actuals'], 'differenceMinutes'), 'Each Actual is compared individually with its 60-minute plan.');
    assertSameValue(-10, $tree[0]['actualMinutes'] - $tree[0]['minutes'], 'Difference compares the sum to the 60-minute plan.');
    assertSameValue([], $tree[1]['actuals'], 'Another plan has no Actual entries.');
    $repo->createActualEvent($userId, $date, 'Part three', 'timed', 54, 56, (int) $item, null, '', (int) $first);
    $tree = (new TodoService())->getDateData($userId, $date)['activityItems'][0]['planBlocks'];
    assertSameValue(10, $tree[0]['actualMinutes'] - $tree[0]['minutes'], 'Additional Actual time can exceed the plan.');
    assertSameValue([-30, -40, -40], array_column($tree[0]['actuals'], 'differenceMinutes'), 'Individual comparisons do not use the combined Actual duration.');
    $repo->createActualEvent($userId, $date, 'Longer', 'timed', 96, 103, (int) $item, null, '', (int) $second);
    $repo->createActualEvent($userId, $date, 'Equal', 'timed', 108, 114, (int) $item, null, '', (int) $first);
    $tree = (new TodoService())->getDateData($userId, $date)['activityItems'][0]['planBlocks'];
    assertSameValue(10, $tree[1]['actuals'][0]['differenceMinutes'], 'A 70-minute Actual exceeds a 60-minute plan by 10 minutes.');
    assertSameValue(0, $tree[0]['actuals'][3]['differenceMinutes'], 'An equal duration has a zero difference.');
};

$tests['todo highlight follows individual placed plan duration'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-plan-highlight@example.test');
    $repo = new CalendarRepository();
    $service = new TodoService();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Highlight', 'D', null, null, null, 120);
    $check = static fn(): bool => (bool) $service->getDateData($userId, $date)['activityItems'][0]['isLongDuration'];
    assertTrueValue(!$check(), 'An estimate without a placed plan does not highlight.');
    $first = $repo->createDailyPlanBlock($userId, (int) $item, 12, 15);
    $second = $repo->createDailyPlanBlock($userId, (int) $item, 24, 28);
    assertTrueValue($first !== null && $second !== null, 'Two short plans are placed.');
    assertTrueValue(!$check(), 'Short plans are not summed.');
    assertTrueValue($repo->updateDailyPlanBlock($userId, (int) $second, 24, 30, $date), 'Plan can be resized to 60 minutes.');
    assertTrueValue(!$check(), 'Exactly 60 minutes is not longer than one hour.');
    assertTrueValue($repo->updateDailyPlanBlock($userId, (int) $second, 24, 31, $date), 'Plan can be resized to 70 minutes.');
    assertTrueValue($check(), 'A 70-minute plan highlights.');
    assertTrueValue($repo->deleteDailyPlanBlock($userId, (int) $second), 'Long plan can be deleted.');
    assertTrueValue(!$check(), 'Deleting the long plan removes the highlight.');
};

$tests['template placements do not add duplicate list items'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'template-source-hidden@example.test');
    $plans = new \App\Services\PlanService();
    $template = $plans->createBlockTemplate($userId, ['title' => 'Reusable', 'durationIndex' => 6, 'importance' => 'B', 'goalId' => null]);
    assertTrueValue($template !== null, 'Template must be created.');
    $repository = new CalendarRepository();
    $date = date('Y-m-d');
    $result = $repository->placePlanBlockTemplate($userId, $date, (int) $template, 60, 66);
    assertTrueValue($result !== null, 'Template placement must succeed.');
    $day = $repository->findDay($userId, $date);
    assertSameValue(0, count($repository->getDailyPlanItems($userId, (int) $day['id'])), 'Copied template item must not appear in placement list.');
    assertSameValue(1, count($repository->getDailyPlanBlocksForDay($userId, (int) $day['id'])), 'Placed block must remain visible.');
    assertTrueValue($plans->updateBlockTemplate($userId, (int) $template, ['title' => 'Changed', 'durationIndex' => 6, 'importance' => 'D', 'goalId' => null]), 'Template edit must succeed.');
    $blocks = $repository->getDailyPlanBlocksForDay($userId, (int) $day['id']);
    assertSameValue('Reusable', $blocks[0]['title'], 'Template edit must not change placed block.');
    assertSameValue('B', $blocks[0]['importance'], 'Template edit must not change block importance.');
};

$tests['todo lists only owned enabled plan block templates without creating dated items'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'todo-enabled-templates@example.test');
    $otherUser = seedUser($db, 'todo-other-templates@example.test');
    $plans = new \App\Services\PlanService();
    $data = ['title' => 'Enabled template', 'durationIndex' => 6, 'importance' => 'B', 'goalId' => null];
    $enabled = $plans->createBlockTemplate($userId, $data);
    $disabled = $plans->createBlockTemplate($userId, array_merge($data, ['title' => 'Disabled template']));
    $deleted = $plans->createBlockTemplate($userId, array_merge($data, ['title' => 'Deleted template']));
    $plans->createBlockTemplate($otherUser, array_merge($data, ['title' => 'Other user template']));
    assertTrueValue($enabled !== null && $disabled !== null && $deleted !== null, 'Templates must be created.');
    assertTrueValue($plans->setBlockTemplateEnabled($userId, (int) $disabled, false), 'Template must be disabled.');
    assertTrueValue($plans->deleteBlockTemplate($userId, (int) $deleted), 'Template must be deleted.');
    $service = new TodoService();
    foreach ([date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))] as $date) {
        $todo = $service->getDateData($userId, $date);
        assertSameValue(['Enabled template'], array_column($todo['blockTemplates'], 'title'), 'Only the enabled owned template should appear.');
        assertSameValue([], $todo['items'], 'Displaying templates must not create dated To-do items.');
    }
    assertSameValue(false, $service->create($userId, ['plan_block_template_id' => $disabled])['ok'], 'Disabled templates cannot be used.');
    assertSameValue(false, $service->create($otherUser, ['plan_block_template_id' => $enabled])['ok'], 'Other users cannot use a template.');
    $result = $service->create($userId, ['plan_block_template_id' => $enabled, 'date' => '2000-01-01', 'title' => 'Forged']);
    assertTrueValue($result['ok'], 'Enabled template should create a To-do.');
    $todo = $service->getTodayData($userId);
    assertSameValue(['Enabled template'], array_column($todo['pendingItems'], 'title'), 'Created item must use the template title and be unfinished.');
    assertSameValue('B', $todo['pendingItems'][0]['importance'], 'Created item must copy importance.');
    assertSameValue(null, $todo['pendingItems'][0]['startIndex'], 'Created item must be time-unspecified.');
    assertTrueValue($plans->updateBlockTemplate($userId, (int) $enabled, ['title' => 'Updated template', 'durationIndex' => 6, 'importance' => 'D', 'goalId' => null]), 'Template edit must succeed.');
    assertSameValue('Enabled template', $service->getTodayData($userId)['pendingItems'][0]['title'], 'Template edits must not alter created To-dos.');
};

$tests['calendar retrospect prompt is claimed once per user local day only when eligible'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'calendar-prompt@example.test');
    $service = new CalendarService();
    $date = date('Y-m-d');
    $noReportUser = seedUser($db, 'calendar-prompt-no-report@example.test');
    assertSameValue(false, $service->claimRetrospectPrompt($noReportUser, $date), 'No published report means no prompt.');
    $report = new RetrospectService();
    assertTrueValue($report->publish($userId, $date, ['today_review' => 'note', 'today_thoughts' => '', 'tomorrow_plan' => '']), 'Report must publish.');
    $repository = new CalendarRepository();
    assertTrueValue($repository->createActualEvent($userId, $date, 'Actual only', 'timed', 6, 12, null, null, '') !== null, 'Actual fixture must be created.');
    assertSameValue(true, $service->claimRetrospectPrompt($userId, $date), 'Actual-only day should prompt.');
    assertSameValue(false, $service->claimRetrospectPrompt($userId, $date), 'Second request on same account and date must not prompt.');
    assertSameValue(false, $service->claimRetrospectPrompt($userId, date('Y-m-d', strtotime('-1 day'))), 'Other dates must not prompt.');
    $other = seedUser($db, 'calendar-prompt-plan@example.test');
    assertTrueValue((new RetrospectService())->publish($other, $date, ['today_review' => 'note', 'today_thoughts' => '', 'tomorrow_plan' => '']), 'Second report must publish.');
    $item = $repository->createDailyPlanItem($other, $date, 'Plan', 'D', null, null, null, null);
    assertTrueValue($repository->createDailyPlanBlock($other, (int) $item, 30, 36) !== null, 'Plan block must be created.');
    assertSameValue(false, $service->claimRetrospectPrompt($other, $date), 'A plan block must prevent prompting.');
    $day = $repository->findDay($other, $date);
    $block = $repository->getDailyPlanBlocksForDay($other, (int) $day['id'])[0];
    assertTrueValue($repository->deleteDailyPlanBlock($other, (int) $block['daily_plan_block_id']), 'Fixture block must delete.');
    assertSameValue(false, $service->claimRetrospectPrompt($other, $date), 'A later visit is not the first visit, even if the plan is removed.');
};

$tests['compact execution energy validates values and preserves unchanged legacy amounts'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'compact-energy@example.test');
    $service = new ExecutionEnergyService();
    $default = $service->validateEventSetting($userId, []);
    assertSameValue(['effectType' => 'consume', 'value' => 25], $default['data'], 'Unselected create must default to minus 25.');
    foreach ([5, 25, 30, 75, 100] as $value) {
        $result = $service->validateEventSetting($userId, ['execution_energy_compact' => '1', 'execution_energy_type' => 'recover', 'execution_energy_amount' => 'custom', 'execution_energy_custom_value' => (string) $value]);
        assertTrueValue($result['ok'], 'A valid 5-unit value must pass.');
    }
    foreach (['', '0', '-5', '12', '2.5', '105'] as $value) {
        $result = $service->validateEventSetting($userId, ['execution_energy_compact' => '1', 'execution_energy_type' => 'consume', 'execution_energy_amount' => 'custom', 'execution_energy_custom_value' => $value]);
        assertSameValue(false, $result['ok'], 'Invalid compact value must fail: ' . $value);
    }
    $repository = new CalendarRepository();
    $eventId = $repository->createActualEvent($userId, date('Y-m-d'), 'Legacy', 'timed', 20, 26, null, null, '');
    assertTrueValue($eventId !== null, 'Legacy event must be created.');
    $service->saveEventSetting($userId, (int) $eventId, ['effectType' => 'consume', 'value' => 125]);
    $input = ['event_id' => (int) $eventId, 'execution_energy_compact' => '1', 'execution_energy_type' => 'consume', 'execution_energy_amount' => 'custom', 'execution_energy_custom_value' => '125'];
    assertTrueValue($service->validateEventSetting($userId, $input)['ok'], 'Unchanged legacy value must be preserved.');
    $input['execution_energy_type'] = 'recover';
    assertSameValue(false, $service->validateEventSetting($userId, $input)['ok'], 'A changed legacy value or direction must be rejected.');
    assertSameValue(0, $service->validateEventSetting($userId, ['execution_energy_type' => 'none'])['data']['value'], 'Keep must be zero.');
};

$tests['explicit block completion is independent of actual linkage and migration runs once'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'explicit-completion@example.test');
    $otherUser = seedUser($db, 'explicit-completion-other@example.test');
    $repo = new CalendarRepository();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Status test', 'B', null, null, null, null);
    $block = $repo->createDailyPlanBlock($userId, (int) $item, 60, 66);
    assertTrueValue(!$repo->updateDailyPlanBlockStatus($userId, (int) $block, 'completed'), 'Completion requires an Actual.');
    assertTrueValue(!$repo->updateDailyPlanBlockStatus($otherUser, (int) $block, 'in_progress'), 'Other users cannot update status.');
    $event = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $block, 60, 66);
    assertTrueValue($event !== null, 'Actual should link.');
    $day = $repo->findDay($userId, $date);
    assertSameValue('pending', $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0]['manual_status'], 'Linking alone must not complete.');
    $retrospect = new RetrospectService();
    assertSameValue(0, $retrospect->getPageData($userId, $date, 'time')['summary']['planAchievementRate'], 'Linking alone is not achievement.');
    $sql = file_get_contents(dirname(__DIR__) . '/sql/migration.plan_block_explicit_completion.sqlite.sql');
    $db->exec($sql);
    assertSameValue('completed', $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0]['manual_status'], 'Migration preserves prior linked completion.');
    assertSameValue(100, $retrospect->getPageData($userId, $date, 'time')['summary']['planAchievementRate'], 'Explicit completed block is achieved.');
    assertTrueValue($repo->updateDailyPlanBlockStatus($userId, (int) $block, 'in_progress'), 'Linked block may be in progress.');
    $db->exec($sql);
    assertSameValue('in_progress', $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0]['manual_status'], 'Repeated migration must preserve later choices.');
    assertTrueValue($repo->updateDailyPlanBlockStatus($userId, (int) $block, 'completed'), 'Linked block can be completed.');
    assertTrueValue($repo->softDeleteEvent($userId, (int) $event), 'Actual can be deleted.');
    assertSameValue('pending', $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0]['manual_status'], 'Removing last Actual resets completed to pending.');
};

$tests['plan block importance and actual detachment save independently'] = static function (): void {
    $db = Database::connection();
    $userId = seedUser($db, 'quick-block-edit@example.test');
    $otherUser = seedUser($db, 'quick-block-edit-other@example.test');
    $repo = new CalendarRepository();
    $service = new CalendarService();
    $date = date('Y-m-d');
    $item = $repo->createDailyPlanItem($userId, $date, 'Quick edit', 'B', null, null, null, null);
    $block = $repo->createDailyPlanBlock($userId, (int) $item, 48, 54);
    $first = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $block, 48, 51);
    $second = $repo->createActualFromDailyPlanBlock($userId, $date, (int) $block, 51, 54);
    assertTrueValue($first !== null && $second !== null, 'Two Actuals must link.');
    assertTrueValue($service->updateDailyPlanBlockStatus($userId, (int) $block, 'completed'), 'Linked block can complete.');
    assertTrueValue($service->updateDailyPlanBlockDetails($userId, (int) $block, 'A', false), 'Importance saves independently.');
    assertTrueValue(!$service->detachDailyPlanBlockActuals($otherUser, (int) $block), 'Other users cannot detach.');
    $day = $repo->findDay($userId, $date);
    $row = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0];
    assertSameValue('A', $row['importance'], 'Importance should change.');
    assertSameValue('completed', $row['manual_status'], 'Importance change must preserve status.');
    assertTrueValue((bool) $row['is_linked'], 'Importance change must preserve Actual links.');
    assertTrueValue($service->detachDailyPlanBlockActuals($userId, (int) $block), 'Owner can detach all Actuals.');
    $row = $repo->getDailyPlanBlocksForDay($userId, (int) $day['id'])[0];
    assertSameValue('A', $row['importance'], 'Detachment must not overwrite importance.');
    assertSameValue('pending', $row['manual_status'], 'Detachment resets status.');
    assertSameValue(false, (bool) $row['is_linked'], 'Block must no longer be linked.');
    $events = $repo->getActualEvents($userId, (int) $day['id']);
    assertSameValue(2, count($events), 'Both Actual records must remain.');
    foreach ($events as $event) {
        assertSameValue(null, $event['daily_plan_block_id'], 'Actual block link must be cleared.');
        assertSameValue(null, $event['daily_plan_item_id'], 'Actual item link must be cleared.');
    }
    assertTrueValue(!$service->detachDailyPlanBlockActuals($userId, (int) $block), 'Repeated detachment must report no change.');
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
