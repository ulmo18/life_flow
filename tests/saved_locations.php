<?php
declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
require $root . '/app/Core/helpers.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $db);
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$users = new App\Models\User();
foreach (['owner','other'] as $name) $users->create($name . '@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), $name, true);
$owner = (int) $users->findByEmail('owner@example.invalid')['id'];
$other = (int) $users->findByEmail('other@example.invalid')['id'];
$service = new App\Services\LocationService();
$repo = new App\Models\LocationRepository();
$calendar = new App\Models\CalendarRepository();
$raw = json_encode(['name' => '카페 하루', 'address' => '서울특별시 성동구 왕십리로', 'lat' => 37.5445, 'lng' => 127.0557], JSON_UNESCAPED_UNICODE);
$valid = $service->validate($raw);
check($valid['errors'] === [], 'Valid location');
foreach (['{}', '[]', 'false', 'bad', json_encode(['name'=>'x','address'=>'a','lat'=>91,'lng'=>0]), json_encode(['name'=>'x','address'=>'a','lat'=>0,'lng'=>181])] as $bad) {
    check($service->validate($bad)['errors'] !== [], 'Reject invalid location');
}
check($service->validate('')['change'] === [], 'Missing location preserves stored value');
$todo = $calendar->createDailyPlanItem($owner, '2026-11-11', 'Visit', 'D', null, null, null, null, locationChange:$valid['change']);
check($todo !== null && $repo->find($owner, 'todo', $todo)['name'] === '카페 하루', 'Todo saves location');
check(!$repo->owns($other, 'todo', $todo), 'Location ownership');
check($repo->find($other, 'todo', $todo) === null, 'No cross-user location read');
check($calendar->updateDailyPlanItemDetails($owner, $todo, 'Changed', 'C', null), 'Ordinary edit');
check($repo->find($owner, 'todo', $todo) !== null, 'Ordinary edit preserves location');
$event = $calendar->createActualEvent($owner, '2026-11-11', 'Visit', 'timed', 60, 66, null, null, '', locationChange:$valid['change']);
check($event !== null && $repo->find($owner, 'actual', $event) !== null, 'Actual saves location');
check($calendar->updateActualEvent($owner, $event, '2026-11-11', 'Visit', 'timed', 60, 66, null, null, '', locationChange:$service->validate('null')['change']), 'Clear Actual location');
check($repo->find($owner, 'actual', $event) === null, 'Location removed');
check($repo->find($owner, 'todo', $todo) !== null, 'Separate Todo location preserved');
$before = (int) $db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn();
$db->exec("CREATE TRIGGER fail_location BEFORE INSERT ON saved_locations BEGIN SELECT RAISE(ABORT, 'test failure'); END");
$failed = $calendar->createDailyPlanItem($owner, '2026-11-11', 'Rollback', 'D', null, null, null, null, locationChange:$valid['change']);
check($failed === null && (int) $db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn() === $before, 'Location write failure rolls back record creation');
check(!$calendar->updateDailyPlanItemDetails($owner, $todo, 'Must rollback', 'A', null, $valid['change']), 'Location write failure rejects update');
check($calendar->getDailyPlanItemForCompletion($owner, $todo)['title'] === 'Changed', 'Source update rolled back');
$db->exec('DROP TRIGGER fail_location');
check($calendar->updateDailyPlanItemDetails($owner, $todo, 'Changed', 'C', null, $service->validate('null')['change']), 'Remove Todo location');
check($repo->find($owner, 'todo', $todo) === null, 'Todo location removed');
$todoService = new App\Services\TodoService();
$created = $todoService->create($owner, ['date' => date('Y-m-d'), 'title' => 'Service location', 'location_json' => $raw]);
check($created['ok'], 'Todo service creates located item');
$serviceTodo = (int) $created['item']['id'];
check($repo->find($owner, 'todo', $serviceTodo) !== null, 'Todo service carries location through validation');
$updated = $todoService->updateToday($owner, ['daily_plan_item_id' => $serviceTodo, 'title' => 'Service location edit', 'location_json' => 'null']);
check($updated['ok'] && $repo->find($owner, 'todo', $serviceTodo) === null, 'Todo service removes location');
$calendarService = new App\Services\CalendarService();
$eventInput = ['date' => date('Y-m-d'), 'title' => 'Located service Actual', 'start_index' => '96', 'end_index' => '102', 'location_json' => $raw];
$validated = $calendarService->validateEventInput($owner, $eventInput);
check($validated['ok'], 'Actual validation accepts location');
$serviceEvent = $calendarService->createActualEvent($owner, $validated['data']);
check($serviceEvent !== null && $repo->find($owner, 'actual', $serviceEvent) !== null, 'Actual service carries location');
$eventInput['event_id'] = $serviceEvent;
$eventInput['location_json'] = 'null';
$validated = $calendarService->validateEventUpdateInput($owner, $eventInput);
check($validated['ok'] && $calendarService->updateActualEvent($owner, $validated['data']), 'Actual service update');
check($repo->find($owner, 'actual', $serviceEvent) === null, 'Actual service removes location');
echo "PASS: location validation, ownership, Todo/Actual create/update/remove, transaction rollback\n";
