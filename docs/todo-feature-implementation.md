# To-do Feature Implementation

## Timed placement reminders

### Yesterday review and explicit registration

Today's To-do list starts with a collapsed notice when yesterday contains owned, non-deleted sources in `in_progress` or `postponed`. Older dates, pending/completed/cancelled/delegated sources and other accounts are excluded. Expanding shows source state/date, the follow-up note, an original-detail link and a per-item “오늘 할 일로 등록” action. Merely viewing or expanding creates nothing. Historical/future pages do not show the Today-only notice.

`POST /todo/carryover` requires CSRF and validates source ownership, yesterday's date and its current state. The target is the user's current local date; posted target dates are ignored. Explicit registration creates one untimed pending To-do with title, importance, an available owned Goal and the previous note. Yesterday's status, blocks, Actuals and alarms are unchanged; time blocks, Actual records and alarm settings are not copied. `source_carryover_item_id` preserves provenance, and a unique daily-plan/source pair makes repeated requests idempotent. The notice marks an already registered source.

`GET/POST /todo/followup` reads/saves an optional 500-character note on the owned source. Schedule Plan details and historical To-do state details can use the shared editor. Progress/postponed selections now reveal an optional next-action editor in the To-do state sheet and wait for the explicit 상태 저장 action. Other states retain immediate saving. The state and optional note share one owned update. The optional historical note remains available for reviewing yesterday’s original. Note saves never implicitly change state. The original-detail URL includes `item_id` so the source sheet opens directly.

State choices in both sheets use one six-option row, in pending/progress/completed/postponed/delegated/cancelled order. Compact gaps retain 44px minimum targets; exceptionally narrow sheets may scroll horizontally. Existing MySQL installs require the follow-up and carryover migrations in `database-migrations.md`; SQLite upgrades automatically.

The “상태와 연결 기록” sheet renders the shared reminder editor under every timed Plan placement, including other dates. Each block independently owns its opt-in and 0/5/10/15/30/60-minute lead. Saving uses the Calendar reminder endpoint and synchronizes the block's actual date. Live controls and the inert detail template both refresh so closing/reopening retains the value. Untimed sources alone have no reminder editor. See `notification-feature-implementation.md`.

The shared editor is initially collapsed. Expansion shows global states and a Settings link if either master/Plan notifications is off; otherwise it presents off/time choice buttons and save. Calendar Plan details reuse `/todo/status` to change this same source state, without a separate completion store or an Actual-link requirement.

## Role

`GET /todo` is the current-day execution list. It reads `daily_plan_items` for Today in the user's configured IANA time zone and does not create a parallel To-do table.

The page opens on `할 일`, followed by `반복 할 일`. The To-do table shows each item's importance, editable name and state symbol, without plan or Actual counts. The 44px state button opens one bottom sheet containing both state choices and the selected item's plan/Actual tree; closing returns focus to that button. Plans and Actuals show clock ranges and minutes; each Actual line shows a clock icon and its own duration minus its plan's duration, negative in red, positive in green and zero in the normal muted color. The accessible label describes the comparison. Untimed Actuals have no difference. With no plan or no linked Actual, the sheet displays `배치된 계획이 없어요` or `실제 기록 없음`. Item-only Actual links whose exact plan cannot be determined appear separately as `계획 연결 미지정`. Deleted or unavailable sources retain accessible records in the sheet, but their state choices are disabled.

To-dos and recurring templates with a linked Goal show a flag immediately after the name, including read-only and disabled-template rows. Long names truncate while keeping the flag visible; screen readers announce the Goal link.

An item's state button opens a bottom sheet: `V 완료`, `X 취소`, `→ 연기`, `· 진행 중`, `○ 위임`, and `미정`. Selection saves the owned source via `POST /todo/status`, returns HTTP 200 JSON on success, updates the button without a reload, and closes the sheet. Completion does not require an Actual link. Failed saves keep the old state; malformed responses or network errors prompt a reload to verify the saved value without exposing technical parsing details. State can be set for any selected date; other source editing remains today-only. Calendar displays To-do-backed plans using the source state and importance; Retrospect has not yet adopted them.

A To-do receives a brand-color highlighter behind its title when at least one linked Plan block is longer than 60 minutes. Blocks are evaluated individually, not summed; exactly 60 minutes, unplaced items, and estimated duration alone do not trigger it. Legacy timed items without block support use their own time range. A screen-reader-only description identifies the reason.

The tabs switch without navigation or a page reload and support arrow-key navigation. The To-do tab defaults to importance order A, B, C, D and can switch without a reload to status order 미정, 진행 중, 완료, 취소, 연기, 위임. Equal values retain their initial order. A successful status change reapplies the selected order; a failed save leaves it unchanged. The recurring tab count includes only enabled templates; the unused group has its own count.

The `반복 할 일` tab shows the current user's non-deleted reusable templates in vertically stacked `사용` and `미사용` groups. The `미사용` group is initially collapsed and can be opened or closed. Disabled names are struck through, and their editor can switch them back on; enabled items can similarly be switched off. Disabled templates cannot be added to the day's To-dos and are omitted from Calendar placement, without changing existing copies. On today, `사용하기` copies title, importance and eligible Goal into a new, time-unspecified To-do; the action is disabled on other dates. The server verifies ownership and enabled status. The recurring tab's `반복 할 일 추가` button creates a reusable template with title, importance and optional Goal, using the existing hidden default duration. Creation is available on any viewed date, preserves that date and returns to the recurring tab. A failed save leaves the sheet open with an error.

Placing a recurring template directly in Calendar also creates a dated To-do and links the placed block to it. Calendar shows that To-do in place of the template for the selected date. Existing template-only historical blocks are not automatically converted.

The recurring tab shows a check mark and disables the add button when the template has an active To-do for the selected date, whether it was added from To-do or placed in Calendar. The add and added controls have accessible names and match the To-do status button's 44px touch area. Repeated requests reuse that To-do. Deleting the To-do makes the template available again for that date; other dates are unaffected.

The recurring groups use the same bordered table surface, compact cell padding, typography, dividers and importance badges as the To-do tab. The unused group remains collapsed by default.

Selecting a recurring To-do name opens the existing recurring editor with its name, importance and Goal; its hidden stored duration is preserved. Save updates the reusable template. The delete action requires a 1.5-second pointer or keyboard hold with progress feedback; releasing early shows a toast, and cancelling the hold makes no request. Editing or deleting the template does not change previously copied To-dos, Plan blocks or Actual records. Success returns to the recurring tab on the selected date; failure leaves the sheet open.

The shared recurring sheet hides the usage-state and delete actions when creating a new template; both actions appear only while editing an existing template.

`할 일 추가` is a floating action above the bottom navigator on every viewed date. Its bottom sheet accepts a title, importance and Goal, creates a time-unspecified `daily_plan_items` row for the selected date, and returns to that date after submission. Editing existing To-dos remains limited to today. To-do remains a view over the same Plan data rather than a parallel store.

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

To-do creation, editing, recurring creation/editing, status and time sheets keep Tab/Shift+Tab within their visible controls and defer to native date dialogs or shared confirmations. The create sheet returns focus to its add button when dismissed; existing edit/status/recurring focus restoration remains in place.

The shared To-do/Calendar importance controls use 44px radio targets and visible keyboard focus in both themes. Time inputs share equal shrinkable columns and common label typography; sheet height uses the dynamic viewport with a legacy `vh` fallback. Selection and persistence behavior are unchanged.

- `GET /todo`: render the selected date's list, defaulting to today.
- `POST /todo`: create a time-unspecified Plan item for the selected date, defaulting to today.
- `POST /todo/update`: edit the title, importance and Goal of one owned item for the current date without altering its time or links.
- `POST /todo/delete`: delete one owned source for today with CSRF protection; placed Plans and their Actual links remain.
- `POST /todo/recurring`: create a reusable template using the shared Plan validation and storage, with CSRF protection.
- `POST /todo/complete`: create and link the Actual event, or return an HTTP 409 JSON response requesting another time.

## Persistence

Timed and time-unspecified intentions share `daily_plan_items`. `todo_status` stores the explicit item state, initially `pending`; existing block states are not overwritten or automatically interpreted as item completion. A valid range has both `start_index` and `end_index`; an unspecified range has both values null. Actual execution stays separate in `calendar_events`.

## Manual test checklist

- Set a time-unspecified To-do to completed without an Actual and verify the symbol and accessible label update. Place it twice, connect several Actuals to one plan, and verify the state sheet includes each Actual's signed difference, internal scrolling and focus restoration. For an item without plans, verify its empty message. Delete the source and verify its read-only state and plan/Actual records remain accessible through the same button. Simulate a failed state request and verify the previous state remains.

- Confirm `할 일` is selected on entry and `반복 할 일` opens by touch and arrow keys without a reload. Use a recurring item on today and confirm it appears in the To-do list.
- Check that each tab shows only its corresponding add button. Create a recurring item, verify it appears in that tab and the selected date remains unchanged, and simulate a failed save. On another date confirm the recurring add button remains available while `사용하기` remains disabled.

- Enable and disable Plan block templates in Plan management, then open today's recurring To-do tab: the tab count includes only enabled templates, and disabled templates appear in the collapsed unused group. Add an enabled template and confirm one independent unfinished, time-unspecified To-do is created with the template's title and importance. Disable or edit the template and confirm the created To-do remains unchanged. Other dates cannot add templates.
- Open the add sheet and verify direct entry; search for a matching and a nonexistent template in the recurring tab and clear the search.

- Confirm each tab's empty message and count, and that an editable source name opens its edit sheet by mouse, touch, and keyboard.
- Confirm a linked 70-minute Plan highlights the To-do title; a 60-minute Plan, multiple shorter Plans totaling over an hour, and an unplaced To-do do not. Verify the highlight updates after resizing or deleting the long Plan and reloading.
- Edit items with and without a Goal. Confirm the summary shows `연결하기 ›` or the selected Goal name, the inline list scrolls independently when long, and saving persists the selected radio value.
- Open the edit sheet by selecting a name, press Escape or close it, and confirm focus returns to the name.
- In the edit sheet, release `할 일 삭제` before 1.5 seconds and confirm the toast and unchanged data. Hold for 1.5 seconds and confirm deletion, retained plan and Actual records, and no duplicate request. Verify pointer cancellation, keyboard hold and a failed request.
- Confirm the edit sheet contains no time or Actual-link controls. Edit a timed item linked to Actual events and confirm its time, Plan blocks and Actual links remain unchanged after saving and reloading.

## Optional location

Dated To-do Add/Edit share the optional location section with Actual schedules. Search/select a place to draft its map card; normal form save persists/removes it atomically with the owned To-do. Existing dates retain their established edit permissions. See `location-feature-implementation.md` for migration, Kakao domain setup and verification. Recurring templates are outside this addition.

## Dashboard data reuse (2026-11-13)

Dashboard uses getDateData items/completedItems for the same today counts as To-do. planItems now additionally expose endIndex for nearest-event interval comparison, in both placement-backed and legacy paths. No To-do state, visibility, completion, copy or persistence behavior changes.

## Yesterday notice and immediate status correction (2026-11-13)

The current-date tab shows yesterday’s in_progress/postponed records in a separate aside above the “오늘 할 일” list. The notice explicitly states that yesterday’s records are not automatically added. Current-day counts and rows remain unchanged until the user explicitly registers an item; the original date/state remains unchanged. Older records, other users, deleted records and other states are excluded. Template-backed owned source items are included in the notice and source lookup instead of being silently excluded. The notice still renders on databases without optional follow-up/carryover columns; registration remains disabled when unsupported.

The progress/postponed bug left status buttons disabled after a successful save while keeping the sheet open for followup input. The handler now refreshes selected/enabled states and closes consistently for every successful state. Opening another item during an in-flight status save is blocked. The earlier immediate-close correction is superseded by the explicit progress/postponed draft-and-save flow below; optional notes are retained.

Validation: todo_status_sheet.test.js now covers progress → reopen → postponed → reopen and confirms enabled controls/selected state/closure. Isolated SQLite todo_yesterday_notice.php covers yesterday-only filtering, template sources, ownership, no automatic copy, explicit/idempotent registration, original-state retention, separate today view, no today followup and legacy-schema display. Manual: change today’s task to progress/postponed, reopen and change again; verify yesterday’s notice above today’s list, then review/register one item and compare counts and original state.

## Yesterday review sheet (2026-11-14)

The current-day notice is now a button opening todoCarryoverSheet, rather than an inline details disclosure. The shared todo-layer/todo-sheet/ui-standard-sheet shell provides a fixed overlay, internal scrolling, close/overlay/Escape handling, focus return and the existing Tab trap. Original-item links and explicit registration forms move into the sheet; reviewing still never copies tasks automatically.

Disabled register buttons now expose a visible associated reason: already registered today, or the required application database update is not yet applied. The connected MySQL instance was inspected read-only and reported follow-up/carryover support unavailable. No database migrations were executed in this task. Existing sql/migration.todo_followup.mysql.sql and sql/migration.todo_carryover.mysql.sql are the documented upgrades; the button remains gated until support is available.

Validation: PHP view/model regression for separate notice, sheet structure and registered explanation; JS 34 files including shared focus handling; Chrome 320/390/1024px × two themes verifies opening, initial hidden state, scroll lock, viewport fit, reasons, close button/overlay/Escape, no overflow and the new bottom labels. The sheet was visually inspected. Manual: open the notice, inspect the disabled reason, visit yesterday’s original, close via all controls and verify focus return. Registration requires the existing schema support and must preserve yesterday’s state.

## State and optional note in one flow (2026-11-14)

Selecting in_progress or postponed updates the selected draft and reveals an optional textarea labelled 다음에 할 행동 or 미룬 이유 · 다음 행동. The existing note is loaded when opening the state sheet; Save is unavailable until that read finishes, avoiding accidental blank overwrites. The dedicated 상태 저장 button sends the selected state and note together to /todo/status. An empty note is valid. Other states still save immediately without modifying notes. Close/overlay/Escape discards the unsaved selection/note; a failed POST leaves both drafts intact for retry.

The service validates the optional 500-character note and allowed state before writing. CalendarRepository updates todo_status and optional followup_note in one prepared, owner-scoped UPDATE; invalid/unsupported notes never leave a partially changed state. Omitting note preserves the existing note. Calendar Plan editing and the existing follow-up endpoint remain compatible. A native capability check found followup_note already present in the configured MySQL database, so the prepared migration was skipped and no DB schema change was executed.

Validation: todo_status_note.php covers atomic persistence, invalid length, ownership, optional clearing, same-state note edits, state-only preservation and legacy capability fallback. todo_status_sheet.test.js covers draft-only selection, one combined request, optional empty note, failed-save retry and fast saving for other states. Chrome 320/390/1024px in both themes verifies load/reopen, labels, draft cancellation, combined save, optional note, failure retention, aside naming and no overflow; the sheet was visually inspected. Manual: select progress/postponed, edit/omit the note and save/reopen; close without saving; confirm failed saves preserve drafts and another state still saves quickly.

## Carryover re-registration after deletion (2026-11-14)

Registration lookups and the yesterday notice count only non-deleted current-day copies. Soft-deleting a copy clears its optional source_carryover_item_id in the same transaction, releasing the daily-plan/source unique key without restoring the deleted task. Re-registration creates a fresh untimed pending copy from yesterday’s current source data.

Older deleted copies may still hold their unique key. The registration transaction now clears that key only on deleted rows for the same daily plan and source before inserting. Active copies are untouched and repeated registration remains idempotent; unique-key conflict fallback also returns active copies only. Yesterday’s source state and records are unchanged. No schema migration or bulk data rewrite is required.

Validation: isolated SQLite tests cover delete → notice re-enabled → fresh registration, repeated registration, an older soft-delete retaining its key, other-user delete rejection and original-state retention. The same delete/re-register/legacy-key sequence passed against configured MySQL using a temporary fixture account, which was removed afterward. Manual: register yesterday’s progress/postponed item, delete today’s copy, reopen yesterday’s sheet and register again; confirm exactly one active copy and yesterday’s status unchanged.
