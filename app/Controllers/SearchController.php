<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\SearchService;

final class SearchController
{
    private SearchService $service;

    public function __construct()
    {
        $this->service = new SearchService();
    }

    public function index(): void
    {
        $this->render('pages/search/index', [
            'title' => '검색',
            'search' => $this->service->search(
                $this->userId(), $_GET['q'] ?? '', $_GET['type'] ?? 'all', $_GET['from'] ?? '', $_GET['to'] ?? ''
            ),
            'pageStyles' => [$this->versionedAsset('/assets/css/pages/search.css')],
        ]);
    }

    private function userId(): int { return (int) ($_SESSION['user_id'] ?? 0); }
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
