<?php
declare(strict_types=1);

$root = dirname(__DIR__);
putenv('DB_CONNECTION=sqlite');
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
require $root . '/app/Core/helpers.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $db);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$users = new App\Models\User();
$users->create('notification@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), 'Fixture', true);
$user = (int) $users->findByEmail('notification@example.invalid')['id'];
$service = new App\Services\NotificationService();
$settings = $service->settings($user);
$settings['notification_enabled'] = $settings['goal_deadline_reminder_enabled'] = $settings['goal_deadline_day_before_enabled'] = 1;
$settings['goal_deadline_time'] = '09:30';
$goals = new ReflectionMethod($service, 'goalDeadlinePayload');
$year = (int) date('Y') + 1;
$spring = (new DateTimeImmutable('second sunday of march ' . $year))->format('Y-m-d');
$fall = (new DateTimeImmutable('first sunday of november ' . $year))->format('Y-m-d');
$insert = $db->prepare('INSERT INTO goals (user_id, goal_type, title, period_end_date) VALUES (:user, :type, :title, :date)');
foreach ([$spring, $fall] as $date) $insert->execute(['user'=>$user,'type'=>'yearly','title'=>'Deadline','date'=>$date]);
$hostZone = date_default_timezone_get();
date_default_timezone_set('Asia/Seoul');
$settings['timezone'] = 'America/New_York';
$items = $goals->invoke($service, $user, $settings);
check(count($items) === 4, 'Two deadlines and D-1');
foreach ($items as $item) {
    check(str_contains($item['fireAt'], 'T09:30:00'), 'Wall-clock deadline preserved');
    $instant = new DateTimeImmutable($item['fireAt']);
    $expected = new DateTimeImmutable(substr($item['fireAt'], 0, 10) . ' 09:30', new DateTimeZone('America/New_York'));
    check($instant->getTimestamp() === $expected->getTimestamp(), 'Absolute instant independent of host zone');
}
check(str_ends_with($items[0]['fireAt'], '-05:00') && str_ends_with($items[1]['fireAt'], '-04:00'), 'Spring D-1 and deadline have separate DST offsets');
check(str_ends_with($items[2]['fireAt'], '-04:00') && str_ends_with($items[3]['fireAt'], '-05:00'), 'Fall D-1 and deadline have separate DST offsets');
$settings['timezone'] = 'Asia/Seoul';
foreach ($goals->invoke($service, $user, $settings) as $item) check(str_ends_with($item['fireAt'], '+09:00'), 'Current user Seoul offset');
$settings['goal_deadline_day_before_enabled'] = 0;
check(count($goals->invoke($service, $user, $settings)) === 2, 'D-1 gate retained');
$settings['timezone'] = 'Pacific/Honolulu';
$today = new DateTimeImmutable('now', new DateTimeZone($settings['timezone']));
$insert->execute(['user'=>$user,'type'=>'monthly','title'=>'Past today','date'=>$today->format('Y-m-d')]);
$pastId = (int) $db->lastInsertId();
$settings['goal_deadline_time'] = '00:00';
foreach ($goals->invoke($service, $user, $settings) as $item) check($item['goalId'] !== $pastId, 'Past trigger excluded using user-local today');
$settings['notification_enabled'] = 0;
check($goals->invoke($service, $user, $settings) === [], 'Master gate retained');
date_default_timezone_set($hostZone);
$routines = new App\Models\RoutineRepository();
$expected = [];
foreach (['16:45','08:20:00',null,'25:00','bad','00:00'] as $time) {
    $id = $routines->create($user, null, 'Routine', date('Y-m-d'), 7, true, $time);
    check($id !== null, 'Create routine fixture');
    $expected[$id] = in_array($time, ['16:45','08:20:00','00:00'], true) ? substr($time,0,5) : '14:00';
}
$settings['notification_enabled'] = $settings['routine_reminder_enabled'] = 1;
$settings['routine_reminder_time'] = '14:00';
$method = new ReflectionMethod($service, 'routineReminderPayload');
$items = $method->invoke($service, $user, $settings);
check(count($items) === 6, 'Routine fixtures returned');
foreach ($items as $item) check($item['time'] === $expected[$item['routineId']], 'Individual time first; fallback only for missing/invalid time');
$settings['routine_reminder_enabled'] = 0;
check($method->invoke($service, $user, $settings) === [], 'Routine global gate retained');
echo "PASS: user-zone goal alarms, DST, past exclusion and individual routine priority\n";
