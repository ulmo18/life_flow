<?php
declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $db);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$users = new App\Models\User();
foreach (['owner', 'other'] as $name) $users->create($name . '@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), $name, true);
$owner = (int) $users->findByEmail('owner@example.invalid')['id'];
$other = (int) $users->findByEmail('other@example.invalid')['id'];
$body = '검색 기록 ' . str_repeat('전체 본문입니다. ', 50) . '</script><img src=x onerror=alert(1)>';
$insert = $db->prepare('INSERT INTO notes (user_id, content, deleted_at) VALUES (?, ?, ?)');
$insert->execute([$owner, $body, null]);
$insert->execute([$owner, '검색 삭제한 기록', '2026-11-13']);
$insert->execute([$other, '검색 다른 사용자 비공개', null]);
$service = new App\Services\SearchService();
$result = $service->search($owner, '검색', 'all', '', '');
check(count($result['items']) === 1, 'Deleted and cross-user records excluded');
check($result['items'][0]['details'][0]['value'] === $body, 'Full body retained without snippet truncation');
check(mb_strlen($result['items'][0]['snippet']) < mb_strlen($body), 'List remains compact');
check($service->search($owner, '검색', 'all', '2099-01-01', '')['items'] === [], 'Date filter retained');
$calendar = new App\Models\CalendarRepository();
$calendar->createActualEvent($owner, '2026-11-13', '검색 일정', 'timed', 138, 144, null, null, '전체 일정 메모');
$items = $service->search($owner, '검색', 'schedule', '', '')['items'];
check(count($items) === 1 && $items[0]['details'][0]['value'] === '23:00 – 24:00', 'Time metadata and midnight boundary');
$insert = $db->prepare('INSERT INTO goals (user_id, goal_type, title, behavior_when, behavior_where, behavior_how) VALUES (?, ?, ?, ?, ?, ?)');
$insert->execute([$owner, 'yearly', '검색 목표', '매일', '집에서', '꾸준히']);
$details = $service->search($owner, '검색', 'goal', '', '')['items'][0]['details'];
check(array_column($details, 'value') === ['꾸준히', '매일', '집에서'], 'All goal detail fields included');
echo "PASS search full details, metadata, filtering, ownership and deletion\n";

$todoId = $calendar->createDailyPlanItem($owner, '2026-11-13', '검색 할 일', 'D', null, null, null, null);
$update = $db->prepare('UPDATE daily_plan_items SET followup_note = ? WHERE id = ?');
$update->execute(['계속 진행할 내용', $todoId]);
check($service->search($owner, '검색', 'todo', '', '')['items'][0]['details'][0]['value'] === '계속 진행할 내용', 'Optional full note shown when supported');
$db->exec('ALTER TABLE daily_plan_items DROP COLUMN followup_note');
$legacy = $service->search($owner, '검색', 'todo', '2026-11-01', '2026-11-30');
check(count($legacy['items']) === 1, 'Search supports schemas without optional followup_note');
check($legacy['filterSummary'] === '할 일 · 2026.11.01 ~ 2026.11.30', 'Applied filters summarized');
check($service->search($owner, '', 'all', '', '')['filterSummary'] === '전체 · 전체 기간', 'Unfiltered summary');
echo "PASS legacy schema without followup_note and filter summaries\n";
