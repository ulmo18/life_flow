<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\PlanController;
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
$planController = new PlanController();

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
$router->get('/retrospect', [$authController, 'retrospect'], ['auth']);
$router->get('/calendar', [$calendarController, 'index'], ['auth']);
$router->get('/routine', [$authController, 'routine'], ['auth']);
$router->get('/plan', [$planController, 'index'], ['auth']);
$router->get('/plan/show', [$planController, 'show'], ['auth']);
$router->get('/plan/new', [$planController, 'create'], ['auth']);
$router->get('/plan/edit', [$planController, 'edit'], ['auth']);
$router->post('/plan', [$planController, 'store'], ['auth']);
$router->post('/plan/update', [$planController, 'update'], ['auth']);
$router->post('/plan/copy', [$planController, 'copy'], ['auth']);
$router->post('/plan/delete', [$planController, 'delete'], ['auth']);
$router->get('/settings', [$authController, 'settings'], ['auth']);
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
