<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoalRepository;
use App\Models\RoutineRepository;
use App\Models\UserPreferenceRepository;
use DateTimeImmutable;
use DateTimeZone;

final class NotificationService
{
    private UserPreferenceRepository $preferences;
    private RoutineRepository $routineRepository;
    private GoalRepository $goalRepository;

    public function __construct()
    {
        $this->preferences = new UserPreferenceRepository();
        $this->routineRepository = new RoutineRepository();
        $this->goalRepository = new GoalRepository();
    }

    /** @return array<string, mixed> */
    public function settings(int $userId): array
    {
        return $this->normalizeSettings($this->preferences->get($userId));
    }

    /** @return array{ok: bool, errors: array<string, string>, data: array<string, mixed>} */
    public function validateSettings(array $input, array $currentSettings): array
    {
        $timeFields = [
            'retrospect_morning_time' => '아침 회고 알림 시간을 확인해주세요.',
            'retrospect_evening_time' => '저녁 회고 알림 시간을 확인해주세요.',
            'routine_reminder_time' => '루틴 알림 시간을 확인해주세요.',
            'goal_deadline_time' => '목표 마감 알림 시간을 확인해주세요.',
        ];
        $errors = [];
        $data = $this->normalizeSettings($currentSettings);

        foreach ([
            'notification_enabled',
            'retrospect_morning_enabled',
            'retrospect_evening_enabled',
            'routine_reminder_enabled',
            'calendar_plan_reminder_enabled',
            'goal_deadline_reminder_enabled',
            'goal_deadline_day_before_enabled',
        ] as $field) {
            $data[$field] = isset($input[$field]) && (string) $input[$field] === '1' ? 1 : 0;
        }

        foreach ($timeFields as $field => $message) {
            $time = trim((string) ($input[$field] ?? $data[$field]));
            if (!$this->isValidTime($time)) {
                $errors[$field] = $message;
                continue;
            }

            $data[$field] = $time;
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => $data,
        ];
    }

    /** @param array<string, mixed> $settings */
    public function updateSettings(int $userId, array $settings): void
    {
        $this->preferences->updateNotificationSettings($userId, $this->normalizeSettings($settings));
    }

    /** @return array<string, mixed> */
    public function buildSettingsSyncPayload(int $userId): array
    {
        $settings = $this->settings($userId);

        return $this->basePayload($settings)
            + [
                'daily' => $this->dailyReminderPayload($settings),
                'routine' => $this->routineReminderPayload($userId, $settings),
                'specific' => $this->goalDeadlinePayload($userId, $settings),
            ];
    }

    /** @return array<string, mixed> */
    public function buildRoutineSyncPayload(int $userId): array
    {
        $settings = $this->settings($userId);

        return $this->basePayload($settings)
            + [
                'daily' => [],
                'routine' => $this->routineReminderPayload($userId, $settings),
                'specific' => [],
            ];
    }

    /** @param array<string, mixed> $calendar */
    public function buildCalendarSyncPayload(int $userId, array $calendar): array
    {
        $settings = $this->settings($userId);
        $date = (string) ($calendar['date'] ?? date('Y-m-d'));

        return $this->basePayload($settings)
            + [
                'operation' => 'replace',
                'scope' => 'calendar_plan',
                'scopeKey' => $date,
                'daily' => [],
                'routine' => [],
                'specific' => $this->calendarPlanPayload($calendar, $settings),
            ];
    }

    /** @return array<string, mixed> */
    public function buildGoalSyncPayload(int $userId): array
    {
        $settings = $this->settings($userId);

        return $this->basePayload($settings)
            + [
                'daily' => [],
                'routine' => [],
                'specific' => $this->goalDeadlinePayload($userId, $settings),
            ];
    }

    /** @param array<string, mixed> $settings */
    private function basePayload(array $settings): array
    {
        $timezone = new DateTimeZone('Asia/Seoul');

        return [
            'version' => 2,
            'enabled' => (int) $settings['notification_enabled'] === 1,
            'timeZone' => $timezone->getName(),
            'generatedAt' => (new DateTimeImmutable('now', $timezone))->format(DATE_ATOM),
        ];
    }

    /** @param array<string, mixed> $settings @return array<int, array<string, mixed>> */
    private function dailyReminderPayload(array $settings): array
    {
        if ((int) $settings['notification_enabled'] !== 1) {
            return [];
        }

        $items = [];
        if ((int) $settings['retrospect_morning_enabled'] === 1) {
            $items[] = [
                'key' => 'retrospect_morning',
                'type' => 'retrospect',
                'time' => (string) $settings['retrospect_morning_time'],
                'title' => 'LifeFlow',
                'message' => '회고 알림입니다',
                'repeat' => 'daily',
            ];
        }

        if ((int) $settings['retrospect_evening_enabled'] === 1) {
            $items[] = [
                'key' => 'retrospect_evening',
                'type' => 'retrospect',
                'time' => (string) $settings['retrospect_evening_time'],
                'title' => 'LifeFlow',
                'message' => '회고 알림입니다',
                'repeat' => 'daily',
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $settings @return array<int, array<string, mixed>> */
    private function routineReminderPayload(int $userId, array $settings): array
    {
        if ((int) $settings['notification_enabled'] !== 1 || (int) $settings['routine_reminder_enabled'] !== 1) {
            return [];
        }

        return array_map(function (array $routine) use ($settings): array {
            $startDate = (string) $routine['start_date'];
            $durationDays = max(1, (int) $routine['duration_days']);
            $endDate = (new DateTimeImmutable($startDate))->modify('+' . ($durationDays - 1) . ' days')->format('Y-m-d');
            $name = (string) $routine['name'];

            return [
                'key' => 'routine_' . (string) $routine['id'],
                'type' => 'routine',
                'routineId' => (int) $routine['id'],
                'time' => (string) $settings['routine_reminder_time'],
                'startDate' => $startDate,
                'endDate' => $endDate,
                'title' => '루틴 알림',
                'message' => $name . ' 루틴을 확인할 시간입니다.',
                'repeat' => 'daily',
            ];
        }, $this->routineRepository->listNotificationEnabled($userId));
    }

    /** @param array<string, mixed> $calendar @param array<string, mixed> $settings @return array<int, array<string, mixed>> */
    private function calendarPlanPayload(array $calendar, array $settings): array
    {
        if ((int) $settings['notification_enabled'] !== 1 || (int) $settings['calendar_plan_reminder_enabled'] !== 1) {
            return [];
        }

        $date = (string) ($calendar['date'] ?? date('Y-m-d'));
        $timezone = new DateTimeZone('Asia/Seoul');
        $now = new DateTimeImmutable('now', $timezone);
        $items = [];
        foreach (($calendar['planReminderItems'] ?? []) as $item) {
            $title = (string) ($item['title'] ?? '');
            if ($title === '' || !isset($item['startIndex'])) {
                continue;
            }

            $time = $this->indexToTime((int) $item['startIndex']);
            $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $timezone);
            if (!$startsAt instanceof DateTimeImmutable) {
                continue;
            }

            $fireAt = $startsAt->modify('-5 minutes');
            if ($fireAt <= $now) {
                continue;
            }

            $items[] = [
                'key' => 'calendar_plan_' . $date . '_' . (string) ($item['templateId'] ?? md5($title . $time)),
                'type' => 'calendar_plan',
                'templateId' => (int) ($item['templateId'] ?? 0),
                'fireAt' => $fireAt->format(DATE_ATOM),
                'startsAt' => $startsAt->format(DATE_ATOM),
                'title' => '계획 알림',
                'message' => $title . ' 시작 5분 전입니다.',
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $settings @return array<int, array<string, mixed>> */
    private function goalDeadlinePayload(int $userId, array $settings): array
    {
        if ((int) $settings['notification_enabled'] !== 1 || (int) $settings['goal_deadline_reminder_enabled'] !== 1) {
            return [];
        }

        $items = [];
        foreach ($this->goalRepository->listDeadlineReminderTargets($userId, date('Y-m-d')) as $goal) {
            $endDate = (string) $goal['period_end_date'];
            $goalId = (int) $goal['id'];

            if ((int) $settings['goal_deadline_day_before_enabled'] === 1) {
                $dayBefore = (new DateTimeImmutable($endDate))->modify('-1 day')->format('Y-m-d');
                if ($dayBefore >= date('Y-m-d')) {
                    $items[] = [
                        'key' => 'goal_deadline_d1_' . (string) $goalId,
                        'type' => 'goal_deadline_d1',
                        'goalId' => $goalId,
                        'fireAt' => $dayBefore . 'T' . (string) $settings['goal_deadline_time'] . ':00',
                        'title' => '목표 알림',
                        'message' => '목표 마감일이 하루 남았습니다.',
                    ];
                }
            }

            $items[] = [
                'key' => 'goal_deadline_' . (string) $goalId,
                'type' => 'goal_deadline',
                'goalId' => $goalId,
                'fireAt' => $endDate . 'T' . (string) $settings['goal_deadline_time'] . ':00',
                'title' => '목표 알림',
                'message' => '목표 마감일입니다 고생하셨습니다',
            ];
        }

        return $items;
    }

    /** @param array<string, mixed> $settings */
    private function normalizeSettings(array $settings): array
    {
        foreach ([
            'notification_enabled',
            'retrospect_morning_enabled',
            'retrospect_evening_enabled',
            'routine_reminder_enabled',
            'calendar_plan_reminder_enabled',
            'goal_deadline_reminder_enabled',
            'goal_deadline_day_before_enabled',
        ] as $field) {
            $settings[$field] = (int) ($settings[$field] ?? 1) === 1 ? 1 : 0;
        }

        foreach ([
            'retrospect_morning_time' => '07:00',
            'retrospect_evening_time' => '20:00',
            'routine_reminder_time' => '14:00',
            'goal_deadline_time' => '12:00',
        ] as $field => $default) {
            $time = substr((string) ($settings[$field] ?? $default), 0, 5);
            $settings[$field] = $this->isValidTime($time) ? $time : $default;
        }

        $settings['theme'] = (string) ($settings['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

        return $settings;
    }

    private function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
    }

    private function indexToTime(int $index): string
    {
        $minutes = max(0, min(143, $index)) * 10;
        $hour = (int) floor($minutes / 60);
        $minute = $minutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
