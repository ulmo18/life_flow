<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\CalendarService;
use App\Services\DailyDateAppearanceService;
use App\Services\NotificationService;

final class CalendarController
{
    private CalendarService $calendarService;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->calendarService = new CalendarService();
        $this->notificationService = new NotificationService();
    }

    public function index(): void
    {
        $userId = $this->userId();
        $notificationSettings = $this->notificationService->settings($userId);
        $calendar = $this->calendarService->getDayViewData(
            $userId,
            $_GET['date'] ?? null,
            $notificationSettings
        );
        $calendarMode = $this->calendarMode($_GET['mode'] ?? null);

        $this->render('pages/calendar/index', [
            'title' => t('nav.schedule'),
            'calendar' => $calendar,
            'dailyDateClass' => (new DailyDateAppearanceService())->getClass((string) $calendar['date']),
            'calendarMode' => $calendarMode,
            'headerGuidance' => $calendar['headerGuidance'] ?? null,
            'csrfToken' => Csrf::token(),
            'reminderMinuteOptions' => NotificationService::PLAN_LEAD_MINUTES,
            'notificationSettings' => $notificationSettings,
            'errors' => $_SESSION['errors'] ?? [],
            'flashSuccess' => $_SESSION['flash_success'] ?? null,
            'notificationSyncPayload' => $this->notificationService->buildCalendarSyncPayload($userId, $calendar),
            'pageStyles' => [
                $this->versionedAsset('/assets/css/components/routine-state.css'),
                $this->versionedAsset('/assets/css/components/todo-sheet.css'),
                $this->versionedAsset('/assets/css/pages/retrospect-report.css'),
                $this->versionedAsset('/assets/css/pages/calendar.css'),
            ],
            'pageScripts' => [
                '/assets/js/components/time-grid-selection.js',
                '/assets/js/components/routine-state.js',
                $this->versionedAsset('/assets/js/components/calendar-energy-input.js'),
                $this->versionedAsset('/assets/js/components/plan-reminder.js'),
                $this->versionedAsset('/assets/js/components/todo-followup.js'),
                $this->versionedAsset('/assets/js/components/calendar-resize-hold.js'),
                $this->versionedAsset('/assets/js/pages/calendar.js'),
            ],
        ]);

        unset($_SESSION['errors'], $_SESSION['flash_success']);
    }

    public function claimRetrospectPrompt(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->respondJson(['ok' => false], 419);
        }
        $date = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
        $this->respondJson(['ok' => true, 'show' => $this->calendarService->claimRetrospectPrompt($this->userId(), $date)]);
    }

    public function storeEvent(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null, $_POST['calendar_mode'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->calendarService->validateEventInput($this->userId(), $_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), $validation['errors']);
        }

        $eventId = $this->calendarService->createActualEvent($this->userId(), $validation['data']);
        if ($eventId === null) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), [
                'general' => '일정 저장에 실패했습니다. 시간이 겹치거나 계획 연결이 이미 사용되었는지 확인해주세요.',
            ]);
        }

        $feedback = $this->calendarService->claimExecutionEnergyFeedback(
            $this->userId(),
            (string) ($validation['data']['date'] ?? ''),
            (array) ($validation['data']['energySetting'] ?? [])
        );
        $_SESSION['flash_success'] = '일정이 저장되었습니다.' . (trim((string) ($validation['data']['memo'] ?? '')) !== '' ? ' 메모도 저장했어요.' : '') . ($feedback === null ? '' : ' ' . $feedback);
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

        $validation = $this->calendarService->validateEventUpdateInput($this->userId(), $_POST);
        if (!$validation['ok']) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), $validation['errors']);
        }

        if (!$this->calendarService->updateActualEvent($this->userId(), $validation['data'])) {
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), [
                'general' => '일정 수정 중 오류가 발생했습니다. 시간 겹침, 계획 연결 또는 태그를 다시 확인해주세요.',
            ]);
        }

        $feedback = $this->calendarService->claimExecutionEnergyFeedback(
            $this->userId(),
            (string) ($validation['data']['date'] ?? ''),
            (array) ($validation['data']['energySetting'] ?? [])
        );
        $_SESSION['flash_success'] = '일정이 수정되었습니다.' . (trim((string) ($validation['data']['memo'] ?? '')) !== '' ? ' 메모도 저장했어요.' : '') . ($feedback === null ? '' : ' ' . $feedback);
        $this->redirect($this->calendarPath($validation['data']['date'] ?? null));
    }

    public function updateEventTag(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
        }

        $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
        $rawTagId = $_POST['calendar_tag_id'] ?? '';
        $tagId = $rawTagId === '' || $rawTagId === null ? null : filter_var($rawTagId, FILTER_VALIDATE_INT);
        if ($eventId === false || $eventId <= 0 || $tagId === false || !$this->calendarService->updateActualEventTag(
            $this->userId(),
            (int) $eventId,
            $tagId === null ? null : (int) $tagId
        )) {
            $this->respondJson(['ok' => false, 'message' => '태그를 적용하지 못했습니다.'], 422);
        }

        $this->respondJson(['ok' => true, 'eventId' => (int) $eventId, 'calendarTagId' => $tagId]);
    }

    public function copyPlanBlockToActual(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }

        $validation = $this->calendarService->validateActualRangeInput($_POST, false, true);
        if (!$validation['ok'] || $this->calendarService->copyDailyPlanBlockToActual($this->userId(), $validation['data']) === null) {
            $this->redirectWithErrors(
                $this->calendarPath($validation['data']['date'] ?? null),
                $validation['errors'] ?: ['general' => '계획 블록을 실제 일정으로 복사하지 못했습니다. 시간 겹침과 계획 블록을 확인해 주세요.']
            );
        }

        $_SESSION['flash_success'] = '계획 블록을 실제 일정으로 기록했습니다.';
        $this->redirect($this->calendarPath($validation['data']['date']));
    }

    public function resizeActualEvent(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }

        $validation = $this->calendarService->validateActualRangeInput($_POST, true);
        if (!$validation['ok'] || !$this->calendarService->resizeActualEvent($this->userId(), $validation['data'])) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '실제 일정 시간을 수정하지 못했습니다. 다른 실제 일정과의 시간 겹침을 확인해 주세요.')], 422);
            $this->redirectWithErrors(
                $this->calendarPath($validation['data']['date'] ?? null),
                $validation['errors'] ?: ['general' => '실제 일정 시간을 수정하지 못했습니다. 다른 실제 일정과의 시간 겹침을 확인해 주세요.']
            );
        }

        if ($this->isJsonRequest()) $this->respondJson(['ok' => true]);
        $_SESSION['flash_success'] = '실제 일정 시간을 수정했습니다.';
        $this->redirect($this->calendarPath($validation['data']['date']));
    }

    public function setDayPlan(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다.'], 419);
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $date = is_string($_POST['date'] ?? null) ? (string) $_POST['date'] : null;
        $planGroupId = filter_var($_POST['plan_group_id'] ?? null, FILTER_VALIDATE_INT);
        $planGroupId = $planGroupId === false || $planGroupId <= 0 ? null : $planGroupId;

        if ($this->isJsonRequest()) {
            if ($planGroupId === null || !$this->calendarService->setDayPlanGroup($this->userId(), (string) $date, $planGroupId, true)) {
                $this->respondJson(['ok' => false, 'message' => '계획이 이미 있거나 템플릿을 적용할 수 없습니다.'], 409);
            }
            $calendar = $this->calendarService->getDayViewData($this->userId(), $date);
            $this->respondJson(['ok' => true, 'items' => $calendar['planActivityItems'], 'segments' => $calendar['planSegments']]);
        }

        if (!$this->calendarService->setDayPlanGroup($this->userId(), (string) $date, $planGroupId)) {
            $this->redirectWithErrors($this->calendarPath($date), ['general' => '계획 일정 연결에 실패했습니다.']);
        }

        $_SESSION['flash_success'] = '선택한 날짜의 계획 일정이 변경되었습니다.';
        $this->redirect($this->calendarPath($date));
    }

    public function storePlanItem(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }
        $validation = $this->calendarService->validateDailyPlanItemInput($_POST);
        if (!$validation['ok'] || $this->calendarService->createDailyPlanItem($this->userId(), $validation['data']) === null) {
            $errors = $validation['errors'] ?: ['general' => '계획 일정을 추가하지 못했습니다. 시간이 겹치는지 확인해주세요.'];
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null, $_POST['calendar_mode'] ?? null), $errors);
        }
        $_SESSION['flash_success'] = '오늘의 계획 일정이 추가되었습니다.';
        $this->redirect($this->calendarPath($validation['data']['date'], $_POST['calendar_mode'] ?? null));
    }

    public function storePlanItemWithBlock(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
        $input = $_POST;
        $input['schedule_type'] = 'timed';
        $validation = $this->calendarService->validateDailyPlanItemInput($input);
        $itemId = $validation['ok'] ? $this->calendarService->createPlanItemWithBlock($this->userId(), $validation['data']) : null;
        if ($itemId === null) $this->respondJson(['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '계획을 추가하지 못했습니다. 시간 겹침을 확인해 주세요.')], 422);
        $settings = $this->notificationService->settings($this->userId());
        $calendar = $this->calendarService->getDayViewData($this->userId(), $validation['data']['date'], $settings);
        $segments = array_values(array_filter($calendar['planSegments'], static fn(array $segment): bool => (int) $segment['itemId'] === $itemId));
        $this->respondJson(['ok' => true, 'item' => ['id' => $itemId, 'title' => $validation['data']['title'], 'importance' => $validation['data']['importance'], 'goalId' => $validation['data']['goalId'], 'scheduleType' => 'unscheduled'], 'segments' => $segments, 'notificationSyncPayload' => $this->notificationService->buildCalendarSyncPayload($this->userId(), $calendar)]);
    }

    public function updatePlanItem(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }
        $validation = $this->calendarService->validateDailyPlanItemInput($_POST, true);
        if (!$validation['ok'] || !$this->calendarService->updateDailyPlanItem($this->userId(), $validation['data'])) {
            $errors = $validation['errors'] ?: ['general' => '계획 일정을 수정하지 못했습니다. 연결된 실제 일정이나 겹치는 시간을 확인해주세요.'];
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null), $errors);
        }
        $_SESSION['flash_success'] = '오늘의 계획 일정이 수정되었습니다.';
        $this->redirect($this->calendarPath($validation['data']['date']));
    }

    public function updatePlanItemDetails(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null, $_POST['calendar_mode'] ?? null), ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $validation = $this->calendarService->validateDailyPlanItemDetailsInput($_POST);
        if (!$validation['ok'] || !$this->calendarService->updateDailyPlanItemDetails($this->userId(), $validation['data'])) {
            $errors = $validation['errors'] ?: ['general' => '활동 정보를 수정하지 못했습니다. 목표 연결을 확인해주세요.'];
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null, $_POST['calendar_mode'] ?? null), $errors);
        }

        $_SESSION['flash_success'] = '활동 정보가 수정되었습니다. 계획 블록 시간은 유지됩니다.';
        $this->redirect($this->calendarPath($validation['data']['date'], $_POST['calendar_mode'] ?? null));
    }

    public function deletePlanItem(): void
    {
        $path = $this->calendarPath($_POST['date'] ?? null);

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해주세요.'], 419);
            }
            $this->redirectWithErrors($path, ['general' => '요청이 만료되었습니다. 다시 시도해주세요.']);
        }

        $itemId = filter_var($_POST['daily_plan_item_id'] ?? null, FILTER_VALIDATE_INT);
        if ($itemId === false || $itemId <= 0 || !$this->calendarService->deleteDailyPlanItem($this->userId(), (int) $itemId)) {
            if ($this->isJsonRequest()) {
                $this->respondJson(['ok' => false, 'message' => '계획 일정을 삭제하지 못했습니다.'], 422);
            }
            $this->redirectWithErrors($path, ['general' => '계획 일정을 삭제하지 못했습니다.']);
        }

        $message = '계획 일정이 삭제되었습니다. 연결된 실제 일정은 유지됩니다.';
        $_SESSION['flash_success'] = $message;
        if ($this->isJsonRequest()) {
            $this->respondJson([
                'ok' => true,
                'itemId' => (int) $itemId,
                'message' => $message,
                'redirect' => $path,
            ]);
        }
        $this->redirect($path);
    }

    public function storePlanBlock(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null, 'plan'), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }
        $templateId = filter_var($_POST['block_template_id'] ?? null, FILTER_VALIDATE_INT);
        if ($templateId !== false && $templateId > 0 && $this->isJsonRequest()) {
            $input = $_POST;
            $input['daily_plan_item_id'] = 1;
            $validation = $this->calendarService->validateDailyPlanBlockInput($input);
            $result = $validation['ok'] ? $this->calendarService->placePlanBlockTemplate($this->userId(), $validation['data'], (int) $templateId) : null;
            if ($result === null) $this->respondJson(['ok' => false, 'message' => '계획을 배치하지 못했습니다. 시간 충돌이나 사용 상태를 확인해 주세요.'], 422);
            $this->respondJson(['ok' => true] + $result);
        }
        $validation = $this->calendarService->validateDailyPlanBlockInput($_POST);
        $blockId = $validation['ok'] ? $this->calendarService->createDailyPlanBlock($this->userId(), $validation['data']) : null;
        if (!$validation['ok'] || $blockId === null) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '계획 블록을 추가하지 못했습니다. 시간 충돌을 확인해 주세요.')], 422);
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null, 'plan'), $validation['errors'] ?: ['general' => '계획 블록을 추가하지 못했습니다. 시간 충돌을 확인해 주세요.']);
        }
        if ($this->isJsonRequest()) $this->respondJson(['ok' => true, 'blockId' => $blockId]);
        $_SESSION['flash_success'] = '계획 블록을 추가했습니다.';
        $this->redirect($this->calendarPath($validation['data']['date'], $_POST['calendar_mode'] ?? 'plan'));
    }

    public function updatePlanBlock(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null, 'plan'), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }
        $validation = $this->calendarService->validateDailyPlanBlockInput($_POST, true);
        if (!$validation['ok'] || !$this->calendarService->updateDailyPlanBlock($this->userId(), $validation['data'])) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => (string) (reset($validation['errors']) ?: '계획 블록을 수정하지 못했습니다. 시간 충돌을 확인해 주세요.')], 422);
            $this->redirectWithErrors($this->calendarPath($validation['data']['date'] ?? null, 'plan'), $validation['errors'] ?: ['general' => '계획 블록을 수정하지 못했습니다. 시간 충돌을 확인해 주세요.']);
        }
        if ($this->isJsonRequest()) {
            $settings = $this->notificationService->settings($this->userId());
            $calendar = $this->calendarService->getDayViewData($this->userId(), $validation['data']['date'], $settings);
            $this->respondJson(['ok' => true, 'notificationSyncPayload' => $this->notificationService->buildCalendarSyncPayload($this->userId(), $calendar)]);
        }
        $_SESSION['flash_success'] = '계획 블록을 수정했습니다.';
        $this->redirect($this->calendarPath($validation['data']['date'], $_POST['calendar_mode'] ?? 'plan'));
    }

    public function deletePlanBlock(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
            $this->redirectWithErrors($this->calendarPath($_POST['date'] ?? null, 'plan'), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }
        $blockId = filter_var($_POST['daily_plan_block_id'] ?? null, FILTER_VALIDATE_INT);
        $date = $_POST['date'] ?? null;
        $reminder = $blockId !== false && $blockId > 0 ? $this->calendarService->getPlanReminder($this->userId(), (int) $blockId) : null;
        if ($blockId === false || $blockId <= 0 || !$this->calendarService->deleteDailyPlanBlock($this->userId(), (int) $blockId)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '계획 블록을 삭제하지 못했습니다.'], 422);
            $this->redirectWithErrors($this->calendarPath($date, 'plan'), ['general' => '계획 블록을 삭제하지 못했습니다.']);
        }
        if ($this->isJsonRequest()) {
            $result = ['ok' => true, 'message' => '계획 블록을 삭제했습니다. 연결된 실제 일정은 유지됩니다.'];
            if ($reminder !== null) {
                $calendar = $this->calendarService->getDayViewData($this->userId(), $reminder['calendar_date']);
                $result['notificationSyncPayload'] = $this->notificationService->buildCalendarSyncPayload($this->userId(), $calendar);
            }
            $this->respondJson($result);
        }
        $_SESSION['flash_success'] = '계획 블록을 삭제했습니다. 연결된 실제 일정은 유지됩니다.';
        $this->redirect($this->calendarPath($date, 'plan'));
    }

    public function updatePlanBlockReminder(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
        $result = $this->calendarService->updatePlanReminder($this->userId(), $_POST);
        if (!$result['ok']) $this->respondJson($result, (int) $result['status']);
        $settings = $this->notificationService->settings($this->userId());
        $calendar = $this->calendarService->getDayViewData($this->userId(), $result['date'], $settings);
        foreach ($calendar['planSegments'] as $segment) {
            if ((int) $segment['blockId'] === $result['reminder']['blockId']) { $result['reminder']['timing'] = $segment['reminderTiming']; break; }
        }
        $this->respondJson(['ok' => true, 'reminder' => $result['reminder'], 'notificationSyncPayload' => $this->notificationService->buildCalendarSyncPayload($this->userId(), $calendar)]);
    }

    public function showPlanBlockReminder(): void
    {
        $id = filter_var($_GET['daily_plan_block_id'] ?? null, FILTER_VALIDATE_INT);
        $block = $id !== false && $id > 0 ? $this->calendarService->getPlanReminder($this->userId(), (int) $id) : null;
        if ($block === null) $this->respondJson(['ok' => false, 'message' => '계획 블록을 찾을 수 없습니다.'], 404);
        $this->respondJson(['ok' => true, 'reminder' => [
            'blockId' => (int) $block['id'], 'enabled' => (int) $block['reminder_enabled'] === 1,
            'minutesBefore' => (int) $block['reminder_minutes_before'], 'supported' => true,
            'timing' => $this->notificationService->planReminderTiming($block['calendar_date'], (int) $block['start_index'], $this->notificationService->settings($this->userId())),
        ]]);
    }

    public function updatePlanBlockDetails(): void
    {
        $date = $_POST['date'] ?? null;
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
            $this->redirectWithErrors($this->calendarPath($date, 'plan'), ['general' => '요청이 만료되었습니다. 다시 시도해 주세요.']);
        }
        $blockId = filter_var($_POST['daily_plan_block_id'] ?? null, FILTER_VALIDATE_INT);
        $importance = (string) ($_POST['importance'] ?? 'D');
        if ($this->isJsonRequest() && !in_array($importance, ['A', 'B', 'C', 'D'], true)) {
            $this->respondJson(['ok' => false, 'message' => '중요도를 확인해 주세요.'], 422);
        }
        $detach = (string) ($_POST['actual_link_action'] ?? 'keep') === 'detach_all';
        $manualStatus = array_key_exists('manual_status', $_POST) ? (is_string($_POST['manual_status']) ? $_POST['manual_status'] : '') : null;
        if ($blockId !== false && $blockId > 0 && $this->calendarService->planBlockFollowsTodo($this->userId(), (int) $blockId)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '상태와 중요도는 할 일에서 변경해 주세요.'], 422);
            $this->redirectWithErrors($this->calendarPath($date, 'plan'), ['general' => '상태와 중요도는 할 일에서 변경해 주세요.']);
        }
        if ($blockId === false || $blockId <= 0 || !$this->calendarService->updateDailyPlanBlockDetails($this->userId(), (int) $blockId, $importance, $detach, $manualStatus)) {
            if ($this->isJsonRequest()) $this->respondJson(['ok' => false, 'message' => '계획 수정에 실패했습니다.'], 422);
            $this->redirectWithErrors($this->calendarPath($date, 'plan'), ['general' => '계획 수정에 실패했습니다.']);
        }
        if ($this->isJsonRequest()) $this->respondJson(['ok' => true, 'message' => '계획을 수정했습니다.']);
        $_SESSION['flash_success'] = $detach ? '계획 수정 및 연결 Actual 전체 해제를 완료했습니다.' : '계획을 수정했습니다.';
        $this->redirect($this->calendarPath($date, 'plan'));
    }

    public function updatePlanBlockStatus(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다.'], 419);
        $id = filter_var($_POST['daily_plan_block_id'] ?? null, FILTER_VALIDATE_INT);
        $status = is_string($_POST['manual_status'] ?? null) ? $_POST['manual_status'] : '';
        if ($id !== false && $id > 0 && $this->calendarService->planBlockFollowsTodo($this->userId(), (int) $id)) {
            $this->respondJson(['ok' => false, 'message' => '상태는 할 일에서 변경해 주세요.'], 422);
        }
        if ($id === false || $id <= 0 || !$this->calendarService->updateDailyPlanBlockStatus($this->userId(), (int) $id, $status)) {
            $this->respondJson(['ok' => false, 'message' => $status === 'completed' ? '실제 일정을 연결한 뒤 완료할 수 있어요.' : '상태를 변경하지 못했습니다.'], 422);
        }
        $this->respondJson(['ok' => true, 'status' => $status]);
    }

    public function detachPlanBlockActuals(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) $this->respondJson(['ok' => false, 'message' => '요청이 만료되었습니다. 다시 시도해 주세요.'], 419);
        $id = filter_var($_POST['daily_plan_block_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0 || !$this->calendarService->detachDailyPlanBlockActuals($this->userId(), (int) $id)) {
            $this->respondJson(['ok' => false, 'message' => '연결을 해제하지 못했습니다.'], 422);
        }
        $this->respondJson(['ok' => true, 'message' => '연결을 해제했습니다. 실제 일정은 유지됩니다.']);
    }

    private function versionedAsset(string $publicPath): string
    {
        $filePath = dirname(__DIR__, 2) . '/public' . $publicPath;
        $version = is_file($filePath) ? filemtime($filePath) : false;

        return $version === false ? $publicPath : $publicPath . '?v=' . $version;
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
    private function redirectWithErrors(string $path, array $errors): void
    {
        $_SESSION['errors'] = $errors;
        $this->redirect($path);
    }

    private function calendarPath(mixed $date, mixed $mode = null): string
    {
        $date = is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : date('Y-m-d');
        $mode = $this->calendarMode($mode ?? ($_POST['calendar_mode'] ?? null));

        return '/calendar?date=' . rawurlencode($date) . '&mode=' . rawurlencode($mode);
    }

    private function calendarMode(mixed $mode): string
    {
        return is_string($mode) && $mode === 'plan' ? 'plan' : 'actual';
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
