<?php

declare(strict_types=1);

// Run only against a separately provisioned, disposable MySQL database.
$name = getenv('DB_NAME') ?: '';
if (getenv('DB_CONNECTION') !== 'mysql' || !preg_match('/^lifeflow_test_[a-z0-9_]+$/', $name)) {
    fwrite(STDERR, "SKIP: an explicitly named disposable MySQL database is required.\n");
    exit(2);
}
date_default_timezone_set('Asia/Seoul');
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
require $root . '/app/Core/helpers.php';
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$db = App\Core\Database::connection();
$insert = $db->prepare('INSERT INTO user (email, password_hash, nickname, role, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
$insert->execute(['one@example.test', password_hash('test', PASSWORD_DEFAULT), 'One', 'user']); $user = (int) $db->lastInsertId();
$insert->execute(['other@example.test', password_hash('test', PASSWORD_DEFAULT), 'Other', 'user']); $other = (int) $db->lastInsertId();
$service = new App\Services\RetrospectService();
$calendar = new App\Models\CalendarRepository();
$date = date('Y-m-d', strtotime('-1 day')); $today = date('Y-m-d');
$texts = ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => '처음 Try'];
$check($service->publish($user, $date, $texts), 'Publish should succeed.');
$event = $calendar->createActualEvent($user, $today, 'Actual', 'timed', 24, 30, null, null, '');
$otherEvent = $calendar->createActualEvent($other, $today, 'Other', 'timed', 24, 30, null, null, '');
$check($event !== null && $otherEvent !== null, 'Actuals should save.');
$check(!$service->changeTryEventLink($user, $date, (int) $otherEvent, false), 'Cross-user event must be rejected.');
$check($service->changeTryEventLink($user, $date, (int) $event, false), 'Link should save.');
$check($service->changeTryEventLink($user, $date, (int) $event, false), 'Repeated link must be idempotent.');
$data = $service->getTryLinkData($user, $date);
$check(count($data['linked']) === 1, 'Exactly one link should exist.');
$texts['tomorrow_plan'] = '수정한 Try';
$check($service->publish($user, $date, $texts), 'Republish should succeed.');
$data = $service->getTryLinkData($user, $date);
$check(count($data['linked']) === 1 && $data['linked'][0]['tryChanged'] && $data['linked'][0]['try_text_snapshot'] === '처음 Try', 'Republish must retain link and original text.');
$linkId = (int) $data['linked'][0]['link_id'];
$check(!$service->changeTryEventLink($other, $date, $linkId, true), 'Cross-user unlink must fail.');
$check($service->changeTryEventLink($user, $date, $linkId, true), 'Unlink should succeed.');
$check($service->getTryLinkData($user, $date)['linked'] === [], 'Unlinked entry must disappear.');
$check($service->changeTryEventLink($user, $date, (int) $event, false), 'Relink should succeed.');
$check($calendar->softDeleteEvent($user, (int) $event), 'Actual deletion should succeed.');
$data = $service->getTryLinkData($user, $date);
$check(count($data['linked']) === 0 && count($data['unavailable']) === 1, 'Deleted event must be shown as unavailable.');
$check($service->changeTryEventLink($user, $date, (int) $data['unavailable'][0]['link_id'], true), 'Unavailable link should be removable.');
$actual = $calendar->createActualEvent($user, $today, 'Snapshot actual', 'timed', 36, 42, null, null, '');
$check($actual !== null && $service->publish($user, $today, $texts), 'Actual-only report must publish.');
$feedback = $service->getPublishedReportData($user, $today)['feedback'];
$check(($feedback['rule'] ?? null) === 'actual_records' && str_contains($feedback['message'], '60분'), 'Published actual must use 60-minute snapshot.');
$check($calendar->updateActualEvent($user, (int) $actual, $today, 'Updated actual', 'timed', 36, 39, null, null, ''), 'Live actual should update.');
$check(str_contains($service->getPublishedReportData($user, $today)['feedback']['message'], '60분'), 'Published evidence must not follow a live edit.');
$check($service->republish($user, $today), 'Actual report must republish.');
$check(str_contains($service->getPublishedReportData($user, $today)['feedback']['message'], '30분'), 'Republished feedback must use updated 30-minute snapshot.');
$routineRepo = new App\Models\RoutineRepository();
$start = date('Y-m-d', strtotime('-120 days'));
$routine = $routineRepo->create($other, null, 'Routine', $start, 121, false, null);
$check($routine !== null, 'Routine should save.');
$insertLog = $db->prepare('INSERT INTO routine_logs (routine_id, user_id, log_date, is_done, created_at, updated_at) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
$db->beginTransaction();
for ($i = 118; $i >= 0; $i -= 2) $insertLog->execute([$routine, $other, date('Y-m-d', strtotime('-' . $i . ' days'))]);
$db->commit();
$check($service->publish($other, $today, $texts), 'Routine milestone report should publish.');
$check(($service->getPublishedReportData($other, $today)['feedback']['rule'] ?? null) === 'routine_60_days', '60 nonconsecutive days must be recognized in MySQL.');
$db->prepare('DELETE FROM routine_logs WHERE routine_id = ? AND log_date = ?')->execute([$routine, date('Y-m-d', strtotime('-118 days'))]);
$check(($service->getPublishedReportData($other, $today)['feedback']['rule'] ?? null) === 'routine_60_days', 'Published milestone must stay stable after live correction.');
$check($service->republish($other, $today), 'Routine report must republish.');
$check(($service->getPublishedReportData($other, $today)['feedback']['rule'] ?? null) === 'routine_period_comparison', 'Republishing must calculate from corrected routine snapshot.');
fwrite(STDOUT, "PASS isolated MySQL Try links, actual snapshots and routine milestones\n");
