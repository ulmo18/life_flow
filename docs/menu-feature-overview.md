# Menu Feature Overview

## Timed Plan notification controls

Today's To-do list may show a notice about yesterday's progress/postponed items. Users review the original state/context and explicitly choose which ones to register today. Viewing the notice never copies tasks, and older dates do not automatically accumulate in it. Today's registered item starts untimed/pending and retains a source relation and note; yesterday's records remain unchanged. State controls are a single row in both detail sheets. See `todo-feature-implementation.md`.

Schedule Plan-block details and To-do status/linked-record details edit the same placement-owned reminder. Default is off with a five-minute lead; choices are start time or 5/10/15/30/60 minutes before. Untimed sources alone do not generate alarms. Settings owns master/Calendar gates. See `notification-feature-implementation.md` for scheduling and native integration limits.

Reminder controls start collapsed; opening shows either global states with a Settings link or per-block choice/save buttons. Schedule Plan details also change the linked To-do's state directly, including completion without Actuals. Timed creation preserves the current split/workspace and reports Plan/To-do creation by Toast. Both timetables require a 450 ms stationary hold on resize handles; scrolling before activation never resizes. Work-drawer title icons match their opener buttons.

## Purpose
This document describes the intended roles of the main LifeFlow menus before feature work begins.

Read this file before changing menu behavior, menu navigation, calendar presentation of plan/routine data, or retrospective reporting flows.

## Navigation
- The bottom navigator is reserved for daily execution in this order: Retrospect, Routine, To-do, Schedule. Execution Energy remains available as a separate 52px glass-liquid HUD on the left of the navigator; it opens the usage history without occupying a navigation slot or overlapping the daily floating action.
- Schedule opens Calendar at the shared viewed date when navigating between daily menus in the same session; a new session defaults to today. The route and internal feature name remain `/calendar` and Calendar; `Today` is reserved for controls that return to the current date.
- The bottom Routine page accepts `?date=YYYY-MM-DD`, starts at today, and shows routines whose execution period includes the selected date. Today and past dates can update execution state in `routine_logs`; future dates are read-only.
- The bottom To-do page accepts `?date=YYYY-MM-DD` and opens with the `할 일` tab: a compact table shows each To-do's importance, name and state symbol. The state button opens one bottom sheet with state choices and the plan/Actual tree, comparing each Actual to its own plan. Its add button creates a To-do for the selected date. The `반복 할 일` tab groups reusable templates by enabled state, allows changing that state in the editor, and adds enabled templates to today's To-dos. State changes work on any date, while other source edits remain today-only. Calendar follows item-level state and importance for linked plans; Retrospect has not yet adopted them.
- Routine, To-do, Retrospect and Calendar use the same compact sticky date context, typography and colors, with previous/next-day navigation, swipe, the shared date picker, a conditional Today action, and mobile Search/aside access. Retrospect's view tabs sit on the next row.
- Retrospect, Routine, and To-do use the same centered daily primary floating-action style. Routine's Management link uses the same secondary pill treatment as Retrospect date controls.
- Daily Routine uses unfinished/completed groups; To-do uses To-do/recurring tabs with compact counts, divided rows, and empty messages. Source rows retain their state actions and editable names.
- The aside groups Plan, Routine Management, Goal, and Execution Energy Management under Management because these structures are configured less frequently.
- Dashboard remains a standalone aside entry. Memo and Schedule Tag Management are grouped as tools.
- Settings is reached from the aside header gear, and logout is kept inside Settings rather than the main aside menu.
- Settings owns the persisted light/dark display mode.
- Settings owns notification management for Retrospect, Routine, Calendar selected plans, and Goal deadlines.
- The tag management menu label is "Schedule Tag Management" to clarify that it manages calendar schedule tags.
- Goal is a distinct feature menu for creating and managing long-term goals.
- Goal includes the all-period Goal review; Retrospect retains Daily and Published History tabs. Date navigation appears on Daily only, and older published dates link directly to the report instead of displaying daily records.
- Memo appears directly below Goal in the aside and owns standalone quick notes.

## Plan
- Plan stores reusable daily schedule templates. Editing a template updates that template in place and never changes a date-specific plan already copied into Calendar.
- Plan has two template levels: a Plan-block library for one reusable activity and a daily template that places copied blocks at specific times. A block template stores title, 10-minute-unit default duration, Eisenhower importance, and an optional goal, but no start or end clock time.
- The `/plan` page is `하루 템플릿` and directly lists day templates without tabs. Reusable To-do blocks are managed only in `/todo?tab=recurring`; legacy `/plan?view=blocks` links also show day templates.
- Daily-template editing can mix blocks copied from the Plan-block library with blocks entered directly. Copying isolates the daily template from later library edits or deletion.
- The Plan list omits the large menu-name introduction so the first plan card or empty state begins at the top of the content area; a screen-reader heading remains available.
- A plan is built in the same day-grid style as the calendar.
- Users define a plan from `00:00` to `24:00` in 10-minute blocks.
- Saved daily templates are shown as compact cards. The template name opens its detail page, the only right-side action is a confirmed copy icon, and a collapsed block list shows its block count before expanding to importance, block name, and time range in chronological order.
- The Plan-block library keeps creation in a fixed bottom action and reuses one bottom sheet for create and edit. Each list row keeps compact edit and delete actions on the right.
- The To-do recurring tab also opens its reusable template editor from the name, with a 1.5-second hold-to-delete action. Changes do not rewrite copied daily records.
- The Plan-block management list and editor hide the stored default duration; new templates retain the existing one-hour default and edits preserve previously saved durations.
- Plan-block templates have a per-item usage toggle. Existing templates default to enabled; disabled templates are omitted from Calendar placement sources without deleting any copied daily Plans. Calendar placement chooses the range directly and does not use the template's stored default duration.
- Selecting a saved Plan in Calendar creates a date-specific copy, including each block's goal link. Calendar edits apply only to that copy.
- Plan data is copied into Calendar as daily intention; the daily copy and its actual links feed retrospective analysis.
- To-do-backed Plan blocks show their To-do's state and importance in Calendar. Placing a recurring template creates a dated To-do and linked block; the template is hidden from that date's placement list. Actual linking does not complete the To-do, and detachment does not reset its state. Historical template-only blocks retain their independent stored state and importance.

## Routine
- Routine is a habit-tracking area.
- Its purpose is consistency, not one-time scheduling.
- Routine data should support day-by-day completion tracking.
- `GET /routine` is the daily execution surface. `GET /manage/routines` owns creation, editing, extension, completion, stopping, deletion, and period history.
- The daily Routine surface can create a title-only routine from a bottom sheet. It uses today's date, the 7-day default, no Goal, and no reminder; Routine Management remains the place for detailed configuration.
- Both entry points and Calendar use the same `/routine/toggle` endpoint and `routine_logs` records.
- Routine duration is user-selectable from 1 to 60 inclusive days, with 7 days as the default so a user can begin with a short cycle and extend it later. During creation, duration and the inclusive end date stay synchronized; the latest initial end date is the start date plus 59 days.
- Active routines can be extended by a user-selected number of days up to 365 days, but their start date and duration cannot be shortened after creation.
- A routine can be completed early or stopped. Its logs remain available to Retrospect after it leaves the active Routine list.
- Routine Management omits the former weekly summary and separates `활성 루틴` from `완료·종료 루틴`. Active includes future-start records; completed/ended includes manually completed, stopped, and naturally expired records. Both tabs use the same card and calendar presentation for now.
- The bottom-navigation Routine page groups unfinished routines above completed routines in compact tables for the selected date. Both groups remain visible, an empty completed group encourages the first completion, and completing every routine shows a hopeful all-done message.
- Each Routine Management card shows name, lifecycle state, linked goal, a concise achievement summary, and a Monday-first 42-cell calendar for the selected month. The former percentage, progress bar, and date-less 14-cell tracker are omitted.
- Sixty accumulated completed routine days constitutes habit formation; continuity is still shown separately only from a three-day streak. Crossing the 60-day total and selected streak milestones can use the shared restrained celebration Toast.
- Streak feedback is hidden for zero, one, and two days. From three consecutive completed days onward, both Routine surfaces use the fire-marked compact label (`🔥 연속 n일`) without an `어제까지` prefix.
- The bottom-navigation Routine page keeps its execution list compact and shows `🔥 n일 연속 완료` beneath the name only from three consecutive completed days onward, including immediate updates after today's toggle.
- The optional linked goal sits in the card's upper-right context area and truncates before it can crowd out the routine name.
- `기록 관리` uses the same weekday-aligned monthly calendar for the full routine period. Future and out-of-period cells are read-only, while today and past in-period records retain the existing blank/O/X correction cycle.
- The Routine detail sheet keeps the routine name and close control sticky while its period record scrolls.
- Routine should be visible in the calendar in a way that is easy to understand and quick to confirm.
- Routine data also feeds retrospective reports.
- See `docs/routine-feature-implementation.md` for table, route, and UI details.

## Calendar
- Calendar is the daily intention and execution screen.
- On mobile, a long press on an empty Plan grid selects one 10-minute cell; continuing to drag selects a range. The resulting Plan source and block are created in one save, and the source remains independent and selected in the Plan placement drawer afterward. Short taps do not start creation.
- On mobile, Plan placement and Tag painting remain reachable in a fixed bottom work bar above the daily navigator, alongside the existing `+` quick menu. Condition and memo remain inside that menu; a timer is not displayed until it has an implementation.
- The Plan schedule tab edits only the selected date's copied plan blocks. The Actual schedule tab keeps the existing actual-event behavior.
- The selected Plan/Actual tab is carried in the Calendar URL and every Calendar write redirect, date link, date picker move, and date swipe. Adding or editing a Plan therefore returns to Plan instead of unexpectedly switching to Actual; a Calendar URL without `mode` still defaults to Actual.
- Calendar uses the current day-grid interaction model.
- Calendar supports time-unspecified daily Plan items for work that belongs to a date but does not yet have a fixed time range. They stay out of both time grids and appear in a compact Plan-mode list above the grid. One row is visible by default; the heading shows `X개`, and its top-right toggle expands or collapses additional rows. A template-created item retains its expected duration so a one-hour-or-longer title can use the shared highlighter even before time is fixed. Selecting `시간 설정` opens the existing Plan editor; assigning time does not create an Actual event or consume Execution Energy.
- Calendar uses one Plan-add flow for both timed and time-unspecified daily Plan items. General entry points start without a time, the user may enable `시간 정하기`, and a range selected directly on the Plan grid opens the same form with that range enabled.
- Calendar Plan creation can copy a Plan-block template or accept direct input. When a copied default duration exceeds today's range, Calendar asks whether to discard the later portion and end only that date's copy at `23:50`; it never truncates silently.
- New Actual schedules always require a start and end time. Legacy untimed Actual records may only be selected and placed into a timed range.
- Calendar event creation can mark selected routines complete for the same date, avoiding duplicate completion work across menus.
- Calendar, Routine, and daily Retrospect use the same blank/completed/missed Routine state cell and update only the affected state and metrics after a successful JSON response.
- Calendar's `+` menu offers 발행 회고 보기, opening the latest published report in an internally scrolling popup. On the first Calendar visit of each account-local day, with no Plan blocks and a published report, it asks once whether to open it; Actual events do not block the prompt. Later visits do not prompt even if a plan is removed or the user declined.
- The popup provides previous/next published-date controls and reuses the same report content as the published Retrospect archive.
- If a calendar entry overlaps with a plan, the UI should help the user confirm whether the entry is a copy of that plan or a different schedule.
- Actual calendar entries are used for retrospective reports together with Plan and Routine data.
- Saving an Actual event applies its per-event Execution Energy change to that day's current value. New events initially show a 25-point decrease; the user may select increase or decrease, use slider values 0/25/50/75/100 (0 means keep), or enter a 5-unit value up to 100. Existing unchanged legacy values over 100 remain valid. A Plan item is never current usage and contributes only a projected 25-point decrease until completed.
- The floating action opens a bottom sheet ordered as `오늘의 컨디션`, `계획 템플릿 연결`, `메모 작성`, `계획 일정 추가`, and `실제 일정 추가`. Today To-do and Today Routine entries are omitted because bottom navigation already exposes those pages.
- `오늘의 컨디션` opens a radio-choice bottom sheet for sleep, fatigue, mood, stress, and perceived burden. Saving replaces that day's earlier condition adjustment; it is not shown as a Calendar-top summary.
- Fixed schedule tags can be enabled or hidden per user. Hiding a fixed tag removes it from new event choices while preserving its color and all existing event links.
- Empty Plan and Actual tabs provide a small first-action prompt so a new user can start without learning the full service flow first.
- Actual-event entry keeps the form open while creating a missing tag inline. Tag maintenance stays in Schedule Tag Management, and Plan-link controls stay hidden when no usable Plan item exists.
- Actual-event create/edit uses a mobile quick-entry hierarchy: title, time, compact tag chips, and the visible Plan-link row come first. Plan and Routine use compact selection views inside the same sheet; Life Record, Memo, and detailed Execution Energy settings remain summarized inline disclosures. The save action remains reachable throughout the form.
- Shared structured-sheet primitives standardize a fixed header, one internally scrolling body, a safe-area-aware action area, and a 44px close target. Sheets do not show a drag handle without real drag-to-dismiss behavior, and long selections switch the current sheet view instead of nesting another sheet.
- Existing Actual events can change their time range from the same editor. The edit keeps its saved values in the collapsed summaries and rejects overlap with another Actual event while excluding the event being edited.
- Selecting a range across an existing block is rejected before an editor opens, with non-layout-shifting Toast feedback. Background Plan references do not prevent Actual-event entry.
- Desktop Actual-event hover details may include an 80-character memo preview; mobile touch continues to use the edit sheet.
- A time-unspecified Plan item remains selectable as an Actual-event link and can later be edited from the `+` menu to receive a time range.
- Selecting a Plan link for a new Actual event, or changing an existing event to a different Plan link, sets the Actual-event title from that Plan item. Later Actual-title edits stay independent; Calendar does not expose a per-save title-sync selector.
- Actual-event forms keep long Plan-link choices inside a collapsed summary control with an internally scrolling list. The chosen Plan remains visible in the summary without making the whole sheet excessively tall.
- Plan and Actual grid blocks use the same title size, including the compact mobile size, and have no border or inset outline. Plan blocks retain their importance letter badges and type weight. Actual mode deliberately mutes background Plan references with a translucent achromatic treatment so Actual events retain visual priority.
- On today's Calendar, the shared header may show one compact next action. Its priority is a configured Retrospect window, the current Plan, the next Plan, an empty-Plan prompt, then an empty-Actual prompt. The action opens the corresponding Retrospect date or Calendar form directly.
- Actual Calendar event creation and editing optionally attach one Sleep, Meal, Snack, Exercise, or Rest Life Record. The event owns the time range; the Life Record stores detail, note, and perceived-state data for later nutrition, weight-management, and exercise expansion without overloading Schedule Tags.

## Retrospect
- Retrospect is a daily reflection and reporting area.
- The Retrospect page omits the large menu-name introduction so its view/date controls and first feedback card start near the top; a screen-reader heading remains available.
- The daily view hides the 작성중 and 발행됨 status labels.
- Retrospect uses Calendar actual events, selected Plan blocks, and Routine logs to prepare the daily review.
- The daily view shows a 100% composition bar and single-row count-and-percentage legend for Routine, Plan, Actual, and Memo, followed by record tabs. Legend buttons open the relevant tab. Plan and Actual counts remain independent even when linked; Schedule presents individual records with importance, time or tag ordering.
- Routine, Schedule, and Memo rows open their own type-specific detail sheets rather than one mixed detail popup.
- Routine and Schedule tabs use compact tables. Routine columns are ordered `루틴명 / 연속 / 완료` so the label receives the flexible width. Actual-tag color is limited to a left accent line on Schedule rows so tag context does not compete with Plan and difference information. Schedule differences use signed minutes, and Plans of at least one hour reuse To-do's title highlighter.
- Schedule sorting updates the list and matching details without a page reload, retaining the date and published-review edit state; links remain usable without JavaScript.
- KPT remains editable and publishable for today and yesterday in the user's configured time zone. Earlier submitted reports are read-only.
- It appears twice a day through calendar reminders:
  - Morning retrospect shows yesterday's created retrospect.
  - Evening retrospect shows today's created retrospect.
- Retrospect entries should be stored as report-style documents.
- Published retrospects must be viewable later at any time through date navigation or direct date lookup.
- Publishing is always initiated by the user; the former automatic publication setting and page-load trigger are removed. Try-to-Actual linking is disabled while its existing stored relationships are retained.
- Published retrospects can be republished from the floating action button to refresh metrics and snapshots after Calendar or Routine edits while preserving written reflection text.
- A submitted day's daily view first shows the newspaper link. For today and yesterday, `회고 수정` opens an explicit edit view using current records and the saved KPT text; the published schedule, routine and memo snapshots remain unchanged until republished. The existing KPT autosave continues to update its saved text.
- Today and yesterday can edit and republish KPT text; earlier published KPT remains read-only. Republishing updates the one date report and does not keep version history.
- The Keep/Problem/Try sheet displays one input step at a time. Previous appears after Keep, Next appears before Try, and Publish or Republish appears only on Try. Close is visible only when Record View collapses the editor; the collapsed header shows only Close and Return. The collapsed editor preserves step, text, and timer state. On mobile its normal height is 40% of the dynamic viewport; focusing an input uses 80% of the visual viewport above the keyboard.
- Submitted history opens a dedicated newspaper-style report page. Calendar opens the same report content in a large local popup; both provide adjacent published-date navigation and completion feedback for Goals and Routines captured when the report is published.
- The published newspaper optionally shows one evidence-backed recorded-activity fact below the user's KPT, with expandable snapshot evidence; lack of reliable evidence leaves it hidden. The Calendar preview uses the same component.
- Opening the KPT sheet starts a visible 10-minute soft timer that continues while collapsed and never blocks editing or publishing at `00:00`.
- Future dates use a localized hopeful empty state with one of four randomly selected encouragement messages instead of the past-date missing-report notice.
- Retrospect actual-event rows use the Calendar tag color as a left accent line, while keeping a neutral row background and showing duration plus time-range labels.
- Retrospect should summarize progress based on calendar, plan, routine, and goal data.
- Daily Retrospect does not show the starting Execution Energy card. The condition input remains in Today's Calendar `+` menu.
- Retrospect has a daily view and a goal review view. Goal review lists completed goals and shows their linked Plan blocks, Actual records and Routine logs in a chronological detail sheet without inferring links that are not stored.
- Memo-page standalone records remain independent from goals and Calendar event memos. Daily Retrospect combines active same-day standalone Memos with non-empty Actual-event memos into one chronological Memo tab, with source labels and the Actual start time used for event memos.
- Publishing snapshots active same-day standalone Memos and Actual-event memos through their existing snapshot tables, so later source deletion does not remove either kind from the published report.
- Memo trash entries are purged after 30 days when the trash is opened, and users can empty the trash immediately.
- See `docs/retrospect-feature-implementation.md` for table, route, metric, and UI details.

## Goal
- Goal is a higher-level planning structure presented as an automatically arranged Goal Map rather than a metadata-heavy card list.
- Root and child goals can be created with a title only. Type, period, status, parent, and behavior details are optional refinements in the selected goal's detail panel.
- The Goal Map supports unlimited practical depth, depth-based emphasis, collapse/expand with descendant counts, sibling ordering, and cycle-safe parent movement without storing layout coordinates.
- All-goal rows use a focus star, a strike-through for paused names, muted archived names and a quiet completed label; focused-list rows omit the redundant focus star. Goal tabs expose their own help via a compact popover, and reminders wrap in shaded cards. The edit sheet reuses the shared date picker and a 1.5-second delayed delete; save and focus actions preserve the current Goal tab.
- `지금 집중` is the default Goal tab and shows up to five active goals explicitly selected with the focus flag. `전체 목표` preserves the full hierarchy, initially expands only paths containing focused goals, and offers a global expand/collapse toggle. Selecting a goal opens its detail panel and linked-record tabs; the detail footer opens the separate editor. Desktop panels scroll on the right and mobile panels open as bottom sheets.
- Goal depth should support:
  - Bucket list
  - Yearly goal
  - Half-year goal
  - Quarterly goal
  - Monthly goal
- Goal hierarchy is optional. Users can start with a monthly goal only, or connect smaller goals under larger goals.
- Goal records support a lightweight behavior reminder shown below the name in the Goal Map and in Goal details.
- Goals connect to plan schedules, actual schedules, and routines.
- Existing active, completed, archived, paused, bucket, yearly, half-year, quarterly, and monthly values remain available in detailed editing for compatibility.
- Time-bound goals show period progress so users can quickly see how far the goal window has advanced.
- Goal Map is the default Goal surface and helps users see how larger intentions break down into smaller execution goals.
- Goal data is used in retrospective feedback to show how well the user followed through and how much progress was made.
- See `docs/goal-feature-implementation.md` for table, route, and UI details.

## To-do

- To-do is a date-navigable view over `daily_plan_items`; it is not a second persistence model and does not introduce a separate `todos` table. To-do state can change on any date; other editing remains today-only.
- The recurring To-do tab offers searchable enabled reusable templates. `사용하기` creates an independent time-unspecified To-do using the template's title, importance, and eligible Goal; management remains in Plan.
- Timed and time-unspecified items remain in the same table. Both time indexes are populated for a timed item and both are null for a time-unspecified item.
- Actual linking records execution independently of To-do completion. Users select the To-do's state, including completion without a link; Calendar displays that state for its linked plans. Actual ranges are shown under the associated plan in the To-do tree.
- The To-do list defaults to importance A–D and can switch without a page reload to status order 미정, 진행 중, 완료, 취소, 연기, 위임. Status changes reapply the selected ordering.
- The Completed group remains visible with an encouraging empty message when its count is zero.
- To-do uses compact divider rows rather than one card per item. Group placement, subdued text, and strike-through already communicate completion, so rows do not repeat a `완료됨` label.
- General editing lives in an accessible vertical-more popover; the list no longer displays separate `시간 설정` or `완료` buttons.
- Actual-event Plan linking and Routine selection use compact selection views within the existing sheet shell instead of stacking another sheet. Plan choices provide recommended/all filters, while Routine choices show only incomplete routines in creation order; reminder time does not affect this ordering.
- To-do edit keeps time controls hidden and disabled for `시간 미정`. Goal selection uses an inline summary row and internally scrolling radio list matching Calendar's connection pattern: unlinked shows `연결하기 ›`, while a saved selection shows its Goal name.
- To-do edits title, importance, Goal, and optional planned time in one bottom sheet. Time setting uses a two-button radio choice and reveals start/end controls only for a timed item. Linked items require an explicit Plan-only, synchronize, or detach choice, with Plan-only as the default.
- Completing a timed item proposes its planned range. If that range is free, an Actual event is created and linked immediately.
- Completing a time-unspecified item, or a timed item whose proposed range overlaps an Actual event, opens a time-selection sheet. Completion succeeds only after a valid non-overlapping Actual event is created.
- See `docs/todo-feature-implementation.md` for routes and interaction details.

## Execution Energy

- Execution Energy is a user-controlled self-management estimate, not a health or medical assessment.
- Its default daily capacity is 500 and values use five-point units between 0 and 1,000.
- The aside management page owns the next-day baseline and explains the per-event input model. Reusable consume/recover groups are no longer part of current input.
- Morning sleep, fatigue, mood, and stress selections adjust only that date's starting value and replace the previous same-day condition adjustment when resaved.
- Actual Calendar events default to a 25-point decrease, including events created by To-do completion. Each event can instead record no change, a decrease, or an increase using a preset or custom five-point value. Editing replaces that event's saved setting, deleting removes its contribution, and capacity remains floored at zero.
- There is no user-facing manual capacity correction. The system records recovery above the daily start value as overflow instead of increasing capacity past the start value.
- Retrospect stores optional perceived-burden feedback (lower than expected, similar, or higher than expected) separately from the morning condition adjustment; feedback never changes event values automatically.
- See `docs/execution-energy-feature-implementation.md` for calculation, tables, routes, and deployment migration details.

## Search

- Search is available from the normal header, each mobile daily-page context, and the aside Tool at `GET /search`. Either icon expands the shared inline query form; Enter or the labeled search button navigates to the result page.
- The first implementation searches active Goals, Routines, Plan blocks, dated To-dos, Actual schedules and their memos, standalone Memos, submitted KPT reports, and Life Records.
- Users can filter by record type and date range. Results show a type badge, record date, title, matching excerpt, and a link to the owning feature.
- Queries require at least two characters. Prepared partial-match queries stay compatible with MySQL and SQLite; deleted source records are excluded, while submitted Retrospect snapshots remain searchable as historical documents.

## Life Records

- `wellness_entries` stores Sleep, Meal, Snack, Exercise, and Rest details through a nullable one-to-one link to an Actual Calendar event. Legacy unlinked rows remain readable after migration.
- The linked Actual event supplies the time range, avoiding duplicate point/range input in the Life Record fields.
- An optional 1–5 perceived-state rating and note leave room for later nutrition, sleep, and exercise management without treating the record as medical data.
- Life Record categories do not automatically consume or recover Execution Energy. A later explicit connection may suggest an effect while keeping user control.

## Memo

- Memo stores free-form notes independently from Calendar event memos and Retrospect text.
- Calendar exposes a quick Memo action from its bottom-right `+` menu.
- The Memo page uses the same centered daily floating-action pattern as Retrospect. Its editor grows with content up to the available 80% dynamic viewport area, then scrolls internally.
- Notes under 300 trimmed characters are short notes; notes at or above 300 characters are long notes.
- Deleted notes move to a recoverable trash list ordered by the latest deletion time.
- See `docs/memo-feature-implementation.md` for routes, persistence, and UI rules.

## Cross-Menu Rules
- Plan defines reusable intention templates.
- To-do exposes today's copied Plan items for execution without duplicating them.
- Calendar owns the copied intention for one date and records execution.
- Routine captures repetition and consistency.
- Retrospect summarizes the day and stores the report.
- Goal connects lower-level actions to longer-term outcomes.
- Memo captures standalone thoughts without automatically turning them into Calendar, Routine, or Retrospect records.
- Execution Energy turns actual execution and recovery records into optional daily pacing feedback without changing completion evidence.
- Keep these responsibilities separate in both UI and data design.
- Linking records should not require duplicate execution records: Goal supplies context, Plan supplies intention, Calendar records the event, and selected Routine completion can be saved with that event.

## Notes For Future Work
- Keep the plan-to-calendar selection flow simple and predictable.
- Keep routine display light enough that it does not block actual schedule input.
- Keep retrospect reports durable and searchable.
- Keep goal linkage flexible so it can evolve as the feature set grows.
- See `docs/database-migrations.md` before deploying schema changes to an existing database.
- See `docs/android-webview-integration.md` before changing native bridge or pull-to-refresh behavior.
- See `docs/notification-feature-implementation.md` before changing Android bridge notification payloads or Settings notification behavior.

- On mobile, a quick horizontal swipe on empty Calendar grid space moves between Plan-only, split and Actual-only views. Plan-only puts the time labels on the right; long-press selection and block manipulation remain separate.
- Browsing a published report preserves the shared daily selected date independently of the report date.

- Daily-template detail keeps Delete and Edit fixed above bottom navigation. Delete requires a 1.5-second hold; creation and editing use a labelled back button without a large title.

- A quick memo from Calendar is appended to the single owned Actual event containing the current user-local time, so its memo indicator appears. When no event matches, multiple events match, or the combined text would exceed the event memo 500-character limit, it is saved as a standalone memo with a corresponding message. Attached quick memos are stored only in the event, not duplicated as standalone notes; earlier standalone memos are not retroactively linked.

- Calendar displays standalone memos at their local creation time as grouped Actual-grid markers when the corresponding slot has no Actual block; selecting a marker opens a memo list without creating an event.

- Dashboard hides links to the prototype pages; the working Schedule link remains available. Calendar offers a review link from 22:00 to midnight when today’s review is not submitted.

## Repeated To-do entry point review (2026-11-14)

Reusable To-do templates are managed in the To-do recurring tab, using plan_block_templates and the existing /plan/block-template endpoints. The separate /plan menu now lists day templates only.

The daily To-do tab additionally inserts an enabled template into today’s dated items and marks it used for that date. The Plan-block tab is a library used by Calendar placement and the separate daily-template editor. CRUD is currently duplicated across the two entry points. Recommended future simplification: use the daily recurring tab primarily for selecting/adding to today, and centralize template management in the Plan menu. This review did not remove or merge either UI.

### Day-template editor (2026-11-14)
- Long press/click an empty time cell (450 ms) to register a schedule; dragging before activation cancels selection so normal scrolling remains available.
- The registration sheet has 일정 등록 / 반복 일정 tabs. Reusable sources copy title, importance and goal into the selected time range; the original source is unchanged.
- Tap an existing schedule to edit its title, importance and goal, or delete it. The sheet omits start/end inputs: the selected or existing range is preserved, and time changes use timetable movement/resize handles. Long press its body to move; long press either endpoint handle to resize in 10-minute steps. Handles also accept arrow keys (left/right 10 minutes; up/down one hour).
- Overlaps and ranges outside 00:00–24:00 are rejected. Invalid/cancelled gestures retain the original block. Changes persist with the page-level template save; existing date-specific plans are unchanged.

- Day-template create/edit uses the focused Calendar Plan appearance: hour-only labels (00–23) on the right, shared importance badges and resize-handle design. Usage and edit-scope guidance appears below the timetable.

- Day-template read-only details reuse the create/edit focused Plan timetable, including right-side hour-only labels and neutral importance badges. The editor’s existing-template save button uses red text/border on the surface background; new-template creation retains its primary action.

- Template-editor endpoint handles remain visible independently of Calendar work-mode visibility rules and use a high-contrast current-color marker.
