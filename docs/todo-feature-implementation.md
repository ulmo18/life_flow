# To-do Feature Implementation

## Role

`GET /todo` is the current-day execution list. It reads `daily_plan_items` for Today in the user's configured IANA time zone and does not create a parallel To-do table.

The page opens on `할 일`, followed by `반복 할 일`. The To-do table shows each item's importance, editable name and state symbol, without plan or Actual counts. The 44px state button opens one bottom sheet containing both state choices and the selected item's plan/Actual tree; closing returns focus to that button. Plans and Actuals show clock ranges and minutes; each Actual line shows a clock icon and its own duration minus its plan's duration, negative in red, positive in green and zero in the normal muted color. The accessible label describes the comparison. Untimed Actuals have no difference. With no plan or no linked Actual, the sheet displays `배치된 계획이 없어요` or `실제 기록 없음`. Item-only Actual links whose exact plan cannot be determined appear separately as `계획 연결 미지정`. Deleted or unavailable sources retain accessible records in the sheet, but their state choices are disabled.

An item's state button opens a bottom sheet: `V 완료`, `X 취소`, `→ 연기`, `· 진행 중`, `○ 위임`, and `미정`. Selection saves the owned source via `POST /todo/status`, returns HTTP 200 JSON on success, updates the button without a reload, and closes the sheet. Completion does not require an Actual link. Failed saves keep the old state; malformed responses or network errors prompt a reload to verify the saved value without exposing technical parsing details. State can be set for any selected date; other source editing remains today-only. Calendar displays To-do-backed plans using the source state and importance; Retrospect has not yet adopted them.

A To-do receives a brand-color highlighter behind its title when at least one linked Plan block is longer than 60 minutes. Blocks are evaluated individually, not summed; exactly 60 minutes, unplaced items, and estimated duration alone do not trigger it. Legacy timed items without block support use their own time range. A screen-reader-only description identifies the reason.

The tabs switch without navigation or a page reload and support arrow-key navigation. Each has its own count and empty message. Source items retain their existing ordering.

The `반복 할 일` tab lists the current user's enabled, non-deleted reusable templates. Name search filters the list. On today, `사용하기` copies title, importance and eligible Goal into a new, time-unspecified To-do; the action is disabled on other dates. The server verifies ownership and enabled status. The recurring tab's `반복 할 일 추가` button creates a reusable template with title, importance and optional Goal, using the existing hidden default duration. Creation is available on any viewed date, preserves that date and returns to the recurring tab. A failed save leaves the sheet open with an error; the To-do tab instead shows the existing `할 일 추가` button only on today.

`할 일 추가` is a floating action above the bottom navigator. Its bottom sheet also accepts direct entry of a title, importance and Goal and creates today's time-unspecified `daily_plan_items` row, so To-do remains a view over the same Plan data rather than a parallel store.

## Completion

- `POST /todo/complete` requires authentication and CSRF validation.
- A timed Plan item first proposes its planned start and end indexes.
- When the proposed range does not overlap another timed Actual event, the request creates a timed Actual event and links it to the Plan item in the repository transaction.
- A time-unspecified item returns `needsTime`, with a current-time suggestion, before creating an Actual event.
- An overlapping proposed range returns `needsTime` and preserves the attempted range so the time-selection sheet can open with useful defaults.
- The server revalidates ownership, current date, range validity, overlap, and one-to-one active Plan linkage. Client-side checks are advisory only.
- The Actual event copies the Plan title. Tag and memo start empty and remain editable from Today/Calendar.

## Editing

- Selecting an editable To-do's name opens its bottom sheet for title, importance and connected Goal. Names remain read-only for dates other than today or unavailable sources. The sheet does not expose time or Actual-link actions. Saving changes only these source details and preserves existing time, Plan blocks and Actual links; submitted schedule or link fields are ignored.
- The same sheet provides `할 일 삭제` as a 1.5-second delayed button with fill progress. Releasing early shows a toast; leaving the button, canceling the pointer, closing the sheet or losing focus cancels the hold. Space and Enter support the same hold. Only today's owned source can be deleted. A successful deletion hides the source but preserves its placed Plan blocks and their Actual links; failed deletion shows an error and keeps the sheet available.
- Goal connection uses the same inline summary-and-list pattern as Calendar's Plan connection without opening a secondary sheet. An unlinked item shows `연결하기 ›`; a linked item shows the selected Goal name, and the internally scrolling radio list continues to submit the existing `goal_id` field.
- The To-do edit sheet omits the borders under the Goal picker and above its action row.
- Rows do not display separate `시간 설정` or `완료` actions. General editing remains in the popover; the existing completion endpoint is retained for compatibility.
- Time placement and Actual links are managed in Calendar.

## Routes

- `GET /todo`: render today's list.
- `POST /todo`: create a title-only, time-unspecified Plan item for today.
- `POST /todo/update`: edit the title, importance and Goal of one owned item for the current date without altering its time or links.
- `POST /todo/delete`: delete one owned source for today with CSRF protection; placed Plans and their Actual links remain.
- `POST /todo/recurring`: create a reusable template using the shared Plan validation and storage, with CSRF protection.
- `POST /todo/complete`: create and link the Actual event, or return an HTTP 409 JSON response requesting another time.

## Persistence

Timed and time-unspecified intentions share `daily_plan_items`. `todo_status` stores the explicit item state, initially `pending`; existing block states are not overwritten or automatically interpreted as item completion. A valid range has both `start_index` and `end_index`; an unspecified range has both values null. Actual execution stays separate in `calendar_events`.

## Manual test checklist

- Set a time-unspecified To-do to completed without an Actual and verify the symbol and accessible label update. Place it twice, connect several Actuals to one plan, and verify the state sheet includes each Actual's signed difference, internal scrolling and focus restoration. For an item without plans, verify its empty message. Delete the source and verify its read-only state and plan/Actual records remain accessible through the same button. Simulate a failed state request and verify the previous state remains.

- Confirm `할 일` is selected on entry and `반복 할 일` opens by touch and arrow keys without a reload. Search and use a recurring item on today and confirm it appears in the To-do list.
- Check that each tab shows only its corresponding add button. Create a recurring item, verify it appears in that tab and the selected date remains unchanged, and simulate a failed save. On another date confirm the recurring add button remains available while `사용하기` remains disabled.

- Enable and disable Plan block templates in Plan management, then open today's recurring To-do tab: only enabled templates belonging to the account appear. Select `사용하기` and confirm one independent unfinished, time-unspecified To-do is created with the template's title and importance. Disable or edit the template and confirm the created To-do remains unchanged. Other dates cannot add templates.
- Open the add sheet and verify direct entry; search for a matching and a nonexistent template in the recurring tab and clear the search.

- Confirm each tab's empty message and count, and that an editable source name opens its edit sheet by mouse, touch, and keyboard.
- Confirm a linked 70-minute Plan highlights the To-do title; a 60-minute Plan, multiple shorter Plans totaling over an hour, and an unplaced To-do do not. Verify the highlight updates after resizing or deleting the long Plan and reloading.
- Edit items with and without a Goal. Confirm the summary shows `연결하기 ›` or the selected Goal name, the inline list scrolls independently when long, and saving persists the selected radio value.
- Open the edit sheet by selecting a name, press Escape or close it, and confirm focus returns to the name.
- In the edit sheet, release `할 일 삭제` before 1.5 seconds and confirm the toast and unchanged data. Hold for 1.5 seconds and confirm deletion, retained plan and Actual records, and no duplicate request. Verify pointer cancellation, keyboard hold and a failed request.
- Confirm the edit sheet contains no time or Actual-link controls. Edit a timed item linked to Actual events and confirm its time, Plan blocks and Actual links remain unchanged after saving and reloading.
