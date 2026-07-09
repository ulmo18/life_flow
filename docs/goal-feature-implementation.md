# Goal Feature Implementation

## Purpose
Goal helps users live intentionally by connecting long-term outcomes to smaller plans and routines.

The feature supports both lightweight goals and hierarchical breakdowns:
- Bucket list
- Yearly goal
- Half-year goal
- Quarterly goal
- Monthly goal

Users do not have to start from a bucket list. A single monthly goal is valid, and larger goals can optionally be split into smaller child goals.

## Current Scope
Implemented in this phase:
- Goal list page at `/goal`
- Goal create, update, and soft delete
- Bottom-sheet create/edit UI
- Optional parent goal connection
- Goal type selection as radio chips
- Status and period editing from the update sheet
- Automatic period defaults for non-bucket goals
- Behavior reminder memo
- Linked Plan and Routine lists on goal cards
- Goal selection inside Plan and Routine forms
- MySQL and SQLite schema support

Not implemented yet:
- Retrospect goal progress metrics
- Calendar goal badges or filters

## Routes
- `GET /goal`: shows the goal list.
- `POST /goal`: creates a goal.
- `POST /goal/update`: updates a goal.
- `POST /goal/delete`: soft-deletes a goal.

All POST routes require CSRF verification.

## MVC Structure
- Controller: `app/Controllers/GoalController.php`
- Service: `app/Services/GoalService.php`
- Model: `app/Models/GoalRepository.php`
- View: `app/Views/pages/goal/index.php`
- CSS: `public/assets/css/pages/goal.css`
- JS: `public/assets/js/pages/goal.js`

Controllers only handle request and response flow. Validation and formatting live in `GoalService`. All database queries live in `GoalRepository`.

## Database
### `goals`
Stores the primary goal records.

Important columns:
- `user_id`
- `parent_goal_id`
- `goal_type`
- `title`
- `behavior_when`
- `behavior_where`
- `behavior_how`
- `period_start_date`
- `period_end_date`
- `status`
- `sort_order`
- `completed_at`
- `deleted_at`

Allowed `goal_type` values:
- `bucket`
- `yearly`
- `half_year`
- `quarterly`
- `monthly`

Allowed `status` values:
- `active`
- `completed`
- `paused`
- `archived`

Bucket-list goals are stored without period dates. Non-bucket goals default to the creation date as `period_start_date`; `period_end_date` is calculated from the goal type when the user does not provide dates.

Default period calculation:
- `yearly`: start date + 1 year
- `half_year`: start date + 6 months
- `quarterly`: start date + 3 months
- `monthly`: start date + 1 month

`behavior_how` is currently used as the single behavior reminder memo. The older `behavior_when` and `behavior_where` columns are kept nullable for compatibility.

### Goal Hierarchy
Hierarchy is optional and uses `goals.parent_goal_id`.

The service prevents direct self-linking and descendant-to-parent cycles. When a parent goal is deleted, child goals are not deleted; their `parent_goal_id` is cleared.

### Plan Linkage
`plan_templates.goal_id` connects plan blocks to active goals.

Plan blocks connect through `plan_templates.goal_id`, not through `plan_groups` or `plan_blocks`. The Plan editor only offers active goals in the goal select.

### Routine Linkage
`routines.goal_id` connects routines to active goals.

Routine creation/editing only offers active goals in the goal select.

## SQLite Compatibility
SQLite schema support is maintained in `sql/schema.sqlite.sql`.

For existing SQLite databases, `app/Core/Database.php` ensures the nullable `routines.goal_id` column exists before running shared schema objects, so the new routine goal index can be created safely.

## UX Notes
- Goal creation/editing uses a bottom sheet to match existing Routine and Retrospect interaction patterns.
- The intro card explains that users can start with any goal depth.
- Goal creation intentionally keeps the form light: title, type, parent goal, and behavior reminder.
- Goal editing exposes status and period controls.
- Goal cards show type, optional parent goal in an arrow path, status, period, behavior reminder, linked plans, and linked routines.
- Bucket-list goals hide effective period data by storing null period dates.

## Future Work
- Add Retrospect goal feedback based on linked plan blocks, actual calendar events, and routine completion.
- Consider goal-level progress views after plan/routine link data exists.
