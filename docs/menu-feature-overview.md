# Menu Feature Overview

## Purpose
This document describes the intended roles of the main LifeFlow menus before feature work begins.

Read this file before changing menu behavior, menu navigation, calendar presentation of plan/routine data, or retrospective reporting flows.

## Navigation
- The aside groups navigation as Dashboard, feature menus, and management menus.
- Feature menus are Calendar, Plan, Routine, Retrospect, Goal, and Memo.
- The bottom navigator is optimized for thumb reach in this order: Retrospect, Plan, Routine, Calendar.
- Settings is reached from the aside header gear, and logout is kept inside Settings rather than the main aside menu.
- Settings owns the persisted light/dark display mode.
- Settings owns notification management for Retrospect, Routine, Calendar selected plans, and Goal deadlines.
- The tag management menu label is "Schedule Tag Management" to clarify that it manages calendar schedule tags.
- Goal is a distinct feature menu for creating and managing long-term goals.
- Memo appears directly below Goal in the aside and owns standalone quick notes.

## Plan
- Plan stores reusable daily schedule templates. Editing a template updates that template in place and never changes a date-specific plan already copied into Calendar.
- Plan has two template levels: a Plan-block library for one reusable activity and a daily template that places copied blocks at specific times. A block template stores title, 10-minute-unit default duration, Eisenhower importance, and an optional goal, but no start or end clock time.
- Daily-template editing can mix blocks copied from the Plan-block library with blocks entered directly. Copying isolates the daily template from later library edits or deletion.
- The Plan list omits the large menu-name introduction so the first plan card or empty state begins at the top of the content area; a screen-reader heading remains available.
- A plan is built in the same day-grid style as the calendar.
- Users define a plan from `00:00` to `24:00` in 10-minute blocks.
- Saved plans are shown as a list.
- Selecting a saved Plan in Calendar creates a date-specific copy, including each block's goal link. Calendar edits apply only to that copy.
- Plan data is copied into Calendar as daily intention; the daily copy and its actual links feed retrospective analysis.

## Routine
- Routine is a habit-tracking area.
- Its purpose is consistency, not one-time scheduling.
- Routine data should support day-by-day completion tracking.
- Routine duration is user-selectable from 1 to 60 days, with 60 days as the recommended default for habit formation.
- Active routines can be extended by a user-selected number of days up to 365 days, but their start date and duration cannot be shortened after creation.
- A routine can be completed early or stopped. Its logs remain available to Retrospect after it leaves the active Routine list.
- Routine opens with a weekly summary of active routine count, scheduled-day achievement, and the longest current success streak.
- The Routine page omits the large menu-name introduction so the weekly summary is the first visible card; a screen-reader heading remains available.
- Each Routine card is a small always-visible habit tracker: name, streak, cumulative count and progress, a compact execution grid, linked goal, and compact detail/edit actions.
- A zero-day streak is hidden. Streaks of one or two days use `연속 n일`, and streaks of three days or more add a fire marker (`🔥 연속 n일`) without an `어제까지` prefix.
- The optional linked goal sits in the card's upper-right context area and truncates before it can crowd out the routine name.
- The card tracker shows the whole period for routines up to 14 days. Longer routines show a 14-cell window centered on momentum (normally the previous 10 days, today, and the next 3 planned days, shifted at either period boundary).
- The tracker omits date and weekday labels so completed cells form a simple visual accumulation. Blank and `X` days stay neutral, future cells use a planned style, and today's outlined cell is the direct execution control.
- Routine detail groups only actual routine dates by `YYYY년 M월` and lays them out sequentially in seven columns. It is a period record grid rather than a weekday-aligned calendar; future cells are visible as plans but remain read-only.
- The Routine detail sheet keeps the routine name and close control sticky while its period record scrolls.
- Routine should be visible in the calendar in a way that is easy to understand and quick to confirm.
- Routine data also feeds retrospective reports.
- See `docs/routine-feature-implementation.md` for table, route, and UI details.

## Calendar
- Calendar is the daily intention and execution screen.
- The Plan schedule tab edits only the selected date's copied plan blocks. The Actual schedule tab keeps the existing actual-event behavior.
- Calendar uses the current day-grid interaction model.
- Calendar supports time-unspecified daily Plan items for work that belongs to a date but does not yet have a fixed time range. They stay out of both time grids and appear in the `+` menu's Today Plan list with a `시간 미정` label.
- Calendar uses one Plan-add flow for both timed and time-unspecified daily Plan items. General entry points start without a time, the user may enable `시간 정하기`, and a range selected directly on the Plan grid opens the same form with that range enabled.
- Calendar Plan creation can copy a Plan-block template or accept direct input. When a copied default duration exceeds today's range, Calendar asks whether to discard the later portion and end only that date's copy at `23:50`; it never truncates silently.
- New Actual schedules always require a start and end time. Legacy untimed Actual records may only be selected and placed into a timed range.
- Calendar event creation can mark selected routines complete for the same date, avoiding duplicate completion work across menus.
- Calendar, Routine, and daily Retrospect use the same blank/completed/missed Routine state cell and update only the affected state and metrics after a successful JSON response.
- Calendar's Retrospect button previews the user's latest submitted report regardless of the selected calendar date.
- If a calendar entry overlaps with a plan, the UI should help the user confirm whether the entry is a copy of that plan or a different schedule.
- Actual calendar entries are used for retrospective reports together with Plan and Routine data.
- The floating action opens a bottom sheet with timed Actual-event creation, quick Memo, and Plan-link actions plus the first three daily Plan and Routine items. Longer lists expand in the same sheet.
- Fixed schedule tags can be enabled or hidden per user. Hiding a fixed tag removes it from new event choices while preserving its color and all existing event links.
- Empty Plan and Actual tabs provide a small first-action prompt so a new user can start without learning the full service flow first.
- Actual-event entry keeps the form open while creating a missing tag inline. Tag maintenance stays in Schedule Tag Management, and Plan-link controls stay hidden when no usable Plan item exists.
- Selecting a range across an existing block is rejected before an editor opens, with non-layout-shifting Toast feedback. Background Plan references do not prevent Actual-event entry.
- Desktop Actual-event hover details may include an 80-character memo preview; mobile touch continues to use the edit sheet.
- A time-unspecified Plan item remains selectable as an Actual-event link and can later be edited from the `+` menu to receive a time range.
- Selecting a Plan link for a new Actual event, or changing an existing event to a different Plan link, sets the Actual-event title from that Plan item. Later Actual-title edits stay independent; Calendar does not expose a per-save title-sync selector.
- On today's Calendar, the shared header may show one compact next action. Its priority is a configured Retrospect window, the current Plan, the next Plan, an empty-Plan prompt, then an empty-Actual prompt. The action opens the corresponding Retrospect date or Calendar form directly.

## Retrospect
- Retrospect is a daily reflection and reporting area.
- The Retrospect page omits the large menu-name introduction so its view/date controls and first feedback card start near the top; a screen-reader heading remains available.
- Retrospect uses Calendar actual events, selected Plan blocks, and Routine logs to prepare the daily review.
- Today's KPT remains editable before and after publishing, while published past reports are read-only and viewed by date.
- It appears twice a day through calendar reminders:
  - Morning retrospect shows yesterday's created retrospect.
  - Evening retrospect shows today's created retrospect.
- Retrospect entries should be stored as report-style documents.
- Published retrospects must be viewable later at any time through date navigation or direct date lookup.
- Published retrospects can be republished from the floating action button to refresh metrics and snapshots after Calendar or Routine edits while preserving written reflection text.
- Today's published retrospect can edit and republish KPT text; earlier published KPT remains read-only.
- Retrospect actual-event rows should visually match Calendar actual events by using tag color backgrounds and duration plus time-range labels.
- Retrospect should summarize progress based on calendar, plan, routine, and goal data.
- Retrospect has a daily view and a goal review view. Goal review aggregates goal-linked Plan execution, actual Calendar events, Routine follow-through, and completed Routine history.
- Completed Routine history uses the same month-grouped seven-column period record as Routine detail and keeps past-state correction available without a page reload.
- Standalone Memo records remain independent from goals and appear in daily Retrospect by their creation date.
- Memo trash entries are purged after 30 days when the trash is opened, and users can empty the trash immediately.
- See `docs/retrospect-feature-implementation.md` for table, route, metric, and UI details.

## Goal
- Goal is a higher-level planning structure.
- Goal depth should support:
  - Bucket list
  - Yearly goal
  - Half-year goal
  - Quarterly goal
  - Monthly goal
- Goal hierarchy is optional. Users can start with a monthly goal only, or connect smaller goals under larger goals.
- Goal records support a lightweight behavior reminder memo.
- Goals connect to plan schedules, actual schedules, and routines.
- Goal list supports status and goal-type focused review for active, completed, archived, paused, bucket, yearly, half-year, quarterly, and monthly goals.
- Time-bound goals show period progress so users can quickly see how far the goal window has advanced.
- Goal tree view focuses on active goals and helps users see how larger goals break down into smaller execution goals.
- Goal data is used in retrospective feedback to show how well the user followed through and how much progress was made.
- See `docs/goal-feature-implementation.md` for table, route, and UI details.

## Memo

- Memo stores free-form notes independently from Calendar event memos and Retrospect text.
- Calendar exposes a quick Memo action from its bottom-right `+` menu.
- Notes under 300 trimmed characters are short notes; notes at or above 300 characters are long notes.
- Deleted notes move to a recoverable trash list ordered by the latest deletion time.
- See `docs/memo-feature-implementation.md` for routes, persistence, and UI rules.

## Cross-Menu Rules
- Plan defines reusable intention templates.
- Calendar owns the copied intention for one date and records execution.
- Routine captures repetition and consistency.
- Retrospect summarizes the day and stores the report.
- Goal connects lower-level actions to longer-term outcomes.
- Memo captures standalone thoughts without automatically turning them into Calendar, Routine, or Retrospect records.
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
