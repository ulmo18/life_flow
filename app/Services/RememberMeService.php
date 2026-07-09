<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RememberToken;
use App\Models\User;
use App\Models\UserPreferenceRepository;
use DateInterval;
use DateTimeImmutable;

final class RememberMeService
{
    private const COOKIE_NAME = 'lf_remember';
    private const EXPIRY_DAYS = 30;

    private RememberToken $rememberTokenModel;
    private User $userModel;

    public function __construct()
    {
        $this->rememberTokenModel = new RememberToken();
        $this->userModel = new User();
    }

    public function issueToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validator);
        $expiresAt = (new DateTimeImmutable('now'))->add(new DateInterval('P' . self::EXPIRY_DAYS . 'D'));

        $this->rememberTokenModel->create(
            $userId,
            $selector,
            $tokenHash,
            $expiresAt->format('Y-m-d H:i:s')
        );

        $this->setCookie($selector . ':' . $validator, $expiresAt->getTimestamp());
    }

    public function clearTokenFromCookie(): void
    {
        $cookieValue = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($cookieValue) || strpos($cookieValue, ':') === false) {
            $this->expireCookie();
            return;
        }

        [$selector] = explode(':', $cookieValue, 2);
        if ($selector !== '') {
            $this->rememberTokenModel->deleteBySelector($selector);
        }

        $this->expireCookie();
    }

    public function autoLoginFromCookie(): bool
    {
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        $cookieValue = $_COOKIE[self::COOKIE_NAME] ?? null;
        if (!is_string($cookieValue) || strpos($cookieValue, ':') === false) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookieValue, 2);
        if ($selector === '' || $validator === '') {
            $this->expireCookie();
            return false;
        }

        $tokenRow = $this->rememberTokenModel->findBySelector($selector);
        if ($tokenRow === null) {
            $this->expireCookie();
            return false;
        }

        $isExpired = strtotime((string) $tokenRow['expires_at']) < time();
        $isValid = hash_equals((string) $tokenRow['token_hash'], hash('sha256', $validator));

        if ($isExpired || !$isValid) {
            $this->rememberTokenModel->deleteBySelector($selector);
            $this->expireCookie();
            return false;
        }

        $user = $this->userModel->findById((int) $tokenRow['user_id']);
        if ($user === null || (int) $user['is_active'] !== 1) {
            $this->rememberTokenModel->deleteBySelector($selector);
            $this->expireCookie();
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_nickname'] = (string) ($user['nickname'] ?? '');
        $_SESSION['user_email'] = (string) ($user['email'] ?? '');
        $preferences = (new UserPreferenceRepository())->get((int) $user['id']);
        $_SESSION['theme_preference'] = (string) ($preferences['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

        // Rotate token after successful auto-login to reduce replay window.
        $this->rememberTokenModel->deleteBySelector($selector);
        $this->issueToken((int) $user['id']);

        return true;
    }

    private function setCookie(string $value, int $expiresAt): void
    {
        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function expireCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
