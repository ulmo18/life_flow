# Retrospect Feature Implementation

## Scope

Retrospect is the daily review surface. It combines the selected date's Routine logs, copied daily Plan items, Actual Calendar events, and standalone Memos before the user writes and publishes a KPT report.

Daily Retrospect also provides optional Execution Energy pacing feedback. The editable condition and perceived-burden form belongs to Today's Calendar `+` menu; Retrospect shows the calculated record and feedback only. Execution Energy remains a self-management estimate and is not presented as a health score or medical result.

## Main Views

- `일일 회고` shows date navigation, a 100% Routine/Plan/Actual/Memo composition bar, and `루틴 / 일정 / 메모` record tabs. Plan and Actual are counted independently even when linked, time-unspecified Plan items are included, and largest-remainder rounding keeps the displayed total at exactly 100%.
- The conditional `오늘로 이동`, `날짜 선택`, and automatic-publish controls use the same compact secondary-button dimensions and typography.
- `발행 이력` lists submitted reports by date in pages of 20. Opening an item returns to that date's report. Republishing updates that date's one report; version history is intentionally not stored.
- `목표 회고` retains aggregate Goal execution feedback.

### Daily tabs

- Routine is the initial tab. Today can be toggled from Retrospect; previous dates are read-only here and are corrected only in Routine Management.
- A Routine row shows a fire marker from a three-day streak onward. Its dedicated Routine detail sheet shows the connected Goal and a weekday-aligned calendar covering the Routine period.
- Routine and Schedule records use compact table layouts instead of separate block cards. Routine columns are state, name, and streak; Schedule columns are link state, title, time, and difference. On narrow mobile screens the Schedule time column may be omitted because full timing remains available in detail.
- Schedule combines each Plan item with its linked Actual event so the same execution is not listed twice. Unlinked Actual events are labeled `계획 외 일정`.
- Schedule defaults to importance order. Time order merges linked Plan/Actual rows and plan-free Actual rows on one timeline; time-unspecified plans follow timed rows.
- A changed Plan/Actual pair shows `🕒 일정 변경` plus the duration difference. The dedicated Schedule detail sheet shows planned time, actual time, start difference, end difference, duration difference, connected Goal, and Actual memo.
- An Actual event uses its Schedule Tag color only as a left accent line. Linked Plan/Actual rows and plan-free Actual rows use the same accent rule; row backgrounds remain neutral.
- Memo rows show the user-time-zone creation time and a one-line ellipsis. Selecting a Memo opens its full read-only content in a dedicated Memo detail sheet.
- Routine, Schedule, and Memo details use separate titled layers; a detail layer never contains panels from another record type.
- The Execution Energy card shows starting capacity, current capacity, plan-aware predicted remaining capacity, consumed amount, recovered amount, over-consumption, and recovery overflow. Perceived burden is collected in Today's Calendar condition sheet and remains feedback only; it does not automatically rewrite group values.

### Empty states

- No Routine: show `오늘 확인할 루틴이 없어요.` and link `루틴 만들기` to `/manage/routines`.
- No schedule: show `오늘 계획된 일정이 없어요.` and link `오늘 일정 추가` to the selected Calendar date.
- No Memo: show only `오늘 작성한 메모가 없어요.`.

## KPT workflow

- `회고하기` opens a non-modal bottom sheet so the underlying Routine, Schedule, and Memo tabs remain inspectable.
- The sheet uses three exclusive steps: Keep, Problem, and Try. Exactly one input form is visible at a time, and Previous/Next controls move between steps without a combined scroll view.
- Close, Collapse, Previous, Next, and final Publish controls stay in the sheet header. The final step replaces Next with Publish.
- The sheet can be collapsed to its header and reopened without losing the current step or text.
- `닫기` hides the sheet after saving the current text; it does not discard the KPT draft.
- On mobile, the expanded sheet occupies 40% of the dynamic viewport height. While it is expanded, the page receives matching bottom space so every underlying record remains reachable by scrolling.
- Close, Collapse, Previous, Next, Publish, empty-state, and navigation actions reuse the shared button classes and sizing conventions.
- Text changes are debounce-saved through `POST /retrospect/draft` with CSRF protection.
- Empty KPT fields are allowed, but manual publishing asks for confirmation when any field is blank.
- Existing columns remain mapped as `today_review` = Keep, `today_thoughts` = Problem, and `tomorrow_plan` = Try.

## Edit window

- The signed-in user's configured IANA time zone defines today and yesterday.
- Today and yesterday allow draft saving, first publishing, KPT editing, and republishing.
- Reports older than yesterday are read-only. Future reports cannot be written.
- One `retrospect_reports` row exists per user/date. Republishing replaces metrics and child snapshots without creating version history.
- An unpublished future date shows a localized hopeful empty-state title and one randomly selected message from four translation keys. Past unpublished dates keep the normal unavailable-report explanation.

## Published snapshots

- `retrospect_reports` stores the date, KPT text, status, summary metrics, and submission timestamps.
- `retrospect_report_plan_items` stores copied daily Plan fields plus `goal_title_snapshot`.
- `retrospect_report_actual_items` stores Actual event title, memo, time, tag, and Plan-link snapshots.
- `retrospect_report_routine_items` stores that date's Routine state and connected Goal title snapshot.
- `retrospect_report_memo_items` stores Memo content and original creation time at publication.
- Deleted source Memos disappear from an unpublished/live daily view but remain in an already published report. Republishing replaces the snapshot with the currently active same-day Memos.
- Reports published before the Memo-snapshot migration have no reconstructable Memo snapshots until they are republished within the allowed edit window.

## Routes

- `GET /retrospect?date=YYYY-MM-DD&sort=importance|time`: daily report.
- `GET /retrospect?view=history&page=N`: submitted-report history.
- `GET /retrospect?view=goals`: Goal review.
- `POST /retrospect/draft`: auto/manual draft save for today or yesterday.
- `POST /retrospect/publish`: initial publish or republish for today or yesterday.
- `POST /retrospect/republish`: compatibility endpoint that rebuilds the current report snapshot.
- `POST /retrospect/settings`: automatic publish settings.

All POST routes require CSRF verification.

## Calendar integration

- Calendar's Retrospect button resolves the exact selected date.
- If that date has a submitted report, Calendar shows its KPT and summary preview with a full-report link.
- If no submitted report exists for that date, the popup explains the empty state and links to that date's Retrospect page.

## Routine mutation ownership

- `/routine` (today), Calendar, and daily Retrospect can change only today's Routine state.
- `/manage/routines` owns corrections for previous dates.
- The server enforces this boundary; it is not only a hidden/disabled UI rule.

## Migration

New databases use the current schema files. Existing databases apply:

- MySQL/MariaDB: `sql/migration.add_retrospect_review.mysql.sql`
- SQLite: `sql/migration.add_retrospect_review.sqlite.sql` or the SQLite runtime compatibility upgrade
