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
$todo = new App\Services\TodoService();
$make = static function(string $date,string $title,string $status,int $owner=0)use($repo,$user):int{
    $id=(int)$repo->createDailyPlanItem($owner?:$user,$date,$title,'B',null,null,null,null);
    check($repo->updateTodoStatus($owner?:$user,$id,$status),'Fixture state saves.');return $id;
};
$today=date('Y-m-d');$yesterday=date('Y-m-d',strtotime('-1 day'));$older=date('Y-m-d',strtotime('-2 days'));$tomorrow=date('Y-m-d',strtotime('+1 day'));
$progress=$make($yesterday,'Progress','in_progress');$postponed=$make($yesterday,'Postponed','postponed');
$old=$make($older,'Older','in_progress');$pending=$make($yesterday,'Pending','pending');$done=$make($yesterday,'Done','completed');
$make($today,'Same day','in_progress');$make($tomorrow,'Future','postponed');$make($yesterday,'Other account','in_progress',$other);
$save=static fn(int $owner,int $id,mixed $note):array=>$todo->saveFollowup($owner,['daily_plan_item_id'=>(string)$id,'note'=>$note]);
check($save($user,$progress,'남은 자료 확인 후 제출')['ok'],'Progress next action saves.');
check($save($user,$postponed,'담당자 답변 대기 · 내일 오전 연락')['ok'],'Postponement reason saves.');
check($save($other,$progress,'overwrite')['status']===404,'Other account cannot modify notes.');
check($save($user,$progress,str_repeat('가',501))['status']===422,'Unicode length is validated.');
check($save($user,$progress,[])['status']===422,'Non-text note is rejected.');
$rows=$repo->listTodoCarryovers($user,$today);
check(array_map('intval',array_column($rows,'id'))==[$progress,$postponed],'Only yesterday progress/postponed sources appear in the notice.');
check($rows[0]['followup_note']==='남은 자료 확인 후 제출','Notice preserves source context.');
$before=(int)$db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn();$data=$todo->getDateData($user,$today);
check(count($data['carryovers'])===2,'Service exposes review candidates separately.');
check((int)$db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn()===$before,'Viewing today never copies tasks.');
$register=static fn(int $owner,int $id):array=>$todo->registerCarryover($owner,['source_item_id'=>(string)$id,'date'=>'2099-12-31']);
check($register($other,$progress)['status']===404,'Another account cannot register the source.');
check($register($user,$old)['status']===422&&$register($user,$pending)['status']===422&&$register($user,$done)['status']===422,'Only yesterday eligible states can be registered.');
$created=$register($user,$progress);check($created['ok']&&$created['date']===$today,'Explicit choice registers today, ignoring a posted target date.');
check((int)$db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn()===$before+1,'Registration creates one task only.');
$again=$register($user,$progress);check($again['ok']&&$again['itemId']===$created['itemId'],'Duplicate registration is idempotent.');
check((int)$db->query('SELECT COUNT(*) FROM daily_plan_items')->fetchColumn()===$before+1,'Duplicate requests cannot create another copy.');
$copied=$repo->getTodoFollowup($user,$created['itemId']);
check($copied['todo_status']==='pending'&&(int)$copied['source_carryover_item_id']===$progress&&$copied['followup_note']==='남은 자료 확인 후 제출','New task preserves source relation/context and begins pending.');
check($repo->getTodoFollowup($user,$progress)['todo_status']==='in_progress','Yesterday state is unchanged.');
check((int)$repo->listTodoCarryovers($user,$today)[0]['registered_item_id']===$created['itemId'],'Notice marks an already registered source.');
$repo->updateTodoStatus($user,$progress,'completed');$repo->updateTodoStatus($user,$postponed,'cancelled');
check($repo->listTodoCarryovers($user,$today)===[],'Completed/cancelled sources leave the notice.');
check($todo->getFollowup($user,$progress)['note']==='남은 자료 확인 후 제출','Completion preserves the explanation.');
check(count($repo->listTodoCarryovers($user,$tomorrow))===1,'The next notice examines only its preceding date.');
$repo->updateTodoStatus($user,$postponed,'postponed');$stmt=$db->prepare('UPDATE daily_plan_items SET deleted_at=CURRENT_TIMESTAMP WHERE id=:id');$stmt->execute(['id'=>$postponed]);
check($todo->getFollowup($user,$postponed)['status']===404&&$repo->listTodoCarryovers($user,$today)===[],'Deleted sources are excluded and not editable.');
$clock=$notifications->planReminderTiming('2030-01-02',1,['timezone'=>'Asia/Seoul']);
check($clock['startLabel']==='01/02 00:10'&&$clock['choices'][60]==='01/01 23:10','Lead crossing midnight shows the previous date.');
$clock=$notifications->planReminderTiming('2030-01-02',60,['timezone'=>'America/New_York']);
check($clock['choices'][15]==='01/02 09:45','Clock labels use user wall time, independent of the device.');
if(!$mysql){
 $legacy=preg_replace('/^\s*(?:followup_note TEXT NULL,|source_carryover_item_id INTEGER NULL REFERENCES daily_plan_items\(id\) ON DELETE SET NULL,)\R|^CREATE UNIQUE INDEX IF NOT EXISTS uq_daily_plan_items_carryover[^\r\n]+\R/m','',(string)file_get_contents($root.'/sql/schema.sqlite.sql'));
 $old=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$old->exec($legacy);$connection->setValue(null,$old);
 check(!(new App\Models\CalendarRepository())->supportsTodoFollowup(),'Missing note schema is detected.');
 $temporary=tempnam(sys_get_temp_dir(),'lifeflow-followup-');register_shutdown_function(static function()use($temporary){if(is_file($temporary))unlink($temporary);});
 $oldFile=new PDO('sqlite:'.$temporary);$oldFile->exec($legacy);$oldFile=null;putenv('SQLITE_PATH='.$temporary);$connection->setValue(null,null);
 check((new App\Models\CalendarRepository())->supportsTodoFollowup(),'SQLite bootstraps the note column.');$connection->setValue(null,null);
 check((new App\Models\CalendarRepository())->supportsTodoCarryover(),'SQLite bootstraps registration identity and uniqueness.');$connection->setValue(null,null);
 check((new App\Models\CalendarRepository())->supportsTodoFollowup(),'SQLite migration is repeatable.');
}
echo 'PASS '.($mysql?'MySQL':'SQLite').' carryover source identity, dates, ownership, notes, completion, deletion, clock labels and compatibility'.PHP_EOL;
