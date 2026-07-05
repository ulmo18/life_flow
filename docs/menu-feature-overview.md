# Menu Feature Overview

## Purpose
This document describes the intended roles of the main LifeFlow menus before feature work begins.

Read this file before changing menu behavior, menu navigation, calendar presentation of plan/routine data, or retrospective reporting flows.

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
- Routine should be visible in the calendar in a way that is easy to understand and quick to confirm.
- Routine data also feeds retrospective reports.

## Calendar
- Calendar is the actual execution screen.
- Users enter real schedules here based on what they actually did.
- Calendar uses the current day-grid interaction model.
- If a calendar entry overlaps with a plan, the UI should help the user confirm whether the entry is a copy of that plan or a different schedule.
- Actual calendar entries are used for retrospective reports together with Plan and Routine data.

## Retrospect
- Retrospect is a daily reflection and reporting area.
- It appears twice a day through calendar reminders:
  - Morning retrospect shows yesterday's created retrospect.
  - Evening retrospect shows today's created retrospect.
- Retrospect entries should be stored as report-style documents.
- Retrospect history must be viewable later at any time.
- Retrospect should summarize progress based on calendar, plan, routine, and goal data.

## Goal
- Goal is a higher-level planning structure.
- Goal depth should support:
  - Bucket list
  - Yearly goal
  - Half-year goal
  - Quarterly goal
  - Monthly goal
- Goals connect to plan schedules, actual schedules, and routines.
- Goal data is used in retrospective feedback to show how well the user followed through and how much progress was made.

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
