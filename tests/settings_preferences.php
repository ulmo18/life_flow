<?php
declare(strict_types=1);

if (!isset($argv[1])) {
    foreach (['notifications', 'localization', 'save'] as $mode) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($mode), $status);
        if ($status !== 0) exit($status);
    }
    exit;
}
$root = dirname(__DIR__);
putenv('DB_CONNECTION=sqlite');
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
require $root . '/app/Core/helpers.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec((string) file_get_contents($root . '/sql/schema.sqlite.sql'));
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $db);
$users = new App\Models\User();
$users->create('settings@example.invalid', password_hash('fixture', PASSWORD_DEFAULT), 'Settings', true);
$userId = (int) $users->findByEmail('settings@example.invalid')['id'];
$_SESSION = ['user_id' => $userId, 'locale_preference' => 'ko'];
$_SERVER['REQUEST_URI'] = '/settings';
$csrf = App\Core\Csrf::token();
$controller = new App\Controllers\AuthController();
$mode = $argv[1];
if ($mode === 'save') {
    $service = new App\Services\NotificationService();
    $initial = $service->settings($userId);
    $initial['retrospect_morning_time'] = '09:17';
    $service->updateSettings($userId, $initial);
}
register_shutdown_function(static function () use ($mode, $controller, $userId): void {
    if ($mode === 'save') {
        $saved = (new App\Services\NotificationService())->settings($userId);
        if ($saved['retrospect_morning_time'] !== '09:17' || $saved['retrospect_morning_enabled'] !== 0 || $saved['routine_reminder_time'] !== '15:23') throw new RuntimeException('Disabled time or minute-level save changed');
        if (empty($_SESSION['notification_sync_settings'])) throw new RuntimeException('Save must request schedule sync');
        echo "PASS settings save preserves disabled time, minute resolution and schedule sync\n";
        return;
    }
    if (empty($_SESSION['errors'])) throw new RuntimeException('Validation failure must be reported');
    $draft = $_SESSION['settings_draft'] ?? [];
    if ($mode === 'notifications' && ($draft['notifications']['routine_reminder_time'] ?? '') !== '09:17') {
        throw new RuntimeException('Valid minute-level draft time was lost');
    }
    if ($mode === 'localization' && ($draft['localization']['locale'] ?? '') !== 'en') {
        throw new RuntimeException('Valid locale draft was lost');
    }
    ob_start(); $controller->settings(); $html = ob_get_clean();
    $group = $mode === 'notifications' ? 'notificationSettings' : 'settingsBasicTitle';
    if (!preg_match('/<details[^>]*aria-labelledby="' . $group . '"[^>]*\bopen\b/', $html)) throw new RuntimeException('Error group must expand automatically');
    if (!str_contains($html, 'data-settings-draft="1"')) throw new RuntimeException('Draft must render as unsaved');
    if ($mode === 'notifications' && !str_contains($html, 'value="09:17"')) throw new RuntimeException('Draft time must render');
    if ($mode === 'localization' && !str_contains($html, 'value="en" selected')) throw new RuntimeException('Draft locale must render');
    if (isset($_SESSION['settings_draft'])) throw new RuntimeException('Draft must be consumed after rendering');
    echo 'PASS settings ' . $mode . " validation draft and rendering\n";
});
$_POST = ['_csrf_token' => $csrf];
if ($mode === 'save') {
    $_POST += ['notification_enabled'=>'1', 'routine_reminder_enabled'=>'1', 'routine_reminder_time'=>'15:23'];
    $controller->updateNotifications();
} elseif ($mode === 'notifications') {
    $_POST += ['notification_enabled'=>'1', 'routine_reminder_enabled'=>'1', 'routine_reminder_time'=>'09:17', 'retrospect_morning_time'=>'99:99'];
    $controller->updateNotifications();
} else {
    $_POST += ['locale'=>'en', 'timezone'=>'invalid/timezone'];
    $controller->updateLocalization();
}
