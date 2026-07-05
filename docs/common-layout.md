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
- The aside owns management navigation:
  - Dashboard
  - Calendar
  - Settings
  - Logout
- Logout must remain a POST form with a CSRF token.

## Bottom Navigator
- The bottom navigator is fixed to the bottom center of the viewport.
- It owns the primary feature navigation in this order:
  - Retrospect
  - Calendar
  - Routine
  - Plan
- Keep active page state through `aria-current="page"`.

## Assets
- Shared layout CSS lives in `public/assets/css/app.css`.
- Shared layout JavaScript lives in `public/assets/js/components/app-layout.js`.
- Page-specific CSS and JavaScript should continue using `$pageStyles` and `$pageScripts`.

## Responsive Rules
- Mobile is the primary layout target.
- Tablet keeps the same navigation model with wider content.
- Desktop keeps the same navigation model with a wider content container and drawer.
