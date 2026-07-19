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
- The header is sticky.
- The header contains the `LifeFlow` brand and a hamburger menu button.
- Do not place primary navigation links directly in the header.

## Aside Menu
- The aside is opened from the header hamburger button.
- Show a compact signed-in user summary near the top of the aside.
- The aside header uses a settings gear link instead of a text brand.
- The aside owns management navigation:
  - Dashboard
  - Calendar
  - Plan
  - Routine
  - Retrospect
  - Goal
  - Memo
  - Schedule Tag Management
- Group aside links with separators:
  - Dashboard
  - Calendar, Plan, Routine, Retrospect, Goal, Memo
  - Schedule Tag Management
- Logout lives inside the Settings page and must remain a POST form with a CSRF token.
- Account identity is shown in the aside only; Settings does not repeat the same account-information card.

## Bottom Navigator
- The bottom navigator is fixed to the bottom center of the viewport.
- It owns the primary feature navigation in this order:
  - Retrospect
  - Plan
  - Routine
  - Calendar
- Keep active page state through `aria-current="page"`.

## Assets
- Shared layout CSS lives in `public/assets/css/app.css`.
- Shared layout JavaScript lives in `public/assets/js/components/app-layout.js`.
- Page-specific CSS and JavaScript should continue using `$pageStyles` and `$pageScripts`.
- Android notification bridge JavaScript is loaded as a shared component so Settings and feature pages can sync scheduling payloads consistently.
- Shared modal and sheet close actions should use click handlers consistently; avoid registering pointerup and click on the same close control.
- Shared UI modals must stack above page-local sheets so confirmation dialogs opened from a sheet are never hidden behind it.
- Shared hover tooltips should only appear on hover-capable fine pointers, not on touch devices.
- Shared `.input` fields use the app typography, 15px text, soft card-like surfaces, and visible focus states so inputs and textareas match the current visual system.
- Shared bottom sheets focus their primary input during the opening user action and keep the active input visible when the mobile visual viewport changes.

## Theme
- The authenticated shell reads `$_SESSION['theme_preference']` and writes it to `body[data-theme]`.
- Supported values are `light` and `dark`.
- Settings persists the user choice in `user_preferences.theme`.
- Choosing light or dark mode applies and persists immediately when JavaScript is available. The POST redirect remains as the non-JavaScript fallback.
- Shared colors should be expressed through CSS variables so page-specific styles respond to the active theme.
- Dark mode coverage must include shared inputs, buttons, toast, aside profile/settings surfaces, page cards, bottom sheets, and page-local selection controls.
- Page-specific CSS should use `body[data-theme='dark']` for dark overrides because the shell writes the theme to the body `data-theme` attribute.

## Responsive Rules
- Mobile is the primary layout target.
- Tablet keeps the same navigation model with wider content.
- Desktop keeps the same navigation model with a wider content container and drawer.
