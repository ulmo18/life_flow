# Retrospect Feature Implementation

## Scope

Retrospect is the daily review surface. It combines the selected date's Routine logs, copied daily Plan items, Actual Calendar events, and standalone Memos before the user writes and publishes a KPT report.

Daily Retrospect also provides optional Execution Energy pacing feedback. The editable condition and perceived-burden form belongs to Today's Calendar `+` menu; Retrospect shows the calculated record and feedback only. Execution Energy remains a self-management estimate and is not presented as a health score or medical result.

## Main Views

- `일일 회고` shows date navigation, a 100% Routine/Plan/Actual/Memo composition bar, and `루틴 / 일정 / 메모` record tabs. Plan and Actual are counted independently even when linked, time-unspecified Plan items are included, and largest-remainder rounding keeps the displayed total at exactly 100%. Memo activity includes both standalone notes and non-empty Actual-event memos; this is a record-type activity ratio, so one Actual event may contribute to both Actual and Memo.
- The conditional `오늘로 이동`, `날짜 선택`, and automatic-publish controls use the same compact secondary-button dimensions and typography.
- `발행 이력` lists submitted reports by date in pages of 20. Opening an item goes to the dedicated newspaper-style published report at `/retrospect/report?date=YYYY-MM-DD`. Republishing updates that date's one report; version history is intentionally not stored.
- `목표 회고` retains aggregate Goal execution feedback.

### Daily tabs

- Routine is the initial tab. Today can be toggled from Retrospect; previous dates are read-only here and are corrected only in Routine Management.
- A Routine row shows a fire marker from a three-day streak onward. Its dedicated Routine detail sheet shows the connected Goal and a weekday-aligned calendar covering the Routine period.
- Routine and Schedule records use compact table layouts instead of separate block cards. Routine columns are ordered `루틴명 / 연속 달성 / 루틴 완료 체크`, leaving the flexible width for the name. The Schedule title owns the flexible width; on mobile, time and difference move below the title instead of squeezing it. Schedule adds an Execution Energy column: Plan-only rows show `—`, consume values use a red badge, and recovery values use a green badge.
- Schedule combines each Plan item with its linked Actual event so the same execution is not listed twice. Unlinked Actual events are labeled `계획 외 일정`.
- Schedule defaults to importance order. Time order merges linked Plan/Actual rows and plan-free Actual rows on one timeline; time-unspecified plans follow timed rows.
- A changed Plan/Actual pair shows a compact signed duration difference such as `🕒 +20분`, `🕒 -10분`, or `🕒 0분`. Positive means the Actual event lasted longer than planned and negative means it was shorter. The dedicated Schedule detail sheet shows planned time, actual time, start difference, end difference, signed duration difference, connected Goal, and Actual memo.
- A timed Plan lasting at least one hour reuses To-do's brand-color title highlighter. Time-unspecified Plans and plan-free Actual events are not highlighted.
- An Actual event uses its Schedule Tag color only as a left accent line. Linked Plan/Actual rows and plan-free Actual rows use the same accent rule; row backgrounds remain neutral.
- Memo rows show the user-time-zone creation time and a one-line ellipsis. Selecting a Memo opens its full read-only content in a dedicated Memo detail sheet.
- Routine, Schedule, and Memo details use separate titled layers. A record click explicitly hides every sibling detail panel before showing exactly one; author CSS also preserves the `hidden` state.
- The Execution Energy card shows the selected condition values, baseline, condition adjustment, and daily starting capacity. Current and predicted capacity are omitted because the bottom orb/history owns current state. Daily consume/recover totals and feedback remain review context.
- Schedule detail includes the Actual event's saved energy group, consume/recover direction, and value. Published Actual snapshots retain these fields. Existing MySQL databases apply `migration.retrospect_execution_energy.mysql.sql`; SQLite fallback upgrades missing columns during connection startup, with the companion SQL retained for controlled upgrades.

### Empty states

- No Routine: show `오늘 확인할 루틴이 없어요.` and link `루틴 만들기` to `/manage/routines`.
- No schedule: show `이 날짜에 계획된 일정이 없어요.` and a no-underline `일정 추가` secondary action matching the date controls, linked to the selected Calendar date.
- No standalone or Actual-event Memo: show only `이 날짜에 작성한 메모가 없어요.`.
- The Memo tab merges standalone notes and Actual-event memos chronologically without merging their tables. It labels the source, uses note creation time or Actual start time as appropriate, and shows only the selected memo in its detail sheet.

## KPT workflow

- `회고하기` opens a non-modal bottom sheet so the underlying Routine, Schedule, and Memo tabs remain inspectable.
- The sheet uses three exclusive steps: Keep, Problem, and Try. Exactly one input form is visible at a time, and Previous/Next controls move between steps without a combined scroll view.
- Close, Record View/Return to Writing, Previous, Next, and final Publish controls stay in the sheet header. The final step replaces Next with Publish.
- The sheet can be collapsed to its header and reopened without losing the current step or text.
- `닫기` hides the sheet after saving the current text; it does not discard the KPT draft.
- Opening the editable KPT sheet starts a visible, non-blocking 10-minute countdown in the sticky header. It continues across Keep/Problem/Try navigation and while the sheet is collapsed. At `00:00` it shows a gentle completion prompt without closing, publishing, or disabling the form; Close stops it, and the next opening starts a new 10-minute session.
- On mobile, the normal expanded sheet occupies 40% of the dynamic viewport height. Focusing a KPT textarea switches it to an 80% visual-viewport writing mode so the active step remains usable above the software keyboard. `기록 보기` saves the current state, collapses the body, and releases the keyboard; `작성으로 돌아가기` restores the same step, text, and running timer. The page keeps matching bottom space while the normal sheet is open so every underlying record remains reachable.
- Close, Record View, Previous, Next, Publish, empty-state, and navigation actions reuse the shared button classes and sizing conventions.
- Text changes are debounce-saved through `POST /retrospect/draft` with CSRF protection.
- Empty KPT fields are allowed, but manual publishing asks for confirmation when any field is blank.
- Manual check: open KPT and confirm `10:00` starts immediately, keeps counting across steps and Collapse, reaches `00:00` without blocking the form, and resets to a new 10-minute session after Close and reopen.
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
- `retrospect_report_actual_items` stores Actual event title, memo, time, tag, Plan-link, and Execution Energy snapshots. Existing MySQL deployments must apply `sql/migration.retrospect_execution_energy.mysql.sql` before publishing or republishing.
- `retrospect_report_routine_items` stores that date's Routine state and connected Goal title snapshot.
- `retrospect_report_memo_items` stores Memo content and original creation time at publication.
- `retrospect_report_milestone_items` snapshots Goals completed on the report date and Routines completed or reaching their configured final day, including deterministic completion feedback. The published report therefore stays historically stable if source records later change.
- Deleted source Memos disappear from an unpublished/live daily view but remain in an already published report. Republishing replaces the snapshot with the currently active same-day Memos.
- Reports published before the Memo-snapshot migration have no reconstructable Memo snapshots until they are republished within the allowed edit window.

## Routes

- `GET /retrospect?date=YYYY-MM-DD&sort=importance|time`: daily report.
- `GET /retrospect?view=history&page=N`: submitted-report history.
- `GET /retrospect/report`: latest published report by report date; `?date=YYYY-MM-DD` selects an exact archive date. The page includes adjacent published-date navigation, KPT, daily records, Execution Energy changes, and completion news.
- `GET /retrospect/report/preview?date=YYYY-MM-DD`: authenticated JSON fragment for Calendar's report popup, including the requested report content and adjacent published dates.
- `GET /retrospect?view=goals`: Goal review.
- `POST /retrospect/draft`: auto/manual draft save for today or yesterday.
- `POST /retrospect/publish`: initial publish or republish for today or yesterday.
- `POST /retrospect/republish`: compatibility endpoint that rebuilds the current report snapshot.
- `POST /retrospect/settings`: automatic publish settings.

All POST routes require CSRF verification.

## Calendar integration

- Calendar's Retrospect button resolves the exact selected date.
- Calendar's header action opens the user's latest submitted report by report date in a read-only popup, regardless of the selected Calendar date. It reuses the published report snapshot content without navigating away and can load the previous or next published date inside the same popup.
- `GET /retrospect/report` without a date resolves to the latest submitted report. Supplying `?date=YYYY-MM-DD` continues to open that exact archive date.

## Routine mutation ownership

- `/routine` (today), Calendar, and daily Retrospect can change only today's Routine state.
- `/manage/routines` owns corrections for previous dates.
- The server enforces this boundary; it is not only a hidden/disabled UI rule.

## Migration

New databases use the current schema files. Existing databases apply:

- MySQL/MariaDB: `sql/migration.add_retrospect_review.mysql.sql`
- SQLite: `sql/migration.add_retrospect_review.sqlite.sql` or the SQLite runtime compatibility upgrade
- Existing MySQL/MariaDB installations: `sql/migration.retrospect_report_milestones.mysql.sql`
- Existing SQLite installations: `sql/migration.retrospect_report_milestones.sqlite.sql` for controlled upgrades; normal bootstrap reapplies the idempotent SQLite schema and creates the table when missing.
