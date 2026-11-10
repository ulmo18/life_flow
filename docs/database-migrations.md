# Database Migrations

## To-do follow-up and explicit carryover registration

Existing MySQL/MariaDB installs apply `sql/migration.todo_followup.mysql.sql`, then `sql/migration.todo_carryover.mysql.sql`, each once after backup. They add a nullable follow-up note, a nullable self-referencing source ID and a unique daily-plan/source index. Existing tasks stay unchanged and no tasks are copied by migration. Current full schemas already include these objects; do not reapply incremental ALTERs to them.

SQLite bootstraps each missing column and the unique index independently. Matching SQLite migration files are available for controlled manual use only when their target objects do not exist. Existing MySQL without the columns remains readable: the notice can show eligible yesterday sources, but note saving/registration stays unavailable until migration. Both the pre-feature MariaDB 10.11 migration path and repeatable SQLite bootstrap were verified in disposable databases.

## Plan block reminders

Existing MySQL/MariaDB databases must apply `sql/migration.plan_block_reminders.mysql.sql` once before using block reminders. It adds `reminder_enabled` (0) and `reminder_minutes_before` (5) to `daily_plan_blocks`; existing blocks remain opted out. Do not reapply after columns exist or after initializing the current full schema. SQLite adds each missing column at connection time; `sql/migration.plan_block_reminders.sqlite.sql` is for manual use only when neither column exists. Partially migrated SQLite databases should use automatic bootstrap. Missing MySQL columns disable controls and writes return 503 without breaking block reads. Disposable MariaDB 10.11 migration and SQLite repeatable-bootstrap tests passed.

Recurring-template placements require `sql/migration.daily_plan_recurring_source.mysql.sql` once on existing MySQL/MariaDB databases. SQLite adds the equivalent nullable column automatically; the matching SQLite migration is available for manual use. Existing template-only records are not changed.

To-do item states require `sql/migration.daily_plan_item_todo_status.mysql.sql` once on existing MySQL/MariaDB databases. SQLite applies the equivalent migration automatically. Existing items begin at `pending`; existing per-block states are preserved separately and are not converted into item completion.

Placed Plan block manual state requires `migration.daily_plan_block_status.mysql.sql` once on existing MySQL/MariaDB databases after backup. Existing rows default to `pending`. SQLite adds the same column automatically on connection; `migration.daily_plan_block_status.sqlite.sql` is available for controlled manual migration. Do not reapply either one-time `ALTER TABLE` to a database where the column already exists.

The Calendar daily published-Retrospect prompt uses `calendar_retrospect_prompt_date` in `user_preferences`. New schemas contain it; existing installations may apply `migration.calendar_retrospect_prompt.mysql.sql` or `migration.calendar_retrospect_prompt.sqlite.sql` once. The existing preference repository also adds missing columns when initialized.

Independent Calendar blocks require `migration.calendar_block_snapshots.mysql.sql` once on existing MySQL installations, after a verified backup and before deploying the updated application. SQLite upgrades existing databases at connection time; new databases include the columns. Existing block snapshots preserve the source values at migration time; existing duplicate-looking placement items cannot be identified retroactively and are not changed. The SQLite in-memory and MySQL connection-local temporary-table smoke tests are `tests/calendar_block_migration.php` and `tests/calendar_block_migration_mysql.php`.

Calendar Plan-block usage requires `migration.plan_block_enabled.mysql.sql` once on existing MySQL databases after `migration.plan_block_templates.mysql.sql`. Existing templates default to enabled. SQLite applies the matching migration automatically for existing databases.

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

Before deploying explicit Plan-block completion, back up the database and run `sql/migration.plan_block_explicit_completion.mysql.sql` or `sql/migration.plan_block_explicit_completion.sqlite.sql` once. It marks previously Actual-linked blocks completed to preserve their old interpretation. A marker prevents reapplying it from overwriting later user choices; do not run this backfill on a new database after users start creating explicit states.

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
13. `sql/migration.retrospect_execution_energy.mysql.sql` — requires `retrospect_report_actual_items`; adds the Actual-event Execution Energy snapshot columns required by Retrospect publish and republish.
14. `sql/migration.retrospect_report_milestones.mysql.sql` — requires `retrospect_reports`; creates immutable Goal/Routine completion-news snapshots used by the published report archive.
15. `sql/migration.wellness_entries.mysql.sql` — requires `user` and `calendar_events`; creates Sleep, Meal, Snack, Exercise, and Rest records used by Calendar and Search.
16. `sql/migration.wellness_event_link.mysql.sql` — apply only when migration 15 was already deployed; adds the nullable unique Actual-event link used by schedule-bound Life Records.
17. `sql/migration.daily_plan_estimated_duration.mysql.sql` — requires `daily_plan_items`; adds the optional expected duration used to highlight long time-unspecified Plan items.
18. `sql/migration.execution_energy_zero_notice.mysql.sql` — requires `execution_energy_notices`; expands its milestone constraint from 25/50 to 0/25/50.
19. `sql/migration.daily_plan_blocks.mysql.sql` — requires `daily_plan_items` and `calendar_events`; adds the one-to-many time-block table and nullable Actual block link. Apply its DDL once. Its timed-Plan and Actual-link backfill statements are safe to rerun and preserve `daily_plan_item_id`.

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
- Existing SQLite Execution Energy notices automatically apply `sql/migration.execution_energy_zero_notice.sqlite.sql` when the old 25/50-only milestone constraint is detected.
- Existing SQLite Retrospect Actual snapshots receive missing Execution Energy columns automatically; `sql/migration.retrospect_execution_energy.sqlite.sql` remains available for controlled manual migration.
- Reapplying `sql/schema.sqlite.sql` creates the Retrospect milestone snapshot table; `sql/migration.retrospect_report_milestones.sqlite.sql` is available for controlled manual migration.
- Reapplying `sql/schema.sqlite.sql` creates `wellness_entries`; `sql/migration.wellness_entries.sqlite.sql` is available for controlled manual migration. Existing standalone tables receive `calendar_event_id` and its unique index automatically; `sql/migration.wellness_event_link.sqlite.sql` is the matching controlled migration.
- Reapplying `sql/schema.sqlite.sql` creates `calendar_tag_preferences`; `sql/migration.calendar_tag_preferences.sqlite.sql` is available for controlled manual migration.
- `app/Core/Database.php` automatically adds `daily_plan_items.estimated_duration_minutes`; `sql/migration.daily_plan_estimated_duration.sqlite.sql` is available for controlled manual migration.
- `app/Core/Database.php` automatically adds `daily_plan_blocks` and `calendar_events.daily_plan_block_id`, then backfills one block for each timed legacy Plan item. `sql/migration.daily_plan_blocks.sqlite.sql` is available for controlled manual migration; its data backfill is idempotent.
- The matching `*.sqlite.sql` files remain available for controlled manual migrations.
- Do not delete SQLite compatibility or fallback code even when production uses MySQL; it remains part of local and fallback support.

## Existing Docker Development Database

- The MariaDB `db_data` named volume persists between container rebuilds. Files mounted under `/docker-entrypoint-initdb.d` run only when that volume is first initialized, so rebuilding or restarting containers does not upgrade an existing development schema.
- Do not remove the volume merely to pick up a schema change when development data should be preserved. Apply only the required migration file to the running `db` service.
- PowerShell examples for the Goal focus and Calendar-linked Life Record upgrades:

```powershell
Get-Content -Raw -Encoding utf8 sql/migration.goal_focus.mysql.sql |
  docker compose exec -T db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'

Get-Content -Raw -Encoding utf8 sql/migration.wellness_event_link.mysql.sql |
  docker compose exec -T db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'

Get-Content -Raw -Encoding utf8 sql/migration.daily_plan_estimated_duration.mysql.sql |
  docker compose exec -T db sh -lc 'mariadb -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
```

- Record which migrations were applied. These `ALTER TABLE` migrations are not intended to be rerun.

## Execution Energy 0% milestone

Execution Energy feedback now records 50%, 25%, and 0% remaining-capacity milestones. Existing databases that already contain `execution_energy_notices` must update its check constraint:

- MySQL/MariaDB: `sql/migration.execution_energy_zero_notice.mysql.sql`
- SQLite: `sql/migration.execution_energy_zero_notice.sqlite.sql`

SQLite fallback performs this compatibility migration automatically when the old 25/50-only constraint is detected.
# 회고 루틴 누적 기록 스냅샷

기존 MySQL DB에 `sql/migration.retrospect_routine_feedback.mysql.sql`을 한 번 적용한다. SQLite는 앱이 기존 DB의 누락된 열을 자동 추가하며, 별도 수동 마이그레이션이 필요한 경우 `sql/migration.retrospect_routine_feedback.sqlite.sql`을 사용한다. 두 스크립트는 이미 열이 존재하면 다시 실행하지 않는다. 기존 발행 회고의 데이터를 소급 변경하지 않는다.

## Try와 실제 일정의 명시적 연결

기존 MySQL DB에 `sql/migration.retrospect_try_event_links.mysql.sql`을 적용한다. SQLite는 앱 시작 시 현재 스키마의 테이블을 보강하며, 수동 적용 시 `sql/migration.retrospect_try_event_links.sqlite.sql`을 사용한다. 두 스크립트는 테이블이 이미 있으면 기존 연결을 보존한다.
