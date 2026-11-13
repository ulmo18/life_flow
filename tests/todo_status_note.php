<?php
declare(strict_types=1);
$root=dirname(__DIR__); putenv('DB_CONNECTION=sqlite');
spl_autoload_register(static function(string $class) use($root):void {
    if(str_starts_with($class,'App\\')) require $root.'/app/'.str_replace('\\','/',substr($class,4)).'.php';
});
require $root.'/app/Core/helpers.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec((string)file_get_contents($root.'/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class,'connection'))->setValue(null,$db);
function check(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$users=new App\Models\User();
foreach(['owner','other'] as $name)$users->create($name.'@example.invalid',password_hash('fixture',PASSWORD_DEFAULT),$name,true);
$owner=(int)$users->findByEmail('owner@example.invalid')['id']; $other=(int)$users->findByEmail('other@example.invalid')['id'];
$repo=new App\Models\CalendarRepository(); $service=new App\Services\TodoService();
$id=$repo->createDailyPlanItem($owner,date('Y-m-d'),'Task','D',null,null,null,null);
check($service->saveStatus($owner,$id,'in_progress','다음 행동')['ok'],'Progress and note save together');
$saved=$repo->getTodoFollowup($owner,$id);
check($saved['todo_status']==='in_progress' && $saved['followup_note']==='다음 행동','Both values persisted');
check(!$service->saveStatus($owner,$id,'postponed',str_repeat('가',501))['ok'],'Long note rejected');
check($repo->getTodoFollowup($owner,$id)['todo_status']==='in_progress','Invalid note never partially changes state');
check(!$service->saveStatus($other,$id,'postponed','foreign')['ok'],'Owner scope');
check($repo->getTodoFollowup($owner,$id)['followup_note']==='다음 행동','Foreign write cannot change note');
check($service->saveStatus($owner,$id,'postponed','')['ok'],'Optional empty note');
check($repo->getTodoFollowup($owner,$id)['followup_note']==='','Explicit empty note clears');
check($service->saveStatus($owner,$id,'postponed','연기 사유')['ok'],'Same status can update note');
check($service->saveStatus($owner,$id,'completed')['ok'],'Other states retain quick save');
check($repo->getTodoFollowup($owner,$id)['followup_note']==='연기 사유','State-only save preserves note');
$db->exec('ALTER TABLE daily_plan_items DROP COLUMN followup_note');
$legacy=new App\Services\TodoService();
check(!$legacy->saveStatus($owner,$id,'postponed','memo')['ok'],'Unsupported note never changes status');
check($legacy->getFollowup($owner,$id)['note']==='','Legacy read remains valid');
check($legacy->saveStatus($owner,$id,'in_progress','')['ok'],'Legacy state-only path remains available');
echo "PASS atomic state/note save, optional note, ownership, validation and legacy support\n";
