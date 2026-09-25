# Calendar Feature Implementation

## Scope

Calendar is the date-specific planning and actual execution screen. It copies templates into a mutable daily Plan, stores actual events, and connects actual events to copied Plan items.

The current implementation supports:

- date navigation by previous/next day and direct date lookup
- one date-specific daily Plan per calendar day, optionally copied from a Plan template group
- direct add/edit/delete of daily Plan items
- many actual events per calendar day
- timed actual-event creation, with legacy untimed records retained only for placement into a timed range
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
- Calendar-created daily items may keep both `start_index` and `end_index` null until the user assigns a time. Template copies remain timed, and partially-null ranges are invalid.
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
- `schedule_type` remains `timed` or `unscheduled` for compatibility. New Actual-event requests must be `timed`; legacy untimed entries store null `start_index`, `end_index`, and `plan_template_id` values until they are placed into a timed range.
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

`POST /tags` also accepts an XHR/JSON request from the Calendar event sheet. It returns the created tag (`id`, `name`, and `colorHex`) so both open-form tag lists can update without navigation; the regular Tags-page redirect remains the non-JavaScript behavior.

## Current UI Behavior

- The Calendar page uses the shared feature-page width: up to 680px by default, 920px from 768px viewports, and 1080px from 1024px viewports. The floating action follows the responsive container's right edge instead of the former Calendar-only 430px boundary.
- The header keeps the selected date, previous day, next day, conditional Today, and Retrospect on one vertically centered row in that order. Weekdays use Korean one-character labels. The Today action is omitted when today is already selected; other controls use 32px visual pills inside 40px touch targets, and tapping the selected date opens the native picker.
- Swiping or pointer-dragging the date header at least 48px horizontally changes the selected date by one day: right for the previous date and left for the next date. Pointer Events cover touch, mouse, and pen with a touch fallback; a gesture is ignored when vertical movement is equal or greater, preserving page scroll and ordinary control taps.
- Pointer drag tracking does not call `setPointerCapture` on the whole date header, so its child date picker, navigation links, and latest-published-Retrospect button receive normal clicks.
- Fixed tag management shows the tag identity and per-user enabled switch without exposing its fixed color. Disabled fixed tags are hidden from new event registration, while existing event tags and colors remain intact.
- Per-user fixed-tag visibility is stored in `calendar_tag_preferences`; existing deployments must apply `sql/migration.calendar_tag_preferences.mysql.sql` or its SQLite counterpart first.
- The Retrospect button opens a local preview popup with the user's latest submitted report regardless of the selected calendar date.
- The Retrospect button is disabled when there is no submitted report yet.
- The Plan schedule and Actual schedule tabs share the time grid. Range selection opens the editor for the active tab.
- The grid no longer shows long-press/drag instruction text or the `시간 미정 추가` button. Existing range selection remains unchanged.
- The bottom-right `+` opens a bottom sheet containing Actual-event creation, unified Plan creation, Memo, and Plan-link actions, three Plan items, and three Routine items. `실제 일정 추가` opens the existing event sheet with required editable 10-minute start/end selectors; today defaults near the current time and other dates default to 09:00–10:00. More items expand in the same scrollable sheet.
- `계획 추가` opens one Plan form with `시간 정하기` initially off. Enabling it reveals required start/end selectors. Selecting a range in the Plan tab opens the same form with the toggle on and the selected range prefilled, while the empty-state and `+` entry points start as time-unspecified.
- The same form can copy a saved Plan-block template. It fills title, importance, goal, and—when time is enabled—calculates the end from the selected start and default duration. An overflow beyond today's range requires explicit confirmation before the later portion is discarded and that date's copy ends at `23:50`.
- Plan create and edit forms expose Eisenhower A/B/C/D importance as four horizontal radio-style buttons.
- Today without actual records gives the floating action a slow breathing treatment; `prefers-reduced-motion` disables the animation.
- Plan picker labels show user-facing plan names only. Internal plan version numbers are not shown.
- The former collapsed Plan summary below the date header is removed so the Calendar grid retains visual priority.
- A tab with no blocks shows a compact first-action card. The Plan card opens direct Plan entry and offers template connection only when a template exists; the Actual card opens timed event entry. Existing data suppresses the corresponding card.
- Plan importance and time ranges appear in the floating bottom sheet rather than a header reminder panel.
- Time-unspecified Plan items display `시간 미정` only in the floating Today Plan list. Selecting a row opens Plan edit; changing `시간 설정` to `시간 지정` and choosing start/end values turns it into a normal grid block. No auxiliary Plan list is rendered around the grid.
- Planned blocks already linked to actual events are rendered with muted gray treatment and sorted below unlinked blocks. Strikethrough is intentionally not used.
- Planned block titles are highlighted by duration: 30 minutes or less gets a blue marker style, and 60 minutes or more gets a red marker style.
- The selected date's `daily_plan_items` are shown and edited in Plan schedule mode. Calendar always initializes in Actual schedule mode on page entry; the previous tab is not restored from browser storage.
- Actual schedule mode keeps the selected date's Plan blocks visible underneath actual events as a non-interactive background reference. The server-rendered initial state already applies this background treatment before page JavaScript runs. Actual-event controls remain above the Plan layer, and background Plan blocks cannot open the Plan editor.
- Plan schedule mode renders daily Plan items as solid schedule blocks with their neutral `A/B/C/D` priority badge. Switching to Actual mode changes the same blocks to muted, dashed background references rather than rendering a second data source.
- Actual Calendar blocks and Retrospect event cards share the same tag-background and contrast-text rule in both themes.
- Background plan events show a small neutral `A/B/C/D` badge before the plan title. The badge intentionally avoids importance coloring inside the calendar grid so future tag colors can own block backgrounds.
- Calendar and Plan add/edit pages share `public/assets/js/components/time-grid-selection.js` for range selection.
- On touch devices, the grid keeps native vertical scrolling as the default. A stationary long press activates selection and subsequent dragging extends the selected range.
- Moving before the long-press delay remains a native scroll. A short tap has no schedule-creation behavior.
- Once a touch long press is confirmed, the shared grid controller asks the Android bridge to disable native pull-to-refresh until the matching pointer ends or the selection is otherwise cancelled. Normal browsers continue without this native enhancement.
- Mouse and pen input keep immediate drag-to-select behavior. Range selection can start on top of a background plan event because the script resolves the underlying grid cell.
- Touching or clicking an actual event opens its edit bottom sheet; range selection does not start from an actual-event control.
- A completed mouse, pen, or touch range that overlaps an existing block in the active tab is rejected before an editor opens and reports the conflict through Toast. Actual mode ignores background Plan blocks for this check. Server validation repeats the actual-event overlap check before persistence.
- The actual-event bottom sheet asks for a title, an optional common tag, an optional daily Plan-item link, and routines that should be completed with the event.
- Start and end selectors are the only time-range display in the actual-event sheet; a second text summary is intentionally omitted.
- The Plan-link field is not rendered for creation when no unused daily Plan item exists. During edit it appears only when the event has a current link or another selectable Plan item.
- Timed and time-unspecified Plan items are both eligible link choices. Selecting a Plan during Actual creation, or changing an Actual event to another Plan, replaces the Actual title with the selected Plan title. Editing an already-linked Actual title changes only the Actual record; unlinking preserves that title. The former `실제 일정만 변경 / 계획 일정명도 함께 변경 / 연결 해제` selector is removed.
- `+ 태그 추가` creates a personal tag inside the create/edit event sheet and selects it without discarding the current title, time, memo, or links. Editing and deleting tags remain in Schedule Tag Management.
- Saving a new event marks the selected active routines complete for the same calendar date, so Calendar and Routine do not require separate completion actions.
- New untimed-entry creation is no longer exposed from the Calendar grid. Existing untimed entries remain available from the timed event sheet and can still be placed into a selected time range.
- After selecting a time range, the event sheet offers `새 일정 입력` and, when available, `시간 미정 일정 (n)` tabs. Selecting an untimed entry converts the same record to a timed event instead of copying it.
- Calendar bottom sheets keep their header and close button reachable while long content scrolls internally. Grid range selection focuses the event title, while `실제 일정 추가` focuses the start-time selector; visual viewport changes keep the focused control visible.
- Actual event blocks use the selected tag's `color_hex` as the block background color.
- On desktop hover, an actual-event tooltip includes the event title and a memo preview limited to 80 characters. Touch interaction continues to open the edit sheet without showing the hover tooltip.
- The Calendar page does not show a global status legend. Actual event colors represent tag categories, not execution status, and the current time is indicated directly in the grid cell.
- The event edit bottom sheet supports title, tag, plan link, memo, and delete actions.
- Already linked daily Plan items are disabled in the actual-event bottom sheet.
- Clicking an actual event asks for confirmation and then soft-deletes it.
- Routine items are displayed and toggled directly inside the `+` bottom sheet.
- Routine state changes submit to `POST /routine/toggle` and persist to `routine_logs`.
- If a routine duration change moves the selected date outside the routine's active period, that routine is excluded from the Calendar floating sheet for that date. Existing routine logs are preserved but ignored outside the active period.
- If the selected date is today, the current 10-minute cell is highlighted using the app timezone (`Asia/Seoul`) and moves in place at each 10-minute boundary without reloading the page. Returning to a backgrounded Calendar page refreshes the highlight immediately.
- Calendar success and validation flash messages are rendered as hidden `data-toast-message` triggers and shown only through the shared Toast UI, preventing error rows from shifting the grid.
- Calendar page-specific CSS and JavaScript URLs include their current file modification time. This cache-busting value keeps browser and Android WebView clients aligned with deployed Calendar behavior without disabling caching for unrelated assets.
- Calendar notification synchronization includes only Plan reminders whose five-minute-prior fire time is still in the future. Entering Calendar replaces that date's local Android reservations and is not itself a notification trigger.
- Today's shared app header shows at most one contextual action, chosen server-side in this order: enabled morning/evening Retrospect window, current unlinked timed Plan, next unlinked timed Plan, no Plan, then no timed Actual record. Retrospect windows begin at the configured Settings reminder time and last two hours. Plan guidance opens timed Actual entry with the Plan link, title, and range prefilled; empty Plan/Actual guidance opens the matching form. Non-today dates do not show this guidance.

## Retrospect Integration

Retrospect persistence is handled by the Retrospect feature. Calendar remains the source of actual events and selected plan data, while `/retrospect` owns report snapshots, draft text, publishing, and history. Calendar reads the latest submitted report preview on or before the selected date and does not edit retrospect data.

## Existing Database Migration

- MySQL: run `sql/migration.daily_plans.mysql.sql` once after the existing Plan and Calendar tables are present.
- SQLite: `app/Core/Database.php` adds `daily_plan_item_id`, creates daily Plan tables, and backfills existing selected Plans automatically.

- MySQL: run `sql/migration.add_calendar_schedule_type.mysql.sql` once.
- MySQL: also run `sql/migration.calendar_tag_preferences.mysql.sql` once before enabling per-user fixed-tag visibility.
- MySQL/MariaDB: run `sql/migration.daily_plan_untimed.mysql.sql` once to make daily Plan and Retrospect Plan-snapshot time ranges nullable.
- SQLite: `app/Core/Database.php` applies `sql/migration.add_calendar_schedule_type.sqlite.sql` when an existing `calendar_events` table does not yet have `schedule_type`.
- SQLite: application bootstrap applies the daily Plan and Retrospect Plan-snapshot untimed migrations when their existing `start_index` columns are still non-nullable.
- See `docs/database-migrations.md` for the recommended deployment order when applying this together with other current migrations.

## Manual Test Checklist

- Compare Calendar with another feature menu at mobile, tablet, and desktop widths. Confirm both content containers use the same responsive width and the Calendar `+` action stays aligned with the container's right edge.
- Connect a Plan template, edit the template from Plan, and confirm the already-copied Calendar day does not change.
- Open a date with no Plan, switch to Plan schedule, add two blocks, and confirm one `[YYYY-MM-DD]의 계획` receives both items.
- Edit and delete a daily Plan item. With JavaScript enabled, confirm the deletion request returns JSON before the detail sheet closes and Calendar reloads; for a linked item, verify the actual event remains and its Plan link is cleared. Repeat without JavaScript to verify the POST redirect fallback.
- Leave Calendar while the Plan tab is selected, enter Calendar again, and confirm the Actual tab is selected by default.
- Switch between Plan schedule and Actual schedule and confirm the date and scroll context remain. In Actual mode, verify Plan blocks stay visible as a non-interactive background reference while actual events remain clickable above them.
- Open `+` and confirm Actual-event, Memo, and Plan-link actions, three Plan rows, and three Routine rows appear. Expand longer lists inside the same scrollable bottom sheet.
- Open `계획 추가` from `+` and confirm the form starts with `시간 정하기` off. Save it and confirm it appears only in Today Plan with the `시간 미정` label. Open the same action again, enable time, save a non-overlapping range, and confirm it becomes a Plan block and Actual-tab background block. Drag a Plan-grid range and confirm the same form opens with time enabled and the range prefilled.
- Select a Plan-block template in Calendar and confirm its title, importance, goal, and duration are copied. Place it near midnight, cancel the overflow prompt once, then confirm `오늘까지만 등록` produces an end time of `23:50` without changing the library duration.
- Publish or republish Retrospect and confirm copied Plan items, linked/unlinked actual events, Routine state, standalone Memos, and KPT remain available as the submitted snapshot.

- On touch, vertically swipe the time grid and confirm the page scrolls without creating a selection.
- Short-tap an empty time cell and confirm no event sheet opens.
- Long-press an empty time cell, drag across several cells, and confirm the timed event sheet opens with the selected range.
- With an existing actual event, drag from an adjacent empty cell across that event and confirm no input sheet opens and an overlap Toast appears. Repeat in the Plan tab with an existing Plan block. In Actual mode, drag across only a background Plan block and confirm event entry is still allowed.
- In Android WebView at the top of the page, long-press a time cell and drag downward. Confirm range selection continues without showing or triggering pull-to-refresh, then confirm pull-to-refresh works again after release.
- Repeat the previous test while cancelling the pointer, leaving the page, and sending the app to the background; native pull-to-refresh must be restored each time.
- Open `+`, select `실제 일정 추가`, change the 10-minute start/end values, and confirm the event is rendered at the selected range. Verify an end time at or before the start time cannot be saved.
- Submit an Actual-event request with `schedule_type=unscheduled` and confirm server validation rejects it; existing legacy untimed records must still be placeable into a timed range.
- Confirm the actual-event sheet does not repeat a `00:40 ~ 01:00`-style range below the selectors, and that server-side validation failures appear as Toast without moving the grid.
- On a date with no selectable Plan item, confirm the Plan-link field is absent. Add an unlinked daily Plan item, reload, and confirm it becomes available.
- Link a time-unspecified Plan while creating an Actual event and confirm the Actual title immediately becomes the Plan title. Edit the linked Actual title and confirm the Plan title does not change. Change the link to a different Plan and confirm the new Plan title is applied; select `연결하지 않음` and confirm the current Actual title remains.
- While entering an event, type a title and memo, create a tag inline, and confirm the form values remain, the tag is selected in both create/edit lists, and its used palette color is no longer offered. Confirm tag edit/delete remains available from Schedule Tag Management.
- Hover an actual block with a long memo on desktop and confirm its tooltip shows the title plus a memo preview capped at 80 characters; confirm no tooltip obstructs touch editing.
- Open dates with empty Plan and Actual tabs and confirm each first-action card opens the correct input. Add a block and confirm the corresponding card no longer renders after reload.
- Confirm neither Calendar tab shows a long-press/drag instruction or `시간 미정 추가` button. If existing untimed entries are present, select one from the timed event sheet and confirm the same record is placed into the chosen range.
- Create a timed event with a Routine selected and confirm the event and the same-date Routine completion are both saved once.
- Open and close Memo and Plan connection from `+`; expand Plan and Routine lists without leaving the floating bottom sheet.
- Tap the displayed date to move to a specific date and confirm previous/next navigation still works. Confirm Today appears only on non-today dates and returns to today.
- Confirm the date, previous, next, conditional Today, and Retrospect controls remain vertically centered on one row with 32px visuals and 40px touch targets.
- Swipe on touch and drag with a mouse or pen across the date header to move one day backward and forward. Verify short gestures, vertical scrolling, and normal header-control taps do not trigger date navigation.
- Tap the date picker, previous, next, conditional Today, and latest-published-Retrospect controls individually and confirm each works without being swallowed by header drag tracking. Confirm the Retrospect control remains disabled when no published preview exists.
- Disable a fixed tag and confirm it has no color swatch in tag management and disappears from new event choices.
- Edit an existing event that used the disabled fixed tag and confirm its stored tag can remain selected and saved.
- Keep today's Calendar page open across a 10-minute boundary and confirm the current-time highlight moves to the next cell without a page reload. Confirm it refreshes immediately after returning from the background.
- Enter Calendar after several Plan blocks have already passed and confirm no notification is displayed immediately. Verify only the remaining blocks are reserved for five minutes before their start times.
- On today's Calendar, verify the shared header stays one line and shows only the highest-priority applicable action. Test enabled morning/evening Retrospect times, a current Plan, a next Plan, no Plan, and no timed Actual record; confirm each action opens the expected date or prefilled form. Confirm the contextual action is absent on another date.
