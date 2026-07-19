<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\MemoService;

final class MemoController
{
    private MemoService $memoService;

    public function __construct()
    {
        $this->memoService = new MemoService();
    }

    public function index(): void
    {
        $this->render('pages/memo/index', [
            'title' => 'Memo',
            'memoData' => $this->memoService->pageData(
                $this->userId(),
                $_GET['type'] ?? 'short',
                $_GET['trash'] ?? null,
                $_GET['q'] ?? ''
            ),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'editMemoId' => $_SESSION['memo_edit_id'] ?? null,
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/pages/memo.css'],
            'pageScripts' => ['/assets/js/pages/memo.js'],
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['memo_edit_id'], $_SESSION['flash_success']);
    }

    public function store(): void
    {
        $returnPath = $this->returnPath($_POST);
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($returnPath, ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->memoService->validate($_POST);
        if (!$validation['ok']) {
            $errors = str_starts_with($returnPath, '/calendar')
                ? ['general' => (string) ($validation['errors']['content'] ?? '메모 내용을 확인해주세요.')]
                : $validation['errors'];
            $this->redirectWithErrors($returnPath, $errors, str_starts_with($returnPath, '/calendar') ? [] : $_POST);
        }

        if ($this->memoService->create($this->userId(), $validation['data']['content']) === null) {
            $this->redirectWithErrors($returnPath, ['general' => '메모 저장 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '메모를 저장했습니다.';
        $this->redirect($returnPath);
    }

    public function update(): void
    {
        $validation = $this->validatePost();
        $memoId = (int) ($_POST['memo_id'] ?? 0);
        if (!$validation['ok'] || $memoId <= 0) {
            $_SESSION['memo_edit_id'] = $memoId > 0 ? $memoId : null;
            $this->redirectWithErrors('/memo', $validation['errors'] ?: ['general' => '수정할 메모를 찾을 수 없습니다.'], $_POST);
        }

        if (!$this->memoService->update($this->userId(), $memoId, $validation['data']['content'])) {
            $this->redirectWithErrors('/memo', ['general' => '메모 수정에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '메모를 수정했습니다.';
        $this->redirect('/memo?type=' . rawurlencode((string) ($_POST['type'] ?? 'short')));
    }

    public function delete(): void
    {
        $this->requireCsrf();
        $memoId = (int) ($_POST['memo_id'] ?? 0);
        if ($memoId <= 0 || !$this->memoService->delete($this->userId(), $memoId)) {
            $this->redirectWithErrors('/memo', ['general' => '메모 삭제에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '메모를 휴지통으로 이동했습니다.';
        $this->redirect('/memo?type=' . rawurlencode((string) ($_POST['type'] ?? 'short')));
    }

    public function restore(): void
    {
        $this->requireCsrf();
        $memoId = (int) ($_POST['memo_id'] ?? 0);
        if ($memoId <= 0 || !$this->memoService->restore($this->userId(), $memoId)) {
            $this->redirectWithErrors('/memo?trash=1', ['general' => '메모 복원에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '메모를 복원했습니다.';
        $this->redirect('/memo?trash=1');
    }

    /** @return array<string, mixed> */
    private function validatePost(): array
    {
        $this->requireCsrf();
        return $this->memoService->validate($_POST);
    }

    private function requireCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/memo', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }
    }

    /** @param array<string, mixed> $input */
    private function returnPath(array $input): string
    {
        if (($input['return_to'] ?? '') === 'calendar') {
            $date = (string) ($input['date'] ?? '');
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
                ? '/calendar?date=' . rawurlencode($date)
                : '/calendar';
        }

        return '/memo';
    }

    private function redirectWithErrors(string $path, array $errors, array $old = []): void
    {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $old;
        $this->redirect($path);
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
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
