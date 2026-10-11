# Execution Energy Feature Implementation

## Purpose

Execution Energy is an optional self-management estimate for pacing a day. It is not a medical measurement, diagnosis, or health recommendation. It translates saved Actual Calendar events and recorded recovery into an easy-to-read capacity signal.

## User experience

- The aside's **Execution Energy Management** page manages the next-day baseline and explains event-level input. It intentionally has no current-capacity card, health/reference-state inputs, or reusable-group editor.
- A 52px glass-liquid orb is presented as an independent HUD on the left of the four-item bottom navigator. It links to daily use history rather than Management, does not occupy a navigation slot, and stays below the floating-action area. Its rounded selection backing is suppressed; the visible text label is omitted and desktop exposes `실행력 사용 이력` as a hover/focus tooltip.
- The orb restores the signed-in user's same-day value from session storage before requesting fresh status. Initial hydration does not animate from zero; only a real same-day value change after restoration uses the liquid-level transition. A missing cache keeps the liquid hidden until the current status arrives, and a different date invalidates the cached value. Liquid waves, bubbles, and the subtle glass-orb breathing effect remain active after hydration independently of the level transition, except when the operating system requests reduced motion.
- Today's Calendar has no execution-energy summary or manual correction at its top. The `+` menu includes `오늘의 컨디션` only when today is selected. Its radio-choice sheet records sleep, fatigue, mood, stress, and optional perceived burden.
- Actual-event create and edit forms present direction in the semantic order `− 감소 / 0 유지 / + 증가`. Decrease/increase reveals signed choices `±10`, `±25`, `±50`, `±75`, `±100`, and custom input; custom values remain positive five-point units from 5 through 1,000 while the selected direction supplies the fixed sign. No-change hides the amount controls. Saving replaces the event snapshot rather than adding another transaction.
- Calendar summaries and Retrospect rows share one signed visual language: coral `−n` means use/decrease, green `+n` means recovery/increase, and neutral `0` means no change. Symbols and accessible labels always accompany color. The Calendar helper sentence updates with the selected direction and amount.
- A projected post-save capacity is intentionally not calculated in the form. Editing an event would have to remove its already-applied effect before applying the new value, so a client-only preview could disagree with the existing server calculation.
- New Actual events and Actual events created by To-do completion default to a 25-point decrease.
- Retrospect presents the saved condition choices, baseline adjustment, starting capacity, daily consume/recover totals, and one actionable sentence. Current capacity stays in the orb/history and Retrospect never changes saved values automatically.

## Calculation rules

- New users start at **500**. Baselines and custom event values use five-point units from 5 through 1,000. Configurable baseline suggestions are 300, 400, 500, 600, and 700, with manual entry also available.
- A daily row snapshots the baseline before any condition or event activity. Changing the management baseline is effective from the following day and never silently rewrites an existing daily snapshot.
- Morning sleep, fatigue, mood, and stress use five radio-selectable states ordered from unfavorable to favorable. Each contributes -50, -25, 0, +25, or +50; fatigue and stress labels already reverse their wording, so calculation does not reverse their numeric state again. Resaving replaces the same day's contribution.
- Perceived burden can be recorded as lower than expected, similar, or higher than expected without automatically changing event values.
- An Actual event decreases, increases, or does not change capacity according to its saved event-level setting. Missing settings on historical events are treated as no change and are not retroactively charged.
- Capacity cannot exceed the daily starting capacity. Excess recovery is tracked as recovery overflow, consumption below zero is tracked separately as over-consumption, and displayed capacity cannot drop below zero.
- Plan items do not consume current capacity. Each unlinked Plan item projects a 25-point decrease to calculate **predicted remaining**.
- Manual capacity correction is not exposed as a user-facing action.
- Remaining-capacity milestone feedback is positive and non-blocking: 50% encourages continuing planned completion, 25% recognizes progress and suggests prioritizing the final tasks, and 0% combines completion encouragement with recovery guidance. Each threshold is recorded once per user/day, and a jump across several thresholds shows only the most urgent message.

## Data model

- `execution_energy_profiles`: user baseline, next effective date, and legacy reference columns retained for migration compatibility. Reference-state values are not editable or used in capacity calculations.
- `execution_energy_groups`: retained for legacy snapshots and migration compatibility; no current UI writes it.
- `execution_energy_days`: per-user/day baseline snapshot, morning-check state, and optional perceived-burden feedback.
- `execution_energy_event_settings`: Actual-event direction/value snapshot. Legacy group snapshot columns remain nullable for history compatibility.
- `execution_energy_adjustments`: retained migration-compatible historical correction records; no current user-facing route creates them.
- `execution_energy_notices`: per-user/day records for the 50%, 25%, and 0% supportive messages.

All tables exist in both primary schema files. Existing databases must apply one matching migration before application deployment:

- MySQL/MariaDB: `sql/migration.add_execution_energy.mysql.sql`
- SQLite: `sql/migration.add_execution_energy.sqlite.sql` (or the SQLite compatibility upgrade on first connection)

If the base migration was already applied before `burden_feedback` was introduced, apply `sql/migration.add_execution_energy_feedback.mysql.sql`. SQLite adds the missing column automatically; its matching SQL file is available for controlled upgrades.

Existing installations must also allow the 0% milestone in `execution_energy_notices`:

- MySQL/MariaDB: `sql/migration.execution_energy_zero_notice.mysql.sql`
- SQLite: `sql/migration.execution_energy_zero_notice.sqlite.sql` (also applied by the SQLite compatibility check)

## Routes

- `GET /execution-energy`: management page.
- `GET /execution-energy/history?date=YYYY-MM-DD`: condition-derived starting capacity and time-ordered consume/recover history.
- `GET /execution-energy/status`: compact current-day JSON for the Execution Energy HUD.
- `POST /execution-energy/profile`: save next-day baseline.
- `POST /execution-energy/morning`: save today's Calendar condition sheet.

All write routes require CSRF validation. `ExecutionEnergyController` only coordinates requests; `ExecutionEnergyService` owns calculation and validation, and `ExecutionEnergyRepository` owns persistence.
