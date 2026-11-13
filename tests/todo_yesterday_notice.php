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
foreach (['owner','other'] as $name) $users->create($name . '@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), $name, true);
$owner = (int) $users->findByEmail('owner@example.invalid')['id'];
$other = (int) $users->findByEmail('other@example.invalid')['id'];
$today = date('Y-m-d'); $yesterday = date('Y-m-d', strtotime('-1 day')); $older = date('Y-m-d', strtotime('-2 days'));
$repo = new App\Models\CalendarRepository();
$make = static function (int $user, string $date, string $state) use ($repo): int {
    $id = $repo->createDailyPlanItem($user, $date, $date . ' ' . $state, 'D', null, null, null, null);
    $repo->updateTodoStatus($user, $id, $state);
    return $id;
};
$progress = $make($owner, $yesterday, 'in_progress');
$postponed = $make($owner, $yesterday, 'postponed');
foreach (['pending','completed','cancelled','external'] as $state) $make($owner, $yesterday, $state);
$make($owner, $older, 'in_progress'); $make($other, $yesterday, 'postponed');
$make($owner, $today, 'in_progress');
$db->prepare('INSERT INTO plan_block_templates (user_id,title,duration_index) VALUES (?,?,?)')->execute([$owner,'Template',6]);
$template = (int) $db->lastInsertId();
$db->prepare('UPDATE daily_plan_items SET source_block_template_id = ? WHERE id = ?')->execute([$template,$postponed]);
$service = new App\Services\TodoService();
$before = (int) $db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn();
$data = $service->getDateData($owner, $today);
check(count($data['items']) === 1, 'Today list never includes yesterday automatically');
check(array_column($data['carryovers'],'id') === [$progress,$postponed], 'Only yesterday progress/postponed, including template-backed tasks');
check((int) $db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn() === $before, 'Viewing notice never copies tasks');
check($repo->getTodoFollowup($owner, $postponed) !== null, 'Template-backed original remains reviewable');
$copied = $service->registerCarryover($owner, ['source_item_id'=>$postponed]);
check($copied['ok'], 'Explicit registration remains available');
$again = $service->registerCarryover($owner, ['source_item_id'=>$postponed]);
check($again['ok'] && $again['itemId'] === $copied['itemId'], 'Registration is idempotent');
check($repo->getTodoFollowup($owner, $postponed)['todo_status'] === 'postponed', 'Yesterday original state is retained');
$data = $service->getDateData($owner, $today);
check(count($data['items']) === 2 && count($data['carryovers']) === 2, 'Registered task and yesterday notice stay distinct');
$_SESSION=['user_id'=>$owner]; $_SERVER['REQUEST_URI']='/todo';
$notificationSettings = (new App\Services\NotificationService())->settings($owner);
$todo=$data; $todayDate=$today; $prevDate=$yesterday; $nextDate=date('Y-m-d',strtotime('+1 day')); $isToday=true; $isFuture=false; $canEdit=true; $activeTab='items'; $csrfToken='qa'; $reminderMinuteOptions=App\Services\NotificationService::PLAN_LEAD_MINUTES;
ob_start(); require $root . '/app/Views/pages/todo/index.php'; $html=(string) ob_get_clean();
check(str_contains($html,'todo-yesterday-notice'), 'Notice has a separate container');
check(str_contains($html,'data-todo-carryovers-open') && str_contains($html,'id="todoCarryoverSheet"'), 'Notice opens a separate sheet');
check(!str_contains($html,'<details class="todo-carryovers'), 'Notice is no longer an inline disclosure');
check(str_contains($html,'이미 오늘 할 일에 등록되어 있어요.'), 'Registered button has a visible reason');
check(!str_contains($html,'data-todo-followup') && str_contains($html,'data-status-followup-note'), 'Today state sheet uses the combined optional note editor');
echo "PASS yesterday notice isolation, status filter, templates, explicit registration and today sheet\n";

$firstCopy = (int) $copied['itemId'];
check($repo->deleteDailyPlanItem($owner, $firstCopy), 'Delete registered copy');
$afterDelete = $service->getDateData($owner, $today);
$sourceRow = array_values(array_filter($afterDelete['carryovers'], static fn(array $row): bool => (int) $row['id'] === $postponed))[0];
check($sourceRow['registered_item_id'] === null, 'Deleted copy never marks source as registered');
$retry = $service->registerCarryover($owner, ['source_item_id'=>$postponed]);
check($retry['ok'] && $retry['itemId'] !== $firstCopy, 'Delete then register creates a fresh copy');
check($service->registerCarryover($owner, ['source_item_id'=>$postponed])['itemId'] === $retry['itemId'], 'Repeated registration stays idempotent');
// Simulate an older soft deletion that left its unique source key attached.
$db->prepare('UPDATE daily_plan_items SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$retry['itemId']]);
$oldKeyRetry = $service->registerCarryover($owner, ['source_item_id'=>$postponed]);
check($oldKeyRetry['ok'] && $oldKeyRetry['itemId'] !== $retry['itemId'], 'Legacy deleted copy releases its key during re-registration');
$stmt = $db->prepare('SELECT source_carryover_item_id, deleted_at FROM daily_plan_items WHERE id = ?');
$stmt->execute([$retry['itemId']]); $deleted = $stmt->fetch();
check($deleted['source_carryover_item_id'] === null && $deleted['deleted_at'] !== null, 'Legacy cleanup retains deletion and does not restore old row');
check(!$repo->deleteDailyPlanItem($other, (int) $oldKeyRetry['itemId']), 'Foreign delete cannot release a registration');
check($repo->getTodoFollowup($owner, $postponed)['todo_status'] === 'postponed', 'Re-registration preserves yesterday source');
echo "PASS delete/re-register, legacy deleted key, ownership and active idempotency\n";

$db->exec('ALTER TABLE daily_plan_items DROP COLUMN followup_note');
$legacy = (new App\Services\TodoService())->getDateData($owner, $today);
check(count($legacy['carryovers']) === 2 && !$legacy['carryoverSupported'], 'Yesterday notice remains available without optional note/carryover support');
echo "PASS legacy schema still displays yesterday status notice\n";
