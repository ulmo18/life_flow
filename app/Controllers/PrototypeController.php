<?php

declare(strict_types=1);

namespace App\Controllers;

final class PrototypeController
{
    public function timeblockPlanner(): void
    {
        $this->sendAsset('index.html', 'text/html; charset=utf-8');
    }

    public function timeblockPlannerStyles(): void
    {
        $this->sendAsset('styles.css', 'text/css; charset=utf-8');
    }

    public function timeblockPlannerScript(): void
    {
        $this->sendAsset('app.js', 'application/javascript; charset=utf-8');
    }

    public function actualTimeblockPlanner(): void
    {
        $this->sendActualAsset('index.html', 'text/html; charset=utf-8');
    }

    public function actualTimeblockPlannerStyles(): void
    {
        $this->sendActualAsset('styles.css', 'text/css; charset=utf-8');
    }

    public function actualTimeblockPlannerScript(): void
    {
        $this->sendActualAsset('app.js', 'application/javascript; charset=utf-8');
    }

    public function dayFlow(): void
    {
        $this->sendDayFlowAsset('index.html', 'text/html; charset=utf-8');
    }

    public function dayFlowStyles(): void
    {
        $this->sendDayFlowAsset('styles.css', 'text/css; charset=utf-8');
    }

    public function dayFlowScript(): void
    {
        $this->sendDayFlowAsset('app.js', 'application/javascript; charset=utf-8');
    }

    private function sendAsset(string $filename, string $contentType): void
    {
        $path = dirname(__DIR__, 2) . '/prototypes/timeblock-planner/' . $filename;
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Prototype asset not found.';
            return;
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: no-store');
        readfile($path);
    }

    private function sendActualAsset(string $filename, string $contentType): void
    {
        $path = dirname(__DIR__, 2) . '/prototypes/actual-timeblock-planner/' . $filename;

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Prototype asset not found.';
            return;
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: no-store');
        readfile($path);
    }

    private function sendDayFlowAsset(string $filename, string $contentType): void
    {
        $path = dirname(__DIR__, 2) . '/prototypes/day-flow/' . $filename;

        if (!is_file($path)) {
            http_response_code(404);
            echo 'Prototype asset not found.';
            return;
        }

        header('Content-Type: ' . $contentType);
        header('Cache-Control: no-store');
        readfile($path);
    }
}
