# Database Migrations

## Locale And Time Zone

- New installations include `user_preferences.locale` (default `ko`) and `user_preferences.timezone` (default `Asia/Seoul`) in both schema files.
- Existing MySQL installations apply `sql/migration.add_locale_timezone.mysql.sql`.
- Existing SQLite installations apply `sql/migration.add_locale_timezone.sqlite.sql`.
- The repository also adds missing preference columns defensively at runtime, matching the existing notification-preference compatibility behavior.
- Locale and time zone are independent; changing one must never overwrite the other.

## Daily Plan Copies

Apply after Goal, Plan, and Calendar base tables:

- MySQL/MariaDB: `sql/migration.daily_plans.mysql.sql` once.
- SQLite: application bootstrap applies `sql/migration.daily_plans.sqlite.sql` after adding `calendar_events.daily_plan_item_id`.

The migration creates `daily_plans` and `daily_plan_items`, copies existing `calendar_days.plan_group_id` selections, preserves copied goal links, and backfills actual-event links where a source template match exists.

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
8. `sql/migration.daily_plan_untimed.mysql.sql` — requires `daily_plan_items` and `retrospect_report_plan_items`; allows both time indexes to be null for time-unspecified daily Plan items and their published snapshots.

9. `sql/migration.plan_block_templates.mysql.sql` — requires `user` and `goals`; creates the reusable Plan-block template library.
10. `sql/migration.add_retrospect_review.mysql.sql` — requires the Retrospect snapshot tables; adds connected-Goal labels and published Memo snapshots.
11. `sql/migration.add_execution_energy.mysql.sql` — requires `user` and `calendar_events`; creates Execution Energy baseline, group, daily snapshot, event setting, and legacy correction/notice tables retained for migration compatibility.
12. `sql/migration.add_execution_energy_feedback.mysql.sql` — apply only when migration 11 was deployed before `execution_energy_days.burden_feedback` was introduced; do not apply it after the current full migration.

The Routine validation trigger migration depends on the lifecycle columns; the other feature migrations are independent. The order above is the recommended release order for repeatable deployments. Record each applied migration because the `ALTER TABLE` statements are not intended to be rerun.

Before the Routine lifecycle migration, run `SELECT VERSION()` and `SHOW CREATE TABLE routines`. Older MySQL versions may parse CHECK without enforcing it, while MySQL 8 and MariaDB use different constraint-removal syntax. The portable lifecycle migration therefore leaves CHECK handling out and relies on `RoutineService` validation; apply the companion trigger migration when DB-level enforcement is required. If `SHOW CREATE TABLE` reveals an enforced legacy `duration_days >= 7` constraint, remove that named constraint using syntax verified for the exact server version before enabling 1-day routines.

The trigger file uses MySQL client `DELIMITER` directives. Apply it with a client/import tool that supports those directives, then verify with `SHOW TRIGGERS LIKE 'routines'`. Do not run the full `schema.mysql.sql` against an existing production database.

MySQL application connections set the session timezone to `+00:00`. Before deployment, compare `NOW()` and `UTC_TIMESTAMP()` and inspect recent `notes.created_at` rows. If historical rows were written as local Korean time rather than UTC, migrate those rows separately after taking a backup; do not apply a blind nine-hour shift.

## Existing SQLite Database

- `app/Core/Database.php` automatically upgrades `calendar_events.schedule_type` when required and reapplies `sql/schema.sqlite.sql` to create missing schema objects such as `notes`.
- `UserPreferenceRepository` adds missing notification-preference columns for an existing local database.
- `app/Core/Database.php` automatically applies `sql/migration.routine_lifecycle.sqlite.sql` when the existing Routine table still has the 7-day minimum or lacks lifecycle columns.
- `app/Core/Database.php` adds `retrospect_report_actual_items.memo_snapshot` when it is missing.
- `app/Core/Database.php` automatically applies `migration.daily_plan_untimed.sqlite.sql` and `migration.retrospect_plan_untimed.sqlite.sql` when existing Plan time columns are still non-nullable.
- `app/Core/Database.php` automatically applies `migration.plan_block_templates.sqlite.sql` when the reusable Plan-block template table is missing.
- `app/Core/Database.php` adds Retrospect Goal snapshot columns and reapplies the schema to create the Memo snapshot table; `sql/migration.add_retrospect_review.sqlite.sql` is available for controlled upgrades.
- `app/Core/Database.php` creates the Execution Energy tables when `execution_energy_profiles` is missing; `sql/migration.add_execution_energy.sqlite.sql` is available for controlled manual migration.
- Existing SQLite Execution Energy tables receive the missing `burden_feedback` column automatically; `sql/migration.add_execution_energy_feedback.sqlite.sql` is available for controlled manual migration.
- Reapplying `sql/schema.sqlite.sql` creates `calendar_tag_preferences`; `sql/migration.calendar_tag_preferences.sqlite.sql` is available for controlled manual migration.
- The matching `*.sqlite.sql` files remain available for controlled manual migrations.
- Do not delete SQLite compatibility or fallback code even when production uses MySQL; it remains part of local and fallback support.
