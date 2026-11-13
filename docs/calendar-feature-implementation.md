# Calendar Feature Implementation

## Actual time/tag layout and lightweight records

Create and edit reuse `_actual-time-range.php` with the existing select IDs/names and 10-minute values. Two equal-width labeled controls retain native selection and add non-interactive clock SVGs. The `data-actual-duration` output updates through `syncActualFormSummary()` on initialization, range changes and editor population; it shows elapsed grid minutes or `시간 확인` for invalid ranges. End index 144 remains 24:00.

Each tag legend owns the `+ 새 태그` action; `_quick-tag.php` now supplies only its inline editor. Toggle lookup uses the owning tag group, and new tag insertion anchors on the direct-child editor rather than the nested legend action. Existing validation, selection, palette exhaustion and cross-form updates remain intact.

함께 기록하기 is a set of transparent disclosure/selection rows with horizontal separators. Life Record, Memo, Routine and Execution Energy behavior and summary values remain. Linked Plan retains a soft relationship surface. Actual deletion sits after the records section, inside the scrolling body, with its existing 1.5-second protection. Only save remains in the fixed action area. Shared colors, shell, close control and chevrons remain; no decorative drag handle was added.

## Plan block reminder editing

### Plan editor layout

Current refinements: Plan details show the viewed date and full start/end clock range. Reminder summaries and expanded settings show server-calculated trigger date/time alongside the selected lead, including the previous date across midnight. Lazy clock lookup covers newly created/moved blocks; resize/move invalidates stale timing metadata. User IANA timezone arithmetic matches notification scheduling, without parsing wall-clock labels in the device timezone.

Both Plan and To-do state sheets now use one six-option row. A linked To-do's progress/postponed selection keeps the editor open for its optional reason/next-action note. Management's Actual-detach action is a muted, transparent secondary hold action, while deletion retains the standard ghost hold button; both still require 1.5 seconds.

The current shell/control contract is `docs/design-system-contract.md`: Calendar Plan/Actual create/edit use the To-do Add shell; reminder save is full width; reminder choices reuse state-button styling; management uses standard ghost hold buttons; disclosures share the Actual memo/life-record border chevron. Older row-button descriptions below are superseded by this contract. Fixed Actual actions and 1.5-second protection remain unchanged.

The Plan-edit sheet retains shared surface colors, primary selection colors, type tokens and close controls. Its order is header, prominent Plan title, inline importance plus Actual count/duration, single-row six-option state choices, reminder disclosure, then management disclosure. States are ordered pending / in progress / completed, postponed / delegated / cancelled; their existing symbols remain unchanged. The immediate-save note sits beside the state heading instead of below the choices. State is shown by the selected button rather than repeated in the metadata.

Reminder and management rows use restrained icon tiles and trailing chevrons. Reminder state is right-aligned. Both disclosures reset closed when a block opens. Management holds independent-template importance choices where applicable, Actual detachment only when linked, and Plan deletion as a standard ghost hold button. Existing 1.5-second hold protections remain; collapsing management cancels a hold. There is no decorative drag handle or persistent focus border. The design changes neither persistence nor endpoints.

Plan details include the shared `components/plan-reminder-fields.php` editor and `components/plan-reminder.js`. Each block owns an opt-in flag (default off) and a 0/5/10/15/30/60-minute lead (default 5). `POST /calendar/plan-block/reminder` validates ownership, CSRF and choices through service/repository flow. Save/delete replaces only the stored date's reminders; move/resize retains the setting and recalculates fireAt. Source To-do linkage does not disable this editor. See `notification-feature-implementation.md` and `database-migrations.md`.

Plans backed by a To-do display its state and importance, including its last values after source deletion. The sheet retains an importance/Actual summary and exposes immediate-save state choices through the existing `/todo/status` endpoint. Completion needs no Actual link; all sibling blocks and the placement source refresh together. Deleted sources retain their final state with disabled choices. Importance remains edited in To-do. On mobile the 50:50 timetable hides Plan importance/state icons and Actual tag dots; single-timetable views retain them. Template-only blocks retain independent state/importance and their existing Actual-link requirement for completion.

The state choices and timetable indicators display `V 완료`, `X 취소`, `→ 연기`, `· 진행 중`, and `○ 위임`. The existing `postponed` and `external` values are retained and displayed as 연기 and 위임, including previously saved records; the unspecific initial state remains 미정.

For template-only blocks, the Plan block editor retains the six immediate-save state choices and independent importance. Completion requires a linked Actual under the existing block rules.

The Plan editor has no general save button. State changes save immediately; reminders have their own save action. Movement/resize remains on the timetable. The `실제 일정 연결 해제` button appears only for linked blocks and detaches all without changing To-do state/importance. Detachment/deletion closes on success and keeps Actual records. Failures retain the editor.

## Compact reminder and gesture update (2026-11-10)

- Reminder settings start collapsed as a summary toggle with the current state. When master or Plan notifications are off, expansion shows their separate states and `/settings#notificationSettings`. When both are on, expansion shows pressed-state buttons for off/start/5/10/15/30/60 minutes and the save button. Schedule and To-do share this editor; hidden To-do templates refresh when opened.
- Both Plan and Actual resize handles use `calendar-resize-hold.js`: a stationary 450 ms touch or primary mouse hold activates resize. Movement of 8 px before activation, native scrolling, release, cancellation, multiple touches, focus loss or page hiding cancels the pending hold. Touch keeps `pan-y`; only an activated resize prevents touchmove. A hold without a range change makes no save request. Existing overlap/ownership/CSRF checks remain.
- Timed Plan creation preserves the current timetable split and work mode, appends the source/block without automatic source selection and displays `계획과 할 일이 추가되었습니다.`. Untimed creation displays `할 일이 추가되었습니다.`. Explicit placement/painting buttons still select their workspaces.
- Plan-placement and tag-paint drawer titles use exactly the corresponding work-button SVGs, replacing decorative pseudo-icons.

Plan block deletion, Actual-link detachment and Actual-event deletion use delay buttons: each action starts only after a continuous 1.5-second hold, with a red progress fill. Early release shows the relevant instruction and makes no request. Pointer cancellation, movement outside, focus loss, closing the sheet or hiding the page cancels the hold; keyboard users can hold Space or Enter. A successful request retains each action's existing server behavior: Plan deletion and detachment preserve linked Actual records, while Actual-event deletion keeps its existing redirect.

Manual check: create one To-do with two Plan blocks, change its state and importance in To-do, then verify both blocks and their detail sheets show those values in Calendar. Complete without an Actual, then attach and detach an Actual and confirm the To-do state does not reset. Move and resize a block and delete another; check links and remaining records. Delete the source and verify the last inherited values remain. Separately check that a directly placed reusable template block retains its existing independent controls.

- Energy input modes occupy the same grid cell. The direct input is vertically centered in the slider's reserved height, and an always-reserved hint row explains the 5-unit, 5–100 range in direct mode. The inactive slider retains its natural height without interaction, keeping mode transitions stable and allowing validation messages to expand.
- Switching from an invalid direct-entry draft to the slider discards the draft and restores the value from before direct entry (−25 for a new event). Valid off-tick values remain unchanged and the slider shows no selected tick until the user chooses one.

- Actual-event create and edit sheets group title and time in a borderless subtle surface, keep tags ungrouped, use compact 44px inputs and centered borderless additional-record cards, and retain the fixed header and save actions. 생활 기록, 메모 and 실행력 have centered label, status and chevron columns; 실행력 opens its compact input controls.
- Actual-sheet memo textarea width accounts for the disclosure card margins. Compact time selectors remain 44px high and align to opposite edges, and tags have extra vertical spacing. Calendar's energy component keeps UI state separate from the existing `execution_energy_type` and amount fields: creation initially shows −25, choosing 0 serializes to `none`, and a direction without a value blocks submission. Direct-input mode is on the left, direction controls on the right, and the sign shares one field container with the direct input. Increase uses green and decrease red, with small visual buttons that retain 44px targets. Slider values are 0/25/50/75/100; custom values use 5-unit increments through 100. A pre-existing value above 100 is accepted only if it and its direction remain unchanged.

- Empty Plan and Actual grid touch input distinguishes scrolling from entry: movement of 8px before a 360ms hold cancels the pending selection and allows native scroll; after the hold, non-passive touch movement prevents scrolling and updates the snapped 10-minute selection. Releasing after a stationary long press opens a single-cell entry; a short tap does nothing. Mouse and pen behavior remains unchanged. Touch cancellation and Escape clear the preview.

- The mobile date navigation groups the compact date, weekday, visible picker caret and borderless 44px arrows into the swipe target without stretching the gap; the picker, navigation, search and menu share a vertically centered 44px row. Search and menu remain right-aligned outside the swipe target. The pointer end is tracked on the document so a swipe ending outside the group still navigates.
- The `+` menu opens the existing published-Retrospect popup. On the first visit to today's Calendar per user-local date, a CSRF-protected JSON claim atomically records the date in `user_preferences`; when that first visit has no Plan blocks and a report exists, a confirmation dialog asks whether to open the report. Declining does not reopen the prompt that day. Actual events do not block it. Past/future date visits do not consume the claim.

## Responsive workspace and scrolling

### Mobile work modes

- Mobile starts in `normal` mode with Plan and Actual at 50:50, excluding the central fixed-width time axis. Both grids retain their normal block edit, move, resize, and Actual creation controls.
- `plan-placement` shows only Plan and the source drawer. Closing or selecting the active control returns to the normal comparison view.
- `tag-paint` shows only Actual and the tag drawer. Selecting a palette item immediately applies it to each touched Actual block. Selecting `태그 지우기` immediately clears the tag. No undo history is retained.
- `POST /calendar/event/tag` accepts `_csrf_token`, `event_id`, and nullable `calendar_tag_id`, returns JSON, verifies event ownership and tag visibility through the model, and updates only `calendar_events.calendar_tag_id`.
- On mobile, 계획 배치 source cards wrap at their content width in importance and registration order and scroll internally at 30dvh. Underlined titles and accessible labels identify templates without extra visible type text; a title toggles selection, and a separate options button opens the existing editor for either kind. Template edits save title, importance and goal via JSON `POST /plan/block-template/update`, retain the stored duration, and update the Calendar source without reloading. Existing date-specific copies remain unchanged. The drawer links to `/plan?view=blocks`. Selecting a source allows a single pointer drag across the Plan timetable to choose a range; releasing outside the grid or without changing cells cancels it. Mobile hides the source drag handle; desktop retains it.
- If the selected date has no daily Plan items, a guide points to the compact-workspace 계획 배치 button until the user opens it. Timetable titles are non-interactive labels.
- On mobile, centered abbreviated timetable labels sit in the sticky date header and follow the visible timetable; the tag-paint choices wrap and scroll vertically rather than horizontally. The tag drawer header links to `/tags`, and plan block outlines are solid.
- A successful Plan placement clears its source selection so another placement requires a new selection. Failed saves preserve selection for retry, and a pending save blocks another placement request.
- Plan blocks store title, importance and Goal snapshots. Source edits or soft deletion leave blocks and their linked Actual events intact; in Calendar, To-do-backed blocks display the source's current or last state and importance, while template-only blocks retain independent values. Newly copied template sources are excluded from the placement list.
- Plan-link options list the daily item's placed block ranges chronologically and use the legacy item range or 시간 미정 only when no blocks exist. Plan-to-Actual copy writes the default 25-point consumption in the event transaction; failures roll back both writes, and overlapping duplicate copies are rejected. MySQL serializes these copies per calendar day; existing events are not backfilled.
- The Calendar 계획 추가 sheet submits the existing `POST /todo` form with `Accept: application/json`. `TodoController::store()` retains redirect behavior for ordinary form navigation and returns `201` JSON with the new item for this request. Calendar appends and selects the new source card without reloading.
- Tag painting uses the same bottom-sheet container as 계획 배치, rather than a palette above the timetable.
- `--calendar-row-height` and `--calendar-title-height` are defined on `.calendar-page` so both timetable panels and the sibling central axis resolve identical geometry.
- Mobile no longer uses 80:20 or 20:80 timetable columns. `plan-placement` uses a single full-width Plan panel and hides Actual plus the central axis; `tag-paint` does the inverse. `normal` retains the three-column 50:50 comparison layout.
- The Plan-block placement request (`POST /calendar/plan-block`) returns JSON for XMLHttpRequest/JSON clients. On success Calendar adds only the new block segment(s) to the existing Plan layer, preserving the selected date, work mode, drawer, and page scroll. Ordinary form requests continue to redirect.
- Enabled Plan-block templates can also be sent as `block_template_id`; the server validates ownership and usage, rejects a time overlap, and creates the daily item and block transactionally. Its default duration is not used for Calendar placement.
- JSON `POST /calendar/day-plan` requires a selected template and no active daily Plan items. The empty check is performed inside the copy transaction; Actual events do not prevent applying a template. A successful response updates the current Plan layer without navigation.
- MySQL deployments with an existing `plan_block_templates` table must apply `sql/migration.plan_block_enabled.mysql.sql` once. SQLite upgrades the equivalent column automatically; the default is enabled for existing records.
- Desktop starts at 1440px and uses the original DOM in a five-area CSS grid: sources | Plan | shared time axis | Actual | tags. No timetable or event listener is duplicated.
- Tag painting has no undo history. Its inline tag creator uses the existing JSON `POST /tags` endpoint, synchronizes available colors and tag options with the event editors, and selects a successfully created tag without reloading.
- The paint palette uses a full-width tag-add action with spacing at all widths and a shared inline add/cancel row. The mobile drawer expands only while its tag form is visible and adapts to the visual viewport when the keyboard appears; the desktop paint-mode toggle is hidden in compact workspaces.
- At mobile widths, Plan placement and Tag painting use a neutral segmented bar with icons above the app navigator, beside the existing `+` menu. An open work drawer is anchored at the viewport bottom, covering the navigator and work controls; its close button returns to the normal view. The date header uses compact vertical padding while preserving its 40px controls and safe-area inset. The page reserves bottom space for the fixed controls. Desktop and intermediate layouts retain their existing positioning.
- The closed mobile drawer translates by its own height and a margin so it remains outside the viewport. When the tag creator is open, its bottom anchor follows the keyboard offset.
- Tag management omits system-tag palette colors from creation and editing choices, including disabled system tags; other already-used colors remain disabled and existing tag colors remain editable.
- These mobile work-mode rules supersede the older mobile Plan/Actual-tab and 40:60 split descriptions below.

- Calendar hides visual `PLAN`/`ACTUAL` labels, timetable titles, and block counts at every breakpoint. On mobile the minute-number header is also hidden while the underlying 144 ten-minute cells remain unchanged; precise snapped time is shown only during a drag.
- Mobile preserves the Plan/Actual tabs. Plan mode uses activity placement and Plan timetable at the CSS-variable-controlled 40:60 ratio; Actual mode uses Plan and Actual timetables at the same 40:60 ratio so block-copy remains available.
- Mobile Actual mode removes only the left Plan grid's repeated hourly text. Its 24×6 cells, minute columns, plan-block position, and importance badge remain available for comparison and drag-copy. The right Actual grid retains the concise `00` through `23` hourly labels.
- The two time grids render the full 24 hours in the page flow. Calendar does not rely on per-grid vertical scroll positions or scroll synchronization.
- Every responsive presentation keeps the same `0..143` time indexes and the same row/event-layer geometry. Compact mobile labels never change block coordinates.
- AM and PM rows use low-contrast theme-aware backgrounds only. The 12:00 row has no special divider, so all hourly rows keep identical geometry.
- The activity source is the only Calendar presentation of time-unspecified Plan items. Its compact rows reserve a 44px right-side drag handle; title taps open the activity-information editor (title, importance, Goal), while handle drags start placement and never opens the editor. `+ 할 일 추가` uses the shared Todo creation sheet and posts through `/todo`, returning to Calendar Plan mode after save.
- During source placement, the title, snapped range, duration, and conflict state are shown in a pointer-transparent fixed status area outside the timetable. The grid preview remains visible and Escape still cancels the placement.
- Calendar and To-do use the same activity-create fields (title, importance, Goal, and an untimed default). Calendar passes its selected date through the existing `/todo` save route and returns to Plan mode after a successful save.
- A timed Plan or Actual block body can move as one range while retaining its ID, duration, and links. Plan-body drops in Actual remain copies; resize handles retain their independent start/end resize behavior.
- Minute-number headers are hidden at every breakpoint and reserve no vertical space. The Plan and Actual event layers start at the same first time-row origin; in mobile Actual mode the left Plan panel removes only its hourly gutter.
- Calendar no longer renders its contextual header guidance or normal empty-state cards; notification settings and error feedback remain separate.
- Plan block times are owned by `daily_plan_blocks`. The activity-information editor changes only a `daily_plan_item` title, importance, and goal; it never updates sibling block times. The block editor and resize handles own one block's start/end indexes.
- A Plan add/edit sheet can create a minimal active bucket Goal through the existing `/goal` service JSON response, then appends and selects that Goal without resetting the Plan form.
- Current-time focus occurs only when the bottom Schedule navigation link enters Calendar with `focus=current` or when a user selects a mobile Plan/Actual tab. The first pointer action cancels an in-flight automatic scroll. Sheet focus uses `preventScroll`; Calendar writes restore the captured position after layout so placement, edit, delete, resize, and copy do not jump to the current time or page top.
- Selecting an activity opens `할 일 수정`, which changes only the shared activity attributes. Selecting a placed Plan block opens `계획 수정`, which changes only that block's time range or deletes that one block. The Plan editor has no activity-information action.
- On mobile, each visible workspace title toggles its own temporary 80:20 split; selecting it again restores the default 40:60 split. The selected state applies direct grid columns rather than relying only on inherited variables.
- In mobile Actual mode, Plan blocks are copy sources only: tap, resize, and intra-Plan movement are disabled; drag from Plan to Actual creates the copy. A compact overlapping-card-and-arrow cue and the Actual target-grid treatment communicate the direction without persistent instructional prose.
- Plan-tab changes do not auto-scroll to the current time. Plan block creation captures and restores the user's working scroll position across its redirect.
- For a To-do-backed Plan, `계획 수정` shows inherited state and importance and can detach every connected Actual. Detach preserves each Actual record and clears its Plan connection only.
- Condition, quick memo, and existing template-link actions reuse the Calendar FAB sheets in one vertical action column. No Pomodoro action is rendered because there is no reusable Pomodoro service.

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
- current-hour row and current-time cell highlighting when the selected date is today
- dark-theme current-time highlighting uses the high-contrast secondary accent, inset outline, and glow
- links from the floating bottom sheet to dedicated To-do and Routine execution pages
- Retrospect preview entry point for the exact selected date
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
- The Retrospect button opens the latest submitted report by publication date in a large local popup, independent of the selected Calendar date. Previous/next controls move among published dates without leaving Calendar.
- When no submitted report exists, the popup shows a short first-publication message instead of navigating away.
- The Plan schedule and Actual schedule tabs share the time grid. Range selection opens the editor for the active tab.
- The grid no longer shows long-press/drag instruction text or the `시간 미정 추가` button. Existing range selection remains unchanged.
- The bottom-right `+` opens a bottom sheet containing condition, Plan-template connection, Memo, unified Plan creation, and Actual-event creation actions. `실제 일정 추가` opens the existing event sheet with required editable 10-minute start/end selectors; today defaults near the current time and other dates default to 09:00–10:00.
- `계획 추가` opens one Plan form with `시간 정하기` initially off. Enabling it reveals required start/end selectors. Selecting a range in the Plan tab opens the same form with the toggle on and the selected range prefilled, while the empty-state and `+` entry points start as time-unspecified.
- The same form can copy a saved Plan-block template. It fills title, importance, goal, and—when time is enabled—calculates the end from the selected start and default duration. An overflow beyond today's range requires explicit confirmation before the later portion is discarded and that date's copy ends at `23:50`.
- Plan create and edit forms expose Eisenhower A/B/C/D importance as four horizontal radio-style buttons.
- Today without actual records gives the floating action a slow breathing treatment; `prefers-reduced-motion` disables the animation.
- Plan picker labels show user-facing plan names only. Internal plan version numbers are not shown.
- The former collapsed Plan summary below the date header is removed so the Calendar grid retains visual priority.
- A tab with no blocks shows a compact first-action card. The Plan card opens direct Plan entry and offers template connection only when a template exists; the Actual card opens timed event entry. Existing data suppresses the corresponding card.
- Time-unspecified Plan items appear once in Calendar's activity-placement source, rather than in a duplicate list above the grid. Selecting the activity opens the existing Plan edit flow; choosing start/end values turns it into a normal grid block without creating or completing an Actual event.
- Planned blocks already linked to actual events are rendered with muted gray treatment and sorted below unlinked blocks. Strikethrough is intentionally not used.
- Planned block titles are highlighted by duration: 30 minutes or less gets a blue marker style, and 60 minutes or more gets a red marker style.
- The selected date's `daily_plan_items` are shown and edited in Plan schedule mode. Calendar carries the active `mode=plan|actual` through its URL, date navigation, date picker/swipe movement, form submissions, and delete redirect. A URL without a valid mode initializes in Actual mode, but a Plan create/edit/delete action returns to Plan mode.
- Mobile Actual schedule mode renders a compact interactive Plan grid beside Actual at 40:60 so Plan blocks remain direct copy sources. Desktop keeps Plan and Actual together; Actual-event controls remain independent from Plan controls.
- Plan schedule mode renders daily Plan items as solid schedule blocks with their visible `A/B/C/D` priority badge. A through D use progressively lighter left accents, surfaces, and type weight; the badge remains so importance is not color-only. Plan and Actual block titles explicitly use the same 12px size and 1.2 line height; Plan importance may still vary weight.
- Actual Calendar blocks and Retrospect event cards share the same tag-background and contrast-text rule in both themes.
- Compact mobile Plan-source blocks may omit the priority badge and resize handles to preserve usable copy targets; full Plan mode retains the visible `A/B/C/D` badge and resize controls.
- Calendar and Plan add/edit pages share `public/assets/js/components/time-grid-selection.js` for range selection.
- Timetables render in the page scroll context rather than owning independent vertical scrolling. Touch range selection and block dragging must keep their pointer state distinct from native document scrolling.
- Moving before the long-press delay remains a native scroll. A short tap has no schedule-creation behavior.
- Once a touch long press is confirmed, the shared grid controller asks the Android bridge to disable native pull-to-refresh until the matching pointer ends or the selection is otherwise cancelled. Normal browsers continue without this native enhancement.
- Mouse and pen input keep immediate drag-to-select behavior. Range selection can start on top of a background plan event because the script resolves the underlying grid cell.
- Touching or clicking an actual event opens its edit bottom sheet; range selection does not start from an actual-event control.
- A completed mouse, pen, or touch range that overlaps an existing block in the active tab is rejected before an editor opens and reports the conflict through Toast. Actual mode ignores background Plan blocks for this check. Server validation repeats the actual-event overlap check before persistence.
- The actual-event bottom sheet follows the user's recording order: start/end time, prominent title, compact tag chips, Plan connection, additional records, Execution Energy, and save. The ordinary path keeps only the first four decisions visible and targets completion in roughly ten seconds.
- Life Record, Memo, and Execution Energy remain inline disclosures in the Actual-event sheet because their controls are short and benefit from retaining the event context. Plan linking and Routine selection switch to a dedicated selection view inside the same sheet shell; they do not stack another modal or sheet. Closing a selection view restores its draft, while completing it returns to the main form with a compact summary. No third-level nested sheet is permitted.
- Routine completion, Life Record, and Memo are grouped under `함께 기록하기`. Each uses a one-line summary (`연결 없음`, selected Life Record type, or memo preview) and expands inline only when needed. Edit automatically expands an existing Memo or Life Record so saved content is not hidden accidentally.
- Execution Energy shows the active group and signed value in its collapsed row. Expanding `변경` exposes the existing group, group/direct mode, consume/recover direction, and individual value controls; calculation and persistence rules are unchanged.
- Start and end selectors are the only time-range display in the actual-event sheet; a directional arrow presents them as one connected range and a second text summary is intentionally omitted. Both create and timed edit sheets expose the same controls, preserve a selected grid range, and move an invalid end after the start.
- The Plan-link row is not rendered for creation when no unused daily Plan item exists. During edit it appears only when the event has a current link or another selectable Plan item. `연결하기 ›` and `변경 ›` open a compact recommended/all selection view inside the same sheet; cancel restores the prior Plan and title, while completion keeps the draft until the Actual event itself is saved.
- The three timed Plan choices closest to the current Actual range receive a non-binding `추천` marker, with overlapping ranges ranked first and time-unspecified Plans left in the full list. Nothing is linked automatically; changing the Actual time recomputes only this visual recommendation.
- The Routine selection view receives only active routines whose state is not complete. It preserves repository creation order (`created_at`, then `id`) and does not reinterpret `reminder_time` as an execution schedule. Missed routines remain selectable, and checking a row changes its draft label to `저장 시 완료` without reordering it.
- Timed and time-unspecified Plan items are both eligible link choices. Selecting a Plan during Actual creation, or changing an Actual event to another Plan, replaces the Actual title with the selected Plan title. Editing an already-linked Actual title changes only the Actual record; unlinking preserves that title. The former `실제 일정만 변경 / 계획 일정명도 함께 변경 / 연결 해제` selector is removed.
- `+ 태그 추가` creates a personal tag inside the create/edit event sheet and selects it without discarding the current title, time, memo, or links. Editing and deleting tags remain in Schedule Tag Management.
- Saving a new event marks the selected active routines complete for the same calendar date, so Calendar and Routine do not require separate completion actions.
- New untimed-entry creation is no longer exposed from the Calendar grid. Existing untimed entries remain available from the timed event sheet and can still be placed into a selected time range.
- After selecting a time range, the event sheet offers `새 일정 입력` and, when available, `시간 미정 일정 (n)` tabs. Selecting an untimed entry converts the same record to a timed event instead of copying it.
- Calendar bottom sheets keep their header and at least 44px close target reachable while long content scrolls internally. Actual-event save actions are sticky at the bottom of the sheet. Grid range selection focuses the event title, while `실제 일정 추가` focuses the start-time selector; visual viewport height and keyboard offset resize the sheet and keep the focused control above the sticky action.
- Calendar Actual create/edit and Condition sheets use the shared input-sheet interaction primitives. Their page-specific CSS still owns the time range, tag chips, recommendation rows, and keyboard viewport calculations.
- The `+` action follows the shared primary floating-action color. Its menu is ordered from day setup to recording: condition, Plan template connection, Memo, Plan item, Actual event. Bottom-navigation To-do/Routine links are not duplicated.
- Actual event blocks use the selected tag's `color_hex` as the block background color.
- On desktop hover, an actual-event tooltip includes the event title and a memo preview limited to 80 characters. Touch interaction continues to open the edit sheet without showing the hover tooltip.
- The Calendar page does not show a global status legend. Actual event colors represent tag categories, not execution status, and the current time is indicated directly in the grid cell.
- The event edit bottom sheet supports time, title, tag, Plan link, Routine completion, Life Record, Memo, Execution Energy, and delete actions through the same quick-entry hierarchy as creation.
- Already linked daily Plan items are disabled in the actual-event bottom sheet.
- Clicking an actual event asks for confirmation and then soft-deletes it.
- Routine execution remains in the bottom Routine menu and may also be selected while saving an Actual event; the `+` action list does not duplicate a Routine-page shortcut.
- Routine state changes submit to `POST /routine/toggle` and persist to `routine_logs`.
- If a routine duration change moves the selected date outside the routine's active period, that routine is excluded from the Calendar floating sheet for that date. Existing routine logs are preserved but ignored outside the active period.
- If the selected date is today, the current 10-minute cell is highlighted using the signed-in user's configured IANA time zone and moves in place at each 10-minute boundary without reloading the page. Returning to a backgrounded Calendar page refreshes the highlight immediately.
- On a normal first entry to Today, the current-time row is placed at about 42% of the viewport so upcoming time remains visible. Back/forward restoration, anchor navigation, past or future dates, and an already-scrolled document skip this focus; later timer updates never recenter the user.
- Calendar success and validation flash messages are rendered as hidden `data-toast-message` triggers and shown only through the shared Toast UI, preventing error rows from shifting the grid.
- Calendar page-specific CSS and JavaScript URLs include their current file modification time. This cache-busting value keeps browser and Android WebView clients aligned with deployed Calendar behavior without disabling caching for unrelated assets.
- Calendar notification synchronization includes only Plan reminders whose five-minute-prior fire time is still in the future. Entering Calendar replaces that date's local Android reservations and is not itself a notification trigger.
- Today's shared app header shows at most one contextual action, chosen server-side in this order: enabled morning/evening Retrospect window, current unlinked timed Plan, next unlinked timed Plan, no Plan, then no timed Actual record. Retrospect windows begin at the configured Settings reminder time and last two hours. Plan guidance opens timed Actual entry with the Plan link, title, and range prefilled; empty Plan/Actual guidance opens the matching form. Non-today dates do not show this guidance.

## Retrospect Integration

Retrospect persistence is handled by the Retrospect feature. Calendar remains the source of actual events and selected plan data, while `/retrospect` owns report snapshots, draft text, publishing, and history. Calendar reads the latest and adjacent submitted-report snapshots for its popup and does not edit retrospect data.

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

- In the Actual edit sheet, release `일정 삭제` before 1.5 seconds and confirm the toast appears and the record remains. Hold it for 1.5 seconds and confirm the progress fill, deletion and existing redirect. Repeat with pointer cancellation and with Space or Enter; a cancelled hold must not delete.

- Compare Calendar with another feature menu at mobile, tablet, and desktop widths. Confirm both content containers use the same responsive width and the Calendar `+` action stays aligned with the container's right edge.
- Connect a Plan template, edit the template from Plan, and confirm the already-copied Calendar day does not change.
- Open a date with no Plan, switch to Plan schedule, add two blocks, and confirm one `[YYYY-MM-DD]의 계획` receives both items.
- Edit and delete a daily Plan item. With JavaScript enabled, confirm the deletion request returns JSON before the detail sheet closes and Calendar reloads; for a linked item, verify the actual event remains and its Plan link is cleared. Repeat without JavaScript to verify the POST redirect fallback.
- Open Calendar without `mode` and confirm Actual is selected. Switch to Plan, then create, edit, and delete a Plan item and move dates through previous/next, date picker, and swipe; confirm Plan stays selected throughout. Open a fresh `/calendar` URL and confirm it still defaults to Actual.
- Switch between Plan schedule and Actual schedule and confirm the date and page-scroll context remain. On mobile Actual mode, verify the Plan grid is visible at 40% width, Actual is 60%, and dragging a Plan block to Actual still works.
- Open `+` and confirm its action order is condition, Plan-template connection when available, Memo, Plan item, and Actual event; To-do and Routine shortcuts must not appear.
- Open `+ 할 일 추가` in activity placement and confirm the existing Plan form starts with `시간 정하기` off. Save it and confirm it appears once in activity placement. Select the activity, save a non-overlapping range, and confirm it becomes a Plan block. Drag a Plan-grid range and confirm the same form opens with time enabled and the range prefilled.
- Select a Plan-block template in Calendar and confirm its title, importance, goal, and duration are copied. Place it near midnight, cancel the overflow prompt once, then confirm `오늘까지만 등록` produces an end time of `23:50` without changing the library duration.
- Publish or republish Retrospect and confirm copied Plan items, linked/unlinked actual events, Routine state, standalone Memos, and KPT remain available as the submitted snapshot.
- The Calendar Retrospect header action opens the latest submitted report in the local popup, regardless of the selected Calendar date. Confirm that no full-page navigation occurs, the long report scrolls inside the popup, and previous/next controls replace the popup content with the adjacent published report.
- On Today, the Life Record dock opens type-aware sheets for Sleep, Meal, Snack, Exercise, and Rest. Confirm point-time versus range-time inputs, count badges after reload, and absence of the dock on other dates.

- On touch, vertically swipe the time grid and confirm the page scrolls without creating a selection.
- Short-tap an empty time cell and confirm no event sheet opens.
- Long-press an empty time cell, drag across several cells, and confirm the timed event sheet opens with the selected range.
- With an existing actual event, drag from an adjacent empty cell across that event and confirm no input sheet opens and an overlap Toast appears. Repeat in the Plan tab with an existing Plan block. In Actual mode, drag across only a background Plan block and confirm event entry is still allowed.
- In Android WebView at the top of the page, long-press a time cell and drag downward. Confirm range selection continues without showing or triggering pull-to-refresh, then confirm pull-to-refresh works again after release.
- Repeat the previous test while cancelling the pointer, leaving the page, and sending the app to the background; native pull-to-refresh must be restored each time.
- Open `+`, select `실제 일정 추가`, change the 10-minute start/end values, and confirm the event is rendered at the selected range. Verify an end time at or before the start time cannot be saved.
- Open an existing timed Actual event, change its start/end values, and confirm the block moves. Attempt to overlap another Actual event and confirm both the client and server reject the update without changing the saved event.
- Submit an Actual-event request with `schedule_type=unscheduled` and confirm server validation rejects it; existing legacy untimed records must still be placeable into a timed range.
- Confirm the actual-event sheet does not repeat a `00:40 ~ 01:00`-style range below the selectors, and that server-side validation failures appear as Toast without moving the grid.
- On a date with no selectable Plan item, confirm the Plan-link field is absent. Add an unlinked daily Plan item, reload, and confirm it becomes available.
- Link a time-unspecified Plan while creating an Actual event and confirm the Actual title immediately becomes the Plan title. Edit the linked Actual title and confirm the Plan title does not change. Change the link to a different Plan and confirm the new Plan title is applied; select `연결하지 않음` and confirm the current Actual title remains.
- With many linkable Plan items, confirm `연결하기 ›` opens the compact selection view, Recommended/All filtering works, and completing or cancelling returns to the main form with the correct draft summary.
- Choose an Actual range near several timed Plans and confirm the three closest Plans are recommended, with overlaps first, without selecting anything automatically. Change the range and confirm the recommendation order updates.
- Confirm tag choices render as compact wrapping chips, selection is visible without a native radio control, and the final compact `+ 태그 추가` control still creates and selects a tag inline.
- Confirm Routine opens a compact same-sheet selection view containing only incomplete routines in creation order. Select and clear rows and verify `저장 시 완료` and the main summary count update without reordering. Confirm Life Record, Memo, and Execution Energy remain inline disclosures.
- On a small mobile viewport, confirm the header stays separate, only the body scrolls from title through optional sections, and the action area remains reachable. Open the keyboard in the title, Memo, Life Record detail, and individual Execution Energy value fields and confirm the active field and save area are not covered.
- Create and edit events using group-based, direct consume, and direct recover Execution Energy settings. Confirm collapsed signed values match the submitted values and the existing daily calculation remains unchanged.
- Compare a Plan and Actual block and confirm their title size is consistent. In Plan mode, confirm A/B/C/D Plans have distinct emphasis and retain a readable letter badge in light and dark themes.
- While entering an event, type a title and memo, create a tag inline, and confirm the form values remain, the tag is selected in both create/edit lists, and its used palette color is no longer offered. Confirm tag edit/delete remains available from Schedule Tag Management.
- Hover an actual block with a long memo on desktop and confirm its tooltip shows the title plus a memo preview capped at 80 characters; confirm no tooltip obstructs touch editing.
- Open dates with empty Plan and Actual tabs and confirm each first-action card opens the correct input. Add a block and confirm the corresponding card no longer renders after reload.
- Confirm neither Calendar tab shows a long-press/drag instruction or `시간 미정 추가` button. If existing untimed entries are present, select one from the timed event sheet and confirm the same record is placed into the chosen range.
- Create a timed event with a Routine selected and confirm the event and the same-date Routine completion are both saved once.
- Open and close Memo and Plan connection from `+`; switch to and from the Plan and Routine selection views without losing the entered title, time, tag, or optional records.
- Tap the displayed date to move to a specific date and confirm previous/next navigation still works. Confirm Today appears only on non-today dates and returns to today.
- Confirm the date, previous, next, conditional Today, and Retrospect controls remain vertically centered on one row with 32px visuals and 40px touch targets.
- Swipe on touch and drag with a mouse or pen across the date header to move one day backward and forward. Verify short gestures, vertical scrolling, and normal header-control taps do not trigger date navigation.
- Tap the date picker, previous, next, conditional Today, and latest-published-Retrospect controls individually and confirm each works without being swallowed by header drag tracking. Confirm the Retrospect control remains disabled when no published preview exists.
- Disable a fixed tag and confirm it has no color swatch in tag management and disappears from new event choices.
- Edit an existing event that used the disabled fixed tag and confirm its stored tag can remain selected and saved.
- Keep today's Calendar page open across a 10-minute boundary and confirm the current-time highlight moves to the next cell without a page reload. Confirm it refreshes immediately after returning from the background.
- Enter Today from another menu at the top of the document and confirm the current-time row settles slightly above center. Scroll away and wait for an update; confirm the page does not recenter. Repeat with a past date and browser Back/Forward restoration and confirm no automatic focus occurs.
- Enter Calendar after several Plan blocks have already passed and confirm no notification is displayed immediately. Verify only the remaining blocks are reserved for five minutes before their start times.
- On today's Calendar, verify the shared header stays one line and shows only the highest-priority applicable action. Test enabled morning/evening Retrospect times, a current Plan, a next Plan, no Plan, and no timed Actual record; confirm each action opens the expected date or prefilled form. Confirm the contextual action is absent on another date.

Actual-event memo presence is marked by a small dot on the first segment, including compact blocks. Tapping the block opens the existing editor, where the memo can be expanded; a successful event save with a memo confirms it in the toast.

The memo indicator uses a 7px contrasting dot on the first actual-event segment. Standalone quick memos are separate records and are not attached to events automatically.

- A quick memo from Calendar is appended to the single owned Actual event containing the current user-local time, so its memo indicator appears. When no event matches, multiple events match, or the combined text would exceed the event memo 500-character limit, it is saved as a standalone memo with a corresponding message. Attached quick memos are stored only in the event, not duplicated as standalone notes; earlier standalone memos are not retroactively linked.

## Tag management layout (2026-11-11)

`/tags` uses the shared header title without repeating a page title. One `ui-list-surface` contains a semantic table with tag and usage columns. System tags retain their existing POST switch; its outer pill border/background is removed while the internal switch track and focus indicator remain. User tags retain inline edit/delete and are shown as active; no new toggle/API is introduced.

The fixed Add button sits above bottom navigation and opens a native modal dialog using `ui-standard-sheet`, `ui-form-sheet`, and fixed header/actions with a scrolling form body. Native dialog supplies focus containment and Escape; close/backdrop restore focus and page scroll. Visual viewport updates keep the sheet above the software keyboard. Failed creation with old input reopens the sheet; existing name/palette errors and values remain. CSRF, escaped values, palette restrictions and endpoints are unchanged.

Changed files: `app/Views/pages/tags/index.php`, `public/assets/css/pages/tags.css`, this document.

Verification: PHP view lint and whitespace checks passed. An isolated rendered fixture in headless Chrome verified the two-column table, opening/closing the dialog, no horizontal overflow, and borderless usage button. This fixture does not verify real save requests or device keyboard behavior.

Manual checks: open `/tags` in light/dark at 320/390px, check single title and table; open Add above the bottom navigator, enter a name/color and save; submit invalid name/palette to check retained values and reopened errors; close via X/Escape/backdrop; edit/delete an existing user tag and toggle a system tag; open the keyboard and verify close/save accessibility.

### Tag controls aligned with To-do Add

The floating action now reuses `daily-floating-action` and reads 태그 추가. The native dialog loads the same `todo-sheet.css`, shell/header, `todo-create-form` spacing and action area as To-do Add; custom structured-body and footer styling were removed. System-tag 기본 text is replaced by an accessible lock icon; usage switches have no outer border or text, with a centered 44px target and a fixed-size track. User-tag 사용 중 text is removed. Table padding is reduced to 4px 8px and the usage column to 68px.

Additional changed file: `app/Controllers/CalendarTagController.php` (versioned shared stylesheet only). PHP view/controller lint and whitespace checks passed. Headless Chrome fixtures in light/dark compared computed shell, header, footer and floating-button styles with To-do reference markup: all matched; switch width was 44px and the dialog had no horizontal overflow. Device keyboard behavior remains a manual check. Manual steps: compare To-do Add and Tag Add button/sheet, check system icons and switch on/off, expand a long-name tag for editing, then verify keyboard and 320px layout.

### Tag editing sheets and custom-tag availability

This section supersedes the earlier inline-edit and system-only usage descriptions. Add/Edit now render the same `_form-sheet.php` partial with unique label/error/form IDs. A user-tag row opens its edit dialog with the current name/color; delete remains confirmation-protected in that dialog. Failed validation reopens only the matching create/edit sheet with the submitted name and palette preserved. System tags remain non-editable.

Both system and owned custom tags use authenticated, CSRF-protected `POST /tags/toggle`; `/tags/system-toggle` remains a compatible alias. `calendar_tag_preferences` now applies to all visible tags through COALESCE(is_enabled,1), so new and existing custom tags without preferences default to enabled. No schema migration is needed. The model verifies system status or ownership and rejects deleted tags before writing a user-scoped preference. Hidden tags remain in management and keep their palette reservation and historical event links; Calendar rejects new assignments while allowing an existing event to retain its own hidden tag.

Changed files for this step: `app/Models/CalendarTagRepository.php`, `app/Models/CalendarRepository.php`, `app/Services/CalendarTagService.php`, `app/Controllers/CalendarTagController.php`, `public/index.php`, `app/Views/pages/tags/index.php`, `app/Views/pages/tags/_form-sheet.php`, `public/assets/css/pages/tags.css`, `tests/calendar_tag_preferences.php`, this document.

Verification: isolated SQLite regression covers enabled-by-default, owner-only toggles, system preferences scoped per user, Calendar selection, preservation of existing event tags, re-enable, and rejection after deletion. PHP lint passed on all seven affected PHP source files. Headless Chrome light/dark fixtures verified matching Add/Edit shell styles, current name/color, two usage switches, unique IDs, delete form association, and no horizontal sheet overflow.

Manual checks: create a tag and check its switch starts on; switch off and reload, confirm it is unavailable for a new schedule but existing records retain it; switch on again. Open an owned tag row, change name/color and save, then submit an invalid/duplicate value to verify that only that edit sheet reopens. Compare Add/Edit in light/dark and with the mobile keyboard; cancel/confirm deletion.

Native edit dialogs close before opening the shared delete confirmation so the confirmation remains visible above page content. Cancelling restores the edit sheet with the unsaved form values intact.

### Tag sheet horizontal edges

Override the native dialog user-agent max-width with `max-width:100%`. The shared shell still sets width to min(100%,680px), so Add/Edit reach both viewport edges on mobile and retain the centered desktop maximum; shared internal padding remains unchanged. Changed files: `public/assets/css/pages/tags.css`, this document. Manual check: open Add and Edit at mobile width and compare the outer edges with To-do Add; check desktop centering as well.

## Place cards and placement management label

The placement drawer management link now reads 반복 할 일 관리, retaining `/plan?view=blocks`. Actual Add/Edit include shared address/place search and persisted map cards; see `location-feature-implementation.md`. The actual-to-plan correction proposal is reviewed in `actual-to-plan-conversion-review.md`; no automatic cross-table move was introduced.

Actual location now sits inside 함께 기록하기 immediately before Memo. Plan-block Edit has a read-only location row sourced from its linked To-do; its location input is excluded from submission. Details, tests and the remaining Kakao domain response are recorded in `location-feature-implementation.md`.

## Correct Actual entered in the wrong grid

Actual Edit offers 계획 일정으로 전환 with explicit discard confirmation. The owner transaction creates/reuses a dated To-do/Plan, preserves the saved time and new-source location, removes Actual-only metadata, and rejects overlap/unauthorized/repeated requests without creating duplicates. See `actual-to-plan-conversion-review.md` for preservation, published-snapshot/routine boundaries, tests and manual steps.

## Actual-to-Plan drag conversion

Dragging an Actual block onto the Plan grid previews its preserved duration at the target range and opens the shared conversion confirmation before any write. Confirm sends the optional target indices; cancel/outside/pointercancel makes no request. Server checks saved duration, ownership, day bounds and Plan overlap, moving a linked Plan when present. Actual-to-Actual moves retain their existing endpoint. The sheet fallback is a muted underline text button. Implementation, changed files and verification are in `actual-to-plan-conversion-review.md`.

## Shared time selection (2026-11-13)

Actual create/edit opt into the common time-picker component through _actual-time-range.php. Start/end layout and duration remain; a bottom sheet now selects hour and minute, with an explicit Apply action. Native select values remain 10-minute indices, start 0–143 and end 1–144. At 24:00 only minute 00 is valid. Programmatic form opening/reset and duration updates synchronize the visible trigger. Native dialog keyboard handling takes priority over the parent Calendar sheet so Escape/Tab cannot close or focus the underlying editor. No persistence, overlap or range validation rules changed.

Manual checks: open different existing Actuals, check current times, apply a new time and duration, cancel a draft, select end 24:00, save/reopen, and compare untimed events. Check Android Chrome keyboard/back and both themes.

## Today condition sheet (2026-11-13)

The + menu’s condition sheet uses standard sheet chrome and labelled 1–5 range gauges. Unset values display midpoint 3, and burden feedback defaults to similar; empty radio choices are omitted. Saved values remain authoritative on reopen. Input/change events update the visible label and aria-valuetext. Submission continues to /execution-energy/morning with existing field names and CSRF; calculation/persistence are unchanged. See execution-energy-feature-implementation.md for tests and manual checks.

## Published Retrospect popup alignment (2026-11-13)

The popup groups title/44px close in one grid row and equal-width previous/next published-date buttons in a second row, within one fixed header block. Both empty and populated states retain the same header structure. The popup uses 16px internal padding on all sides; existing 12px mobile/24px desktop outer insets and safe-area guards remain. Only the report body scrolls. Existing preview fetching, ownership and adjacent-date behavior are unchanged. Browser checks at 320/390/1024px in both themes verify no overlap, 44px move buttons, equal widths, symmetric insets, fixed controls while the real report body scrolls, no overflow and empty-state layout; the populated popup was visually inspected.

### Day-template application (2026-11-14)
The placement drawer opens a native template dialog. It posts CSRF, selected date, calendar_mode and plan_group_id to the existing /calendar/day-plan endpoint only after required selection and warning acknowledgement. This intentionally uses the regular POST replacement path; the empty-day-only JSON path is not used. Submission exits placement mode to normal/both before navigation; the server redirects to the selected date without a workspace parameter. Plan editor gestures opt into LifeFlowTimeGrid.requireLongPress without changing the default Calendar mouse-selection behavior.

- Actual mouse/pen entry now uses a cancellable 360 ms timer, matching touch entry. Short click and immediate drag never register Actuals. Scroll/pagehide cancels pending entry. Ratio swipe detection accepts mouse/pen at mobile widths as well as touch and captures only after horizontal intent is recognized; held gestures remain reserved for range entry.

- Plan-to-Actual copying and Plan movement reuse LifeFlowCalendarResizeHold with an endpoint-handle exclusion and the original press point for grab offset. Touch uses non-passive touchmove only after activation; mouse captures only after the hold. Cancellation/Escape/blur clears previews and restores native pull-to-refresh; completed holds suppress the trailing detail click. Existing duration, midnight-boundary and overlap checks remain.

- Actual block drag now reuses the shared hold controller. The final release point is recomputed before validation, including touch release. Actual segments expose their owned linked block ID for conflict exclusion. Plan collisions suppress destination preview and prevent conversion confirmation/submission; existing transactional MySQL/SQLite overlap validation remains the final authority.
