# Execution Energy Feature Implementation

## Purpose

Execution Energy is an optional self-management estimate for pacing a day. It is not a medical measurement, diagnosis, or health recommendation. It translates saved Actual Calendar events and recorded recovery into an easy-to-read capacity signal.

## User experience

- The aside's **Execution Energy Management** page manages the next-day baseline and reusable activity groups. It intentionally has no current-capacity card or health/reference-state inputs. Groups use a compact editable table.
- The center bottom-navigation item is a glass-liquid orb. It links to the daily use history rather than Management. Its rounded navigation-selection backing is suppressed; mobile hides the text label and desktop exposes `실행력 사용 이력` as a hover/focus tooltip.
- The orb restores the signed-in user's same-day value from session storage before requesting fresh status. Initial hydration does not animate from zero; only a real same-day value change after restoration uses the liquid-level transition. A missing cache keeps the liquid hidden until the current status arrives, and a different date invalidates the cached value. Liquid waves, bubbles, and the subtle glass-orb breathing effect remain active after hydration independently of the level transition, except when the operating system requests reduced motion.
- Today's Calendar has no execution-energy summary or manual correction at its top. The `+` menu includes `오늘의 컨디션` only when today is selected. Its radio-choice sheet records sleep, fatigue, mood, stress, and optional perceived burden.
- Actual-event create and edit forms choose a group or a per-event consume/recover override. Saving applies a snapshot to the day; ordinary title, tag, or memo edits preserve the existing snapshot until an energy field is changed.
- Completing a To-do creates the linked Actual event with the active default-group snapshot.
- Retrospect presents the saved condition choices, baseline adjustment, starting capacity, daily consume/recover totals, and one actionable sentence. Current capacity stays in the orb/history and Retrospect never changes saved values automatically.

## Calculation rules

- New users start at **500**. Baselines and group values use five-point units from 5 through 1,000. Configurable baseline suggestions are 300, 400, 500, 600, and 700, with manual entry also available.
- A daily row snapshots the baseline before any condition or event activity. Changing the management baseline is effective from the following day and never silently rewrites an existing daily snapshot.
- Morning sleep, fatigue, mood, and stress use five radio-selectable states ordered from unfavorable to favorable. Each contributes -50, -25, 0, +25, or +50; fatigue and stress labels already reverse their wording, so calculation does not reverse their numeric state again. Resaving replaces the same day's contribution.
- Perceived burden can be recorded as lower than expected, similar, or higher than expected without automatically changing group values.
- An Actual event consumes or recovers its saved snapshot. Groups are only defaults; editing a group never rewrites a completed event.
- Capacity cannot exceed the daily starting capacity. Excess recovery is tracked as recovery overflow, consumption below zero is tracked separately as over-consumption, and displayed capacity cannot drop below zero.
- Plan items do not consume current capacity. Unlinked Plan items are projected using the active default group's direction and value to calculate **predicted remaining**.
- Manual capacity correction is not exposed as a user-facing action.

## Default groups

The first visit seeds editable user-owned groups:

- Light activity: consume 25
- Standard activity: consume 50 (default)
- Focused / demanding activity: consume 100
- High-demand activity: consume 150
- Short rest: recover 25
- Full rest: recover 50

## Data model

- `execution_energy_profiles`: user baseline, next effective date, and legacy reference columns retained for migration compatibility. Reference-state values are not editable or used in capacity calculations.
- `execution_energy_groups`: reusable consume/recover defaults.
- `execution_energy_days`: per-user/day baseline snapshot, morning-check state, and optional perceived-burden feedback.
- `execution_energy_event_settings`: Actual-event energy snapshot and optional override.
- `execution_energy_adjustments`: retained migration-compatible historical correction records; no current user-facing route creates them.
- `execution_energy_notices`: retained threshold-notice records; no current Calendar-top notice is shown.

All tables exist in both primary schema files. Existing databases must apply one matching migration before application deployment:

- MySQL/MariaDB: `sql/migration.add_execution_energy.mysql.sql`
- SQLite: `sql/migration.add_execution_energy.sqlite.sql` (or the SQLite compatibility upgrade on first connection)

If the base migration was already applied before `burden_feedback` was introduced, apply `sql/migration.add_execution_energy_feedback.mysql.sql`. SQLite adds the missing column automatically; its matching SQL file is available for controlled upgrades.

## Routes

- `GET /execution-energy`: management page.
- `GET /execution-energy/history?date=YYYY-MM-DD`: condition-derived starting capacity and time-ordered consume/recover history.
- `GET /execution-energy/status`: compact current-day JSON for the navigation orb.
- `POST /execution-energy/profile`: save next-day baseline.
- `POST /execution-energy/group`: create a group.
- `POST /execution-energy/group/update`: edit a group.
- `POST /execution-energy/morning`: save today's Calendar condition sheet.

All write routes require CSRF validation. `ExecutionEnergyController` only coordinates requests; `ExecutionEnergyService` owns calculation and validation, and `ExecutionEnergyRepository` owns persistence.
