<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\GoalService;
use App\Services\NotificationService;
use App\Services\RoutineService;

final class RoutineController
{
    private RoutineService $routineService;
    private GoalService $goalService;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->routineService = new RoutineService();
        $this->goalService = new GoalService();
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
        $routineData = $this->routineService->getRoutinePageData($this->userId());
        $this->render('pages/routine/index', [
            'title' => 'Routine',
            'routines' => $routineData['routines'],
            'routineSummary' => $routineData['summary'],
            'durationOptions' => $this->routineService->durationOptions(),
            'defaultDurationDays' => $this->routineService->defaultDurationDays(),
            'defaultReminderTime' => $this->routineService->defaultReminderTime(),
            'goalOptions' => $this->goalService->activeGoalOptions($this->userId()),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'notificationSyncPayload' => !empty($_SESSION['notification_sync_routine'])
                ? $this->notificationService->buildRoutineSyncPayload($this->userId())
                : [],
            'pageStyles' => ['/assets/css/components/routine-state.css', '/assets/css/pages/routine.css'],
            'pageScripts' => ['/assets/js/components/routine-state.js', '/assets/js/pages/routine.js'],
        ]);

        unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['flash_success'], $_SESSION['notification_sync_routine']);
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/routine', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->routineService->validateInput($_POST, $this->userId());
        if (!$validation['ok']) {
            $this->redirectWithErrors('/routine', $validation['errors'], $_POST);
        }

        if ($this->routineService->createRoutine($this->userId(), $validation['data']) === null) {
            $this->redirectWithErrors('/routine', ['general' => '루틴 저장 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '루틴을 추가했습니다.';
        $_SESSION['notification_sync_routine'] = true;
        $this->redirect('/routine');
    }

    public function update(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/routine', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $routineId = (int) ($_POST['routine_id'] ?? 0);
        $validation = $this->routineService->validateInput($_POST, $this->userId(), true);
        if ($routineId <= 0 || !$validation['ok']) {
            $this->redirectWithErrors('/routine', $validation['errors'] ?: ['general' => '수정할 루틴을 찾을 수 없습니다.'], $_POST);
        }

        if (!$this->routineService->updateRoutine($this->userId(), $routineId, $validation['data'])) {
            $this->redirectWithErrors('/routine', ['general' => '루틴 수정 중 오류가 발생했습니다.'], $_POST);
        }

        $_SESSION['flash_success'] = '루틴을 수정했습니다.';
        $_SESSION['notification_sync_routine'] = true;
        $this->redirect('/routine');
    }

    public function delete(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/routine', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $routineId = (int) ($_POST['routine_id'] ?? 0);
        if ($routineId <= 0 || !$this->routineService->deleteRoutine($this->userId(), $routineId)) {
            $this->redirectWithErrors('/routine', ['general' => '루틴 삭제에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '루틴을 삭제했습니다.';
        $_SESSION['notification_sync_routine'] = true;
        $this->redirect('/routine');
    }

    public function extend(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/routine', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $routineId = (int) ($_POST['routine_id'] ?? 0);
        $extensionDays = (int) ($_POST['extension_days'] ?? 0);
        if ($routineId <= 0 || !$this->routineService->extendRoutine($this->userId(), $routineId, $extensionDays)) {
            $this->redirectWithErrors('/routine', ['general' => '루틴 기간을 연장할 수 없습니다.']);
        }

        $_SESSION['flash_success'] = '루틴 기간을 연장했습니다.';
        $_SESSION['notification_sync_routine'] = true;
        $this->redirect('/routine');
    }

    public function finish(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/routine', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $routineId = (int) ($_POST['routine_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($routineId <= 0 || !$this->routineService->finishRoutine($this->userId(), $routineId, $status)) {
            $this->redirectWithErrors('/routine', ['general' => '루틴을 마무리할 수 없습니다.']);
        }

        $_SESSION['flash_success'] = $status === 'completed'
            ? '루틴을 완료로 마무리했습니다.'
            : '루틴을 중단했습니다.';
        $_SESSION['notification_sync_routine'] = true;
        $this->redirect('/routine');
    }

    public function toggle(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해주세요.'], 419);
            }

            $this->redirectWithErrors($this->redirectBackPath(), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $routineId = (int) ($_POST['routine_id'] ?? 0);
        $date = $this->normalizeDate((string) ($_POST['date'] ?? date('Y-m-d')));
        $nextState = $routineId > 0
            ? $this->routineService->cycleRoutineState($this->userId(), $routineId, $date)
            : null;

        if ($nextState === null) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '루틴 실행 상태 변경에 실패했습니다.'], 422);
            }

            $this->redirectWithErrors($this->redirectBackPath($date), ['general' => '루틴 실행 상태 변경에 실패했습니다.']);
        }

        if ($this->isJsonRequest()) {
            $isRoutinePageToggle = (string) ($_POST['return_to'] ?? '') === '';
            $this->respondJson([
                'ok' => true,
                'message' => $this->stateMessage($nextState),
                'routineId' => $routineId,
                'date' => $date,
                'state' => $nextState,
                'routine' => $this->routineService->getRoutineSummary($this->userId(), $routineId, $isRoutinePageToggle),
                'pageSummary' => $isRoutinePageToggle
                    ? $this->routineService->getRoutinePageSummary($this->userId())
                    : null,
            ]);
        }

        $_SESSION['flash_success'] = $this->stateMessage($nextState);
        $this->redirect($this->redirectBackPath($date));
    }

    private function stateMessage(string $state): string
    {
        if ($state === 'O') {
            return '루틴을 완료로 표시했습니다.';
        }

        if ($state === 'X') {
            return '루틴을 미완료로 표시했습니다.';
        }

        return '루틴 상태를 미진행으로 되돌렸습니다.';
    }

    private function redirectBackPath(?string $date = null): string
    {
        $returnTo = (string) ($_POST['return_to'] ?? '');
        if ($returnTo === 'calendar') {
            $date = $this->normalizeDate($date ?? (string) ($_POST['date'] ?? date('Y-m-d')));
            return '/calendar?date=' . rawurlencode($date);
        }

        if ($returnTo === 'retrospect') {
            $date = $this->normalizeDate($date ?? (string) ($_POST['date'] ?? date('Y-m-d')));
            return '/retrospect?date=' . rawurlencode($date);
        }

        if ($returnTo === 'retrospect_goals') {
            return '/retrospect?view=goals';
        }

        return '/routine';
    }

    private function normalizeDate(string $date): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
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
