<?php
declare(strict_types=1);

// SQLite uses only an in-memory database. MySQL requires an explicit disposable target.
$root = dirname(__DIR__);
$mysql = getenv('DB_CONNECTION') === 'mysql';
if ($mysql && !preg_match('/^lifeflow_test_[a-z0-9_]+$/', getenv('DB_NAME') ?: '')) {
    fwrite(STDERR, "Refusing to use a non-disposable MySQL target.\n"); exit(2);
}
if (!$mysql) putenv('DB_CONNECTION=sqlite');
date_default_timezone_set('Asia/Seoul');
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});
require $root . '/app/Core/helpers.php';
$connection = new ReflectionProperty(App\Core\Database::class, 'connection');
if ($mysql) {
    $db = App\Core\Database::connection();
} else {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
    $connection->setValue(null, $db);
}
function check(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$users = new App\Models\User();
$suffix = bin2hex(random_bytes(5));
$users->create('reminder-' . $suffix . '@example.invalid', password_hash($suffix, PASSWORD_DEFAULT), 'Reminder test', true);
$user = (int) $users->findByEmail('reminder-' . $suffix . '@example.invalid')['id'];
$users->create('other-' . $suffix . '@example.invalid', password_hash($suffix, PASSWORD_DEFAULT), 'Other test', true);
$other = (int) $users->findByEmail('other-' . $suffix . '@example.invalid')['id'];
$repo = new App\Models\CalendarRepository();
$service = new App\Services\CalendarService();
$notifications = new App\Services\NotificationService();
$date = (new DateTimeImmutable('today'))->modify('+90 days')->format('Y-m-d');
$item = $repo->createDailyPlanItem($user, $date, 'Untimed source', 'A', null, null, null, null);
$first = $repo->createDailyPlanBlock($user, (int) $item, 60, 66);
$second = $repo->createDailyPlanBlock($user, (int) $item, 90, 96);
check($first !== null && $second !== null, 'Two blocks must exist.');
$payload = static function () use ($service,$notifications,$user,$date): array {
    return $notifications->buildCalendarSyncPayload($user, $service->getDayViewData($user, $date));
};
$save = static function (int $owner, int $id, string $enabled, string $minutes) use ($service): array {
    return $service->updatePlanReminder($owner, ['daily_plan_block_id'=>(string)$id,'reminder_enabled'=>$enabled,'reminder_minutes_before'=>$minutes,'date'=>'1900-01-01']);
};
check($payload()['specific'] === [], 'Existing and new blocks default to opt-out.');
check($save($user,$first,'1','15')['ok'], 'First reminder saves.');
check($save($user,$second,'1','0')['ok'], 'At-start reminder saves independently.');
$items = $payload()['specific'];
check(count($items) === 2 && count(array_unique(array_column($items,'key'))) === 2, 'One untimed source produces two distinct block alarms.');
check($items[0]['fireAt'] === $date.'T09:45:00+09:00' && $items[1]['fireAt'] === $date.'T15:00:00+09:00', 'Each block applies its own lead time.');
check($items[0]['message'] === 'Untimed source 시작 15분 전입니다.' && $items[1]['message'] === 'Untimed source 시작 시간입니다.', 'Message follows selected lead time.');
check($save($other,$first,'0','5')['status'] === 404, 'Another user cannot modify a block.');
check($save($user,$first,'1','7')['status'] === 422 && $save($user,$first,'yes','5')['status'] === 422, 'Invalid minutes and flags are rejected.');
check($repo->getPlanBlockReminder($user,$first)['calendar_date'] === $date, 'Posted date cannot choose the reservation scope.');
check($repo->updateDailyPlanBlock($user,$first,66,72), 'Block can move.');
$moved = $payload()['specific'];
check($moved[0]['key'] === $items[0]['key'] && $moved[0]['fireAt'] === $date.'T10:45:00+09:00', 'Move preserves stable identity and applies saved lead.');
$todo = (new App\Services\TodoService())->getDateData($user,$date);
check($todo['items'][0]['planBlocks'][0]['reminderEnabled'] && $todo['items'][0]['planBlocks'][0]['reminderMinutesBefore'] === 15, 'To-do detail uses the saved block setting.');
check($save($user,$first,'0','15')['ok'] && count($payload()['specific']) === 1, 'Disabling removes only its own alarm.');
check($repo->deleteDailyPlanBlock($user,$second) && $payload()['specific'] === [], 'Deleted block disappears from scoped replacement.');
check($save($user,$first,'1','15')['ok'] && count($payload()['specific']) === 1, 'An enabled block exists before testing global gates.');
$settings = $notifications->settings($user);
$settings['notification_enabled']=0;$notifications->updateSettings($user,$settings);
check(!$payload()['enabled'] && $payload()['specific'] === [], 'Master switch remains authoritative.');
$settings['notification_enabled']=1;$settings['calendar_plan_reminder_enabled']=0;$notifications->updateSettings($user,$settings);
check($payload()['specific'] === [], 'Calendar global switch remains authoritative.');
$settings['calendar_plan_reminder_enabled']=1;$notifications->updateSettings($user,$settings);
check(count($payload()['specific']) === 1, 'Restoring global settings restores the enabled block.');
$past = (new DateTimeImmutable('today'))->modify('-1 day')->format('Y-m-d');
$pastItem=$repo->createDailyPlanItem($user,$past,'Past','D',null,null,null,null);
$pastBlock=$repo->createDailyPlanBlock($user,(int)$pastItem,60,66);
check($save($user,(int)$pastBlock,'1','5')['ok'], 'Past preference can save without immediate delivery.');
check($notifications->buildCalendarSyncPayload($user,$service->getDayViewData($user,$past))['specific'] === [], 'Past alarms remain excluded.');

if (!$mysql) {
    $oldSchema = preg_replace('/^\s*reminder_(enabled|minutes_before) INTEGER NOT NULL DEFAULT \d+,\R/m','',(string)file_get_contents($root.'/sql/schema.sqlite.sql'));
    $legacy=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $legacy->exec($oldSchema);$connection->setValue(null,$legacy);
    check(!(new App\Models\CalendarRepository())->supportsPlanBlockReminders(), 'Unmigrated schemas are detected without failing reads.');
    check((new App\Services\CalendarService())->updatePlanReminder(1,['daily_plan_block_id'=>'1','reminder_enabled'=>'1','reminder_minutes_before'=>'5'])['status']===503,'Unmigrated writes fail clearly.');
    $temporary=tempnam(sys_get_temp_dir(),'lifeflow-reminder-migration-');
    register_shutdown_function(static function()use($temporary){if(is_file($temporary))unlink($temporary);});
    $fileDb=new PDO('sqlite:'.$temporary);$fileDb->exec($oldSchema);$fileDb=null;
    putenv('SQLITE_PATH='.$temporary);$connection->setValue(null,null);
    check((new App\Models\CalendarRepository())->supportsPlanBlockReminders(),'SQLite startup migrates old schemas.');
    $connection->setValue(null,null);
    check((new App\Models\CalendarRepository())->supportsPlanBlockReminders(),'SQLite migration is repeatable.');
}
echo 'PASS '.($mysql?'MySQL':'SQLite').' block opt-in, multiple blocks, lead times, ownership, movement, deletion, global switches, past filtering and migration compatibility'.PHP_EOL;
