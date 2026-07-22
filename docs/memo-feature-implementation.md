# Memo Feature Implementation

## Purpose

Memo is a standalone capture area for thoughts that should not automatically become schedules, routines, goals, or Retrospect content. Users can write from Calendar's quick menu or manage notes from the aside Memo menu.

Retrospect may display active standalone Memo records as read-only context grouped by `created_at` date. This does not link a Memo to a goal or copy it into a Retrospect report snapshot.

Timestamps are stored in UTC. Memo list labels explicitly convert UTC values to `Asia/Seoul`, and daily Retrospect grouping converts the selected Korean day to a UTC half-open range (`00:00` inclusive through the next `00:00` exclusive). Memos written between Korean midnight and 09:00 therefore remain under the correct Korean date. MySQL connections set their session timezone to `+00:00`; SQLite `CURRENT_TIMESTAMP` already uses UTC.

## Current Scope

- Quick memo bottom sheet from Calendar
- Memo page at `/memo`
- Automatic short/long classification
- Content search
- Edit and soft delete
- Trash ordered by latest deletion time
- Restore from trash
- MySQL and SQLite support

## Classification

Classification uses the trimmed memo content length and is recalculated after every edit.

- Short memo: fewer than 300 characters
- Long memo: 300 characters or more
- Whitespace inside the content counts toward the length
- Leading and trailing whitespace is removed before persistence and counting

The Memo page shows `짧은 메모 (n)` and `긴 메모 (n)` tabs. Deleted notes are excluded from both counts.

## Persistence

### `notes`

Important columns:

- `id`
- `user_id`
- `content`
- `deleted_at`
- `created_at`
- `updated_at`

`deleted_at` implements recoverable soft deletion. Active lists sort by latest update time; trash sorts by latest deletion time. All reads and writes are scoped by authenticated `user_id`.

Deleted memos remain restorable for 30 days. Opening the trash permanently removes only the signed-in user's entries whose `deleted_at` is at least 30 days old; the cutoff is calculated in UTC and passed as a prepared value for matching MySQL and SQLite behavior.

For an existing MySQL database, run `sql/migration.add_notes.mysql.sql` once. Existing SQLite databases create the table through the shared schema bootstrap; `sql/migration.add_notes.sqlite.sql` is also provided for manual migration.
See `docs/database-migrations.md` for the recommended deployment order when applying this together with other current migrations.

## Routes

- `GET /memo`: list active short or long notes; accepts `type=short|long` and optional `q`.
- `GET /memo?trash=1`: list deleted notes in deletion-date descending order.
- `POST /memo`: create a memo. Calendar may submit `return_to=calendar` and a selected date.
- `POST /memo/update`: update active memo content.
- `POST /memo/delete`: move an active memo to trash.
- `POST /memo/restore`: restore a deleted memo.
- `POST /memo/trash/empty`: permanently delete every memo currently in the signed-in user's trash.

All POST routes require CSRF verification.

## UI Rules

- Calendar's `+` menu orders quick Memo, untimed schedule creation, and Routine actions above a divider; baseline Plan settings are below it.
- Quick memo opens a keyboard-ready textarea and saves without asking for a title.
- Memo cards use the first lines of content as their preview. Short cards show fewer lines than long cards.
- The trash icon is contextual management UI, not a persistent notification badge.
- Trash cards show their automatic deletion time, and the trash toolbar provides a confirmed `휴지통 비우기` action.
- Memo remains separate from Calendar event `memo` fields and Retrospect KPT text.

## Manual Test Checklist

- Save a memo shorter than 300 trimmed characters and confirm it appears under `짧은 메모`.
- Save or edit a memo to 300 or more trimmed characters and confirm it appears under `긴 메모`.
- Search within each length tab and confirm only matching active notes appear.
- Delete a memo, confirm it disappears from active counts, and verify it appears first in the trash list.
- Restore a memo and confirm it returns to the correct length tab based on its current content.
- Set a trash row's `deleted_at` beyond the 30-day cutoff, open trash, and confirm only that user's expired row is permanently removed.
- Empty the trash, accept the destructive confirmation, and confirm all remaining trash rows are permanently removed.
- From Calendar, use `+ > 메모`, save a quick memo, and confirm the selected calendar date remains open after redirect.
- Write a memo shortly after Korean midnight and confirm it appears under the same Korean date in Retrospect for both MySQL and SQLite.
- Confirm empty and no-search-result messages match the current short, long, or trash context.
