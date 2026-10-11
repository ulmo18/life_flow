# Routine Feature Implementation

## Scope

Routine is the habit tracker area. It stores habits a user wants to build, tracks daily execution, and exposes the same execution toggle from both the Routine menu and Calendar routine popup.

The current implementation supports:

- creating routines with a name, start date, duration, and synchronized inclusive end date
- initial duration selection from 1 to 60 days in 1-day steps
- default duration of 7 days; users can start with the shortest practical weekly cycle and extend it later
- separating active/future routines from completed, stopped, and naturally ended routines
- browsing a Monday-first, 42-cell monthly execution calendar on every management card
- treating 60 accumulated completed days as the habit-formation milestone
- creating and editing routines from mobile bottom sheets
- soft-deleting routines from the edit bottom sheet
- extending active routines by a user-selected number of days, up to 365 total days
- completing or stopping a routine early while preserving its logs for Retrospect
- opening a routine detail bottom sheet with month-grouped seven-column period records
- toggling daily execution from the Routine page in the order blank, `O`, `X`, blank
- toggling daily execution from the Calendar routine popup with the same state cycle
- updating Routine and Calendar toggle states without a full page refresh when JavaScript is available
- optional one-per-day middle routine reminder metadata
- connecting a routine to one active goal

## Tables

### `routines`

Stores the habit definition.

Important columns:

- `id`
- `user_id`
- `goal_id`
- `name`
- `start_date`
- `duration_days`
- `reminder_enabled`
- `reminder_time`
- `deleted_at`
- `created_at`
- `updated_at`

Rules:

- Initial `duration_days` must be between 1 and 60. Extension may increase it up to 365.
- The creation form's end date is inclusive. A 1-day routine ends on its start date, and the latest 60-day initial end date is `start_date + 59 days`.
- When an initial end date is submitted, `RoutineService` recalculates `duration_days` from the date range and validates the same 1-to-60-day limit. No separate end-date column is stored.
- `status` is `active`, `completed`, or `stopped`; `ended_at` records manual completion or stopping.
- An `active` row whose planned end date has passed is treated as a natural period completion in Retrospect even before a background lifecycle job exists.
- Once created, the start date and duration cannot be shortened. Metadata such as name, goal, and reminder remains editable.
- `deleted_at` is used for soft deletion.
- `reminder_time` is metadata for the future notification scheduler.
- `goal_id` is nullable and connects a routine to one active `goals.id` record.
- A routine is active for dates from `start_date` through `start_date + duration_days - 1`.
- Future execution dates are read-only in the UI and rejected by the server. Today and past dates remain correctable.
- Extending `duration_days` preserves all existing `routine_logs` and adds future days to the active display range.
- `RoutineService` always validates duration and lifecycle status. On production MySQL/MariaDB versions without enforced CHECK constraints, `sql/migration.routine_lifecycle_validation_triggers.mysql.sql` provides an optional DB-level fallback after the lifecycle columns are added.

### `routine_logs`

Stores daily execution state.

Important columns:

- `id`
- `routine_id`
- `user_id`
- `log_date`
- `is_done`
- `created_at`
- `updated_at`

Rules:

- `UNIQUE(routine_id, log_date)` keeps one execution state per routine per date.
- Calendar and Routine page toggles write to the same table.
- No row means blank/unstarted, `is_done = 1` means `O`, and `is_done = 0` means `X`.
- Retrospect can use this table later for consistency feedback.

## Management Definitions

- `GET /manage/routines?status=active` includes active routines whose planned end date has not passed, including routines that start in the future.
- `GET /manage/routines?status=completed` includes manually completed, stopped, and naturally ended active rows. This tab is labeled `완료·종료 루틴` so stopped records are not hidden by wording.
- The selected calendar month is carried in `month=YYYY-MM`; state-changing forms preserve the selected status and month on redirect.
- Habit formation is based on accumulated execution rather than a streak: at least 60 `routine_logs.is_done = 1` rows inside the routine period sets `habitAchieved`.
- The card summary shows the habit milestone or a streak of at least three days, cumulative completed days, and the selected month's count. It does not show a percentage or progress bar.
- A routine streak first counts consecutive `O` records ending yesterday. If today is `O`, one day is added immediately; if today is blank or `X`, that latest streak count is retained. User-facing streak feedback begins at three days.

## Routes

- `GET /routine`: render today's execution-only routine list.
- `GET /manage/routines?status=active|completed&month=YYYY-MM`: render status tabs, the selected month's calendar, period history, and creation/editing forms.
- `POST /routine`: create a routine.
- `POST /routine/quick`: create a title-only routine from the daily Routine page.
- `POST /routine/update`: update a routine.
- `POST /routine/extend`: extend an active routine by a positive number of days.
- `POST /routine/finish`: complete or stop an active routine early.
- `POST /routine/delete`: soft-delete a routine.
- `POST /routine/toggle`: cycle a routine execution state for a date.

All POST routes require CSRF verification. `POST /routine/toggle` keeps redirect fallback behavior for non-JavaScript requests and returns JSON for `Accept: application/json` or `X-Requested-With: XMLHttpRequest`.

## UI Behavior

- `GET /routine` is the bottom-navigation daily execution view for the current date in the user's configured time zone.
- The daily Routine list shows `🔥 N일 연속 완료` below a routine name from three consecutive completed days onward. It reuses the management-page streak calculation and updates the message immediately after today's state toggle.
- `GET /manage/routines` is the aside Management view and retains routine creation, editing, extension, lifecycle actions, detail, and historical tracking.
- Both views write daily states through `POST /routine/toggle`; Calendar and Retrospect continue using the same endpoint and `routine_logs` rows.
- Calendar Actual-event entry lists only active routines that are not complete for the selected date. The selector preserves creation order and does not use `reminder_time` for sorting; selected rows are completed on Actual-event save and do not create a persistent event-to-routine relation.

- Routine Management starts with `활성 루틴` and `완료·종료 루틴` tabs followed by previous/next month controls; the former three-column weekly summary remains omitted.
- The bottom-navigation daily view groups unfinished routines above completed routines in compact tables. Blank and `X` states belong to unfinished; only `O` belongs to completed. Empty groups remain visible with guidance; when all routines are complete, the hopeful completion message appears as plain helper text in the empty unfinished group.
- Each card shows the routine name, lifecycle badge, optional linked goal, compact achievement summary, and a real monthly calendar. Desktop may place two cards per row; mobile keeps one card per row.
- Streak labels are not rendered for zero, one, or two days. At least three consecutive days use `🔥 연속 n일`. The displayed text never adds an `어제까지` prefix, while the underlying streak calculation remains unchanged. Streak labels use the warning/attention color family; completed tracker cells remain green so repetition feedback and completion state stay semantically distinct.
- Card calendars always use Monday-first weekday headings and 42 cells. Leading/trailing cells outside the selected month are placeholders; dates outside the routine period are disabled; future dates are read-only; today retains the existing state toggle.
- `O` uses a check marker and completion color, `X` uses a visible cross, and today has an outline, so state is never communicated by color alone.
- The execution button cycles from blank to `O`, from `O` to `X`, and from `X` back to blank.
- Routines are shown in the Routine list only while today is inside their active period. If a duration change moves today outside the active period, the routine no longer appears in today's Routine list and today's toggle is not offered.
- The add and edit forms use bottom sheets to keep the list focused.
- The edit sheet groups basic information, reminder settings, fixed period information, extension, and lifecycle actions. Its opaque full-width title row and close control remain sticky without exposing scrolling content around the padded sheet edges. Reminder time appears only when reminders are enabled, Save is the clear sticky primary action, and Delete is separated as a destructive action.
- The add action is fixed near the bottom of the viewport to match the Plan and Goal add interactions.
- The daily Routine page also has a centered `루틴 추가` floating action matching Retrospect and To-do. Its bottom sheet asks only for a name; the server supplies today as the start date, 7 days as the duration, no Goal, and no reminder. After creation the user stays on the daily Routine page, while detailed settings remain in Routine Management.
- On Routine Management, the four-item bottom navigator and separate Execution Energy HUD stay below the centered Routine-add action without overlapping it.
- The create sheet shows start date, duration, and end date together. Changing duration recalculates the end date, and selecting an end date recalculates duration; server validation repeats this calculation.
- The add and edit forms include an optional goal select that only lists active goals.
- Routine cards place the optional connected-goal label in the upper-right header context, constrain its width, and truncate long goal names so the routine name remains primary.
- `기록 관리` opens the period bottom sheet. Every month uses the same Monday-first calendar alignment, weekday headers, and leading/trailing placeholders as the card calendar.
- Completed dates are emphasized, `X` remains visually muted for correction, blank dates remain neutral, and future dates remain visible as read-only planned cells.
- Today and past period cells can be changed using the same blank, `O`, `X`, blank cycle.
- Detail and edit actions are compact secondary buttons while retaining at least a 44px mobile touch target; today's tracker cell remains the card's primary action.
- The detail sheet title is the routine name itself. Its title row and 44px close control remain sticky while the period record scrolls.
- Routine, Calendar, and daily Retrospect load `components/routine-state.css` and `components/routine-state.js` so blank, completed, and missed states share the same marker, color, accessible label, and in-place update behavior.
- Routine Management is the only surface that may correct past Routine dates. The bottom Routine page, Calendar, and Retrospect may mutate only the signed-in user's current date; the controller and service enforce this rule server-side.
- The Calendar routine popup shows only routines active on the selected date.
- Calendar routine toggles update in place when JavaScript is available and redirect back to the selected calendar date as a fallback.
- Future Calendar dates do not expose Routine completion controls.
- Completed and stopped routines leave daily Routine and Calendar views but remain available in Routine Management's `완료·종료 루틴` tab. Their records are editable from `기록 관리`; lifecycle metadata is read-only except for deletion. Naturally ended active routines may still be extended.
- Completing day 60 shows an optional celebration Toast and marks the routine as habitized. Consecutive-day milestones at 3, 7, 14, 30, and 60 days use the same restrained Toast variant. The effect respects reduced-motion preferences and does not change persistence logic.
- A new Calendar event can include active routines for that date. One event save writes the event and marks those routines complete in the same request flow, avoiding a second completion action in the Routine menu.

## Manual Test Checklist

- From the daily Routine page, open the centered `루틴 추가` action, enter only a name, and confirm the new routine appears for today with a 7-day duration, no Goal, and no reminder.
- Complete the third consecutive day from the daily Routine page and confirm `🔥 3일 연속 완료` appears without reloading; cycle away from completion and confirm it disappears when the streak falls below three.
- Submit an empty or over-60-character quick name and confirm the daily page shows a validation message without creating a routine.
- Confirm a detailed creation form initially selects 7 days, then change it to 1 day and confirm the end date becomes the start date.
- Change the start date and confirm the end date keeps the selected duration.
- Select an end date and confirm the duration changes to the inclusive number of days.
- Confirm the end-date picker cannot select before the start date or after `start date + 59 days`.
- Submit a forged end date outside the 60-day range and confirm server validation rejects it.
- Create a routine by end date and confirm its list progress and detail period use the calculated duration without a schema change.
- Switch between active and completed tabs and confirm future-start, naturally ended, completed, and stopped routines appear in the intended group.
- Change months and confirm every card remains a Monday-first 42-cell calendar and mutations return to the same tab/month.
- Reach 60 accumulated completed days and confirm the summary changes to `습관화 목표 달성` and the celebration Toast appears; remove and restore day 60 and confirm the derived state follows the logs.
- Enable reduced motion in the OS/browser and confirm the celebration remains readable without particle or pulse motion.

## Reminder Direction

The initial routine reminder model is separate from Retrospect reminders.

- Morning Retrospect: creation or morning check-in reminder.
- Middle Routine: one optional routine action reminder.
- Evening Retrospect: writing reminder.

The routine reminder is selected per routine through `reminder_enabled`. Settings owns the daily send time, defaulting to `14:00`, and `NotificationService` builds Android bridge payloads for selected routines.
Routine notification synchronization runs after routine mutations rather than every Routine page entry.

## Retrospect Integration Notes

Retrospect should eventually read `routine_logs` together with `calendar_events`.

Useful feedback signals:

- completed days within the routine period
- completion percentage
- recent recovery after missed days
- routines that are close to finishing
- routines that have not started despite being active
