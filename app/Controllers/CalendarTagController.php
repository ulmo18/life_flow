<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\CalendarTagService;

final class CalendarTagController
{
    private CalendarTagService $tagService;

    public function __construct()
    {
        $this->tagService = new CalendarTagService();
    }

    public function index(): void
    {
        $this->render('pages/tags/index', [
            'title' => 'Tags',
            'tagData' => $this->tagService->getTagPageData($this->userId()),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/pages/tags.css'],
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash_success']);
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해주세요.'], 419);
            }
            $this->redirectWithErrors(['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->tagService->validateInput($this->userId(), $_POST);
        if (!$validation['ok']) {
            if ($this->isJsonRequest()) {
                $message = reset($validation['errors']) ?: '태그 정보를 다시 확인해주세요.';
                $this->respondJson([
                    'ok' => false,
                    'message' => $message,
                    'errors' => $validation['errors'],
                ], 422);
            }
            $this->redirectWithErrors($validation['errors'], $_POST);
        }

        $tagId = $this->tagService->createTag($this->userId(), $validation['data']);
        if ($tagId === null) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '태그 저장 중 오류가 발생했습니다.'], 422);
            }
            $this->redirectWithErrors(['general' => '태그 저장 중 오류가 발생했습니다.'], $_POST);
        }

        $message = '태그가 추가되었습니다.';
        if ($this->isJsonRequest()) {
            $this->respondJson([
                'ok' => true,
                'message' => $message,
                'tag' => [
                    'id' => $tagId,
                    'name' => (string) $validation['data']['name'],
                    'colorHex' => (string) $validation['data']['colorHex'],
                ],
            ]);
        }

        $_SESSION['flash_success'] = $message;
        $this->redirect('/tags');
    }

    public function update(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors(['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $tagId = (int) ($_POST['tag_id'] ?? 0);
        $validation = $this->tagService->validateInput($this->userId(), $_POST, $tagId);
        if ($tagId <= 0 || !$validation['ok']) {
            $this->redirectWithErrors($validation['errors'] ?: ['general' => '수정할 태그를 찾을 수 없습니다.'], $_POST);
        }

        if (!$this->tagService->updateTag($this->userId(), $tagId, $validation['data'])) {
            $this->redirectWithErrors(['general' => '태그 수정 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '태그가 수정되었습니다.';
        $this->redirect('/tags');
    }

    public function delete(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors(['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $tagId = (int) ($_POST['tag_id'] ?? 0);
        if ($tagId <= 0 || !$this->tagService->deleteTag($this->userId(), $tagId)) {
            $this->redirectWithErrors(['general' => '태그 삭제 중 오류가 발생했습니다.']);
        }

        $_SESSION['flash_success'] = '태그가 삭제되었습니다.';
        $this->redirect('/tags');
    }

    public function toggleSystem(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors(['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $tagId = (int) ($_POST['tag_id'] ?? 0);
        $enabled = (string) ($_POST['enabled'] ?? '0') === '1';
        if ($tagId <= 0 || !$this->tagService->setSystemTagEnabled($this->userId(), $tagId, $enabled)) {
            $this->redirectWithErrors(['general' => '기본 태그 상태를 변경하지 못했습니다.']);
        }

        $_SESSION['flash_success'] = $enabled
            ? '기본 태그를 다시 표시합니다.'
            : '기본 태그를 일정 등록 목록에서 숨겼습니다.';
        $this->redirect('/tags');
    }

    /** @param array<string, string> $errors */
    private function redirectWithErrors(array $errors, array $old = []): void
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        $this->redirect('/tags');
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function isJsonRequest(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest';
    }

    /** @param array<string, mixed> $payload */
    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
