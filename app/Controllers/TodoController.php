<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\TodoService;

final class TodoController
{
    private TodoService $todoService;

    public function __construct()
    {
        $this->todoService = new TodoService();
    }

    public function index(): void
    {
        $todo = $this->todoService->getTodayData($this->userId());
        $this->render('pages/todo/index', [
            'title' => t('todo.title'),
            'todo' => $todo,
            'csrfToken' => Csrf::token(),
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'errors' => $_SESSION['errors'] ?? [],
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/todo.css')],
            'pageScripts' => [$this->versionedAsset('/assets/js/pages/todo.js')],
        ]);
        unset($_SESSION['flash_success'], $_SESSION['errors']);
    }

    public function complete(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->respond(['ok' => false, 'message' => t('todo.invalid')], 419);
        }

        $itemId = filter_var($_POST['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        $result = $itemId === false || $itemId <= 0
            ? ['ok' => false, 'status' => 422, 'message' => t('todo.invalid')]
            : $this->todoService->complete(
                $this->userId(),
                (int) $itemId,
                $_POST['start_index'] ?? null,
                $_POST['end_index'] ?? null
            );

        if ($this->isJsonRequest()) {
            $this->respond($result, (int) ($result['status'] ?? ($result['ok'] ? 200 : 422)));
        }

        if (!empty($result['ok'])) {
            $_SESSION['flash_success'] = (string) $result['message'];
        } else {
            $_SESSION['errors'] = ['general' => (string) $result['message']];
        }
        header('Location: /todo');
        exit;
    }

    public function store(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['errors'] = ['general' => t('todo.invalid')];
            header('Location: /todo');
            exit;
        }
        $result = $this->todoService->createToday($this->userId(), $_POST['title'] ?? '');
        if ($result['ok']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['errors'] = ['general' => $result['message']];
        }
        header('Location: /todo');
        exit;
    }

    public function update(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $_SESSION['errors'] = ['general' => t('todo.invalid')];
            header('Location: /todo');
            exit;
        }

        $result = $this->todoService->updateToday($this->userId(), $_POST);
        if ($result['ok']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['errors'] = ['general' => $result['message']];
        }

        header('Location: /todo');
        exit;
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function isJsonRequest(): bool
    {
        return stripos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /** @param array<string, mixed> $payload */
    private function respond(array $payload, int $status): void
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

    private function versionedAsset(string $publicPath): string
    {
        $filePath = dirname(__DIR__, 2) . '/public' . $publicPath;
        $version = is_file($filePath) ? filemtime($filePath) : false;

        return $version === false ? $publicPath : $publicPath . '?v=' . $version;
    }
}
