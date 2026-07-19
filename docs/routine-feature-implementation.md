# Routine Feature Implementation

## Scope

Routine is the habit tracker area. It stores habits a user wants to build, tracks daily execution, and exposes the same execution toggle from both the Routine menu and Calendar routine popup.

The current implementation supports:

- creating routines with a name, start date, and duration
- duration selection from 7 to 60 days in 1-day steps
- default duration of 60 days
- listing routines active today with cumulative progress
- showing a compact lawn where filled cells represent total completed days, not exact calendar dates
- creating and editing routines from mobile bottom sheets
- soft-deleting routines from the edit bottom sheet
- opening a routine detail bottom sheet with date-ordered execution rows
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

- `duration_days` must be between 7 and 60.
- `deleted_at` is used for soft deletion.
- `reminder_time` is metadata for the future notification scheduler.
- `goal_id` is nullable and connects a routine to one active `goals.id` record.
- A routine is active for dates from `start_date` through `start_date + duration_days - 1`.
- Updating `start_date` or `duration_days` changes the active display range only. Existing `routine_logs` rows are preserved for retrospective use, but logs outside the new active period are excluded from list progress, lawn counts, detail rows, today's state, and Calendar routine popups.

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

## Routes

- `GET /routine`: render routine list and creation form.
- `POST /routine`: create a routine.
- `POST /routine/update`: update a routine.
- `POST /routine/delete`: soft-delete a routine.
- `POST /routine/toggle`: cycle a routine execution state for a date.

All POST routes require CSRF verification. `POST /routine/toggle` keeps redirect fallback behavior for non-JavaScript requests and returns JSON for `Accept: application/json` or `X-Requested-With: XMLHttpRequest`.

## UI Behavior

- The Routine page shows the routine name, period, completed count, percentage, and lawn.
- The lawn is cumulative: if 18 days are complete in a 60-day routine, the first 18 cells are filled.
- The lawn intentionally does not show which exact dates were done or missed.
- The lawn uses compact 6px cells so the list remains scannable on mobile while still giving visible progress feedback.
- The status guide is kept on the Routine page as a light inline legend with dot markers, matching the simpler calendar-style status guide treatment.
- The today button toggles today's execution state and is labeled so the blank state is understandable.
- The execution button cycles from blank to `O`, from `O` to `X`, and from `X` back to blank.
- Routines are shown in the Routine list only while today is inside their active period. If a duration change moves today outside the active period, the routine no longer appears in today's Routine list and today's toggle is not offered.
- The add and edit forms use bottom sheets to keep the list focused.
- The add action is fixed near the bottom of the viewport to match the Plan and Goal add interactions.
- The add and edit forms include an optional goal select that only lists active goals.
- Routine cards show a compact goal label when a routine is connected to a goal.
- The detail bottom sheet lists every date in the routine period from start date through end date.
- The date-by-date list scrolls inside the bottom sheet when the routine period is long, so the sheet header and context remain easy to keep.
- Dates without a saved `routine_logs` row are shown as blank.
- Dates can be changed from the detail bottom sheet using the same blank, `O`, `X`, blank cycle.
- The Calendar routine popup shows only routines active on the selected date.
- Calendar routine toggles update in place when JavaScript is available and redirect back to the selected calendar date as a fallback.
- A new Calendar event can include active routines for that date. One event save writes the event and marks those routines complete in the same request flow, avoiding a second completion action in the Routine menu.

## Reminder Direction

The initial routine reminder model is separate from Retrospect reminders.

- Morning Retrospect: creation or morning check-in reminder.
- Middle Routine: one optional routine action reminder.
- Evening Retrospect: writing reminder.

The routine reminder is selected per routine through `reminder_enabled`. Settings owns the daily send time, defaulting to `14:00`, and `NotificationService` builds Android bridge payloads for selected routines.

## Retrospect Integration Notes

Retrospect should eventually read `routine_logs` together with `calendar_events`.

Useful feedback signals:

- completed days within the routine period
- completion percentage
- recent recovery after missed days
- routines that are close to finishing
- routines that have not started despite being active
