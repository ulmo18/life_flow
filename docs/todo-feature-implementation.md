# To-do Feature Implementation

## Role

`GET /todo` is the current-day execution list. It reads `daily_plan_items` for Today in the user's configured IANA time zone and does not create a parallel To-do table.

Each row shows the copied or directly-created Plan title, importance, planned time or time-unspecified label, connected Goal, and completion state. A non-deleted `calendar_events` row linked through `daily_plan_item_id` is the completion source of truth.

A timed item whose duration is at least one hour receives a brand-color highlighter behind its title. The text remains visible and a screen-reader-only description identifies why the item is emphasized.

Rows are split into visible Unfinished and Completed groups. Both headings remain visible when empty so their role is discoverable, with a gentle completion prompt in an empty Completed group. Unfinished rows come first; each group sorts by Eisenhower importance A through D, then planned start time, then stable item ID. Compact divider rows replace repeated cards. Completed rows remain editable for corrections but are visually subdued and omit the redundant `완료됨` label.

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

- Every To-do row exposes general editing from a keyboard- and touch-accessible vertical-more popover. It opens the bottom-sheet editor for title, importance, connected Goal, and time setting. The popover closes on outside click or Escape and restores focus when dismissed from the keyboard.
- Goal connection uses the same inline summary-and-list pattern as Calendar's Plan connection without opening a secondary sheet. An unlinked item shows `연결하기 ›`; a linked item shows the selected Goal name, and the internally scrolling radio list continues to submit the existing `goal_id` field.
- A pending time-unspecified row also keeps the primary next action `시간 설정` visible; the same editor can return a timed item to `시간 미정`. Timed and completed rows keep general modification in the popover.
- The edit sheet hides and disables start/end controls while `시간 미정` is selected, and reveals and requires both controls only for `시간 설정`.
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
- Confirm rows use dividers rather than individual cards, completed rows do not repeat a `완료됨` label, and the vertical-more menu opens by mouse, touch, and keyboard.
- Confirm a timed item lasting at least one hour has the title highlighter and shorter or time-unspecified items do not.
- Edit items with and without a Goal. Confirm the summary shows `연결하기 ›` or the selected Goal name, the inline list scrolls independently when long, and saving persists the selected radio value.
- Switch repeatedly between `시간 미정` and `시간 설정`; confirm the time controls disappear completely when unspecified and reappear with valid values when timed.
- Open a row menu, press Escape, and confirm it closes and returns focus to the trigger. Open it again and click outside to confirm it closes.
- Open the editor and confirm the two time radio buttons replace the select and start/end fields exist only while `시간 설정` is selected.
- Open a time-unspecified item, assign a non-overlapping range, and confirm it appears in Today's Plan grid without becoming completed.
- Edit title, importance, Goal, and time, then reload both To-do and Today and confirm both surfaces show the same Plan item.
- Edit a completed item with each link action and confirm Plan-only leaves Actual unchanged, synchronize updates the linked Actual when its time is available, and detach preserves the Actual while removing its Plan link.
- Attempt an overlapping Plan range and confirm the server rejects it without losing the existing item.
