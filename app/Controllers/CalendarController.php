<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
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
            'title' => 'Calendar',
            'calendar' => $this->calendarService->getDayViewData($this->userId(), $_GET['date'] ?? null),
            'csrfToken' => Csrf::token(),
            'errors' => $_SESSION['errors'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'pageStyles' => ['/assets/css/pages/calendar.css'],
            'pageScripts' => ['/assets/js/pages/calendar.js'],
        ]);

        unset($_SESSION['errors'], $_SESSION['flash_success']);
    }

    public function storeEvent(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->calendarService->validateEventInput($_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), $validation['errors']);
        }

        $eventId = $this->calendarService->createActualEvent($this->userId(), $validation['data']);
        if ($eventId === null) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), [
                'general' => '일정 저장에 실패했습니다. 시간이 겹치거나 계획 연결이 이미 사용되었는지 확인해주세요.',
            ]);
        }

        $_SESSION['flash_success'] = '일정이 저장되었습니다.';
        $this->redirect($this->calendarPath($validation['data']['date'] ?? null));
    }

    public function deleteEvent(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId <= 0 || !$this->calendarService->deleteActualEvent($this->userId(), $eventId)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '일정 삭제에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '일정이 삭제되었습니다.';
        $this->redirect($this->calendarPath($_POST['date'] ?? null));
    }

    public function updateEvent(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->calendarService->validateEventUpdateInput($_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), $validation['errors']);
        }

        if (!$this->calendarService->updateActualEvent($this->userId(), $validation['data'])) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), [
                'general' => '일정 수정 중 오류가 발생했습니다. 계획 연결이나 태그를 다시 확인해주세요.',
            ]);
        }

        $_SESSION['flash_success'] = '일정이 수정되었습니다.';
        $this->redirect($this->calendarPath($validation['data']['date'] ?? null));
    }

    public function setDayPlan(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null;
        $planGroupId = filter_var($_POST['plan_group_id'] ?? null, FILTER_VALIDATE_INT);
        $planGroupId = $planGroupId === false || $planGroupId <= 0 ? null : $planGroupId;

        if (!$this->calendarService->setDayPlanGroup($this->userId(), (string) $date, $planGroupId)) {
            $this->redirectWithErrors($this->calendarPath($date), ['general' => '계획 일정 연결에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '선택한 날짜의 계획 일정이 변경되었습니다.';
        $this->redirect($this->calendarPath($date));
    }

    /** @param array<string, string> $errors */
    private function redirectWithErrors(string $path, array $errors): void
    {
        $_SESSION['errors'] = $errors;
        $this->redirect($path);
    }

    private function calendarPath(mixed $date): string
    {
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');

        return '/calendar?date=' . rawurlencode($date);
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

    private function render(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $viewPath . '.php';
    }
}
