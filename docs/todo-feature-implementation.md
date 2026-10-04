# To-do Feature Implementation

## Role

`GET /todo` is the current-day execution list. It reads `daily_plan_items` for Today in the user's configured IANA time zone and does not create a parallel To-do table.

Each row shows the copied or directly-created Plan title, importance, planned time or time-unspecified label, connected Goal, and completion state. A non-deleted `calendar_events` row linked through `daily_plan_item_id` is the completion source of truth.

Rows are split into visible Unfinished and Completed groups. Both headings remain visible when empty so their role is discoverable, with a gentle completion prompt in an empty Completed group. Unfinished rows come first; each group sorts by Eisenhower importance A through D, then planned start time, then stable item ID. Completed rows remain editable for corrections but are visually subdued.

`할 일 추가` is a floating action above the bottom navigator. Its bottom sheet accepts only a title and creates today's time-unspecified `daily_plan_items` row with importance D and no Goal, so To-do remains a view over the same Plan data rather than a parallel store.

## Completion

- `POST /todo/complete` requires authentication and CSRF validation.
- A timed Plan item first proposes its planned start and end indexes.
- When the proposed range does not overlap another timed Actual event, the request creates a timed Actual event and links it to the Plan item in the repository transaction.
- A time-unspecified item returns `needsTime`, with a current-time suggestion, before creating an Actual event.
- An overlapping proposed range returns `needsTime` and preserves the attempted range so the time-selection sheet can open with useful defaults.
- The server revalidates ownership, current date, range validity, overlap, and one-to-one active Plan linkage. Client-side checks are advisory only.
- The Actual event copies the Plan title. Tag and memo start empty and remain editable from Today/Calendar.

## Editing and time assignment

- Every To-do row opens a bottom-sheet editor for title, importance, connected Goal, and time setting. Time-unspecified and timed states use a two-button radio choice; start/end controls are enabled and visible only for the timed choice.
- A time-unspecified row labels its editor action `시간 설정`; the same editor can return a timed item to `시간 미정`.
- Plan time assignment changes only `daily_plan_items` and never completes the item or applies Execution Energy.
- Timed Plan overlap checks remain server-authoritative through `CalendarService` and `CalendarRepository`.
- When an item already has a linked Actual event, the editor explicitly offers Plan-only change (default), Plan-and-Actual synchronization, or link detachment. It never silently moves an Actual event.
- Today's Calendar Plan tab lists time-unspecified Plan items and opens its existing Plan editor, so both Today and To-do can assign their time without duplicating persistence rules.

## Routes

- `GET /todo`: render today's list.
- `POST /todo`: create a title-only, time-unspecified Plan item for today.
- `POST /todo/update`: edit one owned Plan item for the current date using the shared Calendar validation and link-action rules.
- `POST /todo/complete`: create and link the Actual event, or return an HTTP 409 JSON response requesting another time.

## Persistence

Timed and time-unspecified intentions share `daily_plan_items`. A valid range has both `start_index` and `end_index`; an unspecified range has both values null. Actual execution stays separate in `calendar_events` so Retrospect can compare intention and execution.

## Manual test checklist

- Create A, B, and D importance items, complete one, and confirm Unfinished appears first, Completed second, and both groups use A-D order.
- With no completed items, confirm the Completed heading, zero count, and encouraging empty message remain visible.
- Open the editor and confirm the two time radio buttons replace the select and start/end fields exist only while `시간 설정` is selected.
- Open a time-unspecified item, assign a non-overlapping range, and confirm it appears in Today's Plan grid without becoming completed.
- Edit title, importance, Goal, and time, then reload both To-do and Today and confirm both surfaces show the same Plan item.
- Edit a completed item with each link action and confirm Plan-only leaves Actual unchanged, synchronize updates the linked Actual when its time is available, and detach preserves the Actual while removing its Plan link.
- Attempt an overlapping Plan range and confirm the server rejects it without losing the existing item.
