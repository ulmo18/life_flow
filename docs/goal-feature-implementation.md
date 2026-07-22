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
- Status filter chips on the list page for active, completed, archived, and paused goals
- Goal-type filter chips for bucket, yearly, half-year, quarterly, and monthly goals
- Card and tree view switch on the list page
- Active-goal tree view that shows each goal type and title on one tappable line
- Automatic period defaults for non-bucket goals
- Period progress indicators for goals with start and end dates
- Behavior reminder memo
- Compact goal cards with one collapsible details area for behavior notes and linked data
- Bottom floating Goal add button
- Goal usage guide opened from an exclamation icon beside the page title
- Goal selection inside Plan and Routine forms
- MySQL and SQLite schema support

Not implemented yet:
- Retrospect goal progress metrics
- Calendar goal badges or filters

## Routes
- `GET /goal`: shows the goal list.
- `GET /goal?view=tree`: shows the active-goal tree view.
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
- The title-side exclamation icon opens a guide bottom sheet explaining that users can start at any goal depth.
- Goal creation intentionally keeps the form light: title, type, parent goal, and behavior reminder.
- Goal editing exposes status and period controls.
- The default card view keeps status and goal-type filters. Filter links preserve both selections.
- The tree view always focuses on active goals only so users can scan the current hierarchy without completed, archived, or paused goals competing for attention.
- Tapping a one-line tree node opens the existing edit sheet; period, progress, linkage counts, and edit labels are intentionally omitted from the tree card.
- Retrospect owns goal execution feedback by aggregating linked Plan templates, matching actual Calendar events, and linked Routine completion records. It does not automatically mark the goal complete.
- Goal cards show type, optional parent goal in an arrow path, status, period, and period progress in a compact mobile layout.
- Behavior reminders and linked plans/routines are collapsed into one details area. Empty linked Plan or Routine sections are not rendered.
- The usage-guide icon is visually smaller than the page title while retaining an expanded touch target.
- The Goal add action is fixed near the bottom of the viewport to match the Plan list add interaction.
- Bucket-list goals hide effective period data by storing null period dates.

## Future Work
- Add Retrospect goal feedback based on linked plan blocks, actual calendar events, and routine completion.
- Consider goal-level progress views after plan/routine link data exists.
- Goal deadline notification payloads are built by `NotificationService`; Settings owns the on/off and reminder time.
