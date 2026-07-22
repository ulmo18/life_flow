# Plan Feature Implementation

## Scope

Plan stores reusable daily schedule groups. A user creates a plan group, drags one or more 10-minute blocks onto the day grid, names each block, chooses an Eisenhower importance level, and saves the group.

The current implementation supports:

- Plan group list
- Plan group creation
- Plan group detail/preview
- Versioned plan editing
- Plan group copy
- Plan group soft delete

## Runtime Rule

Plan persistence is enabled only when the configured database driver is `mysql`.

If `DB_CONNECTION=sqlite` or `DB_DRIVER=sqlite`, Plan routes render an unavailable page. SQLite schema definitions are still kept as reference/fallback documentation, but the Plan feature should be used with MySQL.

## Versioning Rule

Plan edits always create a new visible version.

That means:

- changing the plan group name creates a new version
- changing block names creates a new version
- changing block importance creates a new version
- changing block positions creates a new version

This keeps existing Calendar and future Goal references stable.

## Tables

### `plan_groups`

The visible list unit. This stores the named plan bundle shown on `/plan`.

Important columns:

- `id`
- `user_id`
- `source_plan_group_id`
- `version_no`
- `name`
- `deleted_at`
- `created_at`
- `updated_at`

Delete behavior:

`deleted_at` is used for soft deletion. Deleted groups disappear from the Plan list, while historical calendar and retrospect references can remain valid in future work.

Version behavior:

Plan edit is versioned. Editing a group creates a new `plan_groups` row and soft-deletes the previous visible row. Existing Calendar and Goal references should continue to point at the old row.

### `plan_blocks`

The placement unit inside a group. This stores where a reusable plan template appears in the 24-hour grid.

Important columns:

- `id`
- `plan_group_id`
- `plan_template_id`
- `start_index`
- `end_index`
- `sort_order`
- `created_at`
- `updated_at`

Time index rule:

`00:00` is index `0`. Each index is 10 minutes. `24:00` is index `144`. `end_index` is exclusive, so `09:00` to `09:30` is `54` to `57`.

### `plan_templates`

The reusable plan data unit. Multiple `plan_blocks` may reference the same `plan_templates.id`.

Important columns:

- `id`
- `user_id`
- `goal_id`
- `title`
- `importance`
- `deleted_at`
- `created_at`
- `updated_at`

Creation behavior:

When creating or editing a plan group, each block creates its own `plan_templates` row. This lets each block have its own title, importance, and `goal_id` connection.

Copy behavior:

When copying a plan group, `plan_templates` rows are not copied. The copied `plan_blocks` reuse the existing `plan_template_id`, so the copy shares the same reusable plan data.

Goal linkage:

`goal_id` is nullable and connects specific plan template rows to active `goals.id` records. The Plan editor exposes active goals inside the block bottom sheet, and list/detail screens show connected goal labels.

Importance mapping:

- `A`: important and urgent
- `B`: important but not urgent
- `C`: urgent but not important
- `D`: not important and not urgent

## Current Routes

- `GET /plan`: list saved plan groups
- `GET /plan/show?id={id}`: preview saved plan group and block titles
- `GET /plan/new`: open plan creation page
- `GET /plan/edit?id={id}`: open versioned edit page
- `POST /plan`: create a plan group
- `POST /plan/update`: create an edited version and hide the previous visible version
- `POST /plan/copy`: copy a plan group
- `POST /plan/delete`: soft delete a plan group

All POST routes require CSRF verification.

## Current UI Behavior

- The Plan list keeps its page heading available to screen readers but removes the large visible menu-name header so the first plan card or empty state begins near the top of the content area.
- The list page shows each visible plan group with its name, time range, block count, detail button, edit button, copy button, and delete button.
- Plan `version_no` is kept as internal data for versioned editing but is not shown in user-facing Plan or Calendar labels.
- The list page uses a floating `계획 추가` submit button with the same visual treatment as the editor's floating `계획 저장` button.
- Plan list action buttons are intentionally compact so repeated plan cards do not become dominated by controls.
- The detail page shows the saved day grid and a block summary list with block title, time range, importance badge, and template id.
- The detail page does not show an `Add plan` button.
- The add/edit page uses the same 24-row, 6-column day-grid shape as Calendar.
- Calendar and Plan add/edit pages use the shared `public/assets/js/components/time-grid-selection.js` controller.
- Touch keeps native vertical grid scrolling by default. A stationary long press activates range selection, while moving before activation remains a native scroll. A short tap does not create a Plan block.
- In Android WebView, confirmed touch selection uses the same native pull-to-refresh suspension and recovery contract as Calendar; see `docs/android-webview-integration.md`.
- Mouse and pen can still drag-select immediately.
- The block bottom sheet can also connect the block to one active goal.
- Plan block titles in the detail and add/edit grids use `data-ui-tooltip` so long names can be shown near the mouse cursor on hover-capable devices. Detail grid blocks must allow pointer events so short blocks can reveal the same tooltip as editor blocks, but touch devices should not show these hover tooltips.
- Saving and destructive actions use the shared modal popup.
- `Plan Save` is a floating button.
- Copying a plan group redirects back to the plan list, not to the copied plan detail page.
- Plan block backgrounds stay neutral in an organic gray tone. Eisenhower importance colors are applied only inside the circular `A/B/C/D` badge so future tag colors can own the block background.

## Shared UI Layer

The app now has common UI primitives available on every authenticated page:

- modal popup
- bottom sheet

These are rendered once in the shared layout footer and controlled by `window.LifeFlowUI`.

Toast messages now appear near the top of the viewport instead of the bottom.
Authenticated pages offset toast messages below the sticky header so they remain visible. Elements with `data-toast-message` trigger the shared toast after page load.
Success flash messages should be rendered as hidden `data-toast-message` triggers, not visible inline success boxes.
Toast styling follows the app's organic surface pattern: light surface background, earth-tone border/shadow, text color from the shared tokens, and the main Sunset Red only as an action/progress accent.

The shared UI layer also provides hover tooltips for elements with `data-ui-tooltip`. This is used by plan grid blocks to reveal long block names without changing the grid layout. Tooltips are limited to hover-capable fine pointers so mobile touch editing does not show tooltip UI at the same time.

## Editing Policy

Do not mutate existing plan groups in place.

Use versioned editing:

- Read the current visible plan group.
- Create a new `plan_groups` row.
- Create new `plan_templates` and `plan_blocks` for the edited content.
- Soft-delete the previous visible group.
- Redirect to the new detail page.

This keeps future Calendar and Goal references to older plan data stable.

## Future Notes

- Calendar should link one selected `plan_group` to one day.
- If a day already has actual schedule entries linked to a plan and the selected plan group changes, the UI should warn that existing actual-plan links must be cleared and re-linked.
- Deleted plan groups should be displayable later as `Deleted Plan` when historical calendar or retrospect data references them.
- Goal linkage uses `plan_templates.goal_id`, not `plan_groups` or `plan_blocks`.
- If editing history becomes user-facing, add a history page that groups rows by `source_plan_group_id`.
