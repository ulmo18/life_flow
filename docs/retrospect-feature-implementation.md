# Retrospect Feature Implementation

## Scope

Retrospect is the daily review surface. It combines the selected date's Routine logs, copied daily Plan items, Actual Calendar events, and standalone Memos before the user writes and publishes a KPT report.

- Daily Retrospect uses shared date navigation and keeps the selected date when moving between daily menus. Published history uses its own month selector without daily navigation; its mobile header retains search and menu controls, while its tabs stay below the desktop header. Goal review is an all-date view in the Goal menu.
- The sticky date and view navigation extends through the page's outer padding, while the report content keeps its normal inset. Daily Retrospect does not display the 작성중 or 발행됨 status label.

Daily Retrospect does not display the starting Execution Energy card. The editable condition and perceived-burden form remains in Today's Calendar `+` menu, and existing per-schedule energy details remain available.

## Main Views

- `일일 회고` shows a 100% Routine/Plan/Actual/Memo composition bar with a single row of count-and-percentage legend buttons and `루틴 / 일정 / 메모` record tabs. Legend buttons open the corresponding tab; Plan and Actual open Schedule and emphasize matching rows without hiding the other records. Plan and Actual are counted independently even when linked, time-unspecified Plan items are included, and largest-remainder rounding keeps the displayed total at exactly 100%. A tappable information icon explains that a memo on an Actual event counts in both categories.
- For an editable day with no Routine, Plan, Actual or Memo records, show only an empty-state guide and a Schedule link for the selected date instead of the record summaries and review action. Existing published reports or nonempty KPT drafts remain accessible; past unpublished and future-date states retain their own guidance.
- The Schedule tab shows individual block records and details, without a duplicate activity aggregate, minute totals, or an edit shortcut. Existing time, difference and per-schedule energy details remain available. Published report pages keep their existing presentation and snapshots.
- 중요도순·시간순·태그별 정렬은 현재 날짜와 발행 회고 수정 상태를 유지하면서 일정 목록과 상세창을 비동기로 함께 갱신한다. 빠른 연속 선택에서는 마지막 요청만 반영하고 실패 시 기존 목록을 유지하며 오류를 표시한다. JavaScript를 사용할 수 없을 때는 기존 정렬 링크가 동작한다.
- The conditional `오늘로 이동` and `날짜 선택` controls use the shared compact secondary-button treatment.
- `발행 이력` lists submitted reports in pages of 20 for the selected month, using the To-do page's bordered table surface and row dividers. A row shows the date, one truncated line each for Keep, Problem and Try (or `작성 없음`), and the saved achievement rates. The whole row opens the dedicated published report at `/retrospect/report?date=YYYY-MM-DD`. Republishing updates that date's one report; version history is intentionally not stored.
- Published reports and Calendar's report preview show at most one optional fact below the KPT text. A direct B Plan block / Actual connection has priority, with distinct block and Actual counts; otherwise the number of recorded Actuals is shown. `근거 보기` lists up to five saved snapshot records. No source event identifier, duplicate identifiers, invalid time ranges, or an empty Actual snapshot suppresses the feedback. Item-only or mismatched block links never qualify for the B rule. Feedback uses no AI, new DB fields, or user's manually selected Plan status; the saved KPT and snapshot are unchanged. Republishing replaces the snapshot, so feedback is recalculated rather than versioned. Generation failure suppresses only the feedback.
- Published history initially selects the current month in the user's time zone, regardless of the date selected in Daily Retrospect. Choosing a year and month immediately loads that month's list; an empty month shows an explanatory message and pagination retains the month. A year-and-month dialog matches the shared date picker; the native month input remains available without dialog support, and without JavaScript a submit button provides the fallback.
- The selected month control leads directly into the history table without a duplicate month or history heading between them.
- For dates before yesterday, Daily Retrospect shows only a dated, newspaper-inspired card linking to that date's published report when one exists; the entire card is tappable. Otherwise it shows an empty message without a border or shadow, distinguishing it from the interactive card. Today and yesterday retain their existing review workflow.
- `목표 회고` is available from the Goal menu and lists completed Goals. Its detail sheet shows linked Plan-block and Actual records, Goal-linked Routine logs, totals and a chronological timeline; it distinguishes item-only links and does not infer a Routine-to-Actual connection. The separate completed-Routine history list is not shown there.

### Daily tabs

- Routine is the initial tab. Today can be toggled from Retrospect; previous dates are read-only here and are corrected only in Routine Management.
- A Routine row shows a fire marker from a three-day streak onward. Its dedicated Routine detail sheet shows the connected Goal and a weekday-aligned calendar covering the Routine period.
- Routine and Schedule records use compact table layouts instead of separate block cards. Routine columns are ordered `루틴명 / 연속 / 완료`, leaving the flexible width for the name. The Schedule title owns the flexible width; on mobile, time and difference move below the title instead of squeezing it. Schedule adds an Execution Energy column: Plan-only rows show `—`, consume values use a red badge, and recovery values use a green badge.
- Schedule combines each Plan item with its linked Actual event so the same execution is not listed twice. Unlinked Actual events are labeled `계획 외 일정`.
- Schedule Plan rows show block-level Actual linkage as `연결(n)`. The count is based on `daily_plan_block_id`, so multiple Actual records for one block are not collapsed into one connection.
- Plan-free reports show `계획 없음` instead of `0%` in the report, preview, and history. If an item-only Actual could belong to multiple Plan blocks, it is shown once as `연결 블록 미확인`, is not credited to individual blocks, and the achievement label becomes `블록 미확인` instead of a misleading percentage. A single candidate block remains attributable. Published snapshots are interpreted without rewriting stored reports.
- A Plan row's detail lists every linked Actual separately with its time, tag, energy and memo when present. Single-Actual time differences retain their existing display; multi-Actual detail does not present the first record's difference as if it represented the group. Published snapshots use their saved linked Actual records.
- When a Plan block is deleted, its Actual events remain in the live records as unplanned events after all Plan links are cleared; previously published snapshots remain unchanged until republished.
- Schedule defaults to importance order. Time order merges linked Plan/Actual rows and plan-free Actual rows on one timeline; time-unspecified plans follow timed rows. Tag order groups a single tag by name, then `여러 태그` for a block linked to different tags, then `태그 없음`; rows within each group use time order. Submitted reports use their stored tag snapshots.
- Tag group headings span the five desktop columns or the three visible mobile columns, so hidden time and difference columns do not change the table geometry.
- Tag headings show the total Actual duration for that tag across the date, including records attached to a multi-tag Plan block. `태그 없음` totals untagged Actuals, and `여러 태그` totals Actuals linked to rows in that group; those multi-tag totals can overlap individual tag totals. Plan-only rows contribute no Actual time. Submitted reports calculate from the saved snapshots.
- A changed Plan/Actual pair shows a compact signed duration difference such as `🕒 +20분`, `🕒 -10분`, or `🕒 0분`. Positive means the Actual event lasted longer than planned and negative means it was shorter. The dedicated Schedule detail sheet shows planned time, actual time, start difference, end difference, signed duration difference, connected Goal, and Actual memo.
- A timed Plan lasting at least one hour reuses To-do's brand-color title highlighter. Time-unspecified Plans and plan-free Actual events are not highlighted.
- An Actual event uses its Schedule Tag color only as a left accent line. Linked Plan/Actual rows and plan-free Actual rows use the same accent rule; row backgrounds remain neutral.
- Memo rows show the user-time-zone creation time and a one-line ellipsis. Selecting a Memo opens its full read-only content in a dedicated Memo detail sheet.
- Routine, Schedule, and Memo details use separate titled layers. A record click explicitly hides every sibling detail panel before showing exactly one; author CSS also preserves the `hidden` state.
- The Execution Energy card shows the selected condition values, baseline, condition adjustment, and daily starting capacity. Current and predicted capacity are omitted because the bottom orb/history owns current state. Daily consume/recover totals and feedback remain review context.
- Schedule detail includes the Actual event's saved no-change/decrease/increase result and value. Published Actual snapshots retain legacy group labels when present, but new records use the event-level direct-setting snapshot. Existing MySQL databases apply `migration.retrospect_execution_energy.mysql.sql`; SQLite fallback upgrades missing columns during connection startup, with the companion SQL retained for controlled upgrades.

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
- In the expanded KPT sheet, Keep hides Previous, Try hides Next and alone shows Publish or Republish, and Close is hidden. Record View collapses the sheet and shows only Close and Return; returning restores the current step and its controls.
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
- `GET /goal/review`: Goal review. The old `/retrospect?view=goals` link redirects here.
- `POST /retrospect/draft`: auto/manual draft save for today or yesterday.
- `POST /retrospect/publish`: initial publish or republish for today or yesterday.
- `POST /retrospect/republish`: compatibility endpoint that rebuilds the current report snapshot.

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

## 기록 피드백

발행한 날짜의 일일 회고는 신문 형태의 발행 회고 링크를 먼저 보여준다. 오늘과 어제는 `회고 수정`으로 현재 일정·루틴·메모를 읽는 편집 화면에 들어가며, 재발행 전에는 발행된 일정·루틴·메모 스냅샷을 바꾸지 않는다. 기존 KPT 자동 저장은 발행한 회고의 텍스트도 갱신한다. 발행 회고와 일정 미리보기는 계획·실제 블록과 연결된 실제 기록을 하나의 일정 영역에서 보여 주고, 중복된 일정 요약 피드백 대신 기록을 돌아보는 질문을 제시한다. 루틴 이정표와 기간 변화는 근거와 함께 별도 표시한다.

공통 발행 회고 본문에서 KEEP·PROBLEM·TRY와 한국어 설명은 같은 줄에 표시한다. `일정` 영역은 각 계획과 실제 일정의 시간대에 소요시간을 함께 표시하고, 계획 중요도는 A~D 원형 아이콘으로 나타낸다. 실제 일정에 태그가 있는 경우 해당 일정의 왼쪽 선에 태그 색을 적용하며, 한 계획에 연결된 실제 일정이 여러 건이면 각 일정의 태그 색을 따로 표시한다. 태그가 없는 일정에는 색 선을 표시하지 않는다.

발행 회고의 피드백은 발행 시 저장된 기록 스냅샷을 열람 시점의 최신 규칙으로 분석한다. 규칙이 변경되면 과거 회고의 피드백 문구도 달라질 수 있으며, 근거 보기에서 이 기준을 안내한다. 재발행하면 기록 스냅샷도 다시 수집한다. Try의 후속 연결은 발행 당시 기록과 분리된 현재 관계로 표시한다.

회고 발행 시 완료한 루틴의 완료 날짜를 해당 회고일까지 스냅샷으로 보존한다. 같은 루틴을 비연속으로 수행해도 회고일이 정확히 60번째 완료일이면 발행 회고에서 근거와 함께 표시한다. 완료 날짜를 기록하지 않은 이전 회고는 추측하지 않으며, 원본 수정은 재발행할 때만 반영된다. 새 규칙은 단순한 기록 안내이며 습관 형성이나 개인의 성향을 판단하지 않는다. MySQL의 기존 DB에는 `migration.retrospect_routine_feedback.mysql.sql` 적용이 필요하고 SQLite는 앱 시작 시 누락 열을 추가한다.

새로 발행한 회고는 같은 루틴의 시작일과 전체 완료 날짜를 버전 2 스냅샷으로 저장한다. 회고일을 포함한 최근 7일과 그 전 7일이 모두 루틴 기간 안에 있고 각 기간에 완료 기록이 3일 이상이며 횟수가 다르면, 직접 연결 계획 피드백이 없는 경우 두 기간의 완료 일수를 비교한다. 근거에 기간과 해당 완료 날짜를 표시한다. 초기 스냅샷은 전체 날짜가 보장되지 않아 비교하지 않는다. 기존 열을 재사용하며 저장된 과거 회고는 변경하지 않는다.

발행 회고의 기록 피드백은 직접 연결이 확인되는 계획 블록 한 개의 계획 시간과 해당 블록에 연결된 실제 일정들의 시간 합을 근거 보기에서 비교할 수 있다. 식별이 모호하거나 시간이 유효하지 않거나 연결된 실제 기록이 겹치면 비교를 생략하며, 수행 실패를 판단하지 않는다.

A·B·C·D 중요도 계획에 직접 연결된 실제 일정이 있으면 해당 중요도의 블록 수, 실제 기록 수와 시간 합을 안내한다. 루틴 60일 이정표를 먼저 선택하며, 그 외에는 A→B→C→D 순으로 한 건만 선택하고 다른 중요도의 기록을 합계에 섞지 않는다. 연결된 실제 기록끼리 겹치는 경우에는 시간 합을 생략하고 연결 건수만 안내한다. 미연결 일정의 중요도를 추측하지 않는다.

선택할 수 있는 직접 연결 계획이 없으면 실제 일정 전체의 기록 건수와 시간 합을 안내한다. 실제 기록의 시간이 겹치면 시간 합 없이 건수만 표시한다. 계획이 없는 날에도 같은 규칙을 적용하며 실패나 미수행으로 판단하지 않는다.

Try와 실제 일정의 연결은 현재 비활성화되어 발행 회고·일정 미리보기에 표시되지 않고 연결 변경 경로도 제공하지 않는다. 기존 연결 데이터와 저장 구조는 보존한다. 자동 발행 설정과 실행 경로는 제거했으며 과거 설정 데이터는 호환성을 위해 보존한다. 발행과 재발행은 사용자가 직접 실행한다.

`tests/retrospect_try_links_mysql.php`는 별도로 준비한 `lifeflow_test_` 접두사의 일회용 MySQL DB에서만 실행된다. 직접 연결의 소유권, 중복 요청, 재발행 후 원문 보존, 해제와 삭제된 일정의 처리뿐 아니라 실제 일정 및 루틴 피드백의 발행 스냅샷과 재발행을 확인한다. 실제 서비스 DB를 대상으로 실행하지 않는다.
# Explicit Plan completion

- Read-only Routine calendar cells use the shared success/danger background and text tokens for O/X in both light and dark themes, while retaining their symbols and grid layout.

For live daily reviews, Plan achievement counts blocks whose stored state is `completed` and which have a linked Actual. Actual linkage and time totals remain separate execution evidence; an in-progress linked block is not counted as achieved. Previously published snapshots keep their saved historical values until republished. Existing linked blocks must be backfilled before deployment as documented in `docs/database-migrations.md`.

- Published reports show a Goal review after daily KPT for Goals currently completed on that report date. The Goal review reuses the completed Goal review sheet’s current relationship, activity totals, and chronological activity history. Daily report snapshots stay unchanged; duplicate Goal milestones are suppressed when the Goal review is shown.

- Published report pages reuse the daily Retrospect sticky context and tab navigation. Routine state marks appear inside a square.

- Daily, history and published-report headers use the same date navigation. In history, changing the selected date opens its month; first entry without a date defaults to the current month. Report navigation keeps adjacent published-date links.
- The report's KPT, Goal review, schedule, routine and memo sections are native independent disclosures, initially expanded. The report no longer exposes its own edit button; daily Retrospect retains the edit entry. A date-selected original editorial sentence appears above KPT without storing or attributing a quotation. Completed Goals use a newspaper-style highlighted card.

- Published history places the existing month selector in the sticky date-header position; adjacent arrows move by month and the main content no longer repeats the selector.

- Published report browsing keeps the session's selected daily date in the shared header. Previous/next report links change only the viewed report; date navigation in the header remains a separate daily action.

## Empty review presentation

The current empty-record guide (showEmptyGuide: today/editable yesterday) and empty published history share pages/retrospect/_empty-state.php with the published-history wrapper and centered muted 12px message style. The guide retains its dated Calendar action. Earlier unpublished/future notices keep their original designs; the mistaken restyling of those branches was reverted. Date/edit eligibility and notice text are unchanged. See dashboard-feature-implementation.md for changed files and manual checks.

## Shared month picker (2026-11-13)

Published history keeps its existing month navigation and dialog behavior, now shared with Routine management via `components/month-picker.js` and the common `ui-month-*` styles. Search parameters and pagination behavior remain unchanged.

## Calendar published-preview layout (2026-11-13)

Calendar’s published-preview popup still embeds report-content.php and uses the same adjacent-report endpoint. Its title/close row and equal previous/next row now form a fixed header above the separately scrolling report body, with consistent 16px internal padding. No report data, publishing or date-selection behavior changes.
