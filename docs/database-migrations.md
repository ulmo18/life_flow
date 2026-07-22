# Database Migrations

## Purpose

This document records the deployment order for the current Calendar, notification-preference, and Memo schema additions. Both MySQL and SQLite remain supported.

## New Database

- MySQL: initialize with `sql/schema.mysql.sql`.
- SQLite: initialize with `sql/schema.sqlite.sql`; the application bootstrap also creates missing schema objects with `CREATE TABLE IF NOT EXISTS`.
- Do not run the matching incremental migrations after applying the current full schema to an empty database.

## Existing MySQL Database

Back up the database, then apply these one-time migrations after their prerequisite tables already exist:

1. `sql/migration.add_calendar_schedule_type.mysql.sql` — requires `calendar_events`.
2. `sql/migration.add_notification_preferences.mysql.sql` — requires `user_preferences`.
3. `sql/migration.add_notes.mysql.sql` — requires `user`.
4. `sql/migration.routine_lifecycle.mysql.sql` — requires the existing `routines` table and adds lifecycle columns without relying on version-specific CHECK syntax.
5. `sql/migration.routine_lifecycle_validation_triggers.mysql.sql` — optional DB-level validation fallback for hosted MySQL/MariaDB versions that do not enforce CHECK constraints.
6. `sql/migration.retrospect_event_memo.mysql.sql` — preserves Calendar event memos in published Retrospect snapshots.
7. `sql/migration.calendar_tag_preferences.mysql.sql` — requires `user` and `calendar_tags`; stores each user's fixed-tag visibility without changing shared tags or historical events.

The Routine validation trigger migration depends on the lifecycle columns; the other feature migrations are independent. The order above is the recommended release order for repeatable deployments. Record each applied migration because the `ALTER TABLE` statements are not intended to be rerun.

Before the Routine lifecycle migration, run `SELECT VERSION()` and `SHOW CREATE TABLE routines`. Older MySQL versions may parse CHECK without enforcing it, while MySQL 8 and MariaDB use different constraint-removal syntax. The portable lifecycle migration therefore leaves CHECK handling out and relies on `RoutineService` validation; apply the companion trigger migration when DB-level enforcement is required. If `SHOW CREATE TABLE` reveals an enforced legacy `duration_days >= 7` constraint, remove that named constraint using syntax verified for the exact server version before enabling 1-day routines.

The trigger file uses MySQL client `DELIMITER` directives. Apply it with a client/import tool that supports those directives, then verify with `SHOW TRIGGERS LIKE 'routines'`. Do not run the full `schema.mysql.sql` against an existing production database.

MySQL application connections set the session timezone to `+00:00`. Before deployment, compare `NOW()` and `UTC_TIMESTAMP()` and inspect recent `notes.created_at` rows. If historical rows were written as local Korean time rather than UTC, migrate those rows separately after taking a backup; do not apply a blind nine-hour shift.

## Existing SQLite Database

- `app/Core/Database.php` automatically upgrades `calendar_events.schedule_type` when required and reapplies `sql/schema.sqlite.sql` to create missing schema objects such as `notes`.
- `UserPreferenceRepository` adds missing notification-preference columns for an existing local database.
- `app/Core/Database.php` automatically applies `sql/migration.routine_lifecycle.sqlite.sql` when the existing Routine table still has the 7-day minimum or lacks lifecycle columns.
- `app/Core/Database.php` adds `retrospect_report_actual_items.memo_snapshot` when it is missing.
- Reapplying `sql/schema.sqlite.sql` creates `calendar_tag_preferences`; `sql/migration.calendar_tag_preferences.sqlite.sql` is available for controlled manual migration.
- The matching `*.sqlite.sql` files remain available for controlled manual migrations.
- Do not delete SQLite compatibility or fallback code even when production uses MySQL; it remains part of local and fallback support.
