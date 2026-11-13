# Notification Feature Implementation

## Scope

Notification management lives in Settings. The web app stores user notification preferences and sends scheduling payloads to the Android WebView bridge when a relevant page is loaded after a save or settings change.

Settings, Goal, and Routine page entry alone must not synchronize schedules. Those pages emit payloads only after a notification setting, goal, or routine mutation. Calendar remains date-scoped and synchronizes on entry because the viewed date determines its selected-Plan reminders.

This is a local app-bridge scheduling integration, not a server-side FCM worker. The web server calculates desired schedules; Android persists and triggers them through its local alarm mechanism.

## Settings

Each timed reminder presents a separately labelled enable checkbox and time field. Validation messages appear beneath and are associated with the relevant time field; saved preferences and scheduling payloads are unchanged.

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

- `timeZone` is the signed-in user's stored IANA time zone, independent from display language.
- App-owned notification titles and messages use the user's stored `ko`, `en`, or `ja` display language. User-authored routine, plan, and goal titles remain unchanged.

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
- Routine: daily reminder at the selected routine’s own valid reminder time; the Settings routine time is a fallback for missing or invalid individual values.
- Calendar: one-time reminder at the individually selected lead before each enabled, placed Plan block starts with `[계획명] 시작 N분 전입니다.`. Past reminder times are omitted instead of being delivered immediately.
- Goal: one-time reminder on the goal deadline at the Settings deadline time with `목표 마감일입니다 고생하셨습니다`.
- Goal D-1: optional one-time reminder one day before the goal deadline.

## Driver Compatibility

Notification preferences are stored in `user_preferences` for both MySQL and SQLite. Runtime column checks in `UserPreferenceRepository` keep existing local databases usable, while schema files and migration files document the expected structure for new or manually migrated databases.

## Calendar Scheduling Contract

Placed Plan reminders, user-zone Goal timestamps and individual Routine time precedence are implemented. Alarm cancellation/account-switch cleanup remains deferred; see `docs/notification-review.md`.

- Calendar page entry is a synchronization event, not a delivery event.
- Calendar `fireAt` and `startsAt` use ISO 8601 with the signed-in user's time-zone offset (`+09:00` for Asia/Seoul). Goal deadline and D-1 timestamps also include the user-zone offset and omit past trigger times.
- The server includes a Calendar Plan item only when `fireAt` is later than the current time in the signed-in user's configured IANA time zone.
- Android must parse `fireAt` into an absolute instant and persist it with AlarmManager, WorkManager, or an equivalent local scheduler.
- Android must discard any item whose parsed trigger time is not in the future. It must never convert a past trigger into an immediate notification.
- Stable `key` values make repeated Calendar entry idempotent. A scoped replace cancels obsolete `calendar_plan_{date}_*` alarms and reschedules only the supplied future set.
- A server-side notification table is not required for this local-only architecture. Add a durable server job table and FCM worker only if cross-device delivery, server retries, or delivery history becomes required.

- The calendar payload accepts future dates beyond today (verified at 90 days). Per-block opt-in is implemented. Web scheduling checks do not verify Android permission, reboot restoration or delivery.

## Per-block reminder controls

Plan-edit reminder presentation now omits calendar dates and emphasizes the trigger `HH:mm` in the summary and expanded clock line. A midnight-crossing lead shows `전날 HH:mm` without a numeric date. The Plan header still supplies its own date/range; To-do keeps its full-date clock display. This is a presentation-only change to the server-calculated timing map.

Plan details always show their date and time range. The shared reminder summary and expanded clock line show the selected lead's actual `m/d H:i` trigger time. `NotificationService::planReminderTiming()` uses the same user-zone `DateTimeImmutable` arithmetic as scheduling, including a lead crossing midnight. The authenticated, ownership-checked `GET /calendar/plan-block/reminder` supplies fresh timing for newly created or moved blocks; its response updates only clock data and preserves an unsaved selection. Save responses also return the timing map. Native delivery remains a separate concern.

- Schedule Plan-block details and To-do “상태와 연결 기록” details use the same reminder editor. Each timed placement owns its enable flag and lead; the untimed source To-do does not own a schedule. Multiple placements remain independent.
- Default: off, with a five-minute lead. Choices: start time (0), 5, 10, 15, 30 or 60 minutes before. Press “알림 저장” after changing either field. Switching off preserves the selected lead. Saving does not assert delivery or request native permission.
- The editor now starts collapsed behind “알림 설정” with a state summary. If either global gate is off, expansion shows the separate master/Plan states and a link to `/settings#notificationSettings`. With both enabled, it shows off/time choice buttons and save. There is no permanent explanatory paragraph. Choices expose pressed states; expanding cloned To-do details refreshes control availability.
- Settings master notifications and Calendar Plan reminders must both be enabled. Past trigger times are excluded. Leads may cross midnight; timestamps retain the user's time-zone offset.
- `POST /calendar/plan-block/reminder` requires authentication, CSRF, ownership and valid choices. The scope uses the stored block date, not a posted date. Invalid choices return 422, missing/non-owned blocks 404, unavailable schema 503.
- `CalendarService` supplies `planBlockReminders` from actual `daily_plan_blocks`. Original `planReminderItems` remains available to placement UI and is not the notification source. Keys are `calendar_plan_{date}_block_{blockId}`; items carry `blockId` and `minutesBefore`.
- Save/delete responses send date-scoped replacements. Move/resize recalculates the reminder using its saved lead. Disabling the final reminder sends an empty scoped set. Enhanced native JSON support is required for scoped replacement; ordinary Android Chrome has no app-specific bridge.
- Existing MySQL databases require `sql/migration.plan_block_reminders.mysql.sql` once. SQLite upgrades missing columns automatically. Unmigrated MySQL remains readable with disabled controls; it does not fall back to original To-do times.

## Verification (2026-11-10)

SQLite and isolated MariaDB 10.11 integration checks cover opt-in, two placements from one untimed source, independent leads, ownership, movement, deletion, global switches, past filtering and migration compatibility. Browser checks cover save/reopen in both sheets, CSRF/ownership/invalid options, scoped deletion and layouts at 320/390/1024 px in both themes. Native Android delivery was not tested.

Regression tests: `tests/plan_block_reminders.php` (SQLite or a disposable MySQL database named `lifeflow_test_*`) and `tests/plan_reminder_controls.test.js`.

Manual checks:
1. Place one untimed To-do twice on a future date. In Schedule, enable one block for 15 minutes before and the other for start time; save both.
2. Open the To-do status/linked-record sheet. Confirm each placement's saved values. Change one, save, close/reopen, then verify in Schedule.
3. Move a block, disable its reminder, and delete the other. Check that replacement payloads follow block times and remove obsolete entries without affecting other dates.
4. Check Settings master and Plan reminder gates. With a supported Android bridge, verify permission, actual delivery, replacement and cancellation on device.

Goal scheduling queries targets using the current user-local date, constructs the deadline in that user’s IANA time zone and subtracts one calendar day for D-1 before formatting each with DATE_ATOM. Host/device time-zone changes do not change the absolute trigger instant. Existing global gates remain. Individual Routine reminder_time accepts valid HH:mm or database TIME HH:mm:ss and is normalized to HH:mm; null/invalid values fall back to Settings. No new alarm cancellation path was added. Changed source: app/Services/NotificationService.php. Regression: tests/notification_timezone_routine.php. Manual checks: configure user New York/device Seoul and compare goal/D-1 clock and instant; set a routine to 16:45 and Settings to 14:00 and verify 16:45 in its sync payload.

## Settings UI (2026-11-13)

The reference settings screen groups master and individual reminders in one divided list. Morning, evening, Routine, Plan, Goal-deadline and day-before gates are all retained. Plan has no global clock input; Routine explains its per-routine time override, and D-1 shares the Goal clock. Each timed reminder uses its existing minute-resolution native time input styled as a pill. Disabling a reminder disables only its time input; omitted time fields preserve the server value and re-enabling restores the draft clock. Master-off leaves individual controls and preferences intact.

Permission and test buttons use existing Android bridge delivery only. Browser-only surfaces show Android-app availability guidance, hide the permission request, and disable the native test action. A native bridge shows permission status as unknown because no state-query API exists. A successful bridge call means the request was handed to the app, not that permission was granted or a notification arrived. No Web Push, permission-query or cancellation integration was added. Explicit settings saves and payload ownership are unchanged.

## Shared reminder-time sheet (2026-11-13)

Settings reminder inputs now use the common time-picker sheet. The original HH:mm input is retained for submission with 1-minute choices, independently of Calendar’s 10-minute indices. Disabled fields preserve their values and disable the corresponding trigger. Each time button retains its validation description. The notification section collapses without affecting checked values or schedule synchronization.
