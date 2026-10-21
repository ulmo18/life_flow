<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Seoul');

use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\CalendarTagController;
use App\Controllers\ExecutionEnergyController;
use App\Controllers\GoalController;
use App\Controllers\MemoController;
use App\Controllers\PlanController;
use App\Controllers\PrototypeController;
use App\Controllers\RetrospectController;
use App\Controllers\RoutineController;
use App\Controllers\SearchController;
use App\Controllers\TodoController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Models\UserPreferenceRepository;
use App\Core\I18n;
use App\Services\RememberMeService;

require_once dirname(__DIR__) . '/config/bootstrap.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/../app/Core/helpers.php';

$rememberMeService = new RememberMeService();
$rememberMeService->autoLoginFromCookie();

if (isset($_SESSION['user_id'])
    && (!isset($_SESSION['locale_preference']) || !isset($_SESSION['timezone_preference']))) {
    $sessionPreferences = (new UserPreferenceRepository())->get((int) $_SESSION['user_id']);
    $_SESSION['locale_preference'] = I18n::normalizeLocale((string) ($sessionPreferences['locale'] ?? 'ko'));
    $_SESSION['timezone_preference'] = (string) ($sessionPreferences['timezone'] ?? 'Asia/Seoul');
}

$sessionTimezone = (string) ($_SESSION['timezone_preference'] ?? 'Asia/Seoul');
if (!in_array($sessionTimezone, timezone_identifiers_list(), true)) {
    $sessionTimezone = 'Asia/Seoul';
}
date_default_timezone_set($sessionTimezone);

$router = new Router();
$authController = new AuthController();
$calendarController = new CalendarController();
$calendarTagController = new CalendarTagController();
$executionEnergyController = new ExecutionEnergyController();
$goalController = new GoalController();
$memoController = new MemoController();
$planController = new PlanController();
$prototypeController = new PrototypeController();
$retrospectController = new RetrospectController();
$routineController = new RoutineController();
$searchController = new SearchController();
$todoController = new TodoController();

$router->aliasMiddleware('auth', AuthMiddleware::class);
$router->aliasMiddleware('guest', GuestMiddleware::class);

$router->get('/login', [$authController, 'showLogin'], ['guest']);
$router->post('/login', [$authController, 'login'], ['guest']);
$router->get('/register', [$authController, 'showRegister'], ['guest']);
$router->post('/register', [$authController, 'register'], ['guest']);
$router->get('/auth/google', [$authController, 'redirectToGoogle'], ['guest']);
$router->get('/auth/google/callback', [$authController, 'handleGoogleCallback'], ['guest']);
$router->get('/auth/google/callback.php', [$authController, 'handleGoogleCallback'], ['guest']);

$router->get('/dashboard', [$authController, 'dashboard'], ['auth']);
$router->get('/prototype/timeblock-planner/', [$prototypeController, 'timeblockPlanner'], ['auth']);
$router->get('/prototype/timeblock-planner/styles.css', [$prototypeController, 'timeblockPlannerStyles'], ['auth']);
$router->get('/prototype/timeblock-planner/app.js', [$prototypeController, 'timeblockPlannerScript'], ['auth']);
$router->get('/prototype/actual-timeblock-planner/', [$prototypeController, 'actualTimeblockPlanner'], ['auth']);
$router->get('/prototype/actual-timeblock-planner/styles.css', [$prototypeController, 'actualTimeblockPlannerStyles'], ['auth']);
$router->get('/prototype/actual-timeblock-planner/app.js', [$prototypeController, 'actualTimeblockPlannerScript'], ['auth']);
$router->get('/prototype/day-flow/', [$prototypeController, 'dayFlow'], ['auth']);
$router->get('/prototype/day-flow/styles.css', [$prototypeController, 'dayFlowStyles'], ['auth']);
$router->get('/prototype/day-flow/app.js', [$prototypeController, 'dayFlowScript'], ['auth']);
$router->get('/retrospect', [$retrospectController, 'index'], ['auth']);
$router->get('/retrospect/report', [$retrospectController, 'report'], ['auth']);
$router->get('/retrospect/report/preview', [$retrospectController, 'reportPreview'], ['auth']);
$router->post('/retrospect/draft', [$retrospectController, 'saveDraft'], ['auth']);
$router->post('/retrospect/publish', [$retrospectController, 'publish'], ['auth']);
$router->post('/retrospect/republish', [$retrospectController, 'republish'], ['auth']);
$router->post('/retrospect/settings', [$retrospectController, 'updateSettings'], ['auth']);
$router->get('/execution-energy', [$executionEnergyController, 'index'], ['auth']);
$router->get('/execution-energy/history', [$executionEnergyController, 'history'], ['auth']);
$router->get('/execution-energy/status', [$executionEnergyController, 'status'], ['auth']);
$router->post('/execution-energy/profile', [$executionEnergyController, 'updateProfile'], ['auth']);
$router->post('/execution-energy/morning', [$executionEnergyController, 'saveMorning'], ['auth']);
$router->get('/calendar', [$calendarController, 'index'], ['auth']);
$router->post('/calendar/event', [$calendarController, 'storeEvent'], ['auth']);
$router->post('/calendar/event/update', [$calendarController, 'updateEvent'], ['auth']);
$router->post('/calendar/event/delete', [$calendarController, 'deleteEvent'], ['auth']);
$router->post('/calendar/event/from-plan-block', [$calendarController, 'copyPlanBlockToActual'], ['auth']);
$router->post('/calendar/event/time-range', [$calendarController, 'resizeActualEvent'], ['auth']);
$router->post('/calendar/day-plan', [$calendarController, 'setDayPlan'], ['auth']);
$router->post('/calendar/plan-item', [$calendarController, 'storePlanItem'], ['auth']);
$router->post('/calendar/plan-item/update', [$calendarController, 'updatePlanItem'], ['auth']);
$router->post('/calendar/plan-item/delete', [$calendarController, 'deletePlanItem'], ['auth']);
$router->post('/calendar/plan-block', [$calendarController, 'storePlanBlock'], ['auth']);
$router->post('/calendar/plan-block/update', [$calendarController, 'updatePlanBlock'], ['auth']);
$router->post('/calendar/plan-block/delete', [$calendarController, 'deletePlanBlock'], ['auth']);
$router->get('/tags', [$calendarTagController, 'index'], ['auth']);
$router->post('/tags', [$calendarTagController, 'store'], ['auth']);
$router->post('/tags/update', [$calendarTagController, 'update'], ['auth']);
$router->post('/tags/delete', [$calendarTagController, 'delete'], ['auth']);
$router->post('/tags/system-toggle', [$calendarTagController, 'toggleSystem'], ['auth']);
$router->get('/routine', [$routineController, 'index'], ['auth']);
$router->get('/manage/routines', [$routineController, 'manage'], ['auth']);
$router->post('/routine', [$routineController, 'store'], ['auth']);
$router->post('/routine/quick', [$routineController, 'quickStore'], ['auth']);
$router->post('/routine/update', [$routineController, 'update'], ['auth']);
$router->post('/routine/extend', [$routineController, 'extend'], ['auth']);
$router->post('/routine/finish', [$routineController, 'finish'], ['auth']);
$router->post('/routine/delete', [$routineController, 'delete'], ['auth']);
$router->post('/routine/toggle', [$routineController, 'toggle'], ['auth']);
$router->get('/todo', [$todoController, 'index'], ['auth']);
$router->post('/todo', [$todoController, 'store'], ['auth']);
$router->post('/todo/update', [$todoController, 'update'], ['auth']);
$router->post('/todo/complete', [$todoController, 'complete'], ['auth']);
$router->get('/goal', [$goalController, 'index'], ['auth']);
$router->post('/goal', [$goalController, 'store'], ['auth']);
$router->post('/goal/update', [$goalController, 'update'], ['auth']);
$router->post('/goal/delete', [$goalController, 'delete'], ['auth']);
$router->post('/goal/move', [$goalController, 'move'], ['auth']);
$router->post('/goal/focus', [$goalController, 'focus'], ['auth']);
$router->get('/memo', [$memoController, 'index'], ['auth']);
$router->post('/memo', [$memoController, 'store'], ['auth']);
$router->post('/memo/update', [$memoController, 'update'], ['auth']);
$router->post('/memo/delete', [$memoController, 'delete'], ['auth']);
$router->post('/memo/restore', [$memoController, 'restore'], ['auth']);
$router->post('/memo/trash/empty', [$memoController, 'emptyTrash'], ['auth']);
$router->get('/search', [$searchController, 'index'], ['auth']);
$router->get('/plan', [$planController, 'index'], ['auth']);
$router->get('/plan/show', [$planController, 'show'], ['auth']);
$router->get('/plan/new', [$planController, 'create'], ['auth']);
$router->get('/plan/edit', [$planController, 'edit'], ['auth']);
$router->post('/plan', [$planController, 'store'], ['auth']);
$router->post('/plan/update', [$planController, 'update'], ['auth']);
$router->post('/plan/copy', [$planController, 'copy'], ['auth']);
$router->post('/plan/delete', [$planController, 'delete'], ['auth']);
$router->post('/plan/block-template', [$planController, 'storeBlockTemplate'], ['auth']);
$router->post('/plan/block-template/update', [$planController, 'updateBlockTemplate'], ['auth']);
$router->post('/plan/block-template/delete', [$planController, 'deleteBlockTemplate'], ['auth']);
$router->get('/settings', [$authController, 'settings'], ['auth']);
$router->post('/settings/theme', [$authController, 'updateTheme'], ['auth']);
$router->post('/settings/localization', [$authController, 'updateLocalization'], ['auth']);
$router->post('/settings/notifications', [$authController, 'updateNotifications'], ['auth']);
$router->get('/notification-guide', [$authController, 'notificationGuide'], ['auth']);
$router->get('/privacy-policy', [$authController, 'privacyPolicy']);
$router->get('/terms', [$authController, 'terms']);
$router->get('/contact', [$authController, 'contact'], ['auth']);
$router->get('/withdraw', [$authController, 'showWithdraw'], ['auth']);
$router->post('/withdraw', [$authController, 'withdraw'], ['auth']);
$router->post('/logout', [$authController, 'logout'], ['auth']);

$router->get('/', static function (): void {
    if (isset($_SESSION['user_id'])) {
        header('Location: /dashboard');
        exit;
    }

    header('Location: /login');
    exit;
});

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$router->dispatch($method, $uri);
