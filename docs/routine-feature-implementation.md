# Routine Feature Implementation

## Scope

Routine is the habit tracker area. It stores habits a user wants to build, tracks daily execution, and exposes the same execution toggle from both the Routine menu and Calendar routine popup.

The current implementation supports:

- creating routines with a name, start date, and duration
- initial duration selection from 1 to 60 days in 1-day steps
- default duration of 60 days
- listing routines active today with a weekly summary and cumulative progress
- showing an always-visible compact habit-tracker grid on every routine card
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

## Summary Definitions

- Weekly active count is the number of routines active today.
- Weekly achievement is completed routine-days divided by scheduled routine-days from Monday through today. A routine that started during the week contributes only dates on or after its start date.
- A routine streak first counts consecutive `O` records ending yesterday. If today is `O`, one day is added immediately; if today is blank or `X`, that latest streak count is retained. The card label is always the compact `연속 n일` form.
- The weekly `연속 성공` value is the largest current streak among active routines.

## Routes

- `GET /routine`: render routine list and creation form.
- `POST /routine`: create a routine.
- `POST /routine/update`: update a routine.
- `POST /routine/extend`: extend an active routine by a positive number of days.
- `POST /routine/finish`: complete or stop an active routine early.
- `POST /routine/delete`: soft-delete a routine.
- `POST /routine/toggle`: cycle a routine execution state for a date.

All POST routes require CSRF verification. `POST /routine/toggle` keeps redirect fallback behavior for non-JavaScript requests and returns JSON for `Accept: application/json` or `X-Requested-With: XMLHttpRequest`.

## UI Behavior

- The Routine page starts with one three-column weekly summary card: active routines, weekly achievement, and the longest current streak.
- Each card shows the routine name, current streak label, optional upper-right linked goal, cumulative count and percentage, progress bar, a compact habit-tracker grid, and compact detail/edit actions.
- A streak of zero is not rendered. One- and two-day streaks use `연속 n일`; streaks of at least three days use `🔥 연속 n일`. The displayed text never adds an `어제까지` prefix, while the underlying streak calculation remains unchanged.
- Routines of 14 days or less show their full period in the card tracker. Longer routines show 14 cells, normally the previous 10 days, today, and the next 3 days; the window shifts near the start or end so it stays full.
- Tracker cells intentionally omit visible dates and weekdays. `O` fills a cell, blank and `X` remain quiet neutral cells, future dates use a lighter planned treatment, and the current day is outlined and identified as today.
- The user toggles today's execution by tapping today's tracker cell, so a separate large status button is not required.
- The tracker provides the visible fill-in reward, while the cumulative count and progress bar preserve evidence of success outside the 14-cell window.
- The execution button cycles from blank to `O`, from `O` to `X`, and from `X` back to blank.
- Routines are shown in the Routine list only while today is inside their active period. If a duration change moves today outside the active period, the routine no longer appears in today's Routine list and today's toggle is not offered.
- The add and edit forms use bottom sheets to keep the list focused.
- The add action is fixed near the bottom of the viewport to match the Plan and Goal add interactions.
- The add and edit forms include an optional goal select that only lists active goals.
- Routine cards place the optional connected-goal label in the upper-right header context, constrain its width, and truncate long goal names so the routine name remains primary.
- The detail bottom sheet groups every date from the routine start through its planned end under `YYYY년 M월` headings and places only those dates sequentially into seven columns. It does not add weekday headers, leading calendar blanks, or dates outside the routine period.
- Completed dates are emphasized, `X` remains visually muted for correction, blank dates remain neutral, and future dates remain visible as read-only planned cells.
- Today and past period cells can be changed using the same blank, `O`, `X`, blank cycle.
- Detail and edit actions are compact secondary buttons while retaining at least a 44px mobile touch target; today's tracker cell remains the card's primary action.
- The detail sheet title is the routine name itself. Its title row and 44px close control remain sticky while the period record scrolls.
- Routine, Calendar, and daily Retrospect load `components/routine-state.css` and `components/routine-state.js` so blank, completed, and missed states share the same marker, color, accessible label, and in-place update behavior.
- The Calendar routine popup shows only routines active on the selected date.
- Calendar routine toggles update in place when JavaScript is available and redirect back to the selected calendar date as a fallback.
- Future Calendar dates do not expose Routine completion controls.
- Completed and stopped routines leave the active Routine and Calendar views; Retrospect owns their historical feedback.
- A new Calendar event can include active routines for that date. One event save writes the event and marks those routines complete in the same request flow, avoiding a second completion action in the Routine menu.

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
