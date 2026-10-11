# Common Layout

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
- On mobile the four daily bottom-navigation pages (Retrospect, Routine, To-do, and Schedule) hide the shared header because each page owns a compact sticky context area. Those local contexts retain Search and aside-menu buttons; opening Search temporarily reveals only the shared header search form. Tablet and desktop keep the normal-flow header visible.
- Do not place primary navigation links directly in the header.
- Dashboard remains available through the aside rather than turning the current-menu label into a misleading link.
- A feature may supply one compact, single-line contextual action between the current-menu label and hamburger. Calendar uses this slot only for today's highest-priority Plan, Actual, or Retrospect next action; the text truncates rather than increasing the header height.
- The search button immediately precedes the hamburger. It expands a header-covering GET form, focuses the query, and submits to `/search` from either Enter or the `검색` button. The submit button includes a move-to-results icon; Escape or the close control restores the normal header, and opening the aside closes search first.

## Aside Menu
- The aside is opened from either the shared-header hamburger or a daily page's mobile context button.
- Show a compact signed-in user summary near the top of the aside.
- The aside header uses a settings gear link instead of a text brand.
- The aside owns infrequent navigation:
  - Dashboard
  - Management: Plan, Routine Management, Goal, Execution Energy Management
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
- Schedule links to Calendar and defaults to today. Routine and To-do also default to today in the signed-in user's time zone but preserve an explicitly selected date in `?date=YYYY-MM-DD`.
- Keep active page state through `aria-current="page"`.
- Execution Energy is a separate 52px HUD on the left of the four-item navigator. It opens usage history, preserves the glass-liquid fill and restrained motion, and does not occupy a navigation column or protrude into the floating-action area. Device orientation and pointer movement remain optional effects, and reduced-motion preferences are respected.

## Assets
- Shared layout CSS lives in `public/assets/css/app.css`.
- The shared app stylesheet URL includes its file modification time so header and shell changes are refreshed in browsers and WebViews after deployment.
- Shared UI and Toast component assets use file-modification cache keys so input-sheet and feedback changes are not hidden by an older browser or WebView cache.
- Shared layout JavaScript lives in `public/assets/js/components/app-layout.js`, and its URL uses a file-modification cache key like the shared stylesheets.
- Page-specific CSS and JavaScript should continue using `$pageStyles` and `$pageScripts`.
- Android bridge JavaScript is loaded as a shared component so Settings and feature pages can sync notification payloads and temporarily control native pull-to-refresh during touch range selection.
- Native bridge calls must degrade safely in normal browsers and try both `window.AndroidBridge` and `window.AndroidInterface` when the requested method exists.
- See `docs/android-webview-integration.md` before changing Android WebView bridge methods or native pull-to-refresh coordination.
- Shared modal and sheet close actions should use click handlers consistently; avoid registering pointerup and click on the same close control.
- Shared UI modals must stack above page-local sheets so confirmation dialogs opened from a sheet are never hidden behind it.
- Shared hover tooltips should only appear on hover-capable fine pointers, not on touch devices.
- The shared Toast accepts an optional `celebration` variant for derived milestones. It uses a restrained pulse/spark treatment, remains readable without motion, and must respect `prefers-reduced-motion`; ordinary Toast calls keep their existing appearance and behavior.
- Shared `.input` fields use the app typography, 15px text, soft card-like surfaces, and visible focus states so inputs and textareas match the current visual system.
- Shared Eisenhower importance input uses four accessible radio controls displayed horizontally as A/B/C/D buttons. The shared prompt sheet and Calendar/Plan forms reuse this pattern.
- Shared `.sr-only` keeps semantic page headings available to assistive technology when a content-first feature page omits its large visible menu introduction.
- Shared bottom sheets focus their primary input during the opening user action and keep the active input visible when the mobile visual viewport changes.
- Right-side panels align content to the top and scroll internally when content exceeds the viewport. Page-level create actions use a shared pill-shaped floating button above the bottom navigator.
- Daily Retrospect, Routine, To-do, and Memo primary actions use the shared centered floating button above the navigator. The bottom navigation shell, including the separate Execution Energy HUD, stays below the floating-action area. Compact page-header actions use a shared 36px secondary pill so Today, Routine Management, and Retrospect controls remain visually consistent.
- Shared `.btn` styling applies the same centering and no-underline treatment to both buttons and links, including Retrospect empty-state actions.
- Compact header actions rendered as links remove the browser's default underline while retaining their visible focus outline. Routine and To-do reuse the shared sticky date context; only the page-specific management/Schedule link remains below it.
- The aside fills the viewport, begins at the top, and scrolls internally when its navigation content is taller than the screen.

## Input bottom sheets

- Input and edit sheets reuse the shared `ui-form-sheet`, `ui-form-sheet-header`, and `ui-form-sheet-actions` primitives. Feature pages continue to own their field markup, validation, and persistence behavior.
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
