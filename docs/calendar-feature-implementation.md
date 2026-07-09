# Calendar Feature Implementation

## Scope

Calendar is the actual execution screen. It stores actual schedule events by user and date, can connect each actual event to one planned block, and provides a lightweight entry point for checking active routines.

The current implementation supports:

- date navigation by previous/next day
- one selected plan group per calendar day
- many actual events per calendar day
- optional one-to-one actual event to plan template connection
- default calendar tags and user-created tags backed by a shared organic 15-color palette
- soft deletion of actual events
- current-time cell highlighting when the selected date is today
- active Routine popup UI backed by routine definitions and daily logs
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
- `plan_group_id` is nullable and represents the single planned schedule group used as the day's background plan.
- Changing `plan_group_id` clears existing actual-event `plan_template_id` links for that day so stale plan links do not remain.

### `calendar_events`

The actual schedule data entered on the calendar.

Important columns:

- `id`
- `user_id`
- `calendar_day_id`
- `title`
- `start_index`
- `end_index`
- `plan_template_id`
- `calendar_tag_id`
- `memo`
- `deleted_at`
- `created_at`
- `updated_at`

Rules:

- Actual events are stored with the same 10-minute index model as plan blocks.
- `start_index` is inclusive and `end_index` is exclusive.
- `plan_template_id` is nullable. If present, the actual event is linked to one planned block.
- `calendar_tag_id` is nullable. If present, the actual event uses the common tag's color for its calendar block.
- `memo` stores optional event notes and is editable from the event bottom sheet.
- The UI only offers unused plan templates for the selected day, so a plan block cannot be selected twice on the same day.
- Active actual events cannot overlap on the same calendar day.
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
- `POST /calendar/day-plan`: save the selected plan group for the day.
- `POST /calendar/event`: create an actual event.
- `POST /calendar/event/update`: update an actual event's title, tag, plan link, or memo.
- `POST /calendar/event/delete`: soft-delete an actual event.
- `GET /tags`: render tag management.
- `POST /tags`: create a user tag.
- `POST /tags/update`: update a user tag.
- `POST /tags/delete`: soft-delete a user tag and clear existing event links.
- `POST /routine/toggle`: set a routine execution state and redirect back to the selected calendar date when submitted from Calendar.

All POST routes require CSRF verification.

## Current UI Behavior

- The header shows previous/next date buttons and the selected date.
- The Retrospect button opens a local preview popup with the latest submitted report on or before the selected calendar date.
- The Retrospect button is disabled when there is no submitted report yet.
- The lower-left plan settings button opens a bottom sheet for selecting one plan group for the day.
- Plan picker labels show user-facing plan names only. Internal plan version numbers are not shown.
- When a plan group is selected, a collapsed "today's plan" reminder panel is shown directly below the date header by default.
- When no plan group is selected, the "today's plan" reminder panel is hidden.
- When no plan group is selected, Calendar shows an entry toast asking the user to set a baseline plan.
- The reminder panel lists the selected plan group's blocks with Eisenhower importance badges and time ranges.
- Planned blocks already linked to actual events are rendered with muted gray treatment and sorted below unlinked blocks. Strikethrough is intentionally not used.
- Planned block titles are highlighted by duration: 30 minutes or less gets a blue marker style, and 60 minutes or more gets a red marker style.
- The selected plan group's blocks are shown as background plan events.
- Background plan events show a small neutral `A/B/C/D` badge before the plan title. The badge intentionally avoids importance coloring inside the calendar grid so future tag colors can own block backgrounds.
- Dragging the day grid opens a page-level bottom sheet for actual schedule entry. Dragging can start on top of a background plan event because the script resolves the underlying grid cell.
- On top of an actual event, a short click opens the event edit bottom sheet, while dragging starts a new selection for actual schedule entry.
- The bottom sheet asks for an actual event title, an optional common tag, and an optional plan template link.
- Actual event blocks use the selected tag's `color_hex` as the block background color.
- The Calendar page does not show a global status legend. Actual event colors represent tag categories, not execution status, and the current time is indicated directly in the grid cell.
- The event edit bottom sheet supports title, tag, plan link, memo, and delete actions.
- Already linked plan templates are disabled in the bottom sheet.
- Clicking an actual event asks for confirmation and then soft-deletes it.
- The Routine floating button opens a popup containing routines active on the selected date.
- Routine state changes submit to `POST /routine/toggle` and persist to `routine_logs`.
- If a routine duration change moves the selected date outside the routine's active period, that routine is excluded from the Calendar routine popup for that date. Existing routine logs are preserved but ignored outside the active period.
- If the selected date is today, the current 10-minute cell is highlighted using the app timezone (`Asia/Seoul`).
- Calendar success flash messages are rendered as hidden `data-toast-message` triggers and shown only through the shared toast UI.

## Retrospect Integration

Retrospect persistence is handled by the Retrospect feature. Calendar remains the source of actual events and selected plan data, while `/retrospect` owns report snapshots, draft text, publishing, and history. Calendar reads the latest submitted report preview on or before the selected date and does not edit retrospect data.
