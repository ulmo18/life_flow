# Retrospect Feature Implementation

## Scope

Retrospect is the daily review area. It lets a user review the selected day, compare plans with actual events, check routine follow-through, write three reflection fields, save the current memo, publish the report, and revisit published reports by date.

The current implementation supports:

- date navigation with previous and next day links
- direct date lookup through a bottom-sheet date picker
- a Today button when the selected Retrospect date is not today
- today's editable KPT retrospect memo before publishing
- manual memo saving for today's KPT retrospect text fields
- manual publishing of a retrospect report
- a floating publish or republish button aligned with the Plan menu's main action pattern
- automatic publish time settings through a bottom sheet
- current automatic publish state shown in the right-aligned toolbar with Today and date lookup actions
- opportunistic automatic publishing when the user opens today's Retrospect page after the configured time
- minimal empty state for past or future dates without a published report
- plan achievement rate based on linked plan blocks
- routine achievement rate based on active routines marked `O`
- linked actual-event time shown as `X시간 X분`
- today routine list with state labels
- actual-event list sorted by time or tag
- snapshot persistence for published plan, actual-event, and routine items

The three retrospect text fields are displayed as KPT prompts:

- `today_review`: "오늘의 잘한 점(Keep)"
- `today_thoughts`: "오늘의 아쉬운 점(Problem)"
- `tomorrow_plan`: "내일을 위해 개선할 점(Try)"

The section helper copy is "오늘의 나의 감정, 이벤트, 내가 배운 점 등을 기억나는 대로 써보자".

## Tables

### `retrospect_settings`

Stores per-user Retrospect automation settings.

Important columns:

- `id`
- `user_id`
- `auto_publish_enabled`
- `auto_publish_time`
- `created_at`
- `updated_at`

Rules:

- `UNIQUE(user_id)` keeps one setting row per user.
- `auto_publish_time` is stored as a local app time using the app timezone (`Asia/Seoul`).
- The current automatic publish behavior is opportunistic. It runs when the user opens today's Retrospect page after the configured time, not from a background scheduler.

### `retrospect_reports`

Stores the daily report header, text fields, and summary metrics.

Important columns:

- `id`
- `user_id`
- `report_date`
- `status`
- `today_review`
- `today_thoughts`
- `tomorrow_plan`
- `plan_total_count`
- `plan_linked_count`
- `plan_unlinked_count`
- `plan_achievement_rate`
- `routine_total_count`
- `routine_done_count`
- `routine_achievement_rate`
- `linked_actual_minutes`
- `linked_actual_count`
- `submitted_at`
- `created_at`
- `updated_at`

Rules:

- `UNIQUE(user_id, report_date)` keeps one report per user/date.
- `status` is `draft` or `submitted`.
- Today's text can be saved as a memo before publishing.
- A submitted report reads from snapshot tables so later calendar, plan, routine, or tag edits do not rewrite the published report.

### `retrospect_report_plan_items`

Stores plan-block snapshots at publish time.

Important columns:

- `report_id`
- `plan_group_id`
- `plan_block_id`
- `plan_template_id`
- `title_snapshot`
- `start_index`
- `end_index`
- `importance_snapshot`
- `is_linked`
- `sort_order`

Rules:

- Plan achievement rate is `linked plan items / total plan items`.
- A plan item is linked when an actual event references the same `plan_template_id`.

### `retrospect_report_actual_items`

Stores actual-event snapshots at publish time.

Important columns:

- `report_id`
- `calendar_day_id`
- `calendar_event_id`
- `title_snapshot`
- `start_index`
- `end_index`
- `tag_name_snapshot`
- `tag_color_snapshot`
- `plan_template_id_snapshot`
- `plan_importance_snapshot`
- `is_linked`
- `sort_order`

Rules:

- Actual-event duration is calculated from the original event row, not split calendar display segments.
- "Actual time" is the sum of durations for linked actual events.
- The list can be sorted by time or tag in the UI.

### `retrospect_report_routine_items`

Stores routine snapshots at publish time.

Important columns:

- `report_id`
- `routine_id`
- `routine_name_snapshot`
- `state_snapshot`
- `was_active`
- `sort_order`

Rules:

- `state_snapshot` is `blank`, `O`, or `X`.
- Routine achievement rate is `O routines / active routines`.
- The active routine set follows the selected report date.

## Routes

- `GET /retrospect`: render the selected Retrospect day. Accepts `date=YYYY-MM-DD` and `sort=time|tag`.
- `POST /retrospect/draft`: save today's retrospect text fields as a memo.
- `POST /retrospect/publish`: publish a report snapshot for the selected date.
- `POST /retrospect/republish`: rebuild an already submitted report snapshot from the current Calendar, Plan, and Routine data while preserving the saved retrospect text fields.
- `POST /retrospect/settings`: update automatic publish settings.

All POST routes require CSRF verification.

## UI Behavior

- The page opens on today's date by default.
- Previous and next controls move through dates like Calendar.
- The date lookup button opens a bottom sheet with a date input.
- The Today, date lookup, and automatic publish buttons share the same right-aligned toolbar.
- When the selected date is not today, a button-styled Today action returns to today's Retrospect page.
- Today shows a live editable memo when the report has not been submitted.
- The memo save button stores today's three KPT text fields without finalizing metrics.
- The floating publish button finalizes the report and creates metric/item snapshots.
- Published reports show persisted snapshots instead of recalculating live data.
- Published reports expose a floating republish action so later Calendar or Routine edits can be reflected without deleting the report. Republish keeps the three KPT retrospect text fields and replaces metric/item snapshots with the latest source data.
- Actual-event rows use the event tag color as their background, matching Calendar actual-event cards.
- Actual-event rows show time as `X시간 X분(시작시간 ~ 마감시간)`.
- Past or future dates without a submitted report show only date navigation, date lookup, and an empty-state message.
- The former "previous retrospect" list is intentionally removed because date navigation and direct lookup already cover that flow.
- The automatic publish button opens a bottom sheet and shows either the configured time or the disabled state.
- The routine edit entry uses a button-styled GET form to move to Calendar for the selected date and includes an external-link icon.
- Calendar's Retrospect button opens the latest submitted-report preview on or before the selected calendar date.
- Calendar disables the Retrospect button only when no submitted report exists yet.

## Metric Definitions

- Plan achievement rate: `plan_linked_count / plan_total_count * 100`.
- Routine achievement rate: `routine_done_count / routine_total_count * 100`.
- Actual time: sum of linked actual-event durations in minutes.

## Future Work

- Replace opportunistic automatic publishing with a scheduled notification/job worker when server scheduling is available.
- Connect Goal data after Plan and Routine screens expose `goal_id` selection. The Goal table exists, but Retrospect metrics should wait until lower-level actions are actively linked to goals.
- Add report search if direct date lookup becomes insufficient.
- Add richer feedback copy once there is enough historical data for trend analysis.
