<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\ExecutionEnergyService;

final class ExecutionEnergyController
{
    private ExecutionEnergyService $energyService;

    public function __construct()
    {
        $this->energyService = new ExecutionEnergyService();
    }

    public function index(): void
    {
        $this->render('pages/execution-energy/index', [
            'title' => '실행력 관리',
            'energy' => $this->energyService->getManagementData($this->userId()),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/execution-energy.css')],
        ]);
        unset($_SESSION['errors'], $_SESSION['flash_success']);
    }

    public function status(): void
    {
        $data = $this->energyService->getDayData($this->userId(), date('Y-m-d'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'date' => date('Y-m-d'),
            'currentCapacity' => $data['currentCapacity'],
            'startCapacity' => $data['startCapacity'],
            'percent' => $data['percent'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function history(): void
    {
        $requestedDate = is_string($_GET['date'] ?? null) ? (string) $_GET['date'] : '';
        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $requestedDate);
        $date = $parsedDate instanceof \DateTimeImmutable && $parsedDate->format('Y-m-d') === $requestedDate
            ? $requestedDate
            : date('Y-m-d');
        $this->render('pages/execution-energy/history', [
            'title' => '실행력 사용 이력',
            'energy' => $this->energyService->getDayData($this->userId(), $date),
            'date' => $date,
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/execution-energy-history.css')],
        ]);
    }

    public function updateProfile(): void
    {
        $validation = $this->energyService->validateProfileInput($_POST);
        if (!Csrf::verify($_POST['_csrf_token'] ?? null) || !$validation['ok']) {
            $this->redirectWithErrors('/execution-energy', $validation['errors'] ?: ['general' => '요청을 다시 확인해주세요.']);
        }
        $this->energyService->getDayData($this->userId(), date('Y-m-d'));
        $this->energyService->saveProfile($this->userId(), $validation['data']);
        $_SESSION['flash_success'] = '기본 실행력은 다음 날부터 적용됩니다.';
        $this->redirect('/execution-energy');
    }

    public function saveMorning(): void
    {
        $date = is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : date('Y-m-d');
        $validation = $this->energyService->validateMorningInput($_POST);
        if (!Csrf::verify($_POST['_csrf_token'] ?? null) || $date !== date('Y-m-d') || !$validation['ok']) {
            $this->redirectWithErrors('/calendar?date=' . rawurlencode($date), $validation['errors'] ?: ['general' => '컨디션을 다시 선택해주세요.']);
        }
        $this->energyService->saveMorningCheck($this->userId(), $date, $validation['data']);
        $_SESSION['flash_success'] = '아침 컨디션을 실행력에 반영했습니다.';
        $this->redirect('/calendar?date=' . rawurlencode($date));
    }

    /** @param array<string, string> $errors */
    private function redirectWithErrors(string $path, array $errors): void
    {
        $_SESSION['errors'] = $errors;
        $this->redirect($path);
    }

    private function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function versionedAsset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public' . $path;
        return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
    }

    private function render(string $viewPath, array $data): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}
