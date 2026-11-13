# Common Layout

Frontend changes follow `docs/design-system-contract.md`. To-do Add owns the canonical sheet shell; `ui-choice-button`, `ui-delay-button` and `ui-disclosure` own selection, hold-action presentation and the Actual memo/life-record chevron. Calendar Plan/Actual create/edit reuse these classes. Avoid introducing page-specific alternatives for those roles. Reminder save spans its available width.

Actual time controls reuse `_actual-time-range.php` in create/edit: visible start/end labels, equal-width time controls backed by native selects, clock icons and a live duration badge. Tag add belongs to the tag heading. Optional records use divider rows rather than cards; Actual edit places lower-emphasis Delete and Save in a fixed 1:4 footer with the same hold mechanism. Regular-weight labels and consistent section edges align its fields. Grid disclosure chevrons are centered in their reserved column so rotation does not create internal horizontal scrollbars.

## Shared Plan reminder editor

The editor also renders server-calculated plan-start/trigger clock labels. It preserves drafts while refreshing missing timing and ignores responses for a previously opened block. State controls in Schedule and To-do share `ui-status-row`: six options on one row with minimum 44px targets and compact gaps. Actual detachment is an intentional lower-emphasis exception to the ghost action styling, with its hold protection unchanged.

Plan edit opts into `data-clock-display="time"`: its reminder summary/body omit calendar dates and emphasize only the trigger `HH:mm` with semantic strong text. A previous-day trigger retains only the `전날` cue. Disabled reminders hide the clock line. The Plan's own date/time header and To-do's full-date reminder presentation stay separate from this compact alarm display; scheduling data/calculation is unchanged.

Compact-clock change files: `app/Views/pages/calendar/index.php`, `app/Views/components/plan-reminder-fields.php`, `public/assets/js/components/plan-reminder.js`, `public/assets/css/components/ui.css`, `tests/plan_reminder_controls.test.js`, this document and `docs/notification-feature-implementation.md`. Verify a same-day lead, a lead crossing midnight, and an off reminder in Plan edit; compare the unchanged To-do date display. JS 30 files, PHP view lint and whitespace checks passed.

`components/todo-followup-fields.php` and `components/todo-followup.js` supply the shared optional reason/next-action editor. It guards duplicate saves, stale item lookups and reopening during a save. API data is inserted through text/value properties; carryover notes in server views are escaped.

`components/plan-reminder-fields.php`, `components/plan-reminder.js` and `.plan-reminder` rules in `components/ui.css` supply the same initially collapsed details/summary toggle in Calendar and To-do sheets. Expansion shows global notification states and a Settings link if either global gate is off; otherwise it shows wrapping choice buttons (off/start/5/10/15/30/60 minutes) and save. Choices expose `aria-pressed` and feedback uses a live region. The editor avoids nested forms and refreshes visible controls and inert To-do templates. The Settings notification heading owns the `notificationSettings` anchor.

Calendar can opt into the reminder row's bell icon using `$reminderWithIcon`. Its Plan editor pairs that row with the same-sized management icon row, preserves shared colors/radii/type, and right-aligns the reminder state. To-do retains its existing compact reminder presentation. Shared summary-state styling targets only `data-reminder-summary` so the row label keeps normal text emphasis.

The reminder wrapper has no grid gap; its choice group keeps its own spacing. In Plan edit the wrapper has zero block padding and its summary uses the same 56px row height as management, keeping icon/label insets aligned without adding wrapper padding. Management is the final visible content section and keeps only its top separator, with no bottom border. Check both collapsed and expanded states when adjusting this spacing.

Calendar forms/popups receive grid layout only while not hidden; the shared grid rule adds no padding. Hidden forms explicitly stay `display:none`, including the Plan-delete form, so they create neither padding nor an extra grid gap. Insets belong to standard sheet shells; legacy non-structured Calendar sheets retain their inset on the shell instead of the form.

## Purpose
This document defines the shared application shell used by authenticated pages.

Read this file before changing the header, aside menu, bottom navigator, shared layout CSS, or layout JavaScript.

## Shell
- Authenticated pages use `app/Views/layouts/header.php` and `app/Views/layouts/footer.php`.
- Login and registration set `$hideNav = true` and should not show app chrome.
- Public unauthenticated pages should not show the app aside or bottom navigator.
- Page views should keep their own content inside `main`.

## Header
- The authenticated header is part of normal document flow rather than sticky. It contains the current top-level menu label, a search button, and a hamburger menu button. Public/authentication surfaces retain the `LifeFlow` brand.
- The `/plan` header and aside use the same localized `하루 템플릿` menu label.
- On mobile the four daily bottom-navigation pages (Retrospect, Routine, To-do, and Schedule) hide the shared header because each page owns a compact sticky context area. Those local contexts retain Search and aside-menu buttons; opening Search temporarily reveals only the shared header search form. Tablet and desktop keep the normal-flow header visible.
- Their mobile date rows share 8px horizontal and 4px vertical padding (respecting the top safe area), a 4px gap between date and actions, vertically centered 44px controls, and the same date-picker caret width. Page-specific content below the row remains independent.
- All four daily pages use the same date-navigation component, typography and localized date format. Saturdays are blue; Sundays and registered holidays or substitute holidays are red, with holidays taking precedence. Other dates use the normal text color. Calendar retains its current mode when changing dates.
- Do not place primary navigation links directly in the header.
- Dashboard remains available through the aside rather than turning the current-menu label into a misleading link.
- A feature may supply one compact, single-line contextual action between the current-menu label and hamburger. Calendar uses this slot only for today's highest-priority Plan, Actual, or Retrospect next action; the text truncates rather than increasing the header height.
- The search button immediately precedes the hamburger. It expands a header-covering GET form, focuses the query, and submits to `/search` from either Enter or the `검색` button. The submit button includes a move-to-results icon; Escape or the close control restores the normal header, and opening the aside closes search first.
- The header search form presents its compact input and neutral submit action as one joined, outlined field, with a separate close control inside an evenly padded, vertically centered square-cornered surface in both themes.
- While header search is open, a dimmed backdrop blocks the rest of the workspace and scrolling; backdrop click, Escape, or close dismisses it. Keyboard focus stays in the search form and returns to its opener on close.

## Aside Menu
- The aside is opened from either the shared-header hamburger or a daily page's mobile context button.
- Show a compact signed-in user summary near the top of the aside.
- The aside header uses a settings gear link instead of a text brand.
- The aside owns infrequent navigation:
  - Dashboard
  - Management: Day Templates (`하루 템플릿`, `/plan`), Routine Management, Goal, Execution Energy Management
  - Tools: Memo, Search, Schedule Tag Management
- Use visible group labels for Management and Tools. Daily execution pages stay in the bottom navigator.
- Logout lives inside the Settings page and must remain a POST form with a CSRF token.
- Account identity is shown in the aside only; Settings does not repeat the same account-information card.

## Bottom Navigator
- The bottom navigator is fixed to the bottom center of the viewport.
- It owns the primary feature navigation in this order:
  - Retrospect
  - Routine
  - To-do
  - Schedule
- The four daily menus share the date last viewed in this browser tab and authenticated session through bottom-navigation links. An explicit date in a destination URL takes precedence; a new authenticated session starts at the account-local today. Calendar's current-time focus is only requested when navigating to today.
- Keep active page state through `aria-current="page"`.
- Execution Energy is a separate 52px HUD on the left of the four-item navigator. It opens usage history, preserves the glass-liquid fill and restrained motion, and does not occupy a navigation column or protrude into the floating-action area. Device orientation and pointer movement remain optional effects, and reduced-motion preferences are respected.

## Assets
- Shared layout CSS lives in `public/assets/css/app.css`.
- Equal-width segmented tabs can use `.ui-segmented-tabs` with direct child buttons and `aria-selected` or navigation links and `aria-current="page"`. To-do, Goal, recurring To-do, Routine management, Memo and Retrospect views share its 44px minimum control height, theme colors and focus treatment. The component supplies presentation only; each feature retains its own navigation or tab behavior and accessibility semantics.
- Compact lists can share `.ui-list-surface`, `.ui-list-table`, and `.ui-list-empty`. To-do and Routine use the same surface, row padding and empty-message treatment; Goal's completed list and linked-record tables also reuse the surface and empty state. Feature-specific columns, controls, grouping and overflow remain local.
- Empty views with a heading and explanation may combine `.ui-list-surface` and `.ui-empty-state`; Memo, Search and recurring To-do template lists use this treatment while retaining their own messages.
- Goal maps and Routine management/detail empty views also reuse this treatment. Empty-state actions retain the feature's original destination and fit within the surface. Shared success/info/error message borders derive from their semantic foreground/background colors in both themes; long status text wraps inside the message.
- The shared app stylesheet URL includes its file modification time so header and shell changes are refreshed in browsers and WebViews after deployment.
- Shared UI and Toast component assets use file-modification cache keys so input-sheet and feedback changes are not hidden by an older browser or WebView cache.
- Routine state CSS is also versioned in Calendar, Routine daily/management and Retrospect. Shared 44px Routine state controls use semantic neutral/success/danger surfaces, preserve their state symbols and provide an inset keyboard-focus outline so table clipping does not hide it.
- Shared layout JavaScript lives in `public/assets/js/components/app-layout.js`, and its URL uses a file-modification cache key like the shared stylesheets.
- The reusable date picker lives in `public/assets/js/components/date-picker.js` and `public/assets/css/components/date-picker.css`. `LifeFlowDatePicker.open(input, trigger)` respects the input's value, min and max, uses its `data-today` for the account-local date, emits native input/change events, and falls back to the native control when modal dialogs are unavailable. Calendar, Routine, To-do and Retrospect date navigation use it; existing form date inputs can opt in separately without nesting sheets.
- Page-specific CSS and JavaScript should continue using `$pageStyles` and `$pageScripts`.
- Android bridge JavaScript is loaded as a shared component so Settings and feature pages can sync notification payloads and temporarily control native pull-to-refresh during touch range selection.
- Native bridge calls must degrade safely in normal browsers and try both `window.AndroidBridge` and `window.AndroidInterface` when the requested method exists.
- See `docs/android-webview-integration.md` before changing Android WebView bridge methods or native pull-to-refresh coordination.
- Shared modal and sheet close actions should use click handlers consistently; avoid registering pointerup and click on the same close control.
- Shared UI modals must stack above page-local sheets so confirmation dialogs opened from a sheet are never hidden behind it.
- Shared hover tooltips should only appear on hover-capable fine pointers, not on touch devices.
- The shared Toast accepts an optional `celebration` variant for derived milestones. It uses a restrained pulse/spark treatment, remains readable without motion, and must respect `prefers-reduced-motion`; ordinary Toast calls keep their existing appearance and behavior.
- Toast uses semantic theme surfaces/borders and primary text for its action. Long messages and action labels wrap within the viewport; actions have a minimum 44px target and visible keyboard focus. Date-picker headers wrap long titles while preserving their 44px close control. Toast duration, callback and dismissal behavior remain unchanged.
- Shared `.input` fields use the app typography, 15px text, soft card-like surfaces, and visible focus states so inputs and textareas match the current visual system.
- Use `.form-group` to keep a label, its `.input` control and any field error together with the shared 8px gap. Quick-create fields in To-do, Calendar, Routine, Memo and Goal, and Schedule Tag name fields use this pattern; form-level gaps remain independent.
- Brief input guidance can use `.form-hint` for the shared 12px muted treatment. Associate field-specific guidance and validation errors with their inputs using `aria-describedby`; invalid fields also use `aria-invalid`.
- `.input[aria-invalid="true"]` and `.input.is-error` share semantic error colors in both themes. Shared inputs and form groups allow shrinking inside narrow grid columns.
- Settings theme choices use shared input-label typography, semantic surfaces/control borders, a selected surface and visible keyboard focus. Native radios retain selection when `:has()` is unavailable. Notification groups use the same label typography and allow long translations to wrap. Shared To-do/Calendar importance radios have 44px targets, control borders and a visible focus ring; time fields use shrinkable equal-width columns. To-do sheet height follows the dynamic viewport with a `vh` fallback.
- General content cards in Memo, Search and Schedule Tag Management use the existing 16px radius, 16px padding, surface and border tokens. Memo management actions and Tag visibility/edit controls retain a minimum 44px touch height. Search filters use the shared 14px label typography and 8px field spacing, and stack on screens narrower than 360px.
- Plan template and Routine management cards also use shared surface/border colors and the 16px radius. Their compact list padding and calendar structure stay feature-specific. Plan copy/expand and Routine month controls provide at least 44px height. Reminder checkboxes and time controls have separate labels; the Routine reminder row stacks below 360px.
- Shared Eisenhower importance input uses four accessible radio controls displayed horizontally as A/B/C/D buttons. The shared prompt sheet and Calendar/Plan forms reuse this pattern.
- Shared `.sr-only` keeps semantic page headings available to assistive technology when a content-first feature page omits its large visible menu introduction.
- Shared bottom sheets focus their primary input during the opening user action and keep the active input visible when the mobile visual viewport changes.
- Right-side panels align content to the top and scroll internally when content exceeds the viewport. Page-level create actions use a shared pill-shaped floating button above the bottom navigator.
- Goal detail/edit/create dialogs focus an internal control, contain Tab navigation and restore their opener when dismissed. Nested date pickers and shared UI confirmations keep control of their own keyboard handling. The deep Goal map owns its horizontal scroll instead of widening the mobile page.
- Memo editors, Routine management dialogs and daily Routine quick creation also contain Tab navigation and restore their opener. Shared confirmation/prompt dialogs contain focus and consume Escape so page-level sheets do not close at the same time. Hidden/disabled fields are excluded from the keyboard boundary controls.
- To-do dialogs, reusable Plan-block editing and Calendar dialogs contain Tab within the visible sheet as well. To-do creation and Plan-block sheets restore their opener. Calendar restores its original visible trigger, falling back to the quick-menu toggle when the originating menu action has become hidden. Calendar's separate Plan-create sheet also supports Escape. Existing drag cancellation and inline selection-view back behavior remain separate.
- Daily Retrospect, Routine, To-do, and Memo primary actions use the shared centered floating button above the navigator. The bottom navigation shell, including the separate Execution Energy HUD, stays below the floating-action area. Compact page-header actions use a shared 36px secondary pill so Today, Routine Management, and Retrospect controls remain visually consistent.
- Shared `.btn` styling applies the same inherited app font, centering and no-underline treatment to both buttons and links, including Retrospect empty-state actions. Shared close controls show the standard visible keyboard-focus outline.
- Compact header actions rendered as links remove the browser's default underline while retaining their visible focus outline. Routine and To-do reuse the shared sticky date context; their respective management links remain below it.
- The aside fills the viewport, begins at the top, and scrolls internally when its navigation content is taller than the screen.

## Input bottom sheets

- Input and edit sheets reuse the shared `ui-form-sheet`, `ui-form-sheet-header`, and `ui-form-sheet-actions` primitives. Feature pages continue to own their field markup, validation, and persistence behavior.
- Shared input-sheet headers use a 15px, 700-weight title, vertically centered layout and a transparent 44px close target. Read-only detail and report headers retain their own hierarchy; feature-specific sticky offsets and spacing remain local.
- Shared confirmation/prompt panels use the same semantic surface and border colors in both themes, 15px/700 titles and a non-shrinking transparent 44px close target. Long titles and body text wrap inside the panel. Prompt action padding aligns with its form fields instead of adding a second horizontal inset. Goal edit headers reuse the input-sheet header styling; read-only Goal headers retain their hierarchy.
- The bottom-aligned standard sheet surface is shared through `ui-standard-sheet`; the To-do sheets, Routine quick-create sheet, Calendar's To-do sheet and Memo editors use it. Sheet-specific size limits, scrolling, fields and event handling remain with each feature.
- Goal quick creation and Routine management create/edit also use `ui-standard-sheet` with its 20px padding, shared shadow and bottom-aligned surface. Routine retains its 560px cap and Goal its 620px cap. Routine read-only details retain the inset panel. Routine's layer removes its outer inset only while a standard input sheet is visible; without `:has()` support it retains the existing inset. Sticky headers/actions use the input sheet padding so their edges align in both themes.
- Long forms may opt into `ui-form-sheet is-structured` with `ui-form-sheet-layout`, `ui-form-sheet-view`, and `ui-form-sheet-body`. The fixed header and action area sit outside the one scrolling body so field labels cannot slide beneath the header.
- A structured sheet may switch from its main view to a long selection view without stacking another bottom sheet. It preserves the draft, restores it on cancel, and returns focus to the row that opened the selection view.
- Do not render a drag handle unless coordinated drag-to-dismiss behavior is actually implemented. Standard LifeFlow sheets use a clear title and a close control with at least a 44px touch target.
- The header and its close target remain reachable, the body scrolls inside the sheet, and the primary action stays above the mobile safe area. Close controls provide at least a 44px touch target.
- A quick-create sheet keeps its essential field visible. It must not add a disclosure or another sheet merely to appear consistent with a longer editor.
- A structured editor keeps its required fields visible and uses an inline summary/disclosure for optional settings. Collapsed summaries must expose the current selection so saved values are not mistaken for empty values.
- A secondary sheet is not the default extension mechanism. Use at most one secondary level only when the task is independently completable and needs a long searchable list, filtering, or meaningful multi-selection. Simple radio choices, dates, times, importance, toggles, and short text stay in the current sheet.
- Never open a third sheet level. If a secondary task itself becomes complex, promote it to a full-screen selector instead of nesting again.
- KPT writing, read-only report/detail views, desktop Goal details, confirmations, and full-page management tables keep their specialized presentation; they share tokens and accessibility behavior rather than the input-sheet information architecture.
- Optional inline sections may reuse `ui-progressive-disclosure`. Avoid card-in-card decoration inside a sheet; use spacing and dividers for subordinate settings.

## Theme
- The authenticated shell reads `$_SESSION['theme_preference']` and writes it to `body[data-theme]`.
- Supported values are `light` and `dark`.
- Settings persists the user choice in `user_preferences.theme`.
- Choosing light or dark mode applies and persists immediately when JavaScript is available. The POST redirect remains as the non-JavaScript fallback.
- Shared colors should be expressed through CSS variables so page-specific styles respond to the active theme.
- Brand coral is reserved for actions and selected surfaces. `--color-primary-text` supplies readable coral-family text on light surfaces, while success, warning, danger, and information/current-time colors keep stable semantic roles.
- Placeholders use the same opaque muted-text token as helper text in both themes. Light-theme danger text is `#a7443c`; focus outlines use the opaque primary-text token rather than a translucent coral. Automated tests cover the shared text/surface token pairs; this does not certify every page or user-owned tag color.
- Do not communicate state through color alone. Completion, energy direction, selection, and warnings also need text, icons, shape, or position. Borders required to identify an input or popup use `--color-control-line`; decorative grouping borders may remain quieter.
- Light mode keeps primary `#FF5E5B` and secondary `#1A1B3A`; dark mode uses primary `#FF756F` and secondary `#F4D27A` with matching contrast-text tokens.
- The shared 15 Calendar tag colors do not change when the theme changes because they identify user-owned schedule categories. Components that fill a card with a tag color must calculate a readable dark or light foreground instead of assuming white text.
- Dark mode coverage must include shared inputs, buttons, toast, aside profile/settings surfaces, page cards, bottom sheets, and page-local selection controls.
- Page-specific CSS should use `body[data-theme='dark']` for dark overrides because the shell writes the theme to the body `data-theme` attribute.

## Language And Time Zone

- Settings persists display language and time zone independently in `user_preferences.locale` and `user_preferences.timezone`.
- Supported display languages are Korean (`ko`), English (`en`), and Japanese (`ja`). User-authored content is never translated.
- The shared shell writes the selected locale to `<html lang>` and uses centralized translation keys for common navigation and shared controls.
- Time zones use IANA identifiers such as `Asia/Seoul` and must not be inferred from display language.
- The selected time zone defines Today, Routine editability, Retrospect date boundaries, Goal deadlines, and notification calculations.

## Responsive Rules
- Mobile is the primary layout target.
- Tablet keeps the same navigation model with wider content.
- Desktop keeps the same navigation model with a wider content container and drawer.
- Feature pages, including Calendar, should use the shared `.page` container so their content widths follow the same responsive breakpoints.

## Information hierarchy

- Use a card for an independently actionable record or a deliberately elevated state, not for every section.
- Group simple information with a heading, spacing, dividers, or a table. Avoid an outer card and repeated inner cards describing the same hierarchy.
- Repeated homogeneous records should prefer compact rows or tables. Controls receive visible shape; passive labels and supporting metadata normally do not.
- Card reduction is evaluated by hierarchy and task clarity rather than a fixed percentage. Retrospect composition/history groups and To-do rows are the first shared examples of this rule.
- Execution Energy changes use one shared badge language in summaries and reports: coral with `−` for use, green with `+` for recovery, and neutral gray with `0` for no change. Never rely on color without the sign and an accessible text label.

- The published Retrospect report shares the mobile Retrospect context header and sticky view switch; its newspaper content and archive navigation remain below it.

- Retrospect daily, published history and report views use the shared daily date navigation in their sticky context. History date changes select that month.

- Published Retrospect history uses the same sticky context layout with month selection and month arrows in the date position.

- Viewing published reports does not overwrite the session's selected daily date; the report header uses that saved date while report navigation changes the viewed report only.

- On mobile, Recurring To-do, Routine Management, Goal, Execution Energy, Memo, Search and Schedule Tag Management use the shared compact sticky header with the menu label and 44px Search/menu controls; the date selector is not rendered.

- On mobile Dashboard uses the same compact sticky menu header as management pages, without a date control.

## Structured confirmation notices

LifeFlowUI.confirm optionally accepts a plain-text question and noticeItems array. It creates a strong multiline question and an accessible trailing exclamation disclosure, with small bullet warnings in a surface-soft speech bubble. Tap/click pins it, mouse hover previews it, and Escape on the disclosure closes the bubble before the modal. The dialog uses a div body so structured content remains valid HTML; all supplied strings use textContent. Only this opt-in confirmation keeps equal-width Cancel/Confirm actions in one row below 479px. Plain confirmations reset the notice class/content and retain their existing layout. Native fallback callers must supply the complete question/warnings in message. Calendar Actual-to-Plan uses this presentation.

## Main menu tab context

To-do, Retrospect (including published reports), recurring Plan, Routine Management, Goal (including review) and Memo wrap their top-level ui-segmented-tabs in ui-menu-context. It owns Retrospect’s sticky layout, full-width context, 12px tab inset, 6px date/tab gap and 8px bottom padding. Date navigation, when present, stays in the same context with position:static. The context has no background; its date row explicitly uses background:none to override the standalone date bar. Memo keeps its existing visible header above the tabs; preceding error messages also keep their space. Feature filters, URLs and button/link semantics stay with their owners. See menu-tabs-unification.md for changed files and checks.

Dashboard replaces its old quick-menu cards with a four-column app-icon card. Configured entries come from config/app.php; ORESEED opens the user-supplied /sub/oreseed.html in the same tab. See dashboard-feature-implementation.md for changed files and checks.

## Menu labels and duplicate introductions (2026-11-13)

Korean management labels are 실행력 and 루틴; the daily Routine header and bottom navigation use 일일루틴. Search, Execution Energy, Memo, and the recurring block tab omit duplicate visible introductions, retaining screen-reader headings. Memo navigation includes short notes, long notes, and trash. See `menu-tabs-unification.md` for top spacing.

## Shared date/time dialogs (2026-11-13)

All .lf-date-dialog sheets, including daily header dates and month selection, use width:min(100%,680px), no outer horizontal inset, bottom alignment and top-only 24px corners. Internal header/body padding remains. Search date filters call the same LifeFlowDatePicker.open API without daily-date auto-submit.

components/time-picker.js exposes LifeFlowTimePicker.open(select, trigger), enhance(root) and sync(root). Opt-in select[data-time-picker] options must have HH:mm text; their original values and allowed choices remain authoritative. Enhancement replaces the visible select with a dialog trigger, retaining native select submission/fallback. Programmatic value changes call sync; Calendar's duration summary does so. Hour/minute choices are drafts until Apply; Cancel/Escape/backdrop preserves the value and restores focus. Existing option boundaries determine valid combinations, including end-only 24:00.

## Sheet close icon parity (2026-11-13)

All ui-close-button and lf-date-close controls use the Actual edit reference: 22px glyph, 400 weight, line-height 1, centered in a 44px square with zero padding. The shared --ui-sheet-close-icon-size token owns the icon size. Date-dialog font inheritance no longer overrides it; previous/next arrows retain their separate styles.

## Routine management header month (2026-11-13 correction)

On /manage/routines the left header label is replaced by components/routine-month-navigation.php: month picker plus previous/next month controls. Search and aside buttons stay in their original right-side slot. Search expansion hides the month control. Both status tabs preserve month/status query parameters; the body no longer repeats the month row. Other menu headers and daily session date handling are unchanged.

## Bottom navigation (2026-11-13)

The bottom menu shows 회고 / 오늘루틴 / 오늘할 일 / 일정 in four equal columns, centered within the full navigator. Dedicated nav.today_routine and nav.today_todo labels apply only to the bottom menu. The Execution Energy orb is omitted from footer rendering and its script is no longer loaded; the shell uses one column without a reserved orb slot. Execution Energy remains available through Management.

Validation: PHP footer/I18n lint and whitespace checks. Manual check: compare all four item widths and center alignment on Android Chrome, confirm active states and destinations, and check both themes.

## Settings header and return navigation (2026-11-13)

Settings joins the same is-compact-menu-header scope as Memo, including the mobile sticky position and shared heading/Search/aside layout. Settings-related information/account views opt into headerBackToSettings. Their header adds a labelled /settings back link with explicit back/title/action grid columns, hides it while header Search is expanded, and retains native link navigation without depending on browser history. No other menu header changes.

The shared time picker now enhances minute-resolution input[type=time] as well as Calendar selects. Its sync/enhance/open APIs are unchanged; the original field still owns submitted values. select-sheet.js provides a parallel searchable sheet for select[data-select-sheet], preserving the options, native fallback, descriptions and change events.

## Header visual insets (2026-11-13)

Shared authenticated headers now use the same 16px inline padding on both sides, respecting safe-area insets. The trailing action group compensates for whitespace inside its 44px menu hit area, and the optional back button compensates for its SVG inset. This aligns visible label/back and menu edges while preserving touch targets and the Memo/Settings sticky behavior. Routine management’s header month strip removes its extra internal 4px inset. Browser fixtures measured left label 16px and trailing menu glyph 16.5px on mobile (15px desktop); 15 settings-return header variants retain nonoverlapping back/title/actions.

## Daily menu labels (2026-11-14)

The bottom labels are 회고 / 루틴 / 할 일 / 일정. Korean daily Routine header naming also uses 루틴. Routes, selected-date handling and the aside’s current 루틴 label remain unchanged. To distinguish the two Routine destinations, the recommended follow-up is to label the aside entry 루틴 관리 (creation/period/reminder configuration) and retain 루틴 for daily execution; this naming proposal was not applied to the aside in this task.

## Routine collection naming (2026-11-14)

The aside and /manage/routines header now use 루틴 모음, while daily /routine and the bottom navigator retain 루틴. The daily Routine management link follows the same Korean collection label. English/Japanese management navigation uses Routine collection/ルーティン集. Routes, date state and routine data are unchanged. This applies the softer collection-name proposal instead of the earlier 루틴 관리 recommendation.
