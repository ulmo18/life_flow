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

These feature migrations do not depend on one another; the order above is the recommended release order for repeatable deployments. Record each applied migration because the `ALTER TABLE` statements are not intended to be rerun.

## Existing SQLite Database

- `app/Core/Database.php` automatically upgrades `calendar_events.schedule_type` when required and reapplies `sql/schema.sqlite.sql` to create missing schema objects such as `notes`.
- `UserPreferenceRepository` adds missing notification-preference columns for an existing local database.
- The matching `*.sqlite.sql` files remain available for controlled manual migrations.
- Do not delete SQLite compatibility or fallback code even when production uses MySQL; it remains part of local and fallback support.
