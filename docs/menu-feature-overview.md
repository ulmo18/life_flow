# Menu Feature Overview

## Purpose
This document describes the intended roles of the main LifeFlow menus before feature work begins.

Read this file before changing menu behavior, menu navigation, calendar presentation of plan/routine data, or retrospective reporting flows.

## Navigation
- The aside groups navigation as Dashboard, feature menus, and management menus.
- Feature menus are Calendar, Plan, Routine, Retrospect, and Goal.
- The bottom navigator is optimized for thumb reach in this order: Retrospect, Plan, Routine, Calendar.
- Settings is reached from the aside header gear, and logout is kept inside Settings rather than the main aside menu.
- Settings owns the persisted light/dark display mode.
- The tag management menu label is "Schedule Tag Management" to clarify that it manages calendar schedule tags.
- Goal is a distinct feature menu for creating and managing long-term goals.

## Plan
- Plan stores daily schedule templates.
- A plan is built in the same day-grid style as the calendar.
- Users define a plan from `00:00` to `24:00` in 10-minute blocks.
- Saved plans are shown as a list.
- A saved plan can be selected in the calendar and displayed as a gray schedule block.
- Plan data is a template source for actual calendar entries and retrospective analysis.

## Routine
- Routine is a habit-tracking area.
- Its purpose is consistency, not one-time scheduling.
- Routine data should support day-by-day completion tracking.
- Routine duration is user-selectable from 7 to 60 days, with 60 days as the default.
- Routine list lawns show cumulative completed days, not the exact dates that were done or missed.
- Routine detail can show date-ordered execution rows for the full routine period so daily states can be corrected.
- Routine detail keeps long date-ordered execution rows scrollable inside the bottom sheet.
- Routine should be visible in the calendar in a way that is easy to understand and quick to confirm.
- Routine data also feeds retrospective reports.
- See `docs/routine-feature-implementation.md` for table, route, and UI details.

## Calendar
- Calendar is the actual execution screen.
- Users enter real schedules here based on what they actually did.
- Calendar uses the current day-grid interaction model.
- Calendar uses the latest submitted Retrospect report as a reminder for how to approach the selected day.
- If a calendar entry overlaps with a plan, the UI should help the user confirm whether the entry is a copy of that plan or a different schedule.
- Actual calendar entries are used for retrospective reports together with Plan and Routine data.

## Retrospect
- Retrospect is a daily reflection and reporting area.
- Retrospect uses Calendar actual events, selected Plan blocks, and Routine logs to prepare the daily review.
- Today can keep an editable KPT memo until the user publishes it, while published past reports are viewed by date.
- It appears twice a day through calendar reminders:
  - Morning retrospect shows yesterday's created retrospect.
  - Evening retrospect shows today's created retrospect.
- Retrospect entries should be stored as report-style documents.
- Published retrospects must be viewable later at any time through date navigation or direct date lookup.
- Published retrospects can be republished from the floating action button to refresh metrics and snapshots after Calendar or Routine edits while preserving written reflection text.
- Retrospect actual-event rows should visually match Calendar actual events by using tag color backgrounds and duration plus time-range labels.
- Retrospect should summarize progress based on calendar, plan, routine, and goal data.
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
- Goal data is used in retrospective feedback to show how well the user followed through and how much progress was made.
- See `docs/goal-feature-implementation.md` for table, route, and UI details.

## Cross-Menu Rules
- Plan defines intention.
- Calendar records execution.
- Routine captures repetition and consistency.
- Retrospect summarizes the day and stores the report.
- Goal connects lower-level actions to longer-term outcomes.
- Keep these responsibilities separate in both UI and data design.

## Notes For Future Work
- Keep the plan-to-calendar selection flow simple and predictable.
- Keep routine display light enough that it does not block actual schedule input.
- Keep retrospect reports durable and searchable.
- Keep goal linkage flexible so it can evolve as the feature set grows.
