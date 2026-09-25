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
- Calendar uses the shared `.page` container width so its content expands at the same mobile, tablet, and desktop breakpoints as the other feature menus. Keep the floating action aligned to the same responsive container edge.
- Keep time-grid selection in the shared `public/assets/js/components/time-grid-selection.js` module. Calendar and Plan should configure that module instead of maintaining separate gesture implementations.
- On touch devices, the day grid uses native vertical scrolling by default. A stationary long press activates range selection, provides visual/haptic feedback, and the following drag extends the range.
- In Android WebView, a confirmed touch long press temporarily disables native pull-to-refresh. Pointer completion, cancellation, explicit selection cancellation, page exit, and background transition restore it.
- A short tap on an empty cell does not create a schedule. Mouse and pen may start range selection immediately.
- A native scroll gesture must not trigger the actual-event click or empty-cell selection that follows it.
- Mouse and pen range selection may start immediately, but range selection must never start from an actual-event control or another interactive control.
- When the selected date is today, the current-time cell updates in place at every 10-minute boundary using the app timezone (`Asia/Seoul`); the page does not reload for this visual update.
- The Calendar header stays on one row in this order: selected date, previous day, next day, conditional Today, and Retrospect. The Today action is hidden while today is selected. Header actions use 32px visual controls inside 40px touch targets, and tapping the selected date opens the native date picker.
- A horizontal swipe or pointer drag on the Calendar date header navigates by one day: right for the previous date and left for the next date. Touch, mouse, and pen use the same 48px horizontal threshold so vertical page scrolling and ordinary header-control taps remain unchanged.
- Date-header drag tracking does not capture the pointer at the parent container. Date picker, previous, next, conditional Today, and latest-published-Retrospect controls therefore retain their native click behavior while completed horizontal drags still navigate.
- Calendar bottom sheets and popups should close from the close button and dimmed overlay through click handlers only; avoid mixing pointerup and click for the same close action.
- Calendar bottom sheets should scroll internally when their content exceeds the mobile viewport.
- Keep the bottom-sheet header and close button reachable while its content scrolls, and keep touch controls out of the page-level grid gesture handling.
- Focus a sheet input while the opening click or pointer gesture still has user activation, then scroll the focused control into view when the visual viewport becomes shorter.
- Calendar switches between a Plan schedule tab and an Actual schedule tab without changing the selected date. Each Calendar page entry defaults to the Actual tab instead of restoring the previous tab. The Plan tab edits the date-specific copy; the Actual tab retains actual-event entry and shows the same date-specific Plan blocks as a non-interactive background reference.
- Deleting a date-specific Plan block asks for confirmation, then submits the CSRF-protected delete request as JSON when JavaScript is available. The Calendar sheet closes only after the server confirms deletion, the page reloads from the returned Calendar path, and ordinary form submission remains the non-JavaScript fallback. If an actual event is linked, the Plan block is soft-deleted and the actual event remains after its Plan link is cleared.
- The initial server-rendered Calendar state already marks Plan blocks as the Actual tab's background layer. Calendar page CSS and JavaScript use file modification versions in their URLs so WebView and browser caches fetch changed behavior after deployment.
- Calendar does not show time-grid gesture instructions or a new untimed-entry button above the grid. The long-press/drag range-selection behavior remains available.
- Calendar uses one bottom-right `+` bottom sheet. It exposes timed Actual-event creation with editable start/end time selectors, quick Memo, and Plan-link actions, then up to three Plan items and three Routine items. Longer lists expand and scroll in the same sheet.
- Calendar exposes one `계획 추가` action rather than separate timed and time-unspecified actions. General entry opens the Plan form with no time, `시간 정하기` reveals required start/end selectors, and selecting a Plan-grid range opens the same form with time enabled and the range prefilled.
- Plan creation can copy one saved Plan-block template or use direct input. The copied fields remain editable and become date-specific data; later library edits or deletion do not propagate. If the copied duration exceeds today's range, confirmation is required before discarding the later portion and ending the date-specific copy at `23:50`.
- Plan importance is shown as four horizontal A/B/C/D radio-style buttons rather than a select menu.
- Actual-event creation always requires a start and end time. Existing legacy untimed events may remain available only as records that can be placed into a timed range.
- When a mouse or pen selection overlaps an existing block in the active tab, Calendar rejects the range before opening an editor and explains the conflict through Toast. Actual mode checks actual events only, so background Plan references never block actual entry; server-side overlap validation remains authoritative.
- Calendar event forms show the editable start/end controls once without repeating the selected range underneath them. A Plan-link field is omitted when there is no usable daily Plan item.
- Actual-event create and edit sheets may create a new tag inline without closing or resetting the event form. Tag editing and deletion remain in Schedule Tag Management.
- On pointer-hover devices, an Actual-event tooltip shows the title and at most 80 characters of its memo. It remains hover-only and is not used as a mobile editing surface.
- Empty Plan and Actual tabs show a compact first-action prompt instead of an initial no-Plan Toast. The Plan prompt disappears when any timed or time-unspecified Plan item exists; the Actual prompt disappears when an Actual block exists.
- A date-specific Plan item may have no time range. It is not rendered above, below, or inside either grid; the `+` menu's Today Plan list owns its visible `시간 미정` entry and opens the same Plan editor so a time can be assigned later.
- Time-unspecified Plan items remain eligible for Actual-event linking. Choosing a new Plan link copies the Plan title into the Actual title once; subsequent Actual-title changes do not rename the Plan item. The former Actual-side link-processing selector is intentionally removed.
- Shared confirmation modals opened from calendar sheets must appear above the calendar-local layer.
- Calendar block title tooltips should remain hover-only and should not appear during mobile touch editing.
- On today's Calendar, the shared header can render one single-line contextual action. The server chooses Retrospect window, current unlinked timed Plan, next unlinked timed Plan, no Plan, then no timed Actual record in that order. Retrospect uses the enabled morning/evening reminder time for a two-hour window; Plan and Actual actions open the matching Calendar sheet directly.
- Keep the WebView bridge contract and native lifecycle safety rules in `docs/android-webview-integration.md` aligned with the shared time-grid controller.

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
- `calendar_days` stores one user/date row. Its legacy `plan_group_id` mirrors the source template group during migration.
- `daily_plans` stores one mutable date-specific Plan copy per Calendar day.
- `daily_plan_items` stores copied or directly-created Plan items, including the copied goal link. `start_index` and `end_index` are both null for a time-unspecified item and both populated for a timed block.
- `calendar_events` stores timed actual schedule blocks and untimed entries. Timed entries link to `daily_plan_items`; the legacy `plan_template_id` remains transitional compatibility data.
- `calendar_tag_palettes` stores the shared 15-color palette for actual-event tags.
- Palette HEX values remain identical in light and dark mode; actual-event cards choose their foreground color from palette luminance so the label stays readable.
- `calendar_tags` stores four default system tags plus user-created personal tags.
- `calendar_date_meta` stores holiday/substitute-holiday metadata for date coloring.
- See `docs/calendar-feature-implementation.md` for table, route, and UI details.

## Routine Entry Point
- The Calendar floating bottom sheet reads active routines for the selected date and updates them without another navigation depth.
- Routine execution toggles from Calendar and Routine page must write to the same `routine_logs` table.
- Routine execution toggles should update in place with JSON when JavaScript is available, while keeping POST redirect fallback behavior.
- Calendar uses the shared Routine state control: blank is an empty neutral square, `O` is a filled check, and `X` is a muted cross. A successful toggle changes the control in place without reloading the page.
- Routine execution controls are available for today and past dates only. Future dates remain visible as calendar dates but cannot receive Routine logs.
- Keep the Routine section light enough that it does not block actual schedule input.

## Retrospect Entry Point
- Calendar shows a Retrospect button for the latest published report preview; it is not a Retrospect creation or navigation action.
- The button opens the user's latest submitted Retrospect report regardless of the selected calendar date.
- If there is no submitted Retrospect report yet, the button is disabled.
- Retrospect owns report creation, draft editing, publishing, history, and report snapshots.
- Calendar should only render submitted Retrospect snapshots and must not provide Retrospect editing.

## Toast
- Toast is a shared UI component.
- Markup lives in `app/Views/components/toast.php`.
- CSS lives in `public/assets/css/components/toast.css`.
- JavaScript lives in `public/assets/js/components/toast.js`.
- Use `window.LifeFlowToast.show(message, options)` from page scripts.
- Validation failures and rejected range selections use Toast so feedback does not insert content above the grid or move Calendar blocks.

## Notifications
- Calendar contributes only future selected-Plan reminders for the currently viewed date. Each reminder is scheduled five minutes before its Plan block starts in `Asia/Seoul`.
- Opening Calendar synchronizes a date-scoped replacement payload; it must never display notifications immediately.
- Settings owns whether Calendar selected-plan reminders are enabled.
- Calendar must not own global notification preferences; see `docs/notification-feature-implementation.md`.

## Future DB Extension Notes
- Support both MySQL and SQLite.
- Keep schema changes in both `sql/schema.mysql.sql` and `sql/schema.sqlite.sql`.
- Use prepared statements for every persisted calendar operation.
- Do not concatenate user input into SQL.
