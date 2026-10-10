<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$selectedMode = (string) ($selectedMode ?? 'focus');
$today = date('Y-m-d');
$statusSymbols = ['active' => '◐', 'completed' => '✓', 'paused' => '○', 'archived' => '○'];

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

$renderGoalBranch = static function (array $nodes, int $depth = 0, ?int $parentId = null) use (&$renderGoalBranch, $renderQuickForm, $statusSymbols, $selectedMode): void {
    ?><ol class="goal-map-branch" data-goal-branch data-parent-id="<?= $parentId === null ? '' : e((string) $parentId) ?>"><?php
    foreach ($nodes as $node):
        $goalId = (int) $node['id'];
        $children = $node['children'] ?? [];
        $status = (string) $node['status'];
        ?><li class="goal-map-item is-<?= e($status) ?>" data-goal-node data-goal-id="<?= e((string) $goalId) ?>" data-parent-id="<?= $parentId === null ? '' : e((string) $parentId) ?>" data-focus-path="<?= !empty($node['hasFocusedDescendant']) ? '1' : '0' ?>" style="--goal-depth:<?= e((string) min($depth, 5)) ?>">
            <div class="goal-drop-before" data-goal-drop-before="<?= e((string) $goalId) ?>" aria-hidden="true"></div>
            <div class="goal-node-row">
                <?php if (!empty($children)): ?><button class="goal-collapse" type="button" data-goal-collapse aria-expanded="true" aria-label="<?= e((string) $node['title']) ?> 하위 목표 접기">▾</button><?php else: ?><span class="goal-collapse-placeholder" aria-hidden="true"></span><?php endif; ?>
                <button class="goal-node-main" type="button" data-goal-open="detail-<?= e((string) $goalId) ?>">
                    <span class="goal-status-symbol" aria-hidden="true"><?= e($statusSymbols[$status] ?? '○') ?></span>
                    <strong><?= e((string) $node['title']) ?></strong>
                    <?php if (!empty($node['descendantCount'])): ?><small><?= e((string) $node['descendantCount']) ?></small><?php endif; ?>
                </button>
                <div class="goal-node-actions">
                    <?php if ($selectedMode === 'all'): ?><button type="button" data-goal-child-add="<?= e((string) $goalId) ?>" aria-label="<?= e((string) $node['title']) ?> 하위 목표 추가">＋</button><?php endif; ?>
                    <?php if ($selectedMode === 'all'): ?><button type="button" class="goal-drag-handle" draggable="true" data-goal-drag="<?= e((string) $goalId) ?>" aria-label="<?= e((string) $node['title']) ?> 이동">⋮⋮</button><?php endif; ?>
                </div>
            </div>
            <?php $renderQuickForm($goalId, '하위 목표를 입력하세요', 'goal-inline-form'); ?>
            <?php if (!empty($children)): ?><div class="goal-children" data-goal-children><?php $renderGoalBranch($children, $depth + 1, $goalId); ?></div><?php endif; ?>
        </li><?php
    endforeach;
    ?><li class="goal-drop-end" data-goal-drop-parent="<?= $parentId === null ? '' : e((string) $parentId) ?>">여기로 이동</li></ol><?php
};
?>

<main class="page goal-page" data-goal-page data-mode="<?= e($selectedMode) ?>">
    <header class="goal-map-header">
        <div><p class="eyebrow">Goal Map</p><h1 class="page-title">내가 향하는 방향</h1><p>큰 목표부터 오늘 떠오른 작은 바람까지, 연결하며 키워보세요.</p></div>
    </header>

    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <nav class="goal-mode-switch" aria-label="목표 범위">
        <a href="/goal" <?= $selectedMode === 'focus' ? 'aria-current="page"' : '' ?>>지금 집중</a>
        <a href="/goal?mode=all" <?= $selectedMode === 'all' ? 'aria-current="page"' : '' ?>>전체 목표</a>
    </nav>
    <?php if ($selectedMode === 'all' && !empty($goalTree)): ?><div class="goal-map-toolbar"><button type="button" class="btn btn-secondary" data-goal-toggle-all>전부 펼치기</button></div><?php endif; ?>

    <section class="goal-map" aria-label="목표 지도">
        <?php if (empty($goalTree)): ?>
            <div class="goal-map-empty"><strong><?= $selectedMode === 'focus' ? '지금 집중할 목표를 골라보세요.' : '하고 싶은 일을 적어보세요.' ?></strong><p><?= $selectedMode === 'focus' ? '전체 목표의 상세 화면에서 최대 5개까지 선택할 수 있어요.' : '큰 목표가 아니어도 괜찮아요.<br>언젠가 해보고 싶은 일이나 지금 해결하고 싶은 것부터 시작해보세요.' ?></p><?php if ($selectedMode === 'focus'): ?><a class="btn btn-secondary" href="/goal?mode=all">전체 목표에서 선택</a><?php else: ?><button class="btn btn-primary" type="button" data-goal-create-open>첫 목표 만들기</button><?php endif; ?></div>
        <?php else: ?>
            <p class="goal-map-help">목표를 눌러 자세히 보고, ＋로 하위 목표를 이어보세요.<?= $selectedMode === 'all' ? ' 이동 손잡이를 끌면 구조와 순서를 바꿀 수 있어요.' : '' ?></p>
            <?php $renderGoalBranch($goalTree); ?>
        <?php endif; ?>
    </section>
</main>

<div class="goal-local-layer" data-goal-layer hidden>
    <button type="button" class="goal-local-overlay" data-goal-close aria-label="목표 상세 닫기"></button>
    <?php foreach ($goals as $goal): $goalId = (int) $goal['id']; $progress = $goal['periodProgress'] ?? []; ?>
        <aside class="goal-detail-panel" data-goal-sheet="detail-<?= e((string) $goalId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="goalDetailTitle<?= e((string) $goalId) ?>">
            <div class="goal-detail-header"><div><small><?= e((string) $goal['goalTypeLabel']) ?></small><strong id="goalDetailTitle<?= e((string) $goalId) ?>"><?= e((string) $goal['title']) ?></strong></div><button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button></div>
            <div class="goal-detail-summary">
                <dl><div><dt>상태</dt><dd><?= e((string) $goal['statusLabel']) ?></dd></div><div><dt>기간</dt><dd><?= e((string) $goal['periodLabel']) ?></dd></div><div><dt>기간 진행</dt><dd><?= !empty($progress['visible']) ? e((string) $progress['percent']) . '%' : '설정 안 함' ?></dd></div></dl>
                <div class="goal-link-counts"><span>연결된 계획 <b><?= e((string) count($goal['linkedPlans'] ?? [])) ?></b></span><span>연결된 할일 <b><?= e((string) ($goal['linkedTodoCount'] ?? 0)) ?></b></span><span>연결된 루틴 <b><?= e((string) count($goal['linkedRoutines'] ?? [])) ?></b></span></div>
            </div>
            <form class="goal-focus-form" method="post" action="/goal/focus"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"><input type="hidden" name="focused" value="<?= !empty($goal['isFocused']) ? '0' : '1' ?>"><button class="btn <?= !empty($goal['isFocused']) ? 'btn-secondary' : 'btn-primary' ?>" type="submit" <?= $goal['status'] !== 'active' ? 'disabled' : '' ?>><?= !empty($goal['isFocused']) ? '지금 집중에서 해제' : '지금 집중에 추가' ?></button><?php if ($goal['status'] !== 'active'): ?><small>진행 중인 목표만 집중 목표로 선택할 수 있어요.</small><?php endif; ?></form>
            <details class="goal-detail-edit"><summary>상세 정보 수정</summary>
                <form class="goal-form" method="post" action="/goal/update">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>">
                    <label>목표명<input class="input" name="title" maxlength="80" value="<?= e((string) $goal['title']) ?>" required></label>
                    <div class="goal-form-grid"><label>상태<select class="input" name="status"><?php foreach ($statusOptions as $value => $label): ?><option value="<?= e((string) $value) ?>" <?= $goal['status'] === $value ? 'selected' : '' ?>><?= e((string) $label) ?></option><?php endforeach; ?></select></label><label>상위 목표<select class="input" name="parent_goal_id"><option value="">최상위 목표</option><?php foreach ($parentOptions as $parent): if ((int) $parent['id'] === $goalId) continue; ?><option value="<?= e((string) $parent['id']) ?>" <?= (int) ($goal['parentGoalId'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>><?= e((string) $parent['title']) ?></option><?php endforeach; ?></select></label></div>
                    <label>목표 구분<select class="input" name="goal_type"><?php foreach ($goalTypes as $value => $label): ?><option value="<?= e((string) $value) ?>" <?= $goal['goalType'] === $value ? 'selected' : '' ?>><?= e((string) $label) ?></option><?php endforeach; ?></select></label>
                    <div class="goal-form-grid"><label>시작일<input class="input" name="period_start_date" type="date" value="<?= e((string) ($goal['periodStartDate'] ?? '')) ?>"></label><label>종료일<input class="input" name="period_end_date" type="date" value="<?= e((string) ($goal['periodEndDate'] ?? '')) ?>"></label></div>
                    <label>행동 리마인드<textarea class="input" name="behavior_note" rows="3" maxlength="300"><?= e((string) $goal['behaviorNote']) ?></textarea></label>
                    <div class="goal-edit-actions"><button class="btn btn-primary" type="submit">저장</button><button class="btn btn-ghost" type="submit" form="goalDelete<?= e((string) $goalId) ?>">삭제</button></div>
                </form>
                <form id="goalDelete<?= e((string) $goalId) ?>" method="post" action="/goal/delete" data-confirm="목표를 삭제할까요? 하위 목표는 최상위 목표로 이동합니다."><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>"></form>
            </details>
        </aside>
    <?php endforeach; ?>
</div>

<button class="page-floating-action goal-floating-add" type="button" data-goal-create-open>목표 추가</button>
<div class="goal-create-layer" data-goal-create-layer hidden><button type="button" class="goal-local-overlay" data-goal-create-close aria-label="목표 추가 닫기"></button><section class="goal-create-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="goalCreateTitle"><div class="goal-detail-header ui-form-sheet-header"><div><small>새로운 방향</small><strong id="goalCreateTitle">목표 추가</strong></div><button type="button" class="ui-close-button" data-goal-create-close aria-label="닫기">×</button></div><form method="post" action="/goal" data-goal-quick-form><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="goal_type" value="bucket"><input type="hidden" name="status" value="active"><input type="hidden" name="mode" value="all"><label class="form-label" for="goalQuickTitle">무엇을 하고 싶나요?</label><input class="input" id="goalQuickTitle" name="title" type="text" maxlength="80" placeholder="목표명을 입력하세요" autocomplete="off" required data-goal-quick-input><p class="muted">기간과 유형은 목표를 만든 뒤 상세 화면에서 정할 수 있어요.</p><div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">목표 추가</button></div></form></section></div>

<?php if (!empty($notificationSyncPayload)): ?><script type="application/json" data-notification-sync><?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?></script><?php endif; ?>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
