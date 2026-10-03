# To-do Feature Implementation

## Role

`GET /todo` is the current-day execution list. It reads `daily_plan_items` for Today in the user's configured IANA time zone and does not create a parallel To-do table.

Each row shows the copied or directly-created Plan title, importance, planned time or time-unspecified label, connected Goal, and completion state. A non-deleted `calendar_events` row linked through `daily_plan_item_id` is the completion source of truth.

## Completion

- `POST /todo/complete` requires authentication and CSRF validation.
- A timed Plan item first proposes its planned start and end indexes.
- When the proposed range does not overlap another timed Actual event, the request creates a timed Actual event and links it to the Plan item in the repository transaction.
- A time-unspecified item returns `needsTime`, with a current-time suggestion, before creating an Actual event.
- An overlapping proposed range returns `needsTime` and preserves the attempted range so the time-selection sheet can open with useful defaults.
- The server revalidates ownership, current date, range validity, overlap, and one-to-one active Plan linkage. Client-side checks are advisory only.
- The Actual event copies the Plan title. Tag and memo start empty and remain editable from Today/Calendar.

## Routes

- `GET /todo`: render today's list.
- `POST /todo/complete`: create and link the Actual event, or return an HTTP 409 JSON response requesting another time.

## Persistence

Timed and time-unspecified intentions share `daily_plan_items`. A valid range has both `start_index` and `end_index`; an unspecified range has both values null. Actual execution stays separate in `calendar_events` so Retrospect can compare intention and execution.
