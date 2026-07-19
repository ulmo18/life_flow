# Notification Feature Implementation

## Scope

Notification management lives in Settings. The web app stores user notification preferences and sends scheduling payloads to the Android WebView bridge when a relevant page is loaded after a save or settings change.

This is a local app-bridge scheduling integration, not a server-side FCM worker.

## Settings

Settings owns these user preferences in `user_preferences`:

- master notification on/off
- morning Retrospect reminder, default `07:00`
- evening Retrospect reminder, default `20:00`
- selected Routine reminder, default `14:00`
- Calendar selected-plan start reminders
- Goal deadline reminder, default `12:00`
- Goal D-1 reminder

The Settings page also exposes app-only actions for notification permission request and a local test notification.

## Scheduling Payloads

`App\Services\NotificationService` builds the payloads consumed by `public/assets/js/components/android-bridge.js`.

Payload groups:

- `daily`: repeating daily reminders, currently Retrospect morning/evening.
- `routine`: repeating reminders for routines whose own reminder flag is enabled.
- `specific`: one-time reminders for Calendar selected plan blocks and Goal deadlines.

The bridge wrapper first tries enhanced JSON methods:

- `syncNotificationSchedules(json)`
- `syncNotifications(json)`
- `scheduleNotifications(json)`
- `scheduleNotification(json)` for individual items
- `clearNotificationSchedules()`, `cancelAllNotifications()`, or `cancelNotificationSchedules()` when notifications are disabled

If enhanced methods are unavailable, it falls back to the sample legacy methods where possible:

- `requestNotificationPermission()`
- `showNotification(title, message)`
- `setRoutineAlarm(morningTime, eveningTime)`
- `setSpecificEventAlarm(eventTime)`

Legacy fallback cannot carry every custom title/message. The Android app should support one of the JSON methods above to honor all LifeFlow notification scenarios.

## Scenarios

- Retrospect: two daily reminders with the message `회고 알림입니다`.
- Routine: daily reminder for selected routines at the Settings routine time.
- Calendar: one-time reminder at each selected Plan block start time with `[계획명]을(를) 하실 시간입니다.`.
- Goal: one-time reminder on the goal deadline at the Settings deadline time with `목표 마감일입니다 고생하셨습니다`.
- Goal D-1: optional one-time reminder one day before the goal deadline.

## Driver Compatibility

Notification preferences are stored in `user_preferences` for both MySQL and SQLite. Runtime column checks in `UserPreferenceRepository` keep existing local databases usable, while schema files and migration files document the expected structure for new or manually migrated databases.
