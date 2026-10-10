# Life Record Feature Implementation

## Scope

Calendar Actual-event create and edit sheets optionally record Sleep, Meal, Snack, Exercise, and Rest. The Actual event remains the owner of time, while the linked Life Record provides domain detail for later nutrition, weight-management, and exercise features. This is not a medical record or an automatic Execution Energy judgment.

## Persistence

`wellness_entries` stores `entry_date`, type, a short detail, optional note, and optional 1–5 perceived-state rating. New entries link one-to-one to `calendar_events` through nullable, unique `calendar_event_id`; nullable preserves standalone legacy rows during migration.

- The linked Actual event supplies the start/end time, so the Life Record form does not ask for duplicate time input.
- One Actual event can have zero or one Life Record type. Choosing `기록 안 함` removes its linked detail.
- Soft-deleting an Actual event also removes its linked Life Record; unrelated legacy rows remain untouched.
- Legacy point/range time columns remain nullable for compatibility with records created before event linking.
- Life Record type remains separate from Schedule Tags, preventing category colors from becoming health semantics.
- No type automatically consumes or recovers Execution Energy.

## MVC and routes

- `POST /calendar/event` and `POST /calendar/event/update` validate and synchronize the optional Life Record with the Actual event under the existing CSRF protection.
- Service: `app/Services/WellnessService.php`
- Model: `app/Models/WellnessRepository.php`
- UI: the optional Life Record section inside Actual-event create and edit sheets.

New databases use the current schema files. Databases without the table apply `migration.wellness_entries.*.sql`; databases that already have the standalone table apply `migration.wellness_event_link.*.sql`. SQLite fallback adds the event-link column and unique index automatically.
