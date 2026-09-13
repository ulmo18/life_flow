# Calendar Feature Implementation

## Scope

Calendar is the date-specific planning and actual execution screen. It copies templates into a mutable daily Plan, stores actual events, and connects actual events to copied Plan items.

The current implementation supports:

- date navigation by previous/next day and direct date lookup
- one date-specific daily Plan per calendar day, optionally copied from a Plan template group
- direct add/edit/delete of daily Plan items
- many actual events per calendar day
- timed and untimed actual events
- optional one-to-one actual event to daily Plan item connection
- default calendar tags and user-created tags backed by a shared organic 15-color palette
- soft deletion of actual events
- current-time cell highlighting when the selected date is today
- active Routine list in the floating bottom sheet, backed by routine definitions and daily logs
- Retrospect preview entry point for the latest submitted report on or before the selected date
- date metadata table for holiday/substitute holiday coloring

## Tables

### `calendar_days`

The day-level calendar record for a user.

Important columns:

- `id`
- `user_id`
- `calendar_date`
- `plan_group_id`
- `created_at`
- `updated_at`

Rules:

- `UNIQUE(user_id, calendar_date)` keeps one day record per user/date.
- `plan_group_id` is retained as the source-template mirror for compatible migration. Runtime Plan blocks come from `daily_plans`.

### `daily_plans` and `daily_plan_items`

- `daily_plans` has one row per Calendar day and records the optional source template group.
- Connecting a template replaces the daily Plan with a copy of the template's current blocks.
- `daily_plan_items` copies title, importance, time range, sort order, and `goal_id`.
- Template edits never propagate to existing daily copies.
- Adding a Plan block without a connected template creates `[YYYY-MM-DD]의 계획` automatically and later additions update the same daily Plan.
- Editing or deleting a linked daily Plan item keeps the actual event by default; the UI can synchronize changes or detach the relationship.

### `calendar_events`

The actual schedule data entered on the calendar.

Important columns:

- `id`
- `user_id`
- `calendar_day_id`
- `title`
- `schedule_type`
- `start_index`
- `end_index`
- `plan_template_id`
- `calendar_tag_id`
- `memo`
- `deleted_at`
- `created_at`
- `updated_at`

Rules:

- Timed actual events are stored with the same 10-minute index model as plan blocks.
- `schedule_type` is `timed` or `unscheduled`. Untimed entries store null `start_index`, `end_index`, and `plan_template_id` values.
- `start_index` is inclusive and `end_index` is exclusive.
- `daily_plan_item_id` is nullable. If present, the actual event is linked to one copied daily Plan block.
- `plan_template_id` is retained during transition and stores the copied item's source template when one exists.
- `calendar_tag_id` is nullable. If present, the actual event uses the common tag's color for its calendar block.
- `memo` stores optional event notes and is editable from the event bottom sheet.
- The UI only offers unused daily Plan items for the selected day, so a copied block cannot be selected twice on the same day.
- Active timed events cannot overlap on the same calendar day. Untimed entries do not participate in overlap checks.
- `deleted_at` is used for soft deletion.

### `calendar_tag_palettes`

The shared 15-color organic palette for calendar tags.

Important columns:

- `id`
- `slug`
- `color_hex`
- `sort_order`
- `created_at`

Rules:

- Palette colors are common system data.
- The same 15 HEX values are preserved in light and dark mode so an assigned tag never changes identity with the display theme.
- Actual-event components calculate a dark or light text color from the tag background; white text is not assumed for every palette color.
- Active visible tags cannot reuse a palette color for the same user-facing tag set.
- The first four colors are currently used by default tags.

### `calendar_tags`

The tag data used by actual calendar events and future retrospect grouping.

Important columns:

- `id`
- `user_id`
- `palette_id`
- `slug`
- `name`
- `color_hex`
- `sort_order`
- `is_system`
- `deleted_at`
- `created_at`
- `updated_at`

Rules:

- Four default system tags are provided: `고정`, `건강`, `업무`, `휴식`.
- Users can create, edit, and delete their own tags from the Tags management page.
- System tags are visible to every user and cannot be edited from the user-facing tag page.
- User-created tags must choose one unused color from the shared 15-color palette.
- Actual events reference tags through `calendar_events.calendar_tag_id`.
- Deleting a user tag soft-deletes the tag and clears it from that user's active calendar events.

### `calendar_date_meta`

The date metadata table used for holiday-aware coloring and future statistics.

Important columns:

- `id`
- `calendar_date`
- `locale_code`
- `date_type`
- `holiday_name`
- `is_holiday`
- `is_substitute_holiday`
- `created_at`
- `updated_at`

Rules:

- `UNIQUE(locale_code, calendar_date)` keeps one metadata record per locale/date.
- Weekends can be calculated from the date, but holidays and substitute holidays should be stored here.

## Current Routes

- `GET /calendar`: render the selected day. Accepts optional `date=YYYY-MM-DD`.
- `POST /calendar/day-plan`: copy or replace the selected Plan template group for the day.
- `POST /calendar/plan-item`: add a block to the date-specific daily Plan.
- `POST /calendar/plan-item/update`: update a daily Plan block and optionally synchronize the linked actual event.
- `POST /calendar/plan-item/delete`: soft-delete a daily Plan block and detach its actual event.
- `POST /calendar/event`: create an actual event.
- `POST /calendar/event/update`: update an actual event's title, tag, plan link, or memo.
- `POST /calendar/event/delete`: soft-delete an actual event.
- `GET /tags`: render tag management.
- `POST /tags`: create a user tag.
- `POST /tags/update`: update a user tag.
- `POST /tags/delete`: soft-delete a user tag and clear existing event links.
- `POST /tags/system-toggle`: enable or disable a fixed system tag for the signed-in user.
- `POST /routine/toggle`: set a routine execution state and redirect back to the selected calendar date when submitted from Calendar.
- `POST /memo`: save a standalone quick memo and return to the selected Calendar date.

All POST routes require CSRF verification.

## Current UI Behavior

- The header keeps previous date, selected date, next date, date picker, and Retrospect on one vertically centered row. Navigation controls use 44px touch targets, and the whole date-picker button opens the native picker.
- Fixed tag management shows the tag identity and per-user enabled switch without exposing its fixed color. Disabled fixed tags are hidden from new event registration, while existing event tags and colors remain intact.
- Per-user fixed-tag visibility is stored in `calendar_tag_preferences`; existing deployments must apply `sql/migration.calendar_tag_preferences.mysql.sql` or its SQLite counterpart first.
- The Retrospect button opens a local preview popup with the latest submitted report on or before the selected calendar date.
- The Retrospect button is disabled when there is no submitted report yet.
- The Plan schedule and Actual schedule tabs share the time grid. Range selection opens the editor for the active tab.
- The bottom-right `+` opens a bottom sheet containing Memo and Plan-link actions, three Plan items, and three Routine items. More items expand in the same scrollable sheet.
- Today without actual records gives the floating action a slow breathing treatment; `prefers-reduced-motion` disables the animation.
- Plan picker labels show user-facing plan names only. Internal plan version numbers are not shown.
- The former collapsed Plan summary below the date header is removed so the Calendar grid retains visual priority.
- When no daily Plan exists, Calendar shows an entry toast explaining direct Plan-tab entry and template connection.
- Plan importance and time ranges appear in the floating bottom sheet rather than a header reminder panel.
- Planned blocks already linked to actual events are rendered with muted gray treatment and sorted below unlinked blocks. Strikethrough is intentionally not used.
- Planned block titles are highlighted by duration: 30 minutes or less gets a blue marker style, and 60 minutes or more gets a red marker style.
- The selected date's `daily_plan_items` are shown and edited in Plan schedule mode.
- Actual Calendar blocks and Retrospect event cards share the same tag-background and contrast-text rule in both themes.
- Background plan events show a small neutral `A/B/C/D` badge before the plan title. The badge intentionally avoids importance coloring inside the calendar grid so future tag colors can own block backgrounds.
- Calendar and Plan add/edit pages share `public/assets/js/components/time-grid-selection.js` for range selection.
- On touch devices, the grid keeps native vertical scrolling as the default. A stationary long press activates selection and subsequent dragging extends the selected range.
- Moving before the long-press delay remains a native scroll. A short tap has no schedule-creation behavior.
- Once a touch long press is confirmed, the shared grid controller asks the Android bridge to disable native pull-to-refresh until the matching pointer ends or the selection is otherwise cancelled. Normal browsers continue without this native enhancement.
- Mouse and pen input keep immediate drag-to-select behavior. Range selection can start on top of a background plan event because the script resolves the underlying grid cell.
- Touching or clicking an actual event opens its edit bottom sheet; range selection does not start from an actual-event control.
- The actual-event bottom sheet asks for a title, an optional common tag, an optional daily Plan-item link, and routines that should be completed with the event.
- Saving a new event marks the selected active routines complete for the same calendar date, so Calendar and Routine do not require separate completion actions.
- The quick menu creates entries without a time range. Untimed entries cannot connect to a plan block and are excluded from time-duration Retrospect metrics.
- Opening the untimed schedule action also lists existing untimed entries so they can be edited or deleted without placing them on the grid.
- After selecting a time range, the event sheet offers `새 일정 입력` and, when available, `시간 미정 일정 (n)` tabs. Selecting an untimed entry converts the same record to a timed event instead of copying it.
- Calendar bottom sheets keep their header and close button reachable while long content scrolls internally. Opening an event sheet focuses its title input during the initiating user gesture, and visual viewport changes keep the focused input visible.
- Actual event blocks use the selected tag's `color_hex` as the block background color.
- The Calendar page does not show a global status legend. Actual event colors represent tag categories, not execution status, and the current time is indicated directly in the grid cell.
- The event edit bottom sheet supports title, tag, plan link, memo, and delete actions.
- Already linked daily Plan items are disabled in the actual-event bottom sheet.
- Clicking an actual event asks for confirmation and then soft-deletes it.
- Routine items are displayed and toggled directly inside the `+` bottom sheet.
- Routine state changes submit to `POST /routine/toggle` and persist to `routine_logs`.
- If a routine duration change moves the selected date outside the routine's active period, that routine is excluded from the Calendar floating sheet for that date. Existing routine logs are preserved but ignored outside the active period.
- If the selected date is today, the current 10-minute cell is highlighted using the app timezone (`Asia/Seoul`) and moves in place at each 10-minute boundary without reloading the page. Returning to a backgrounded Calendar page refreshes the highlight immediately.
- Calendar success flash messages are rendered as hidden `data-toast-message` triggers and shown only through the shared toast UI.
- Calendar notification synchronization includes only Plan reminders whose five-minute-prior fire time is still in the future. Entering Calendar replaces that date's local Android reservations and is not itself a notification trigger.

## Retrospect Integration

Retrospect persistence is handled by the Retrospect feature. Calendar remains the source of actual events and selected plan data, while `/retrospect` owns report snapshots, draft text, publishing, and history. Calendar reads the latest submitted report preview on or before the selected date and does not edit retrospect data.

## Existing Database Migration

- MySQL: run `sql/migration.daily_plans.mysql.sql` once after the existing Plan and Calendar tables are present.
- SQLite: `app/Core/Database.php` adds `daily_plan_item_id`, creates daily Plan tables, and backfills existing selected Plans automatically.

- MySQL: run `sql/migration.add_calendar_schedule_type.mysql.sql` once.
- MySQL: also run `sql/migration.calendar_tag_preferences.mysql.sql` once before enabling per-user fixed-tag visibility.
- SQLite: `app/Core/Database.php` applies `sql/migration.add_calendar_schedule_type.sqlite.sql` when an existing `calendar_events` table does not yet have `schedule_type`.
- See `docs/database-migrations.md` for the recommended deployment order when applying this together with other current migrations.

## Manual Test Checklist

- Connect a Plan template, edit the template from Plan, and confirm the already-copied Calendar day does not change.
- Open a date with no Plan, switch to Plan schedule, add two blocks, and confirm one `[YYYY-MM-DD]의 계획` receives both items.
- Edit and delete a daily Plan item. For a linked item, verify current-only, synchronize, and detach choices preserve the expected actual event.
- Switch between Plan schedule and Actual schedule and confirm only the selected layer is visible while the date and scroll context remain.
- Open `+` and confirm Memo/Plan-link actions, three Plan rows, and three Routine rows appear. Expand longer lists inside the same scrollable bottom sheet.
- Publish or republish Retrospect and confirm copied Plan items, linked/unlinked actual events, Routine state, standalone Memos, and KPT remain available as the submitted snapshot.

- On touch, vertically swipe the time grid and confirm the page scrolls without creating a selection.
- Short-tap an empty time cell and confirm no event sheet opens.
- Long-press an empty time cell, drag across several cells, and confirm the timed event sheet opens with the selected range.
- In Android WebView at the top of the page, long-press a time cell and drag downward. Confirm range selection continues without showing or triggering pull-to-refresh, then confirm pull-to-refresh works again after release.
- Repeat the previous test while cancelling the pointer, leaving the page, and sending the app to the background; native pull-to-refresh must be restored each time.
- In Actual schedule mode, use `시간 미정 추가`, then select a time range and convert that same event from the `시간 미정 일정 (n)` tab.
- Create a timed event with a Routine selected and confirm the event and the same-date Routine completion are both saved once.
- Open and close Memo and Plan connection from `+`; expand Plan and Routine lists without leaving the floating bottom sheet.
- Use the date picker to move to a specific date and confirm previous/next date navigation still works.
- Tap the full date-picker control, not only the glyph, and confirm all header controls remain vertically centered on one row.
- Disable a fixed tag and confirm it has no color swatch in tag management and disappears from new event choices.
- Edit an existing event that used the disabled fixed tag and confirm its stored tag can remain selected and saved.
- Keep today's Calendar page open across a 10-minute boundary and confirm the current-time highlight moves to the next cell without a page reload. Confirm it refreshes immediately after returning from the background.
- Enter Calendar after several Plan blocks have already passed and confirm no notification is displayed immediately. Verify only the remaining blocks are reserved for five minutes before their start times.
