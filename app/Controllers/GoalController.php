<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\GoalService;
use App\Services\NotificationService;

final class GoalController
{
    private GoalService $goalService;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->goalService = new GoalService();
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
        $selectedMode = $this->goalService->normalizeMapMode($_GET['mode'] ?? 'focus');
        $goals = $selectedMode === 'focus'
            ? $this->goalService->getFocusedGoalList($this->userId())
            : $this->goalService->getGoalList($this->userId(), null);

        $this->render('pages/goal/index', [
            'title' => t('nav.goal'),
            'goals' => $goals,
            'goalTree' => $this->goalService->buildGoalTree($goals),
            'goalTypes' => $this->goalService->goalTypeOptions(),
            'statusOptions' => $this->goalService->statusOptions(),
            'selectedMode' => $selectedMode,
            'parentOptions' => $this->goalService->parentOptions($this->userId()),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'notificationSyncPayload' => !empty($_SESSION['notification_sync_goal'])
                ? $this->notificationService->buildGoalSyncPayload($this->userId())
                : [],
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/goal.css')],
            'pageScripts' => [$this->versionedAsset('/assets/js/pages/goal.js')],
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash_success'], $_SESSION['notification_sync_goal']);
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해주세요.'], 419);
            }
            $this->redirectWithErrors('/goal', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->goalService->validateInput($_POST, $this->userId());
        if (!$validation['ok']) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'errors' => $validation['errors']], 422);
            }
            $this->redirectWithErrors('/goal', $validation['errors'], $_POST);
        }

        $goalId = $this->goalService->createGoal($this->userId(), $validation['data']);
        if ($goalId === null) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => '목표 저장 중 오류가 발생했습니다.'], 500);
            }
            $this->redirectWithErrors('/goal', ['general' => '목표 저장 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '목표가 추가되었습니다.';
        $_SESSION['notification_sync_goal'] = true;
        if ($this->wantsJson()) {
            $this->json(['ok' => true, 'goalId' => $goalId]);
        }
        $this->redirect((string) ($_POST['mode'] ?? '') === 'all' ? '/goal?mode=all' : '/goal');
    }

    public function move(): void
    {
        $goalId = filter_var($_POST['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $parentGoalId = filter_var($_POST['parent_goal_id'] ?? null, FILTER_VALIDATE_INT);
        $beforeGoalId = filter_var($_POST['before_goal_id'] ?? null, FILTER_VALIDATE_INT);
        $parentGoalId = $parentGoalId === false || $parentGoalId <= 0 ? null : (int) $parentGoalId;
        $beforeGoalId = $beforeGoalId === false || $beforeGoalId <= 0 ? null : (int) $beforeGoalId;

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)
            || $goalId === false
            || $goalId <= 0
            || !$this->goalService->moveGoal($this->userId(), (int) $goalId, $parentGoalId, $beforeGoalId)) {
            $this->json(['ok' => false, 'message' => '목표 위치를 변경하지 못했습니다.'], 422);
        }

        $this->json(['ok' => true]);
    }

    public function focus(): void
    {
        $goalId = filter_var($_POST['goal_id'] ?? null, FILTER_VALIDATE_INT);
        $focused = (string) ($_POST['focused'] ?? '') === '1';
        if (!Csrf::verify($_POST['_csrf_token'] ?? null) || $goalId === false || $goalId <= 0) {
            $this->redirectWithErrors('/goal?mode=all', ['general' => '집중 목표 요청을 다시 확인해주세요.']);
        }
        $result = $this->goalService->setFocused($this->userId(), (int) $goalId, $focused);
        if ($result === 'limit') {
            $this->redirectWithErrors('/goal?mode=all', ['general' => '집중 목표는 최대 5개까지 선택할 수 있습니다.']);
        }
        if ($result !== 'updated') {
            $this->redirectWithErrors('/goal?mode=all', ['general' => '진행 중인 목표만 집중 목표로 선택할 수 있습니다.']);
        }
        $_SESSION['flash_success'] = $focused ? '지금 집중할 목표에 추가했습니다.' : '집중 목표에서 제외했습니다.';
        $this->redirect($focused ? '/goal' : '/goal?mode=all');
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
        $_SESSION['notification_sync_goal'] = true;
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
        $_SESSION['notification_sync_goal'] = true;
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

    private function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    private function versionedAsset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public' . $path;
        return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
