<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\RetrospectService;

final class RetrospectController
{
    private RetrospectService $retrospectService;

    public function __construct()
    {
        $this->retrospectService = new RetrospectService();
    }

    public function index(): void
    {
        $date = is_string($_GET['date'] ?? null) ? (string) $_GET['date'] : null;
        $sort = is_string($_GET['sort'] ?? null) ? (string) $_GET['sort'] : null;

        $this->render('pages/retrospect/index', [
            'title' => 'Retrospect',
            'retrospect' => $this->retrospectService->getPageData($this->userId(), $date, $sort),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/pages/retrospect.css'],
            'pageScripts' => ['/assets/js/pages/retrospect.js'],
        ]);

        unset($_SESSION['errors'], $_SESSION['flash_success']);
    }

    public function saveDraft(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->retrospectPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = $this->retrospectService->normalizeDate(is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null);
        $validation = $this->retrospectService->validateTextInput($_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->retrospectPath($date), $validation['errors']);
        }

        if (!$this->retrospectService->saveDraft($this->userId(), $date, $validation['data'])) {
            $this->redirectWithErrors($this->retrospectPath($date), ['general' => '오늘 회고만 메모로 저장할 수 있습니다.']);
        }

        $_SESSION['flash_success'] = '오늘의 회고 메모를 저장했습니다.';
        $this->redirect($this->retrospectPath($date));
    }

    public function publish(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->retrospectPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = $this->retrospectService->normalizeDate(is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null);
        $validation = $this->retrospectService->validateTextInput($_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->retrospectPath($date), $validation['errors']);
        }

        if (!$this->retrospectService->publish($this->userId(), $date, $validation['data'])) {
            $this->redirectWithErrors($this->retrospectPath($date), ['general' => '회고 발행에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '오늘의 회고를 발행했습니다.';
        $this->redirect($this->retrospectPath($date));
    }

    public function republish(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->retrospectPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = $this->retrospectService->normalizeDate(is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null);
        if (!$this->retrospectService->republish($this->userId(), $date)) {
            $this->redirectWithErrors($this->retrospectPath($date), ['general' => '회고 재발행에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '회고를 최신 일정으로 재발행했습니다.';
        $this->redirect($this->retrospectPath($date));
    }

    public function updateSettings(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->retrospectPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->retrospectService->validateSettingsInput($_POST);
        $date = $this->retrospectService->normalizeDate(is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->retrospectPath($date), $validation['errors']);
        }

        $this->retrospectService->updateSettings(
            $this->userId(),
            (bool) $validation['data']['enabled'],
            (string) $validation['data']['time']
        );

        $_SESSION['flash_success'] = '자동 회고 발행 시간을 저장했습니다.';
        $this->redirect($this->retrospectPath($date));
    }

    /** @param array<string, string> $errors */
    private function redirectWithErrors(string $path, array $errors): void
    {
        $_SESSION['errors'] = $errors;
        $this->redirect($path);
    }

    private function retrospectPath(mixed $date = null, ?string $sort = null): string
    {
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
        $path = '/retrospect?date=' . rawurlencode($date);
        if ($sort === 'tag') {
            $path .= '&sort=tag';
        }

        return $path;
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}
