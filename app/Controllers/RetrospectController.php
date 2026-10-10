<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\RetrospectService;
use App\Services\ExecutionEnergyService;

final class RetrospectController
{
    private RetrospectService $retrospectService;
    private ExecutionEnergyService $executionEnergyService;

    public function __construct()
    {
        $this->retrospectService = new RetrospectService();
        $this->executionEnergyService = new ExecutionEnergyService();
    }

    public function index(): void
    {
        $date = is_string($_GET['date'] ?? null) ? (string) $_GET['date'] : null;
        $sort = is_string($_GET['sort'] ?? null) ? (string) $_GET['sort'] : null;
        $requestedView = (string) ($_GET['view'] ?? 'daily');
        $view = in_array($requestedView, ['daily', 'history', 'goals'], true) ? $requestedView : 'daily';
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $this->render('pages/retrospect/index', [
            'title' => t('nav.retrospect'),
            'retrospect' => $this->retrospectService->getPageData($this->userId(), $date, $sort),
            'executionEnergy' => $view === 'daily' ? $this->executionEnergyService->getDayData($this->userId(), $date) : [],
            'selectedView' => $view,
            'goalReview' => $view === 'goals' ? $this->retrospectService->getGoalReviewData($this->userId()) : ['goals' => []],
            'history' => $view === 'history' ? $this->retrospectService->getHistoryData($this->userId(), $page) : ['items' => []],
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/components/routine-state.css', $this->versionedAsset('/assets/css/pages/retrospect.css')],
            'pageScripts' => ['/assets/js/components/routine-state.js', $this->versionedAsset('/assets/js/pages/retrospect.js')],
        ]);

        unset($_SESSION['errors'], $_SESSION['flash_success']);
    }

    public function report(): void
    {
        $date = is_string($_GET['date'] ?? null) ? (string) $_GET['date'] : null;
        $report = $date === null || $date === ''
            ? $this->retrospectService->getLatestPublishedReportData($this->userId())
            : $this->retrospectService->getPublishedReportData($this->userId(), $date);
        if ($report === null) {
            $_SESSION['errors'] = ['general' => '이 날짜에 발행된 회고가 없습니다.'];
            $this->redirect($this->retrospectPath($date));
        }

        $this->render('pages/retrospect/report', [
            'title' => '발행 회고',
            'publishedReport' => $report,
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/retrospect-report.css')],
        ]);
        unset($_SESSION['flash_success']);
    }

    public function reportPreview(): void
    {
        $date = is_string($_GET['date'] ?? null) ? (string) $_GET['date'] : null;
        $report = $date === null || $date === ''
            ? $this->retrospectService->getLatestPublishedReportData($this->userId())
            : $this->retrospectService->getPublishedReportData($this->userId(), $date);

        if ($report === null) {
            $this->respondJson(['ok' => false, 'message' => '발행된 회고를 찾을 수 없습니다.'], 404);
        }

        ob_start();
        $embeddedReport = true;
        require dirname(__DIR__) . '/Views/pages/retrospect/report-content.php';
        $html = (string) ob_get_clean();

        $this->respondJson([
            'ok' => true,
            'date' => (string) $report['date'],
            'dateTitle' => (string) $report['dateTitle'],
            'html' => $html,
            'previous' => $report['adjacent']['previous'] ?? null,
            'next' => $report['adjacent']['next'] ?? null,
        ]);
    }

    public function saveDraft(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해주세요.'], 419);
            }
            $this->redirectWithErrors($this->retrospectPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = $this->retrospectService->normalizeDate(is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null);
        $validation = $this->retrospectService->validateTextInput($_POST);
        if (!$validation['ok']) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '각 회고 입력은 2000자 이내로 입력해주세요.'], 422);
            }
            $this->redirectWithErrors($this->retrospectPath($date), $validation['errors']);
        }

        if (!$this->retrospectService->saveDraft($this->userId(), $date, $validation['data'])) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '오늘과 어제의 회고만 저장할 수 있습니다.'], 422);
            }
            $this->redirectWithErrors($this->retrospectPath($date), ['general' => '오늘과 어제의 회고만 저장할 수 있습니다.']);
        }

        if ($this->isJsonRequest()) {
            $this->respondJson(['ok' => true, 'message' => '임시 저장했습니다.']);
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
        $this->redirect($this->reportPath($date));
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
        $this->redirect($this->reportPath($date));
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
        if ($sort === 'time') {
            $path .= '&sort=time';
        }

        return $path;
    }

    private function reportPath(string $date): string
    {
        return '/retrospect/report?date=' . rawurlencode($date);
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

    private function isJsonRequest(): bool
    {
        return stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /** @param array<string, mixed> $payload */
    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function versionedAsset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public' . $path;
        return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}
