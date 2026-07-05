# Calendar Feature Roles

## Purpose
This document defines the ownership boundaries for the dashboard calendar entry point, the calendar feature page, shared toast UI, and temporary fixture data.

Read this file before changing calendar, toast, dashboard entry, or calendar data-source behavior.

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
- Keep drag selection and temporary client-side event creation in page JavaScript until persistence is added.

## Controller
- `CalendarController` handles request and response flow only.
- Controllers may choose views, pass prepared data, and select page assets.
- Do not put SQL or business rules in controllers.

## Model
- `CalendarRepository` is the data access boundary for calendar events.
- Current implementation reads fixture data.
- Future MySQL/SQLite queries should be added here or behind this model boundary.
- Keep returned event shapes stable so views and JavaScript do not depend on the data source.

## Service
- `CalendarService` prepares calendar data for rendering.
- Use it for formatting, segment generation, and data-source normalization.
- Keep DB queries out of services.

## Fixture Data
- `app/Data/calendar_fixture.php` contains temporary test data.
- Preserve the fixture as a development fallback when adding DB persistence.
- Fixture and DB-backed events should normalize to the same structure:
  - `title`
  - `start`
  - `end`

## Toast
- Toast is a shared UI component.
- Markup lives in `app/Views/components/toast.php`.
- CSS lives in `public/assets/css/components/toast.css`.
- JavaScript lives in `public/assets/js/components/toast.js`.
- Use `window.LifeFlowToast.show(message, options)` from page scripts.

## Future DB Extension Notes
- Support both MySQL and SQLite.
- Keep schema changes in both `sql/schema.mysql.sql` and `sql/schema.sqlite.sql`.
- Use prepared statements for every persisted calendar operation.
- Do not concatenate user input into SQL.
