# Calendar Feature Roles

## Placed Plan reminders

Reminder ownership belongs to a timed `daily_plan_blocks` row, independently of source To-do state/importance. Schedule and To-do details share one editor and authenticated CSRF-protected endpoint. `planBlockReminders` is the notification-only source; original `planReminderItems`/`planActivityItems` retain placement responsibilities. Save/delete uses the stored date for scoped replacement. See `notification-feature-implementation.md`.

## Purpose
This document defines the ownership boundaries for the dashboard calendar entry point, the calendar feature page, shared toast UI, actual-event persistence, routine check entry points, and Retrospect preview UI.

Read this file before changing calendar, toast, dashboard entry, or calendar data-source behavior.

## Mobile workspace continuity

- Calendar modal sheets and the separate Plan-create sheet keep Tab/Shift+Tab inside visible controls. Shared confirmation/native date dialogs retain keyboard priority. Closing restores the visible source control, or the quick-menu toggle if the original action is now hidden. Escape closes separate Plan creation; the existing drag cancellation and internal selection-view back behavior remain unchanged.

- Calendar's published-retrospect preview reuses the same newspaper body as the full report. Plan and Actual records appear in one combined section; Try-to-Actual linking is disabled and is not shown in either presentation.
- The shared report body presents inline KPT labels, duration beside each schedule time, A–D importance badges, and a left tag-color accent for each tagged Actual record.

- Timetable selection previews use background fills without per-cell outlines. Plan and Actual blocks have no border or inset outline, including linked Actual blocks; resize handles retain their positions. The mobile sticky timetable header shows labelled, color-distinguished Plan placement, Tag painting, time resizing, schedule movement and Plan-to-Actual copy states, and shows Actual entry while touch long-press range selection is active.
- In the mobile 50:50 view, Plan importance and state icons and Actual tag dots are hidden for all block durations; longer blocks show their title with uniform Plan text weight. Actual tag background colors remain visible. The 10- and 20-minute blocks show only their colored surface; their non-overlapping resize hit areas remain active and the title is available to assistive technology. Mobile single-timetable views restore the existing indicators and show titles and subdued handles for all durations; handles become clearer while resizing. Desktop block presentation is unchanged.
- On mobile, block padding shrinks with narrow time segments so a block continued on the next hour remains inside its 10-minute cells; the time range and resize controls are unchanged.
- The mobile Plan placement/Tag painting pill and the adjacent quick-menu button share a 46px outer height and bottom alignment. The quick-menu button uses the same surface color as the work bar, with the calendar primary red for its border and plus sign.
- The energy slider and direct-input modes share the slider's natural layout height so switching modes does not move surrounding content; the inactive slider is not visible or interactive.
- Direct energy entry is vertically centered and displays its 5-unit, 5–100 guidance before input; the guidance row keeps its space in slider mode.
- Returning to the energy slider cancels an invalid direct-entry draft and restores the value from before direct entry. Valid values between slider ticks remain unchanged until the user selects a tick.

- Actual create/edit share two equal-width time controls with visible start/end labels, clock icons and an automatically updated duration badge. Existing 10-minute selects and the 24:00 end boundary remain. Tag chips wrap below a heading with `+ 새 태그` at its right; inline creation still selects the new tag and updates both forms.
- 생활 기록, 메모, 루틴 and 실행력 under 함께 기록하기 use transparent rows separated by horizontal rules, without individual card fills/radii. Expanded fields stay inside the body's width. Actual edit keeps Delete and Save fixed together in a 1:4 footer and retains the 1.5-second deletion hold. The linked-Plan selector keeps a soft surface to distinguish the selected relationship.
- Actual-sheet memo fields stay within their disclosure row. New Actual events initially display −25; choosing a direction without a value prevents saving. Slider values are 0, 25, 50, 75, 100, with 0 meaning keep; there is no separate keep button. Increase is green and decrease is red. The direct-entry control sits to the left of the direction controls, and direct input visually integrates its sign and field. Direct entry accepts 5-unit increments up to 100; an unchanged legacy value above 100 is preserved. A saved event without an energy setting loads as keep. Existing Plan-to-Actual copy retains its −25 default.

- In normal mode, an empty Plan or Actual cell requires a stationary long press to select a 10-minute entry; continuing to drag selects a range. Short taps do not open an entry sheet. Moving before activation remains a normal page scroll, while movement after activation is reserved for selecting the range. Mouse and pen behavior remains unchanged.

- The mobile work mode previously labelled `할 일 배치` is labelled `계획 배치`. It selects and places date-specific Plan items; it does not change the existing To-do data model or routes.
- On mobile the plan-source list wraps compact, surface-colored cards across each row and scrolls internally above 30dvh. It is ordered by importance and then registration order. Underlined titles identify reusable templates; screen readers also receive the template type. Tapping a title selects it for a single pointer drag across the Plan timetable to select a 10-minute range; tapping it again cancels selection. A successful placement clears that selection, while a failed save keeps it for retry. A separate options button opens the editor for both kinds; editing a template changes its title, importance and goal without changing previously copied daily plans or its stored duration. The drawer links to the Plan block template management page.
- When the selected date has no daily Plan items, a compact-workspace guide points to 계획 배치 and disappears when that work mode is opened. On mobile, the Plan and Actual timetable titles toggle an independent single-timetable view; tapping the active title restores the split view. Choosing a title while a work drawer is open closes the drawer first.
- On today's first Calendar visit per account and local date, if there are no Plan blocks and a published Retrospect exists, Calendar asks whether to view it and opens the latest published report only on confirmation. Actual events do not prevent the prompt. The visit is claimed atomically across devices even when the user declines or no report is shown; the `+` menu also provides 발행 회고 보기 at any time.
- On mobile, Calendar's compact date, weekday, visible date-picker caret and borderless previous/next/Today controls form a subtly styled swipe zone. Date navigation, search and menu share a vertically centered 44px control row; search and menu stay right-aligned outside the swipe zone. Tapping the date opens the shared date picker and swiping the zone changes the date. The shared picker uses the account-local Today value and preserves Calendar's existing date-change navigation.
- The mobile date swipe zone uses 4px of horizontal inner spacing while retaining its existing vertical spacing and date-control touch targets.
- Its mobile date row uses the same outer padding and control alignment as Retrospect, Routine and To-do; timetable headings remain in the following row.
- Calendar reuses their shared date navigation and date coloring: Saturdays are blue and Sundays and registered holidays are red. Calendar mode remains selected during date navigation.
- On mobile the centered, abbreviated 계획 and 실제 labels remain in the sticky date header and follow the active workspace. The tag-paint drawer wraps its tag buttons into rows and scrolls vertically when needed; its header links to 일정 태그 관리.
- Enabled reusable Plan block templates are available as placement sources. Placing one atomically creates a dated To-do and a linked time block; the original template is replaced in that date's source list by the To-do. A later placement of the same template on that date reuses that To-do, while another date gets its own. The library's stored default duration is not applied. Existing template-only records are not converted automatically.
- The To-do recurring tab and Calendar share this date-specific usage record: using a template from either screen hides the original in Calendar's placement sources and shows an added check mark on the To-do tab.
- Placed blocks retain title/goal snapshots. To-do-backed blocks display current state/importance. Calendar can update the connected To-do state through `/todo/status`, including completion without an Actual; sibling blocks and placement sources refresh together. Importance remains in To-do. Deleted sources show their final values with state choices disabled.
- To-do state is 미정, · 진행 중, V 완료, → 연기, X 취소 or ○ 위임; linking or detaching Actuals does not change it. For template-only blocks, the existing independent state rules continue to apply.
- The Plan block sheet shows the number and combined duration of its linked Actual records. Status icons are hidden on short blocks in the narrow split view, while accessible labels retain the state.
- Plan details show title, inline importance and Actual count/duration, single-row six-option immediate-save state choices, reminder disclosure and management disclosure. Reminder state is right-aligned; management contains linked-Actual detachment and deletion, with existing hold protections. Both disclosures start closed and use shared palette/typography. Independent-template importance editing is inside management. Template-only completion rules remain unchanged.
- Actual-link detachment and deletion close the editor on success. The `실제 일정 연결 해제` button is hidden when no Actual is linked. Failed changes keep the editor open; detaching preserves Actual records and never resets To-do state.
- The reusable delay buttons for Plan deletion, Actual-link detachment and Actual-event deletion start their actions only after a 1.5-second hold. A red progress fill runs during the hold; releasing early shows an action-specific toast and makes no request. Pointer cancellation, moving outside, closing the sheet, or leaving the page cancels the hold. Space and Enter also support holding these buttons. Actual-event deletion retains its existing server submission and redirect behavior.
- Actual-event Plan links show all time ranges of the selected daily Plan's placed blocks, in chronological order; plans with no block fall back to their item time or 시간 미정. Copying a Plan block into Actual saves one default 25-point consumption with the event in the same transaction. Earlier Actual events are not backfilled.
- Deleting a Plan block preserves every linked Actual event and clears its block, daily-item and legacy template links, leaving it as an unplanned Actual record.
- The placement drawer offers a daily template when the selected date has no active Plan items. Actual events do not prevent application; the server repeats the empty-Plan check before copying.
- `계획 추가` submits to the existing owner as JSON. Its source is appended without automatic selection or a workspace/view change. Toast reports an added To-do, or an added Plan and To-do for timed creation. Non-JavaScript submission retains its redirect.
- A stationary long press on an empty Plan grid opens Plan add for that cell on release; dragging selects a longer range. Short taps do nothing and movement before activation remains scrolling. Saving atomically creates the untimed source and timed block, preserves the current view (including 50:50) and reports both additions by Toast. Failure retains the editor and creates neither record.
- `태그 칠하기` opens the same mobile bottom-sheet container as 계획 배치. It presents tag choices in that sheet and applies the selected tag, or tag clear, immediately to a touched Actual block.
- Opening the inline Tag add editor clears the previously selected painting choice, including Tag clear. An in-flight paint request retains the tag that was selected when it was sent.
- Plan, the central time axis, and Actual share the same mobile title and hourly-row geometry variables; the central axis must not calculate an independent row height.

## Responsive workspaces

- Calendar remains one `/calendar` page with one Plan grid, one Actual grid, one shared time axis, and one set of editors. Responsive CSS changes workspace visibility and placement; it never renders duplicate schedule DOMs or page copies.
- At widths up to 1439px, `normal` shows Plan / shared axis / Actual at 50:50. `plan-placement` shows only Plan at full width with the plan-source drawer, and `tag-paint` shows only Actual at full width with the tag drawer. Entering or exiting a work mode does not reload the page or change the selected date.
- At mobile widths the normal workspace additionally supports Plan-only and Actual-only viewing through the timetable title buttons. Opening Plan placement or Tag painting selects the corresponding title; closing the drawer returns to the split view and clears title selection. Title buttons use the currently visible workspace when toggling, and the visible single timetable supplies its own hour labels. The data-entry mode stays separate.
- Plan and Actual block moves and resizes use CSRF-protected JSON requests to the existing endpoints. Only a successful response redraws the affected block; failures retain the original range and report an error. Pending range saves block another range gesture, and a Plan change refreshes the linked-Plan range text and Android reminder payload. The page, selected timetable view and scroll position remain in place. Normal user-initiated view transitions remain animated.
- Browser history records mobile work-mode transitions through `workspace=plan-placement|tag-paint`; Back restores normal or the preceding workspace state without a page reload.
- At 1440px and above the same DOM is a five-area workspace: Plan sources, Plan timetable, shared axis, Actual timetable, and tag palette. The palette has an explicit painting on/off control so normal Actual editing does not conflict with tag application.
- Mobile places Plan placement and Tag painting in a neutral, icon-labelled segmented work bar above the bottom navigator, next to the existing quick-menu `+`. An open work drawer is anchored to the bottom of the viewport and covers the navigator, work bar and quick-menu button; its close control returns to the normal view. Mobile plan source cards omit the drag handle; the desktop drag handle remains available.
- When the work drawer is closed, it translates beyond its own height so no portion remains visible; the open tag creator still moves above the mobile keyboard.
- The quick menu retains condition and memo actions and any existing template action. A timer is not shown until a working timer exists.
- Tag painting supports inline tag creation through the existing tag endpoint and immediately selects a successfully created tag. It has no undo action; tag clearing remains available.
- In the mobile drawers, Plan add appears as a compact item at the end of the wrapped Plan list, using the Actual editor's red border, text and pale red surface; Tag add appears at the end of the tag list as the same circular plus control used by the Actual editor. Desktop retains its full-width add actions. Mobile enters paint mode directly, hides the desktop paint-mode toggle, and expands the scrollable drawer while the tag form is open; cancel or successful creation restores its normal height.
- Fixed system-tag colors are omitted from tag-management color choices even if the system tag is hidden. Server-side color uniqueness validation remains authoritative.

## Dashboard
- `GET /dashboard` is the authenticated first page after login.
- Keep it focused on navigation, summaries, and quick actions.
- Do not place full calendar editing behavior in the dashboard.
- Link to `/calendar` for detailed calendar interactions.

## Calendar Page
- `GET /calendar` owns the day-grid calendar experience.
- Calendar views live under `app/Views/pages/calendar/`.
- Page-specific calendar CSS lives under `public/assets/css/pages/`.
- Page-specific calendar JavaScript lives under `public/assets/js/pages/`.
- Calendar uses the shared `.page` container width so its content expands at the same mobile, tablet, and desktop breakpoints as the other feature menus. Keep the floating action aligned to the same responsive container edge.
- Keep time-grid selection in the shared `public/assets/js/components/time-grid-selection.js` module. Calendar and Plan should configure that module instead of maintaining separate gesture implementations.
- On touch devices, the day grid uses native vertical scrolling by default. A stationary long press activates range selection, provides visual/haptic feedback, and the following drag extends the range.
- In Android WebView, a confirmed touch long press temporarily disables native pull-to-refresh. Pointer completion, cancellation, explicit selection cancellation, page exit, and background transition restore it.
- A short tap on an empty cell does not create a schedule. Mouse and pen may start range selection immediately.
- A native scroll gesture must not trigger the actual-event click or empty-cell selection that follows it.
- Mouse and pen range selection may start immediately, but range selection must never start from an actual-event control or another interactive control.
- When the selected date is today, both timetables softly highlight the current hour row and strongly highlight the exact current-time cell. These markers update in place at every 10-minute boundary using the signed-in user's configured IANA time zone; the page does not reload for this visual update.
- Only an explicit bottom Schedule navigation entry on today's date positions the current-time row near 42% of the viewport. A user-selected mobile Plan/Actual tab may also focus the current row. Periodic updates, Calendar writes, redirects, sheet actions, date editing, manual scroll, and browser Back/Forward restoration never recenter the page.
- The Schedule page's sticky context keeps the Calendar date row and Plan/Actual tabs together. The date row stays in this order: selected date, previous day, next day, conditional Today, and Retrospect, followed by mobile Search/menu access. The Today action is hidden while today is selected. Header actions use 32px visual controls inside 40px touch targets, and tapping the selected date opens the native date picker.
- A horizontal swipe or pointer drag on the Calendar date header navigates by one day: right for the previous date and left for the next date. Touch, mouse, and pen use the same 48px horizontal threshold so vertical page scrolling and ordinary header-control taps remain unchanged.
- Date-header drag tracking does not capture the pointer at the parent container. Date picker, previous, next, conditional Today, and latest-published-Retrospect controls therefore retain their native click behavior while completed horizontal drags still navigate.
- Calendar bottom sheets and popups should close from the close button and dimmed overlay through click handlers only; avoid mixing pointerup and click for the same close action.
- Calendar bottom sheets should scroll internally when their content exceeds the mobile viewport.
- Keep the bottom-sheet header and close button reachable while its content scrolls, and keep touch controls out of the page-level grid gesture handling.
- Focus a sheet input while the opening click or pointer gesture still has user activation, using `preventScroll`. Do not call page-level `scrollIntoView` from Calendar sheets because edits, deletes, and Actual detail actions must preserve the working time-grid position. Cancel an in-flight current-time animation on the user's first Calendar pointer action, and restore saved Calendar write positions after browser layout has settled.
- Calendar switches between a Plan schedule tab and an Actual schedule tab without changing the selected date. The current mode is carried as `mode=plan|actual` through Calendar writes and date navigation so the chosen tab remains fixed while the user works; a page entry without a valid mode defaults to Actual. The Plan tab edits the date-specific copy; mobile Actual mode keeps an interactive compact Plan timetable beside Actual so Plan blocks remain copy sources.
- Deleting a date-specific Plan block asks for confirmation, then submits the CSRF-protected delete request as JSON when JavaScript is available. The Calendar sheet closes only after the server confirms deletion, the page reloads from the returned Calendar path, and ordinary form submission remains the non-JavaScript fallback. If an actual event is linked, the Plan block is soft-deleted and the actual event remains after its Plan link is cleared.
- Calendar page CSS and JavaScript use file modification versions in their URLs so WebView and browser caches fetch changed behavior after deployment. Desktop may show both time grids together; mobile Actual mode exposes the Plan grid as an interactive source instead of a muted background layer. Minute-number headers are visually hidden on every grid and do not affect event-layer coordinates; Actual mode removes only the duplicate hourly text from the left Plan grid.
- Calendar does not show time-grid gesture instructions or a new untimed-entry button above the grid. The long-press/drag range-selection behavior remains available.
- Calendar uses one bottom-right `+` bottom sheet with no title or date. Its retained actions are displayed in one vertical column: `오늘의 컨디션`, the conditional existing template-link action, and `메모 작성`; timed Plan and Actual creation remain in their respective grids. No Pomodoro entry is shown until a real reusable timer feature exists. To-do and Routine links are omitted because bottom navigation already owns them.
- Calendar exposes one full `계획 추가` form rather than separate timed and time-unspecified actions. General entry opens the Plan form with no time, `시간 정하기` reveals required start/end selectors, and selecting a Plan-grid range opens the same form with time enabled and the range prefilled. The activity source uses the same Todo-style creation sheet through the existing `/todo` route and returns to Calendar Plan mode after save.
- Calendar activity creation includes the same title, importance, and Goal fields as To-do creation. Editing an activity changes only the shared activity attributes; moving or resizing a Plan block changes only that block's range. The activity editor is labelled `할 일 수정`; the Plan-block editor is labelled `계획 수정` and contains no link back into activity editing.
- Plan creation can copy one saved Plan-block template or use direct input. The copied fields remain editable and become date-specific data; later library edits or deletion do not propagate. If the copied duration exceeds today's range, confirmation is required before discarding the later portion and ending the date-specific copy at `23:50`.
- Plan importance is shown as four horizontal A/B/C/D radio-style buttons rather than a select menu.
- Plan and Actual blocks use matching title typography. Plan mode reinforces A/B/C/D importance with progressively stronger visual emphasis in addition to the visible letter badge.
- Actual-event creation always requires a start and end time. Existing legacy untimed events may remain available only as records that can be placed into a timed range.
- Actual-event creation defaults to a 25-point Execution Energy decrease when left unselected. Creation and editing expose keep and exclusive increase/decrease controls, a 0/25/50/75/100 slider, and custom 5-unit values through 100; signs are presentation only and persisted magnitudes remain positive. Keep hides the other controls, and saving replaces the event setting without duplicate application. To-do completion uses the same 25-point default, while selected Plans affect only the estimated remaining capacity.
- Crossing the 50%, 25%, or 0% remaining threshold after a decrease shows one supportive message per day and threshold. The 0% state never blocks more records and combines completion encouragement with a recovery suggestion.
- Today's `오늘의 컨디션` is a radio-choice sheet for sleep, fatigue, mood, stress, and perceived burden. It can only write today's condition and replaces the earlier same-day condition adjustment rather than stacking it.
- When a mouse or pen selection overlaps an existing block in the active tab, Calendar rejects the range before opening an editor and explains the conflict through Toast. Actual mode checks actual events only, so background Plan references never block actual entry; server-side overlap validation remains authoritative.
- Calendar event forms show the editable start/end controls once without repeating the selected range underneath them. A Plan-link row is omitted when there is no usable daily Plan item; otherwise `연결하기 ›` or `변경 ›` switches the same sheet to a compact selection view so many Plans do not lengthen the main form.
- The Actual create/edit sheet prioritizes the approximately ten-second common path in this order: title, connected start/end selectors, compact tag chips, Plan-link summary, additional records, and Execution Energy. It uses the shared structured-sheet anatomy with a fixed header, one scrolling body, and a keyboard-safe action area; it has no drag handle.
- Life Record, Memo, and Execution Energy disclosure summaries stay on one compact row with one trailing disclosure marker. The Execution Energy badge does not repeat a secondary `변경` label. An unlinked Plan row shows only the trailing `연결하기 ›` action; after linking, the middle summary contains the selected Plan and the trailing action changes to `변경 ›`.
- Plan and Routine rows open compact selection views inside the same Actual sheet instead of stacking another sheet. Plan offers recommended/all views and unlinking. Routine shows only incomplete active routines in stable creation order.
- Routine choices retain their existing meaning: selected routines are marked complete for the Calendar date when the Actual event is saved. They do not create a new event-to-routine relation. Completed routines are omitted; blank and missed states remain eligible for completion.
- Editing a timed Actual event may change start and end indexes. Client checks provide immediate feedback, and repository validation inside the update transaction excludes the current event while rejecting overlap with another timed Actual event.
- Actual-event create and edit sheets may create a new tag inline without closing or resetting the event form. Tag editing and deletion remain in Schedule Tag Management.
- On pointer-hover devices, an Actual-event tooltip shows the title and at most 80 characters of its memo. It remains hover-only and is not used as a mobile editing surface.
- Empty Plan and Actual tabs show a compact first-action prompt instead of an initial no-Plan Toast. The Plan prompt disappears when any timed or time-unspecified Plan item exists; the Actual prompt disappears when an Actual block exists.
- A date-specific Plan item may have no time range. It is not rendered inside either time grid; To-do owns its primary daily-list presentation, while Calendar lists it once in the activity-placement source. Template-created time-unspecified items retain their expected duration, and its `시간 설정` action remains available from the existing Plan editor; saving a time affects only the Plan item and does not mark it complete.
- Time-unspecified Plan items remain eligible for Actual-event linking. Choosing a new Plan link copies the Plan title into the Actual title once; subsequent Actual-title changes do not rename the Plan item. The former Actual-side link-processing selector is intentionally removed.
- Shared confirmation modals opened from calendar sheets must appear above the calendar-local layer.
- Calendar block title tooltips should remain hover-only and should not appear during mobile touch editing.
- On today's Schedule, desktop renders one single-line contextual action in the normal-flow shared header and mobile preserves it as a compact row inside the sticky Schedule context. The server chooses Retrospect window, current unlinked timed Plan, next unlinked timed Plan, no Plan, then no timed Actual record in that order. Retrospect uses the enabled morning/evening reminder time for a two-hour window; Plan and Actual actions open the matching Calendar sheet directly.
- Keep the WebView bridge contract and native lifecycle safety rules in `docs/android-webview-integration.md` aligned with the shared time-grid controller.

## Controller
- `CalendarController` handles request and response flow only.
- Controllers may choose views, pass prepared data, and select page assets.
- Do not put SQL or business rules in controllers.

## Model
- `CalendarRepository` is the data access boundary for calendar events.
- Current implementation persists actual events with `calendar_days`, `calendar_events`, and `calendar_date_meta`.
- Keep returned event shapes stable so views and JavaScript do not depend on the data source.

## Service
- `CalendarService` prepares calendar data for rendering.
- Use it for formatting, segment generation, and data-source normalization.
- Keep DB queries out of services.

## Persistence
- `calendar_days` stores one user/date row. Its legacy `plan_group_id` mirrors the source template group during migration.
- `daily_plans` stores one mutable date-specific Plan copy per Calendar day.
- `daily_plan_items` stores copied or directly-created Plan items, including the copied goal link. `start_index` and `end_index` are both null for a time-unspecified item and both populated for a timed block. `estimated_duration_minutes` optionally retains a template duration while time remains unspecified.
- `calendar_events` stores timed actual schedule blocks and untimed entries. Timed entries link to `daily_plan_items`; the legacy `plan_template_id` remains transitional compatibility data.
- `execution_energy_event_settings` stores the selected consume/recover snapshot for an Actual event without changing the event's completion or Plan-link semantics.
- `calendar_tag_palettes` stores the shared 15-color palette for actual-event tags.
- Palette HEX values remain identical in light and dark mode; actual-event cards choose their foreground color from palette luminance so the label stays readable.
- `calendar_tags` stores four default system tags plus user-created personal tags.
- `calendar_date_meta` stores holiday/substitute-holiday metadata for date coloring.
- See `docs/calendar-feature-implementation.md` for table, route, and UI details.

## Routine Entry Point
- The Calendar floating bottom sheet does not duplicate the dedicated current-day Routine page. Actual-event creation or editing may still mark selected routines complete in the same save flow without creating an event-to-routine relation.
- Routine execution toggles from Calendar and Routine page must write to the same `routine_logs` table.
- Routine execution toggles should update in place with JSON when JavaScript is available, while keeping POST redirect fallback behavior.
- Calendar uses the shared Routine state control: blank is an empty neutral square, `O` is a filled check, and `X` is a muted cross. A successful toggle changes the control in place without reloading the page.
- Routine execution controls are available for today and past dates only. Future dates remain visible as calendar dates but cannot receive Routine logs.
- Keep the Routine section light enough that it does not block actual schedule input.

## Retrospect Entry Point
- Calendar shows a Retrospect button for the selected date.
- The Calendar header Retrospect action is independent of the selected Calendar date. It opens the latest submitted report by `report_date` inside an internally scrolling popup and keeps the user on Today. The popup fills the available viewport with equal visual insets, a fixed header, and a body that owns scrolling.
- If there is no submitted Retrospect report yet, the popup explains that there is no reference report; it does not redirect implicitly.
- Retrospect owns report creation, draft editing, publishing, history, and report snapshots.
- Calendar should only render submitted Retrospect snapshots and must not provide Retrospect editing.

## Life Record Entry Point

- Actual-event create and edit sheets expose an optional Life Record section for Sleep, Meal, Snack, Exercise, and Rest; there is no separate Calendar icon dock.
- The selected Actual event supplies the record date and start/end time. Each linked entry may include a short detail, note, and optional 1–5 perceived-state rating.
- Life Records use `wellness_entries.calendar_event_id` as a nullable one-to-one link. They remain separate from Schedule Tags and automatic Execution Energy changes so later nutrition and exercise details can expand independently.

## Toast
- Toast is a shared UI component.
- Markup lives in `app/Views/components/toast.php`.
- CSS lives in `public/assets/css/components/toast.css`.
- JavaScript lives in `public/assets/js/components/toast.js`.
- Use `window.LifeFlowToast.show(message, options)` from page scripts.
- Validation failures and rejected range selections use Toast so feedback does not insert content above the grid or move Calendar blocks.

## Notifications
- Calendar contributes only future selected-Plan reminders for the currently viewed date. Each reminder is scheduled five minutes before its Plan block starts in the user's configured IANA time zone.
- Opening Calendar synchronizes a date-scoped replacement payload; it must never display notifications immediately.
- Settings owns whether Calendar selected-plan reminders are enabled.
- Calendar must not own global notification preferences; see `docs/notification-feature-implementation.md`.

## Future DB Extension Notes

## Responsive timetable policy

- Mobile Calendar uses a local, mutually exclusive work-mode state: `normal`, `plan-placement`, or `tag-paint`. This state is client-side only and must never replace the persisted `mode=plan|actual` request value.
- Normal mode keeps both grids editable at an even 50:50 split (excluding the fixed shared time axis). Plan-placement opens the To-do source sheet and uses an 80:20 Plan/Actual split. Tag-paint exposes the tag palette and uses a 20:80 split.
- A tag-paint click applies the selected tag immediately through the CSRF-protected `POST /calendar/event/tag` endpoint. Choosing tag removal sends an empty tag ID and persists `calendar_tag_id = NULL`; the endpoint changes no other event field.
- This three-mode interaction is mobile-first. Tablet and desktop retain the established workspace behavior until their dedicated interaction design is specified.
- The mobile work-mode rules above supersede earlier Plan/Actual-tab and 40:60 mobile-layout descriptions in this document.

- Desktop keeps the three-column Calendar workspace: activity placement, Plan timetable, and Actual timetable.
- Tablet may place activity placement above the two timetables. Both timetables still share one 24-hour row geometry.
- On mobile, the Plan tab renders activity placement and the Plan timetable at a 40:60 width ratio. The Actual tab renders the Plan and Actual timetables at a 40:60 width ratio so Plan blocks remain a direct copy source rather than a summary list.
- Timetables use document scrolling, not independently synchronized vertical scroll containers. Their row height, label offset, event-layer offset, and last-row safe-area padding must therefore remain shared CSS values.
- Mobile may compact labels and block content, but preserves all 144 ten-minute cells, pointer coordinates, resize handles, and the Plan-to-Actual copy path. A narrow screen must not replace the Plan timetable with a non-interactive summary.
- Calendar activity placement is the sole Calendar list for date-specific activities. Time-unspecified Plan items stay in data and remain editable, but Calendar must not render a duplicate time-unspecified card above the placement list.
- Calendar's existing floating action already owns Today condition and quick memo entry. Reuse its existing sheets and persistence paths instead of adding duplicate APIs or a second action menu.

- Support both MySQL and SQLite.
- Keep schema changes in both `sql/schema.mysql.sql` and `sql/schema.sqlite.sql`.
- Use prepared statements for every persisted calendar operation.
- Do not concatenate user input into SQL.

- On mobile, a quick horizontal swipe starting in empty timetable space switches one step through Plan-only, split and Actual-only. Block drags, long-press range selection, vertical scrolling and active work drawers keep their existing behavior. Plan-only places the hour labels on the right and aligns the Plan event layer with the grid. Quick-menu actions have labelled icons.

- In the mobile Plan-only view, the right-hand hour column and Plan event-layer inset use rules with equal or greater specificity than both normal and placement mode rules, so the six time cells keep the remaining width.

- Actual events with a memo show a small noninteractive dot on the first segment, including short blocks. The editor shows the memo in a collapsed disclosure and a successful save mentions the memo.

- A 7px contrast-ring memo indicator marks Actual events whose own memo is nonempty. Calendar quick memos attach to the single Actual event containing the save-time instant; if no event matches, the memo remains standalone.

- A quick memo from Calendar is appended to the single owned Actual event containing the current user-local time, so its memo indicator appears. When no event matches, multiple events match, or the combined text would exceed the event memo 500-character limit, it is saved as a standalone memo with a corresponding message. Attached quick memos are stored only in the event, not duplicated as standalone notes; earlier standalone memos are not retroactively linked.

- Standalone memos appear at their local creation time on the Actual grid when that ten-minute slot has no Actual block. Multiple memos in one slot share one labelled marker and open a read-only list in a sheet with a link to Memo management. Deleted memos are omitted; an Actual later covering the slot hides the marker while the standalone memo remains in Memo management. Markers are not events and do not affect totals or Execution Energy.

- Each standalone-memo marker and its touch area stay within its own ten-minute cell, including the first and last cells. Its visible icon shrinks when the cell is narrow so it does not cover adjacent schedule cells.

- The time-grid memo viewer reuses the structured Calendar sheet: a fixed header and close control, an internally scrolling memo list, and a safe-area-aware fixed Memo management action.

- If today’s Retrospect is not submitted, Calendar shows a sticky link to today’s review from 22:00 until midnight in the user’s time zone, regardless of the viewed Calendar date. The ten-minute clock update reveals it if the page stays open. There is currently no sleep-time detection; submission is checked when Calendar loads.

- Actual blocks may be dropped onto Plan to correct a wrong-grid entry. Drop opens confirmation, never an immediate conversion. The range preserves duration/grab offset; server validation and an atomic transaction create/reuse the Plan and remove Actual-only metadata. Existing linked Plans move to the confirmed target without duplication; a sheet text-link fallback uses the saved range. Actual-to-Actual drag remains a range move. See `actual-to-plan-conversion-review.md`.

## Dashboard today summary (2026-11-13)

DashboardService composes existing dated Todo/Routine/Retrospect sources and Calendar Actuals for a read-only overview and one nearest timed event. Todo planItems expose endIndex alongside startIndex for interval comparison; Calendar ownership, notifications and mutation APIs are unchanged. Dashboard links explicitly carry today’s user-local date rather than the shared last-viewed daily date. See dashboard-feature-implementation.md for ranking and validation.

### Day-template entry (2026-11-14)
Plan placement exposes 하루 템플릿 선택 at the top and links 반복 할 일 관리 to /todo?tab=recurring. The + menu no longer offers a separate template connector. A native dialog requires a template and acknowledgement that existing date plans are replaced and Actual links detached (Actual records remain). Applying closes the dialog and placement panel and restores the 50:50 Plan/Actual view. Cancelling preserves placement mode.

- Empty Actual-grid entry requires a stationary 360 ms touch/click hold. Moving 8 px before activation cancels entry, allowing horizontal view-ratio swipes and vertical scrolling. Mobile-width ratio swipes accept primary touch, mouse and pen input; blocks and controls retain their own interactions.

- Moving/copying a placed Plan block starts only after a stationary 450 ms touch/click hold. Before activation, movement/scroll cancels the operation; short taps retain Plan details. After activation, dragging to Actual copies the Plan while dragging within Plan moves it. Endpoint handles keep their separate resize hold.

- Actual-to-Plan and Actual-to-Actual block dragging require a stationary 450 ms touch/click hold. Short taps retain details; movement before activation permits scrolling. A Plan drop overlapping another placed Plan is rejected before confirmation; only the Actual’s own linked Plan block is excluded when reusing it. The Actual remains intact on rejection.
