<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CalendarService;

final class CalendarController
{
    private CalendarService $calendarService;

    public function __construct()
    {
        $this->calendarService = new CalendarService();
    }

    public function index(): void
    {
        $this->render('pages/calendar/index', [
            'title' => '캘린더',
            'calendar' => $this->calendarService->getDayViewData((int) ($_SESSION['user_id'] ?? 0)),
            'pageStyles' => ['/assets/css/pages/calendar.css'],
            'pageScripts' => ['/assets/js/pages/calendar.js'],
        ]);
    }

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}
