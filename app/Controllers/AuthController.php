<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\GoogleOAuthService;
use App\Services\RememberMeService;
use Throwable;

final class AuthController
{
    private const RECENT_LOGIN_COOKIE = 'lf_recent_login_method';

    private RememberMeService $rememberMeService;
    private GoogleOAuthService $googleOAuthService;

    public function __construct()
    {
        $this->rememberMeService = new RememberMeService();
        $this->googleOAuthService = new GoogleOAuthService();
    }

    public function showRegister(): void
    {
        $googleRegistration = $this->getPendingGoogleRegistration();
        $old = $_SESSION['old'] ?? [];
        if ($googleRegistration !== null) {
            $old = array_merge([
                'email' => $googleRegistration['email'],
                'nickname' => $googleRegistration['nickname'],
            ], $old);
        }

        $this->render('auth/register', [
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $old,
            'csrfToken' => Csrf::token(),
            'googleConfigured' => $this->googleOAuthService->isConfigured(),
            'googleRegistration' => $googleRegistration,
        ]);

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function register(): void
    {
        $googleRegistration = $this->getPendingGoogleRegistration();
        $isGoogleRegistration = $googleRegistration !== null;
        $email = $isGoogleRegistration
            ? (string) $googleRegistration['email']
            : trim((string) ($_POST['email'] ?? ''));
        $password = $isGoogleRegistration ? '' : (string) ($_POST['password'] ?? '');
        $passwordConfirm = $isGoogleRegistration ? '' : (string) ($_POST['password_confirm'] ?? '');
        $nickname = trim((string) ($_POST['nickname'] ?? ''));
        $termsAgreed = isset($_POST['terms_agreed']) && $_POST['terms_agreed'] === '1';
        $privacyAgreed = isset($_POST['privacy_agreed']) && $_POST['privacy_agreed'] === '1';

        $errors = $this->validateRegisterInput(
            $email,
            $password,
            $passwordConfirm,
            $nickname,
            $termsAgreed,
            $isGoogleRegistration
        );

        $userModel = new User();
        if (!isset($errors['email']) && $userModel->existsByEmail($email)) {
            $errors['email'] = '이미 사용 중인 이메일입니다.';
        }

        if ($errors !== []) {
            $this->redirectWithErrors('/register', $errors, [
                'email' => $email,
                'nickname' => $nickname,
                'terms_agreed' => $termsAgreed,
                'privacy_agreed' => $privacyAgreed,
            ]);
        }

        if ($isGoogleRegistration) {
            $db = Database::connection();

            try {
                $db->beginTransaction();
                $userId = $userModel->createGoogleUser($email, $nickname, $privacyAgreed);
                $linked = $userId !== null && (new SocialAccount())->create(
                    $userId,
                    'google',
                    (string) $googleRegistration['provider_user_id'],
                    $email
                );

                if ($userId === null || !$linked) {
                    $db->rollBack();
                    $this->redirectWithErrors('/register', [
                        'general' => 'Google 회원가입 처리 중 오류가 발생했습니다.',
                    ], [
                        'email' => $email,
                        'nickname' => $nickname,
                        'terms_agreed' => $termsAgreed,
                        'privacy_agreed' => $privacyAgreed,
                    ]);
                }

                $db->commit();
            } catch (Throwable $exception) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                error_log('[google_oauth] registration failed: ' . $exception->getMessage());
                $this->redirectWithErrors('/register', [
                    'general' => 'Google 회원가입 처리 중 오류가 발생했습니다.',
                ], [
                    'email' => $email,
                    'nickname' => $nickname,
                    'terms_agreed' => $termsAgreed,
                    'privacy_agreed' => $privacyAgreed,
                ]);
            }

            unset($_SESSION['pending_google_registration']);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $this->setRecentLoginMethodCookie('google');
            $this->redirect('/dashboard');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $created = $userModel->create($email, $passwordHash, $nickname, $privacyAgreed);

        if (!$created) {
            $this->redirectWithErrors('/register', [
                'general' => '회원가입 처리 중 오류가 발생했습니다.',
            ], [
                'email' => $email,
                'nickname' => $nickname,
                'terms_agreed' => $termsAgreed,
                'privacy_agreed' => $privacyAgreed,
            ]);
        }

        // TODO: 회원가입 이후 이메일 인증 발송/검증 플로우를 연결합니다.
        $_SESSION['flash_success'] = '회원가입이 완료되었습니다. 로그인해 주세요.';
        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        $this->render('auth/login', [
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'csrfToken' => Csrf::token(),
            'googleConfigured' => $this->googleOAuthService->isConfigured(),
            'recentLoginMethod' => $this->getRecentLoginMethod(),
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash_success']);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

        $errors = $this->validateLoginInput($email, $password);

        $userModel = new User();
        $user = null;
        if ($errors === []) {
            $user = $userModel->findByEmail($email);
            $isValidUser = $user !== null
                && (int) $user['is_active'] === 1
                && password_verify($password, (string) $user['password_hash']);

            if (!$isValidUser) {
                $errors['general'] = '이메일 또는 비밀번호가 올바르지 않습니다.';
            }
        }

        if ($errors !== []) {
            $this->redirectWithErrors('/login', $errors, ['email' => $email]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        if ($rememberMe) {
            $this->rememberMeService->issueToken((int) $user['id']);
        } else {
            $this->rememberMeService->clearTokenFromCookie();
        }

        $this->setRecentLoginMethodCookie('password');
        $this->redirect('/dashboard');
    }

    public function redirectToGoogle(): void
    {
        if (!$this->googleOAuthService->isConfigured()) {
            error_log('[google_oauth] not configured: missing GOOGLE_* env values');
            $this->redirectWithErrors('/login', [
                'general' => 'Google 로그인이 아직 설정되지 않았습니다.',
            ]);
        }

        $state = $this->googleOAuthService->createState();
        unset($_SESSION['pending_google_registration']);
        $_SESSION['google_oauth_state'] = $state;

        $this->redirect($this->googleOAuthService->getAuthorizationUrl($state));
    }

    public function handleGoogleCallback(): void
    {
        $expectedState = (string) ($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        $incomingState = (string) ($_GET['state'] ?? '');
        if ($expectedState === '' || $incomingState === '' || !hash_equals($expectedState, $incomingState)) {
            error_log('[google_oauth] invalid state on callback');
            $this->redirectWithErrors('/login', ['general' => 'Google 로그인 state 검증에 실패했습니다.']);
        }

        if (!empty($_GET['error'])) {
            error_log('[google_oauth] callback error: ' . (string) $_GET['error']);
            $this->redirectWithErrors('/login', ['general' => 'Google 로그인 과정이 취소되었거나 실패했습니다.']);
        }

        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            error_log('[google_oauth] callback missing code');
            $this->redirectWithErrors('/login', ['general' => 'Google 로그인 코드가 없습니다.']);
        }

        $googleUser = $this->googleOAuthService->fetchUserByCode($code);
        if ($googleUser === null) {
            error_log('[google_oauth] failed to fetch google user by code');
            $this->redirectWithErrors('/login', ['general' => 'Google 사용자 정보를 가져오지 못했습니다.']);
        }

        if (($googleUser['provider_user_id'] ?? '') === '' || ($googleUser['email'] ?? '') === '') {
            $this->redirectWithErrors('/login', ['general' => 'Google 계정 이메일 정보를 확인할 수 없습니다.']);
        }

        if (($googleUser['email_verified'] ?? false) !== true) {
            $this->redirectWithErrors('/login', ['general' => '이메일 인증된 Google 계정만 사용할 수 있습니다.']);
        }

        $userModel = new User();
        $socialAccountModel = new SocialAccount();

        $social = $socialAccountModel->findByProviderAndProviderUserId('google', (string) $googleUser['provider_user_id']);
        $userId = null;

        if ($social !== null) {
            $userId = (int) $social['user_id'];
        } else {
            $existingUser = $userModel->findByEmail((string) $googleUser['email']);

            // MVP 채택 정책: 이메일이 같고 활성 계정이면 자동 연결.
            if ($existingUser !== null && (int) $existingUser['is_active'] === 1) {
                $userId = (int) $existingUser['id'];
            } elseif ($existingUser !== null) {
                $this->redirectWithErrors('/login', ['general' => '비활성 계정은 로그인할 수 없습니다.']);
            } else {
                $nickname = trim((string) ($googleUser['name'] ?? ''));
                if ($nickname === '') {
                    $nickname = 'google_user';
                }

                $_SESSION['pending_google_registration'] = [
                    'provider_user_id' => (string) $googleUser['provider_user_id'],
                    'email' => (string) $googleUser['email'],
                    'nickname' => mb_substr($nickname, 0, 50),
                    'expires_at' => time() + 600,
                ];
                $this->redirect('/register');
            }

            $linked = $socialAccountModel->create(
                (int) $userId,
                'google',
                (string) $googleUser['provider_user_id'],
                (string) $googleUser['email']
            );

            if (!$linked) {
                $this->redirectWithErrors('/login', ['general' => 'Google 계정 연동 중 오류가 발생했습니다.']);
            }
        }

        $user = $userModel->findById((int) $userId);
        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->redirectWithErrors('/login', ['general' => '비활성 계정은 로그인할 수 없습니다.']);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        $this->setRecentLoginMethodCookie('google');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/login', ['csrf' => '잘못된 요청입니다. 다시 시도해 주세요.']);
        }

        $this->rememberMeService->clearTokenFromCookie();
        $this->destroySession();
        $this->redirect('/login');
    }

    public function dashboard(): void
    {
        $this->render('pages/dashboard');
    }

    public function settings(): void
    {
        $this->render('pages/settings');
    }

    public function notificationGuide(): void
    {
        $this->render('pages/notification_guide');
    }

    public function privacyPolicy(): void
    {
        $this->render('pages/privacy_policy');
    }

    public function terms(): void
    {
        $this->render('pages/terms');
    }

    public function contact(): void
    {
        $appConfig = require __DIR__ . '/../../config/app.php';

        $this->render('pages/contact', [
            'supportEmail' => $appConfig['support_email'],
        ]);
    }

    public function showWithdraw(): void
    {
        $this->render('pages/withdraw', [
            'errors' => $_SESSION['errors'] ?? [],
            'csrfToken' => Csrf::token(),
        ]);

        unset($_SESSION['errors']);
    }

    public function withdraw(): void
    {
        $errors = [];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $errors['csrf'] = '잘못된 요청입니다. 다시 시도해 주세요.';
        }

        $confirmed = isset($_POST['confirm_withdraw']) && $_POST['confirm_withdraw'] === '1';
        if (!$confirmed) {
            $errors['confirm'] = '탈퇴 동의를 체크해 주세요.';
        }

        if ($errors !== []) {
            $this->redirectWithErrors('/withdraw', $errors);
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $userModel = new User();
        if ($userId <= 0 || !$userModel->deactivateById($userId)) {
            $this->redirectWithErrors('/withdraw', ['general' => '탈퇴 처리 중 오류가 발생했습니다.']);
        }

        $_SESSION['flash_success'] = '계정이 비활성화되었습니다. 문의가 필요하면 지원 이메일로 연락해 주세요.';

        $this->rememberMeService->clearTokenFromCookie();
        $this->destroySession();
        $this->redirect('/login');
    }

    private function validateRegisterInput(
        string $email,
        string $password,
        string $passwordConfirm,
        string $nickname,
        bool $termsAgreed,
        bool $isGoogleRegistration
    ): array {
        $errors = [];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $errors['csrf'] = '잘못된 요청입니다. 다시 시도해 주세요.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '유효한 이메일을 입력해 주세요.';
        }

        if (!$isGoogleRegistration && mb_strlen($password) < 8) {
            $errors['password'] = '비밀번호는 최소 8자 이상이어야 합니다.';
        }

        if (!$isGoogleRegistration && $password !== $passwordConfirm) {
            $errors['password_confirm'] = '비밀번호 확인이 일치하지 않습니다.';
        }

        if ($nickname === '' || mb_strlen($nickname) < 2 || mb_strlen($nickname) > 50) {
            $errors['nickname'] = '닉네임은 2~50자로 입력해 주세요.';
        }

        if (!$termsAgreed) {
            $errors['terms_agreed'] = '회원가입 약관에 동의해 주세요.';
        }

        return $errors;
    }

    private function getPendingGoogleRegistration(): ?array
    {
        $registration = $_SESSION['pending_google_registration'] ?? null;
        if (!is_array($registration)
            || ($registration['provider_user_id'] ?? '') === ''
            || ($registration['email'] ?? '') === ''
            || (int) ($registration['expires_at'] ?? 0) < time()
        ) {
            unset($_SESSION['pending_google_registration']);
            return null;
        }

        return $registration;
    }

    private function getRecentLoginMethod(): ?string
    {
        $method = $_COOKIE[self::RECENT_LOGIN_COOKIE] ?? null;
        if (!is_string($method)) {
            return null;
        }

        return in_array($method, ['google', 'password'], true) ? $method : null;
    }

    private function setRecentLoginMethodCookie(string $method): void
    {
        if (!in_array($method, ['google', 'password'], true)) {
            return;
        }

        setcookie(self::RECENT_LOGIN_COOKIE, $method, [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function validateLoginInput(string $email, string $password): array
    {
        $errors = [];

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $errors['csrf'] = '잘못된 요청입니다. 다시 시도해 주세요.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '유효한 이메일을 입력해 주세요.';
        }

        if ($password === '') {
            $errors['password'] = '비밀번호를 입력해 주세요.';
        }

        return $errors;
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }

    private function redirectWithErrors(string $path, array $errors, array $old = []): void
    {
        $_SESSION['errors'] = $errors;
        if ($old !== []) {
            $_SESSION['old'] = $old;
        }

        $this->redirect($path);
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    private function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }
}
