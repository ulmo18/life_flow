<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Services\GoalService;
use App\Services\PlanService;

final class PlanController
{
    private ?PlanService $planService = null;
    private ?GoalService $goalService = null;

    public function index(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        $this->render('pages/plan/index', [
            'title' => 'Plan',
            'plans' => $this->planService()->getPlanList($this->userId()),
            'csrfToken' => Csrf::token(),
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'errors' => $_SESSION['errors'] ?? [],
            'pageStyles' => ['/assets/css/pages/plan.css'],
        ]);

        unset($_SESSION['flash_success'], $_SESSION['errors']);
    }

    public function show(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        $plan = $this->planService()->getPlanDetail($this->userId(), (int) ($_GET['id'] ?? 0));
        if ($plan === null) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $this->render('pages/plan/show', [
            'title' => (string) $plan['name'],
            'plan' => $plan,
            'csrfToken' => Csrf::token(),
            'pageStyles' => [
                '/assets/css/pages/calendar.css',
                '/assets/css/pages/plan.css',
            ],
        ]);
    }

    public function create(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        $this->renderEditor('계획 추가', '/plan', []);
    }

    public function edit(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        $plan = $this->planService()->getPlanDetail($this->userId(), (int) ($_GET['id'] ?? 0));
        if ($plan === null) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $this->renderEditor('계획 수정', '/plan/update', [
            'sourcePlan' => $plan,
            'old' => [
                'name' => $_SESSION['old']['name'] ?? $plan['name'],
                'blocks' => $_SESSION['old']['blocks'] ?? $plan['blocksJson'],
            ],
        ]);
    }

    public function store(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/plan/new', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $name = (string) ($_POST['name'] ?? '');
        $rawBlocks = $this->decodeBlocks((string) ($_POST['blocks'] ?? '[]'));
        $validation = $this->planService()->validateInput($this->userId(), $name, $rawBlocks);

        if (!$validation['ok']) {
            $this->redirectWithErrors('/plan/new', $validation['errors'], [
                'name' => $name,
                'blocks' => json_encode($validation['blocks'], JSON_UNESCAPED_UNICODE) ?: '[]',
            ]);
        }

        $groupId = $this->planService()->createPlanGroup($this->userId(), $name, $validation['blocks']);
        if ($groupId === null) {
            $this->redirectWithErrors('/plan/new', ['general' => '계획 저장 중 오류가 발생했습니다.'], [
                'name' => $name,
                'blocks' => json_encode($validation['blocks'], JSON_UNESCAPED_UNICODE) ?: '[]',
            ]);
        }

        $_SESSION['flash_success'] = '계획이 저장되었습니다.';
        $this->redirect('/plan/show?id=' . $groupId);
    }

    public function update(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        $sourceGroupId = (int) ($_POST['source_plan_group_id'] ?? 0);
        $editPath = '/plan/edit?id=' . $sourceGroupId;

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($editPath, ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $name = (string) ($_POST['name'] ?? '');
        $rawBlocks = $this->decodeBlocks((string) ($_POST['blocks'] ?? '[]'));
        $validation = $this->planService()->validateInput($this->userId(), $name, $rawBlocks);

        if ($sourceGroupId <= 0 || !$validation['ok']) {
            $this->redirectWithErrors($editPath, $validation['errors'] ?: ['general' => '수정할 계획을 찾을 수 없습니다.'], [
                'name' => $name,
                'blocks' => json_encode($validation['blocks'], JSON_UNESCAPED_UNICODE) ?: '[]',
            ]);
        }

        $groupId = $this->planService()->createEditedPlanGroup($this->userId(), $sourceGroupId, $name, $validation['blocks']);
        if ($groupId === null) {
            $this->redirectWithErrors($editPath, ['general' => '계획 수정 중 오류가 발생했습니다.'], [
                'name' => $name,
                'blocks' => json_encode($validation['blocks'], JSON_UNESCAPED_UNICODE) ?: '[]',
            ]);
        }

        $_SESSION['flash_success'] = '계획 수정본이 저장되었습니다.';
        $this->redirect('/plan/show?id=' . $groupId);
    }

    public function copy(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/plan', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $groupId = (int) ($_POST['plan_group_id'] ?? 0);
        $newGroupId = $groupId > 0 ? $this->planService()->copyPlanGroup($this->userId(), $groupId) : null;
        if ($newGroupId === null) {
            $this->redirectWithErrors('/plan', ['general' => '계획 복사에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '계획이 복사되었습니다.';
        $this->redirect('/plan');
    }

    public function delete(): void
    {
        if (!$this->isPlanDatabaseReady()) {
            $this->renderUnavailable();
            return;
        }

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors('/plan', ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $groupId = (int) ($_POST['plan_group_id'] ?? 0);
        if ($groupId <= 0 || !$this->planService()->deletePlanGroup($this->userId(), $groupId)) {
            $this->redirectWithErrors('/plan', ['general' => '계획 삭제에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '계획이 삭제되었습니다.';
        $this->redirect('/plan');
    }

    /** @return array<int, mixed> */
    private function decodeBlocks(string $blocksJson): array
    {
        $decodedBlocks = json_decode($blocksJson, true);

        return is_array($decodedBlocks) ? $decodedBlocks : [];
    }

    /** @param array<string, mixed> $extraData */
    private function renderEditor(string $heading, string $action, array $extraData): void
    {
        $this->render('pages/plan/create', array_merge([
            'title' => $heading,
            'heading' => $heading,
            'formAction' => $action,
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
            'goalOptions' => $this->goalService()->activeGoalOptions($this->userId()),
            'pageStyles' => [
                '/assets/css/pages/calendar.css',
                '/assets/css/pages/plan.css',
            ],
            'pageScripts' => ['/assets/js/pages/plan.js'],
        ], $extraData));

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    private function planService(): PlanService
    {
        if (!$this->planService instanceof PlanService) {
            $this->planService = new PlanService();
        }

        return $this->planService;
    }

    private function goalService(): GoalService
    {
        if (!$this->goalService instanceof GoalService) {
            $this->goalService = new GoalService();
        }

        return $this->goalService;
    }

    private function isPlanDatabaseReady(): bool
    {
        return Database::configuredDriver() === 'mysql';
    }

    private function renderUnavailable(): void
    {
        http_response_code(503);
        $this->render('pages/plan/unavailable', [
            'title' => 'Plan',
            'pageStyles' => ['/assets/css/pages/plan.css'],
        ]);
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
}
