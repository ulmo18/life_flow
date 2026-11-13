<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$selectedMode = (string) ($selectedMode ?? 'focus');
$today = date('Y-m-d');

$renderQuickForm = static function (?int $parentGoalId, string $placeholder, string $class = '') use ($csrfToken, $selectedMode): void {
    ?><form class="goal-quick-form <?= e($class) ?>" method="post" action="/goal" data-goal-quick-form hidden>
        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
        <input type="hidden" name="goal_type" value="bucket">
        <input type="hidden" name="status" value="active">
        <input type="hidden" name="parent_goal_id" value="<?= $parentGoalId === null ? '' : e((string) $parentGoalId) ?>">
        <input type="hidden" name="mode" value="<?= e($selectedMode) ?>">
        <label><span class="sr-only">목표명</span><input class="input" name="title" type="text" maxlength="80" placeholder="<?= e($placeholder) ?>" autocomplete="off" required data-goal-quick-input></label>
        <button class="btn btn-primary" type="submit">추가</button>
        <button class="btn btn-ghost" type="button" data-goal-inline-cancel>취소</button>
    </form><?php
};

$renderGoalBranch = static function (array $nodes, int $depth = 0, ?int $parentId = null) use (&$renderGoalBranch, $renderQuickForm, $selectedMode, $csrfToken): void {
    ?><ol class="goal-map-branch" data-goal-branch data-parent-id="<?= $parentId === null ? '' : e((string) $parentId) ?>"><?php
    foreach ($nodes as $node):
        $goalId = (int) $node['id'];
        $children = $node['children'] ?? [];
        $status = (string) $node['status'];
        ?><li class="goal-map-item is-<?= e($status) ?>" data-goal-node data-goal-id="<?= e((string) $goalId) ?>" data-parent-id="<?= $parentId === null ? '' : e((string) $parentId) ?>" data-focus-path="<?= !empty($node['hasFocusedDescendant']) ? '1' : '0' ?>" style="--goal-depth:<?= e((string) min($depth, 5)) ?>">
            <div class="goal-drop-before" data-goal-drop-before="<?= e((string) $goalId) ?>" aria-hidden="true"></div>
            <div class="goal-node-row <?= !empty($children) ? 'has-children' : '' ?>">
                <?php if (!empty($children)): ?><button class="goal-collapse" type="button" data-goal-collapse aria-expanded="true" aria-label="<?= e((string) $node['title']) ?> 하위 목표 접기">▾</button><?php elseif ($selectedMode === 'all'): ?><span class="goal-collapse-placeholder" aria-hidden="true"></span><?php endif; ?>
                <?php if ($selectedMode === 'all'): ?>
                <form class="goal-node-focus" method="post" action="/goal/focus"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"><input type="hidden" name="mode" value="<?= e($selectedMode) ?>"><input type="hidden" name="focused" value="<?= !empty($node['isFocused']) ? '0' : '1' ?>"><button type="submit" class="goal-focus-star" aria-pressed="<?= !empty($node['isFocused']) ? 'true' : 'false' ?>" aria-label="<?= e((string) $node['title']) ?> <?= !empty($node['isFocused']) ? '집중에서 해제' : '집중에 추가' ?>" <?= $status !== 'active' ? 'disabled title="진행 중인 목표만 집중할 수 있어요"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9z"/></svg></button></form>
                <?php endif; ?>
                <button class="goal-node-main" type="button" data-goal-open="detail-<?= e((string) $goalId) ?>">
                    <span class="goal-node-copy"><span class="goal-node-title"><strong><?= e((string) $node['title']) ?></strong><?php if ($status === 'completed'): ?><span class="goal-completed-label">완료</span><?php endif; ?></span></span>
                    <?php if (!empty($node['descendantCount'])): ?><small><?= e((string) $node['descendantCount']) ?></small><?php endif; ?>
                </button>
                <div class="goal-node-actions">
                    <?php if ($selectedMode === 'all'): ?><button type="button" data-goal-child-add="<?= e((string) $goalId) ?>" aria-label="<?= e((string) $node['title']) ?> 하위 목표 추가">＋</button><?php endif; ?>
                    <?php if ($selectedMode === 'all'): ?><button type="button" class="goal-drag-handle" draggable="true" data-goal-drag="<?= e((string) $goalId) ?>" aria-label="<?= e((string) $node['title']) ?> 이동">⋮⋮</button><?php endif; ?>
                </div>
                <?php if (trim((string) ($node['behaviorNote'] ?? '')) !== ''): ?><p class="goal-node-note"><?= e((string) $node['behaviorNote']) ?></p><?php endif; ?>
            </div>
            <?php $renderQuickForm($goalId, '하위 목표를 입력하세요', 'goal-inline-form'); ?>
            <?php if (!empty($children)): ?><div class="goal-children" data-goal-children><?php $renderGoalBranch($children, $depth + 1, $goalId); ?></div><?php endif; ?>
        </li><?php
    endforeach;
    ?><li class="goal-drop-end" data-goal-drop-parent="<?= $parentId === null ? '' : e((string) $parentId) ?>">여기로 이동</li></ol><?php
};
?>

<main class="page goal-page" data-goal-page data-mode="<?= e($selectedMode) ?>">
    <h1 class="sr-only">목표</h1>

    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <div class="ui-menu-context">
        <nav class="ui-segmented-tabs goal-mode-switch" aria-label="목표 범위">
            <a href="/goal" <?= $selectedMode === 'focus' ? 'aria-current="page"' : '' ?>>지금 집중</a>
            <a href="/goal?mode=all" <?= $selectedMode === 'all' ? 'aria-current="page"' : '' ?>>전체 목표</a>
            <a href="/goal/review">목표 회고</a>
        </nav>
    </div>
    <?php $goalTabTitle = $selectedMode === 'all' ? '전체 목표' : '지금 집중'; $goalTabHelp = $selectedMode === 'all' ? '목표 이름을 눌러 상세를 보고, +로 하위 목표를 추가하세요. 이동 손잡이를 끌어 순서를 바꾸거나 다른 목표에 연결할 수 있어요.' : '집중 중인 목표를 모아 보여줍니다. 목표를 눌러 상세를 보거나 집중을 해제할 수 있어요.'; $showGoalToggle = $selectedMode === 'all' && !empty($goalTree); require __DIR__ . '/tab-heading.php'; ?>

    <section class="goal-map" aria-label="목표 지도" <?= !empty($goalTree) ? 'tabindex="0"' : '' ?>>
        <?php if (empty($goalTree)): ?>
            <div class="goal-map-empty ui-list-surface ui-empty-state"><strong><?= $selectedMode === 'focus' ? '지금 집중할 목표를 골라보세요.' : '하고 싶은 일을 적어보세요.' ?></strong><p><?= $selectedMode === 'focus' ? '전체 목표의 상세 화면에서 최대 5개까지 선택할 수 있어요.' : '큰 목표가 아니어도 괜찮아요.<br>언젠가 해보고 싶은 일이나 지금 해결하고 싶은 것부터 시작해보세요.' ?></p><?php if ($selectedMode === 'focus'): ?><a class="btn btn-secondary" href="/goal?mode=all">전체 목표에서 선택</a><?php else: ?><button class="btn btn-primary" type="button" data-goal-create-open>첫 목표 만들기</button><?php endif; ?></div>
        <?php else: ?>
            <?php $renderGoalBranch($goalTree); ?>
        <?php endif; ?>
    </section>
</main>

<div class="goal-local-layer" data-goal-layer hidden>
    <button type="button" class="goal-local-overlay" data-goal-close aria-label="목표 상세 닫기"></button>
    <?php foreach ($goals as $goal): $goalId = (int) $goal['id']; $progress = $goal['periodProgress'] ?? []; ?>
        <aside class="goal-detail-panel" data-goal-sheet="detail-<?= e((string) $goalId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="goalDetailTitle<?= e((string) $goalId) ?>">
            <div class="goal-detail-header is-<?= e((string) $goal['status']) ?>"><div><small><?= e((string) $goal['goalTypeLabel']) ?></small><div class="goal-detail-title"><form class="goal-node-focus" method="post" action="/goal/focus"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"><input type="hidden" name="mode" value="<?= e($selectedMode) ?>"><input type="hidden" name="focused" value="<?= !empty($goal['isFocused']) ? '0' : '1' ?>"><button type="submit" class="goal-focus-star" aria-pressed="<?= !empty($goal['isFocused']) ? 'true' : 'false' ?>" aria-label="<?= !empty($goal['isFocused']) ? '집중에서 해제' : '집중에 추가' ?>" <?= $goal['status'] !== 'active' ? 'disabled title="진행 중인 목표만 집중할 수 있어요"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9z"/></svg></button></form><strong id="goalDetailTitle<?= e((string) $goalId) ?>"><?= e((string) $goal['title']) ?></strong><?php if ($goal['status'] === 'completed'): ?><span class="goal-completed-label">완료</span><?php endif; ?></div></div><button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button></div>
            <div class="goal-detail-summary">
                <?php if (trim((string) ($goal['behaviorNote'] ?? '')) !== ''): ?><p class="goal-behavior-note"><?= e((string) $goal['behaviorNote']) ?></p><?php endif; ?>
                <dl><div><dt>기간</dt><dd><?= e((string) $goal['periodLabel']) ?></dd></div><div><dt>기간 진행</dt><dd><?= !empty($progress['visible']) ? e((string) $progress['percent']) . '%' : '설정 안 함' ?></dd></div></dl>
            </div>
            <section class="goal-linked-records" data-goal-linked-records>
                <div class="ui-segmented-tabs goal-linked-tabs" role="tablist" aria-label="목표에 연결된 기록">
                    <?php foreach (['plans' => ['계획', $goal['linkedScheduledPlans'] ?? []], 'todos' => ['할 일', $goal['linkedTodos'] ?? []], 'routines' => ['루틴', $goal['linkedRoutines'] ?? []]] as $key => [$label, $records]): ?>
                    <button type="button" role="tab" id="goalTab<?= e((string) $goalId . $key) ?>" aria-controls="goalPanel<?= e((string) $goalId . $key) ?>" aria-selected="<?= $key === 'plans' ? 'true' : 'false' ?>" <?= $key !== 'plans' ? 'tabindex="-1"' : '' ?> data-goal-record-tab="<?= e($key) ?>"><?= e($label) ?> <span><?= count($records) ?></span></button>
                    <?php endforeach; ?>
                </div>
                <?php foreach (['plans' => ['계획', $goal['linkedScheduledPlans'] ?? []], 'todos' => ['할 일', $goal['linkedTodos'] ?? []], 'routines' => ['루틴', $goal['linkedRoutines'] ?? []]] as $key => [$label, $records]): ?>
                <div role="tabpanel" id="goalPanel<?= e((string) $goalId . $key) ?>" aria-labelledby="goalTab<?= e((string) $goalId . $key) ?>" data-goal-record-panel="<?= e($key) ?>" <?= $key !== 'plans' ? 'hidden' : '' ?>>
                    <?php require __DIR__ . '/linked-record-table.php'; ?>
                </div><?php endforeach; ?>
            </section>
            <div class="goal-detail-actions"><button type="button" class="btn btn-secondary" data-goal-open="edit-<?= e((string) $goalId) ?>">목표 수정</button></div>
        </aside>
        <aside class="goal-detail-panel" data-goal-sheet="edit-<?= e((string) $goalId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="goalEditTitle<?= e((string) $goalId) ?>">
            <div class="goal-detail-header ui-form-sheet-header"><strong id="goalEditTitle<?= e((string) $goalId) ?>">목표 수정</strong><button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button></div>
                <form class="goal-form" method="post" action="/goal/update">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"><input type="hidden" name="mode" value="<?= e($selectedMode) ?>">
                    <label>목표명<input class="input" name="title" maxlength="80" value="<?= e((string) $goal['title']) ?>" required></label>
                    <div class="goal-choice-row"><span>상태</span><fieldset class="goal-choice"><legend class="sr-only">상태</legend><div><?php foreach ($statusOptions as $value => $label): ?><label><input type="radio" name="status" value="<?= e((string) $value) ?>" <?= $goal['status'] === $value ? 'checked' : '' ?>><span><?= e((string) $label) ?></span></label><?php endforeach; ?></div></fieldset></div>
                    <div class="goal-choice-row"><span>목표 구분</span><fieldset class="goal-choice"><legend class="sr-only">목표 구분</legend><div><?php foreach ($goalTypes as $value => $label): ?><label><input type="radio" name="goal_type" value="<?= e((string) $value) ?>" <?= $goal['goalType'] === $value ? 'checked' : '' ?>><span><?= e((string) $label) ?></span></label><?php endforeach; ?></div></fieldset></div>
                    <label>상위 목표 연결<select class="input" name="parent_goal_id"><option value="">최상위 목표</option><?php foreach ($parentOptions as $parent): if ((int) $parent['id'] === $goalId) continue; ?><option value="<?= e((string) $parent['id']) ?>" <?= (int) ($goal['parentGoalId'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>><?= e((string) $parent['title']) ?></option><?php endforeach; ?></select></label>
                    <div class="goal-form-grid"><label>시작일<input class="input" name="period_start_date" type="date" data-goal-date data-today="<?= e($today) ?>" value="<?= e((string) ($goal['periodStartDate'] ?? '')) ?>"></label><label>종료일<input class="input" name="period_end_date" type="date" data-goal-date data-today="<?= e($today) ?>" value="<?= e((string) ($goal['periodEndDate'] ?? '')) ?>"></label></div>
                    <label>행동 리마인드<textarea class="input" name="behavior_note" rows="3" maxlength="300"><?= e((string) $goal['behaviorNote']) ?></textarea></label>
                    <div class="goal-edit-actions"><button class="btn btn-ghost goal-delay-button" type="button" data-goal-delete="goalDelete<?= e((string) $goalId) ?>" aria-describedby="goalDeleteHelp<?= e((string) $goalId) ?>"><span>목표 삭제</span></button><button class="btn btn-primary" type="submit">저장</button><span class="sr-only" id="goalDeleteHelp<?= e((string) $goalId) ?>">삭제하려면 1.5초 이상 누르세요.</span></div>
                </form>
                <form id="goalDelete<?= e((string) $goalId) ?>" method="post" action="/goal/delete"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"><input type="hidden" name="mode" value="<?= e($selectedMode) ?>"></form>
        </aside>
    <?php endforeach; ?>
</div>

<button class="page-floating-action goal-floating-add" type="button" data-goal-create-open>목표 추가</button>
<div class="goal-create-layer" data-goal-create-layer hidden><button type="button" class="goal-local-overlay" data-goal-create-close aria-label="목표 추가 닫기"></button><section class="goal-create-sheet ui-form-sheet ui-standard-sheet" role="dialog" aria-modal="true" aria-labelledby="goalCreateTitle"><div class="goal-detail-header ui-form-sheet-header"><div><small>새로운 방향</small><strong id="goalCreateTitle">목표 추가</strong></div><button type="button" class="ui-close-button" data-goal-create-close aria-label="닫기">×</button></div><form method="post" action="/goal" data-goal-quick-form><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_type" value="bucket"><input type="hidden" name="status" value="active"><input type="hidden" name="mode" value="all"><div class="form-group"><label class="form-label" for="goalQuickTitle">무엇을 하고 싶나요?</label><input class="input" id="goalQuickTitle" name="title" type="text" maxlength="80" placeholder="목표명을 입력하세요" autocomplete="off" aria-describedby="goalQuickHint" required data-goal-quick-input></div><p class="form-hint" id="goalQuickHint">기간과 유형은 목표를 만든 뒤 상세 화면에서 정할 수 있어요.</p><div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">목표 추가</button></div></form></section></div>

<?php if (!empty($notificationSyncPayload)): ?><script type="application/json" data-notification-sync><?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?></script><?php endif; ?>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
