# Calendar Feature Roles

## Purpose
This document defines the ownership boundaries for the dashboard calendar entry point, the calendar feature page, shared toast UI, actual-event persistence, routine check entry points, and Retrospect preview UI.

Read this file before changing calendar, toast, dashboard entry, or calendar data-source behavior.

## Dashboard
- `GET /dashboard` is the authenticated first page after login.
- Keep it focused on navigation, summaries, and quick actions.
- Do not place full calendar editing behavior in the dashboard.
- Link to `/calendar` for detailed calendar interactions.

## Calendar Page
- `GET /calendar` owns the day-grid calendar experience.
- Calendar views live under `app/Views/pages/calendar/`.
- Page-specific calendar CSS lives under `public/assets/css/pages/`.
- Page-specific calendar JavaScript lives under `public/assets/js/pages/`.
- Keep time-grid selection in the shared `public/assets/js/components/time-grid-selection.js` module. Calendar and Plan should configure that module instead of maintaining separate gesture implementations.
- On touch devices, the day grid uses native vertical scrolling by default. A stationary long press activates range selection, provides visual/haptic feedback, and the following drag extends the range.
- A short tap on an empty cell does not create a schedule. Mouse and pen may start range selection immediately.
- A native scroll gesture must not trigger the actual-event click or empty-cell selection that follows it.
- Mouse and pen range selection may start immediately, but range selection must never start from an actual-event control or another interactive control.
- Calendar bottom sheets and popups should close from the close button and dimmed overlay through click handlers only; avoid mixing pointerup and click for the same close action.
- Calendar bottom sheets should scroll internally when their content exceeds the mobile viewport.
- Keep the bottom-sheet header and close button reachable while its content scrolls, and keep touch controls out of the page-level grid gesture handling.
- Focus a sheet input while the opening click or pointer gesture still has user activation, then scroll the focused control into view when the visual viewport becomes shorter.
- The expanded "today's plan" summary should stay compact on mobile, using an internal scroll area once it reaches about 40% of the viewport height.
- Calendar uses one bottom-right `+` menu for quick memo, untimed schedule creation, routine checking, and baseline Plan settings. A divider separates baseline Plan settings from quick actions.
- Shared confirmation modals opened from calendar sheets must appear above the calendar-local layer.
- Calendar block title tooltips should remain hover-only and should not appear during mobile touch editing.

## Controller
- `CalendarController` handles request and response flow only.
- Controllers may choose views, pass prepared data, and select page assets.
- Do not put SQL or business rules in controllers.

## Model
- `CalendarRepository` is the data access boundary for calendar events.
- Current implementation persists actual events with `calendar_days`, `calendar_events`, and `calendar_date_meta`.
- Keep returned event shapes stable so views and JavaScript do not depend on the data source.

## Service
- `CalendarService` prepares calendar data for rendering.
- Use it for formatting, segment generation, and data-source normalization.
- Keep DB queries out of services.

## Persistence
- `calendar_days` stores one user/date row and the one selected `plan_group_id` for that day.
- `calendar_events` stores timed actual schedule blocks and untimed entries. Only timed entries can have a `plan_template_id` link.
- `calendar_tag_palettes` stores the shared 15-color palette for actual-event tags.
- `calendar_tags` stores four default system tags plus user-created personal tags.
- `calendar_date_meta` stores holiday/substitute-holiday metadata for date coloring.
- See `docs/calendar-feature-implementation.md` for table, route, and UI details.

## Routine Entry Point
- The Calendar routine popup reads active routines for the selected date.
- Routine execution toggles from Calendar and Routine page must write to the same `routine_logs` table.
- Routine execution toggles should update in place with JSON when JavaScript is available, while keeping POST redirect fallback behavior.
- Keep the popup light enough that it does not block actual schedule input.

## Retrospect Entry Point
- Calendar shows a Retrospect button as a daily reminder entry point.
- The button opens the latest submitted Retrospect report on or before the selected calendar date.
- If there is no submitted Retrospect report yet, the button is disabled.
- Retrospect owns report creation, draft editing, publishing, history, and report snapshots.
- Calendar should only render submitted Retrospect snapshots and must not provide Retrospect editing.

## Toast
- Toast is a shared UI component.
- Markup lives in `app/Views/components/toast.php`.
- CSS lives in `public/assets/css/components/toast.css`.
- JavaScript lives in `public/assets/js/components/toast.js`.
- Use `window.LifeFlowToast.show(message, options)` from page scripts.
- Calendar may render an initial toast when the selected date has no plan group so the user is nudged to set a baseline plan.

## Notifications
- Calendar contributes selected Plan block start-time reminder payloads for the currently viewed date.
- Settings owns whether Calendar selected-plan reminders are enabled.
- Calendar must not own global notification preferences; see `docs/notification-feature-implementation.md`.

## Future DB Extension Notes
- Support both MySQL and SQLite.
- Keep schema changes in both `sql/schema.mysql.sql` and `sql/schema.sqlite.sql`.
- Use prepared statements for every persisted calendar operation.
- Do not concatenate user input into SQL.
