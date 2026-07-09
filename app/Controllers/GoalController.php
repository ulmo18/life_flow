<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\GoalService;

final class GoalController
{
    private GoalService $goalService;

    public function __construct()
    {
        $this->goalService = new GoalService();
    }

    public function index(): void
    {
        $this->render('pages/goal/index', [
            'title' => 'Goal',
            'goals' => $this->goalService->getGoalList($this->userId()),
            'goalTypes' => $this->goalService->goalTypeOptions(),
            'statusOptions' => $this->goalService->statusOptions(),
            'parentOptions' => $this->goalService->parentOptions($this->userId()),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/pages/goal.css'],
            'pageScripts' => ['/assets/js/pages/goal.js'],
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash_success']);
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/goal', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->goalService->validateInput($_POST, $this->userId());
        if (!$validation['ok']) {
            $this->redirectWithErrors('/goal', $validation['errors'], $_POST);
        }

        if ($this->goalService->createGoal($this->userId(), $validation['data']) === null) {
            $this->redirectWithErrors('/goal', ['general' => '목표 저장 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '목표가 추가되었습니다.';
        $this->redirect('/goal');
    }

    public function update(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/goal', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $goalId = (int) ($_POST['goal_id'] ?? 0);
        $validation = $this->goalService->validateInput($_POST, $this->userId(), $goalId > 0 ? $goalId : null);
        if ($goalId <= 0 || !$validation['ok']) {
            $this->redirectWithErrors('/goal', $validation['errors'] ?: ['general' => '수정할 목표를 찾을 수 없습니다.'], $_POST);
        }

        if (!$this->goalService->updateGoal($this->userId(), $goalId, $validation['data'])) {
            $this->redirectWithErrors('/goal', ['general' => '목표 수정 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '목표가 수정되었습니다.';
        $this->redirect('/goal');
    }

    public function delete(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/goal', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $goalId = (int) ($_POST['goal_id'] ?? 0);
        if ($goalId <= 0 || !$this->goalService->deleteGoal($this->userId(), $goalId)) {
            $this->redirectWithErrors('/goal', ['general' => '목표 삭제에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '목표가 삭제되었습니다.';
        $this->redirect('/goal');
    }

    /** @param array<string, string> $errors */
    private function redirectWithErrors(string $path, array $errors, array $old = []): void
    {
        $_SESSION['errors'] = $errors;
        if ($old !== []) {
            $_SESSION['old'] = $old;
        }

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
