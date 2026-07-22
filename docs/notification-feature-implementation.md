# Notification Feature Implementation

## Scope

Notification management lives in Settings. The web app stores user notification preferences and sends scheduling payloads to the Android WebView bridge when a relevant page is loaded after a save or settings change.

Settings, Goal, and Routine page entry alone must not synchronize schedules. Those pages emit payloads only after a notification setting, goal, or routine mutation. Calendar remains date-scoped and synchronizes on entry because the viewed date determines its selected-Plan reminders.

This is a local app-bridge scheduling integration, not a server-side FCM worker. The web server calculates desired schedules; Android persists and triggers them through its local alarm mechanism.

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

Payload version 2 includes `timeZone` and `generatedAt`. Calendar payloads additionally use `operation: replace`, `scope: calendar_plan`, and the selected date as `scopeKey`. Android must replace only that date's Calendar Plan reservations and must not cancel Routine, Retrospect, or Goal schedules.

The bridge wrapper removes invalid and past one-time reminders before calling native code, then first tries enhanced JSON methods:

- `replaceNotificationSchedules(json)`
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

Legacy fallback cannot carry every custom title/message or guarantee scoped replacement. The Android app should support `replaceNotificationSchedules(json)` or another enhanced JSON method above to honor all LifeFlow notification scenarios.

## Scenarios

- Retrospect: two daily reminders with the message `회고 알림입니다`.
- Routine: daily reminder for selected routines at the Settings routine time.
- Calendar: one-time reminder five minutes before each selected Plan block starts with `[계획명] 시작 5분 전입니다.`. Past reminder times are omitted instead of being delivered immediately.
- Goal: one-time reminder on the goal deadline at the Settings deadline time with `목표 마감일입니다 고생하셨습니다`.
- Goal D-1: optional one-time reminder one day before the goal deadline.

## Driver Compatibility

Notification preferences are stored in `user_preferences` for both MySQL and SQLite. Runtime column checks in `UserPreferenceRepository` keep existing local databases usable, while schema files and migration files document the expected structure for new or manually migrated databases.

## Calendar Scheduling Contract

- Calendar page entry is a synchronization event, not a delivery event.
- `fireAt` and `startsAt` are ISO 8601 timestamps with the `+09:00` offset.
- The server includes a Calendar Plan item only when `fireAt` is later than the current `Asia/Seoul` time.
- Android must parse `fireAt` into an absolute instant and persist it with AlarmManager, WorkManager, or an equivalent local scheduler.
- Android must discard any item whose parsed trigger time is not in the future. It must never convert a past trigger into an immediate notification.
- Stable `key` values make repeated Calendar entry idempotent. A scoped replace cancels obsolete `calendar_plan_{date}_*` alarms and reschedules only the supplied future set.
- A server-side notification table is not required for this local-only architecture. Add a durable server job table and FCM worker only if cross-device delivery, server retries, or delivery history becomes required.
