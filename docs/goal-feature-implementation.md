# Goal Map Feature Implementation

## Purpose

Goal is the structural starting point from thought to execution. `/goal` prioritizes an automatically arranged Goal Map so users can understand what they want, how goals relate, and what they are focusing on without reading a card list.

Existing Goal, Plan, To-do, Calendar, Routine, notification, and Retrospect relationships remain intact. Goal Map does not store canvas coordinates and is not a free-placement whiteboard.

## Main experience

- The `목표 회고` view at `/goal/review` lists completed Goals in a compact To-do-style table using the shared list surface and empty state; Goal detail linked-record tables use those same primitives. Selecting a completed Goal opens a sheet with linked Plan blocks, Actuals and Routines, recorded activity totals and a date/time-ordered timeline. Only explicit Plan-block links are described as such; item-only Actual links remain unspecified, and Routine logs are independent Goal-linked records. Plan status labels describe the current saved status, not a reconstructed historical status. Deleted Actuals and deleted Routines are excluded. The previous `/retrospect?view=goals` URL redirects to this view.
- Each Goal tab shows its name and an exclamation control that opens relevant usage guidance, including on empty lists. Goal pages keep accessible screen-reader headings without a large introductory heading above the tabs.
- A `목표 추가` floating action above the bottom navigator opens a title-only bottom sheet. On mobile it matches the daily Routine add button's centered width, position and styling. The created root goal is active but is not automatically focused.
- Quick-created goals are internally stored as active `bucket` goals with no period. Type, dates, status, parent, and behavior reminder can be refined later in the detail panel.
- Every node exposes a compact `＋` action that opens an inline child-title input. Child creation never requires a page or modal transition.
- The tree is automatically arranged. Root goals can form two columns on wider screens; mobile always uses a vertical indentation hierarchy without horizontal overflow.
- Goal depth controls title emphasis. Root goals are strongest and level four or deeper is deliberately quiet.
- Nodes with children support collapse/expand. In `전체 목표`, the first visit starts collapsed except for ancestor paths needed to reveal focused goals. The `전체 펼치기` toggle expands or collapses the complete tree; it is on only when every branch is expanded. Later per-node and global choices are UI preferences stored per Goal mode in browser `localStorage`; layout state is not written to the database.
- A collapsed node shows the total descendant count.
- Each node has a focus star: an outline when inactive and a filled red star when focused. Only active Goals can be focused; the existing five-goal limit remains. Paused names are struck through, archived names use muted text, and completed Goals use a subdued `완료` label.
- A Goal's behavior reminder appears as a softly shaded, wrapping speech-bubble card beneath its name in the map and in its detail sheet. The detail header mirrors the map's focus star and paused, archived or completed title treatment.
- Selecting a node opens its details in a right panel on desktop and a bottom sheet on mobile; the node's three-dot button opens a separate edit sheet. Desktop content starts at the top and the panel scrolls internally when it exceeds the viewport.
- The detail sheet shows status, period and progress plus accessible tabs containing tables of linked dated Plan blocks, daily To-dos and Routines. Plan block snapshots remain visible after source To-do deletion. The edit sheet aligns status and Goal-type radio choices opposite their labels, places `상위 목표 연결` after Goal type, and uses the shared date picker for the two dates on one row. Delete is on the left and Save on the right at equal width. Delete requires a 1.5-second hold with progress; early release shows a toast. Closing, cancellation or leaving the button cancels the hold. Save, delete and focus operations return to the current map tab.
- Goal edit labels and their controls follow the shared input typography and 8px field spacing; status and type choices keep their horizontal layout.
- The Goal edit header uses the shared input-sheet title and transparent close control. Long detail values wrap inside the available column without widening the panel; read-only detail titles retain their existing typography.
- Goal quick creation reuses the standard 20px input-sheet surface and shared shadow, retaining its 620px width cap and 80dvh height limit. Its sticky header aligns with that padding; detail and edit panels remain separate.
- Empty focused/all Goal maps use the shared empty-state surface, 16px padding and standard text sizes. The existing first-goal and all-goals actions keep their behavior.
- Deep Goal trees scroll horizontally inside the labelled, keyboard-focusable map region. Rows retain a minimum 280px width so hierarchy depth does not make mobile browsers shrink the entire page. Goal details and editing move focus inside their panel; Tab/Shift+Tab stay within the visible modal, and closing restores the original map trigger. Quick creation restores its own trigger. Native date dialogs and shared confirmations retain priority over the Goal keyboard handler.

## Map modes

- `GET /goal` or `GET /goal?mode=focus` is the default and shows only goals explicitly selected for `지금 집중`.
- `GET /goal?mode=all` shows every non-deleted goal and preserves its hierarchy.
- The all-goals tree calculates whether each node has a focused descendant. This display-only flag opens only the paths needed to reveal focused goals on the initial visit and is not persisted to the database.
- A user can focus at most five active goals. Paused, completed, archived, deleted goals are excluded; changing an active focused goal to another status clears its focus flag.
- Moving a Goal into completed status records its completion time once. Later edits that keep it completed preserve that timestamp so date-based published Retrospect feedback remains stable.
- The node star in `전체 목표` and the detail panel can add or remove focus. In `지금 집중` the list hides the star and unused left placeholder, while retaining an expand control for nodes with children. A focused child whose parent is not focused is promoted to a visual root only in this filtered view.
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
- Mobile and pen input use pointer dragging from the same visible handle: drop near a row's top to move before it, or over the row to make it a child. Cancellation leaves the hierarchy unchanged; the server still validates ownership and cycles. Parent changes also remain available in the edit sheet.

## Keyboard behavior

- Enter submits root and inline child creation forms.
- Escape closes the open inline child form or detail layer.
- Tab and Shift+Tab retain normal browser focus navigation in the map and never change the hierarchy. Visible Goal dialogs contain focus until closed; this does not apply when a nested date picker or shared confirmation is active.
- Delete and Backspace are not global shortcuts. The edit sheet's delete control supports a 1.5-second pointer or Space/Enter hold.

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

- Goal list reminders span the card beneath its controls. Editing opens from the detail sheet footer; its star toggles focus for active Goals.

- Focus-list titles align with the reminder inset; expand controls remain available on the right. The detail footer edit action is labeled 목표 수정.
