# Plan Feature Implementation

Plan groups and their blocks are reusable templates. Editing a Plan updates the template in place. Calendar connections are date-specific copies, so an edit never rewrites already-connected days or published Retrospect snapshots.

## Scope

Plan stores reusable daily schedule groups. A user creates a plan group, drags one or more 10-minute blocks onto the day grid, names each block, chooses an Eisenhower importance level, and saves the group.

The current implementation supports:

- Plan group list
- Plan group creation
- Plan group detail/preview
- In-place plan template editing
- Plan group copy
- Plan group soft delete

## Runtime Rule

Plan persistence is supported on MySQL/MariaDB and SQLite. Repository statements stay driver-compatible and both explicit schema files contain the Plan template and Calendar daily-copy tables.

## Editing Rule

Plan is a template list, so edits update the selected template group in place. Calendar stability comes from copying the template into `daily_plans` and `daily_plan_items` when it is connected; already-copied days never read mutable template values again.

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

`source_plan_group_id` and `version_no` remain as legacy compatibility columns but new edits reset them to the single current template identity.

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

When copying a plan group, each block receives a new `plan_templates` row so later edits to either template group stay independent.

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
- `GET /plan/edit?id={id}`: open the template edit page
- `POST /plan`: create a plan group
- `POST /plan/update`: update the selected template group in place
- `POST /plan/copy`: copy a plan group
- `POST /plan/delete`: soft delete a plan group

All POST routes require CSRF verification.

## Current UI Behavior

- The Plan list keeps its page heading available to screen readers but removes the large visible menu-name header so the first plan card or empty state begins near the top of the content area.
- The list page shows each visible plan group with its name, time range, block count, detail button, edit button, copy button, and delete button.
- Legacy Plan version fields are not shown in user-facing Plan or Calendar labels.
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

- Verify ownership of the current visible template group.
- Replace that group's template blocks in one transaction.
- Archive replaced `plan_templates` rows and create independent rows for the new block set.
- Redirect to the same group detail page.
- Never update `daily_plans` or `daily_plan_items` from a Plan edit.

## Future Notes

- Calendar copies one selected `plan_group` into one `daily_plan` per day.
- Replacing a daily Plan warns that the existing copy will be replaced and linked actual events will be detached.
- Deleted template groups do not affect existing daily copies.
- Goal linkage uses `plan_templates.goal_id`, not `plan_groups` or `plan_blocks`.
