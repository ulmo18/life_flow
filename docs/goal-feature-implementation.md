# Goal Map Feature Implementation

## Purpose

Goal is the structural starting point from thought to execution. `/goal` prioritizes an automatically arranged Goal Map so users can understand what they want, how goals relate, and what they are focusing on without reading a card list.

Existing Goal, Plan, To-do, Calendar, Routine, notification, and Retrospect relationships remain intact. Goal Map does not store canvas coordinates and is not a free-placement whiteboard.

## Main experience

- A `목표 추가` floating action above the bottom navigator opens a title-only bottom sheet. The created root goal is active but is not automatically focused.
- Quick-created goals are internally stored as active `bucket` goals with no period. Type, dates, status, parent, and behavior reminder can be refined later in the detail panel.
- Every node exposes a compact `＋` action that opens an inline child-title input. Child creation never requires a page or modal transition.
- The tree is automatically arranged. Root goals can form two columns on wider screens; mobile always uses a vertical indentation hierarchy without horizontal overflow.
- Goal depth controls title emphasis. Root goals are strongest and level four or deeper is deliberately quiet.
- Nodes with children support collapse/expand. In `전체 목표`, the first visit starts collapsed except for ancestor paths needed to reveal focused goals. `전부 펼치기` and `전부 접기` apply to the complete tree. Later per-node and global choices are UI preferences stored per Goal mode in browser `localStorage`; layout state is not written to the database.
- A collapsed node shows the total descendant count.
- Node status uses a compact symbol: `◐` active, `✓` completed, and `○` paused/archived. Completed nodes are visually muted.
- Selecting a node opens its details in a right panel on desktop and a bottom sheet on mobile. Desktop content starts at the top and the panel scrolls internally when it exceeds the viewport.
- The panel shows status, period, period progress, linked Plan count, linked daily To-do count, and linked Routine count. Existing detailed edit and soft-delete actions remain available inside an expandable section.

## Map modes

- `GET /goal` or `GET /goal?mode=focus` is the default and shows only goals explicitly selected for `지금 집중`.
- `GET /goal?mode=all` shows every non-deleted goal and preserves its hierarchy.
- The all-goals tree calculates whether each node has a focused descendant. This display-only flag opens only the paths needed to reveal focused goals on the initial visit and is not persisted to the database.
- A user can focus at most five active goals. Paused, completed, archived, deleted goals are excluded; changing an active focused goal to another status clears its focus flag.
- Moving a Goal into completed status records its completion time once. Later edits that keep it completed preserve that timestamp so date-based published Retrospect feedback remains stable.
- The detail panel owns `지금 집중에 추가/해제`. A focused child whose parent is not focused is promoted to a visual root only in this filtered view.
- Structure dragging is available only in `전체 목표`. It is disabled in the filtered focus view so a visually promoted child cannot accidentally lose its hidden parent.
- Focus persistence uses `goals.is_focused` and `goals.focused_at`; the service enforces the five-goal limit for both database drivers.

## Structure and ordering

- `goals.parent_goal_id` stores hierarchy.
- `goals.sort_order` stores sibling order under the same parent. New goals append to their parent's siblings.
- Desktop drag handles support two operations:
  - drop before a sibling to reorder;
  - drop on another node to append as its child.
- Dropping into an end target appends at that hierarchy level.
- The server validates ownership and cycles before moving a goal and resequences the affected target siblings in a transaction.
- Mobile hides the unreliable native HTML drag handle. Parent changes remain available in the detail sheet; the visual hierarchy itself stays vertical and touch-friendly.

## Keyboard behavior

- Enter submits root and inline child creation forms.
- Escape closes the open inline child form or detail layer.
- Tab and Shift+Tab retain normal browser focus navigation. They are not overridden for hierarchy changes because doing so would harm keyboard and assistive-technology navigation.
- Delete and Backspace are not global shortcuts. Deletion remains a confirmed action in the detail panel.

## Routes

- `GET /goal?mode=all|focus`: render Goal Map.
- `POST /goal`: create a root or child goal. `Accept: application/json` returns a compact result for inline creation.
- `POST /goal/update`: update title, type, parent, status, period, and behavior reminder.
- `POST /goal/move`: reorder or reparent a goal; returns JSON.
- `POST /goal/focus`: add or remove one active goal from the focused set.
- `POST /goal/delete`: soft-delete a goal after confirmation.

All write routes require CSRF validation.

## MVC structure

- Controller: `app/Controllers/GoalController.php`
- Service: `app/Services/GoalService.php`
- Model: `app/Models/GoalRepository.php`
- View: `app/Views/pages/goal/index.php`
- CSS: `public/assets/css/pages/goal.css`
- JavaScript: `public/assets/js/pages/goal.js`

Controllers coordinate HTTP responses, the service owns validation/tree formatting/cycle policy, and the repository owns prepared SQL and movement transactions.

## Data and compatibility

Existing MySQL databases apply `sql/migration.goal_focus.mysql.sql`. SQLite fallback adds missing focus columns during connection startup; `sql/migration.goal_focus.sqlite.sql` remains available for controlled upgrades. The schemas contain:

- `parent_goal_id`
- `sort_order`
- `goal_type`
- `status`
- `is_focused` and `focused_at`
- period and behavior-reminder fields

Allowed goal types and statuses remain unchanged for compatibility. Goal Map does not force them during quick creation but detailed editing retains the existing options.

When a Plan template is copied into Calendar, its `goal_id` remains copied into `daily_plan_items`. Goal details count those daily items as connected To-dos. Plan and Routine selectors continue to offer active goals only.

Deleting a goal keeps the existing safety behavior: direct children become roots, and connected Plan/Routine source links are cleared rather than deleting execution records.

## Verification scenarios

1. Create `세계여행` with the root title input.
2. Add `일본` from `세계여행`'s inline `＋` action.
3. Add `오사카` below `일본` and verify three-level rendering.
4. Collapse and expand `세계여행`; reload and verify the browser remembers the state.
5. Drag sibling goals to reorder them and reload.
6. Drag a goal onto a different parent and reload.
7. Select a goal and verify the desktop right panel/mobile bottom sheet.
8. Switch between all goals and current focus.
9. Verify the mobile hierarchy has no horizontal page overflow and can create children.
10. Verify hierarchy and order persist in both MySQL and SQLite.
