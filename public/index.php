<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Seoul');

use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\CalendarTagController;
use App\Controllers\GoalController;
use App\Controllers\PlanController;
use App\Controllers\RetrospectController;
use App\Controllers\RoutineController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Services\RememberMeService;

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

$router = new Router();
$authController = new AuthController();
$calendarController = new CalendarController();
$calendarTagController = new CalendarTagController();
$goalController = new GoalController();
$planController = new PlanController();
$retrospectController = new RetrospectController();
$routineController = new RoutineController();

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
$router->get('/retrospect', [$retrospectController, 'index'], ['auth']);
$router->post('/retrospect/draft', [$retrospectController, 'saveDraft'], ['auth']);
$router->post('/retrospect/publish', [$retrospectController, 'publish'], ['auth']);
$router->post('/retrospect/republish', [$retrospectController, 'republish'], ['auth']);
$router->post('/retrospect/settings', [$retrospectController, 'updateSettings'], ['auth']);
$router->get('/calendar', [$calendarController, 'index'], ['auth']);
$router->post('/calendar/event', [$calendarController, 'storeEvent'], ['auth']);
$router->post('/calendar/event/update', [$calendarController, 'updateEvent'], ['auth']);
$router->post('/calendar/event/delete', [$calendarController, 'deleteEvent'], ['auth']);
$router->post('/calendar/day-plan', [$calendarController, 'setDayPlan'], ['auth']);
$router->get('/tags', [$calendarTagController, 'index'], ['auth']);
$router->post('/tags', [$calendarTagController, 'store'], ['auth']);
$router->post('/tags/update', [$calendarTagController, 'update'], ['auth']);
$router->post('/tags/delete', [$calendarTagController, 'delete'], ['auth']);
$router->get('/routine', [$routineController, 'index'], ['auth']);
$router->post('/routine', [$routineController, 'store'], ['auth']);
$router->post('/routine/update', [$routineController, 'update'], ['auth']);
$router->post('/routine/delete', [$routineController, 'delete'], ['auth']);
$router->post('/routine/toggle', [$routineController, 'toggle'], ['auth']);
$router->get('/goal', [$goalController, 'index'], ['auth']);
$router->post('/goal', [$goalController, 'store'], ['auth']);
$router->post('/goal/update', [$goalController, 'update'], ['auth']);
$router->post('/goal/delete', [$goalController, 'delete'], ['auth']);
$router->get('/plan', [$planController, 'index'], ['auth']);
$router->get('/plan/show', [$planController, 'show'], ['auth']);
$router->get('/plan/new', [$planController, 'create'], ['auth']);
$router->get('/plan/edit', [$planController, 'edit'], ['auth']);
$router->post('/plan', [$planController, 'store'], ['auth']);
$router->post('/plan/update', [$planController, 'update'], ['auth']);
$router->post('/plan/copy', [$planController, 'copy'], ['auth']);
$router->post('/plan/delete', [$planController, 'delete'], ['auth']);
$router->get('/settings', [$authController, 'settings'], ['auth']);
$router->post('/settings/theme', [$authController, 'updateTheme'], ['auth']);
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
