#!/bin/sh
# Run inside the app container with PHP, curl and sqlite3 available.
set -eu
fixture=$(mktemp -d /tmp/lifeflow-block-edit-XXXXXX)
cleanup() {
    if [ -n "${server_pid:-}" ]; then kill "$server_pid" 2>/dev/null || true; fi
    case "$fixture" in /tmp/lifeflow-block-edit-*) rm -rf -- "$fixture" ;; esac
}
trap cleanup EXIT
cp -a /var/www/html/app /var/www/html/config /var/www/html/sql /var/www/html/public "$fixture/"
mkdir -p "$fixture/storage/logs"
printf 'DB_CONNECTION=sqlite\nSQLITE_PATH=%s/test.sqlite\n' "$fixture" > "$fixture/.env"
cat > "$fixture/seed.php" <<'PHP'
<?php
chdir(__DIR__);
putenv('DB_CONNECTION=sqlite'); putenv('SQLITE_PATH=' . __DIR__ . '/test.sqlite');
date_default_timezone_set('Asia/Seoul');
spl_autoload_register(function ($class) { if (str_starts_with($class, 'App\\')) { $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php'; if (is_file($path)) require $path; } });
require __DIR__ . '/app/Core/helpers.php';
$db = App\Core\Database::connection();
$stmt = $db->prepare('INSERT INTO user (email,password_hash,nickname,role,is_active,created_at,updated_at) VALUES (?,?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');
$stmt->execute(['fixture@example.test', password_hash('fixture-password', PASSWORD_DEFAULT), 'Fixture', 'user']);
$userId = (int) $db->lastInsertId();
$repo = new App\Models\CalendarRepository();
$item = $repo->createDailyPlanItem($userId, date('Y-m-d'), 'Fixture plan', 'B', null, null, null, null);
$block = $repo->createDailyPlanBlock($userId, (int) $item, 48, 54);
$actual = $repo->createActualFromDailyPlanBlock($userId, date('Y-m-d'), (int) $block, 48, 54);
if (!$block || !$actual) throw new RuntimeException('Fixture setup failed');
file_put_contents(__DIR__ . '/block-id', (string) $block);
file_put_contents(__DIR__ . '/item-id', (string) $item);
file_put_contents(__DIR__ . '/fixture-date', date('Y-m-d'));
PHP
php "$fixture/seed.php"
block=$(cat "$fixture/block-id")
item=$(cat "$fixture/item-id")
day=$(cat "$fixture/fixture-date")
cd "$fixture"
php -S 127.0.0.1:18891 -t public public/index.php > "$fixture/server.log" 2>&1 &
server_pid=$!
curl -fsS --retry 10 --retry-connrefused --retry-delay 1 -c "$fixture/cookies" http://127.0.0.1:18891/login > "$fixture/login.html"
token=$(sed -n 's/.*name="_csrf_token" value="\([^"]*\)".*/\1/p' "$fixture/login.html" | head -n 1)
test -n "$token"
curl -fsS -b "$fixture/cookies" -c "$fixture/cookies" -o /dev/null --data-urlencode "_csrf_token=$token" --data-urlencode 'email=fixture@example.test' --data-urlencode 'password=fixture-password' http://127.0.0.1:18891/login
curl -fsS -b "$fixture/cookies" http://127.0.0.1:18891/calendar > "$fixture/calendar.html"
token=$(sed -n 's/.*name="_csrf_token" value="\([^"]*\)".*/\1/p' "$fixture/calendar.html" | head -n 1)
test -n "$token"
curl -fsS -b "$fixture/cookies" http://127.0.0.1:18891/todo > "$fixture/todo.html"
grep -q 'data-todo-record-template' "$fixture/todo.html"
grep -q 'data-todo-status-record' "$fixture/todo.html"
if grep -q 'data-todo-record-open' "$fixture/todo.html"; then exit 1; fi
grep -q 'data-todo-status-symbol' "$fixture/todo.html"
if grep -q 'todo-tree-disclosure' "$fixture/todo.html"; then exit 1; fi
test "$(curl -sS -b "$fixture/cookies" -o "$fixture/response.json" -w '%{http_code}' -H 'Accept: application/json' --data-urlencode "daily_plan_item_id=$item" --data-urlencode 'status=completed' http://127.0.0.1:18891/todo/status)" = 419
test "$(curl -sS -b "$fixture/cookies" -o "$fixture/response.json" -w '%{http_code}' -H 'Accept: application/json' --data-urlencode "_csrf_token=$token" --data-urlencode "daily_plan_item_id=$item" --data-urlencode 'status=completed' http://127.0.0.1:18891/todo/status)" = 200
php -r '$v=json_decode(file_get_contents($argv[1]), true); if (!is_array($v) || ($v["ok"] ?? null) !== true || ($v["status"] ?? null) !== "completed") exit(1);' "$fixture/response.json"
test "$(sqlite3 "$fixture/test.sqlite" "SELECT todo_status FROM daily_plan_items WHERE id=$item")" = 'completed'
test "$(curl -sS -b "$fixture/cookies" -o "$fixture/response.json" -w '%{http_code}' -H 'Accept: application/json' --data-urlencode "_csrf_token=$token" --data-urlencode "daily_plan_item_id=$item" --data-urlencode 'status=invalid' http://127.0.0.1:18891/todo/status)" = 422
php -r '$v=json_decode(file_get_contents($argv[1]), true); if (!is_array($v) || ($v["ok"] ?? null) !== false) exit(1);' "$fixture/response.json"
test "$(sqlite3 "$fixture/test.sqlite" "SELECT todo_status FROM daily_plan_items WHERE id=$item")" = 'completed'
post() {
    curl -sS -b "$fixture/cookies" -o "$fixture/response.json" -w '%{http_code}' -H 'Accept: application/json' \
        --data-urlencode "_csrf_token=$token" --data-urlencode "daily_plan_block_id=$block" "$@"
}
test "$(curl -sS -b "$fixture/cookies" -o "$fixture/response.json" -w '%{http_code}' -H 'Accept: application/json' --data-urlencode "daily_plan_block_id=$block" --data-urlencode 'manual_status=in_progress' http://127.0.0.1:18891/calendar/plan-block/status)" = 419
test "$(post --data-urlencode 'manual_status=in_progress' http://127.0.0.1:18891/calendar/plan-block/status)" = 422
test "$(post --data-urlencode 'importance=A' http://127.0.0.1:18891/calendar/plan-block/details)" = 422
test "$(sqlite3 "$fixture/test.sqlite" "SELECT importance_snapshot || ':' || manual_status FROM daily_plan_blocks WHERE id=$block")" = 'B:pending'
test "$(post --data-urlencode 'importance=invalid' http://127.0.0.1:18891/calendar/plan-block/details)" = 422
test "$(sqlite3 "$fixture/test.sqlite" "SELECT importance_snapshot || ':' || manual_status FROM daily_plan_blocks WHERE id=$block")" = 'B:pending'
test "$(post http://127.0.0.1:18891/calendar/plan-block/detach)" = 200
test "$(sqlite3 "$fixture/test.sqlite" "SELECT importance_snapshot || ':' || manual_status FROM daily_plan_blocks WHERE id=$block")" = 'B:pending'
test "$(sqlite3 "$fixture/test.sqlite" 'SELECT count(*) FROM calendar_events WHERE deleted_at IS NULL AND daily_plan_block_id IS NULL AND daily_plan_item_id IS NULL')" = 1
test "$(post http://127.0.0.1:18891/calendar/plan-block/detach)" = 422
test "$(post http://127.0.0.1:18891/calendar/plan-block/delete)" = 200
test "$(sqlite3 "$fixture/test.sqlite" "SELECT count(*) FROM daily_plan_blocks WHERE id=$block")" = 0
test "$(sqlite3 "$fixture/test.sqlite" 'SELECT count(*) FROM calendar_events WHERE deleted_at IS NULL')" = 1
event=$(sqlite3 "$fixture/test.sqlite" 'SELECT id FROM calendar_events WHERE deleted_at IS NULL LIMIT 1')
test -n "$event"
test "$(curl -sS -b "$fixture/cookies" -o /dev/null -w '%{http_code}' --data-urlencode "event_id=$event" http://127.0.0.1:18891/calendar/event/delete)" = 302
test "$(sqlite3 "$fixture/test.sqlite" "SELECT count(*) FROM calendar_events WHERE id=$event AND deleted_at IS NULL")" = 1
test "$(curl -sS -b "$fixture/cookies" -D "$fixture/headers" -o /dev/null -w '%{http_code}' --data-urlencode "_csrf_token=$token" --data-urlencode "event_id=$event" --data-urlencode "date=$day" --data-urlencode 'calendar_mode=actual' http://127.0.0.1:18891/calendar/event/delete)" = 302
test "$(sqlite3 "$fixture/test.sqlite" "SELECT count(*) FROM calendar_events WHERE id=$event AND deleted_at IS NOT NULL")" = 1
grep -qi '^Location: .*mode=actual' "$fixture/headers"
printf 'PASS isolated HTTP status, importance, detachment, Plan and Actual deletion\n'
