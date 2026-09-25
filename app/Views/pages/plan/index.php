<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page plan-page">
    <h1 class="sr-only">계획</h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>
    <?php foreach (($errors ?? []) as $error): ?>
        <?php if (is_string($error) && $error !== ''): ?>
            <span data-toast-message="<?= e($error) ?>" hidden></span>
            <?php break; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <nav class="plan-view-tabs" aria-label="계획 템플릿 종류">
        <a href="/plan" <?= ($activeView ?? 'days') === 'days' ? 'aria-current="page"' : '' ?>>하루 템플릿</a>
        <a href="/plan?view=blocks" <?= ($activeView ?? 'days') === 'blocks' ? 'aria-current="page"' : '' ?>>계획 블록</a>
    </nav>

    <?php if (($activeView ?? 'days') === 'blocks'): ?>
        <section class="plan-block-template-section" aria-labelledby="blockTemplateHeading">
            <div class="plan-block-template-intro">
                <h2 id="blockTemplateHeading">계획 블록 템플릿</h2>
                <p>반복해서 사용하는 일정명과 기본 소요 시간, 중요도, 목표를 저장합니다. 시작 시각은 하루 템플릿이나 캘린더에서 정합니다.</p>
            </div>

            <form class="plan-block-template-form" method="post" action="/plan/block-template">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <label class="form-label" for="newBlockTemplateTitle">계획 블록명</label>
                <input class="input" id="newBlockTemplateTitle" name="title" type="text" maxlength="80" required>
                <label class="form-label" for="newBlockTemplateDuration">기본 소요 시간</label>
                <select class="input" id="newBlockTemplateDuration" name="duration_index" required>
                    <?php for ($durationIndex = 1; $durationIndex <= 143; $durationIndex++): ?>
                        <?php
                        $durationMinutes = $durationIndex * 10;
                        $durationHours = intdiv($durationMinutes, 60);
                        $durationRemainder = $durationMinutes % 60;
                        $durationLabel = $durationHours > 0
                            ? $durationHours . '시간' . ($durationRemainder > 0 ? ' ' . $durationRemainder . '분' : '')
                            : $durationRemainder . '분';
                        ?>
                        <option value="<?= $durationIndex ?>" <?= $durationIndex === 6 ? 'selected' : '' ?>><?= e($durationLabel) ?></option>
                    <?php endfor; ?>
                </select>
                <fieldset class="importance-choice-group">
                    <legend>중요도</legend>
                    <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                        <label><input type="radio" name="importance" value="<?= $importance ?>" <?= $importance === 'D' ? 'checked' : '' ?>><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <label class="form-label" for="newBlockTemplateGoal">연결할 목표</label>
                <select class="input" id="newBlockTemplateGoal" name="goal_id">
                    <option value="">목표 연결 없음</option>
                    <?php foreach (($goalOptions ?? []) as $goal): ?><option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">계획 블록 저장</button>
            </form>

            <?php if (empty($blockTemplates)): ?>
                <div class="plan-empty"><strong>저장된 계획 블록이 없습니다.</strong></div>
            <?php else: ?>
                <ul class="plan-block-template-list">
                    <?php foreach ($blockTemplates as $template): ?>
                        <?php $editPrefix = 'blockTemplate' . (int) $template['id']; ?>
                        <li class="plan-block-template-card">
                            <div class="plan-block-template-summary">
                                <span class="importance-badge importance-<?= e(strtolower((string) $template['importance'])) ?>"><?= e((string) $template['importance']) ?></span>
                                <div><strong><?= e((string) $template['title']) ?></strong><small><?= e((string) $template['durationLabel']) ?><?= $template['goalTitle'] !== '' ? ' · ' . e((string) $template['goalTitle']) : '' ?></small></div>
                            </div>
                            <details>
                                <summary>수정</summary>
                                <form class="plan-block-template-form is-edit" method="post" action="/plan/block-template/update">
                                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="block_template_id" value="<?= e((string) $template['id']) ?>">
                                    <label class="form-label" for="<?= e($editPrefix) ?>Title">계획 블록명</label>
                                    <input class="input" id="<?= e($editPrefix) ?>Title" name="title" type="text" maxlength="80" value="<?= e((string) $template['title']) ?>" required>
                                    <label class="form-label" for="<?= e($editPrefix) ?>Duration">기본 소요 시간</label>
                                    <select class="input" id="<?= e($editPrefix) ?>Duration" name="duration_index" required>
                                        <?php for ($durationIndex = 1; $durationIndex <= 143; $durationIndex++): ?>
                                            <?php
                                            $durationMinutes = $durationIndex * 10;
                                            $durationHours = intdiv($durationMinutes, 60);
                                            $durationRemainder = $durationMinutes % 60;
                                            $durationLabel = $durationHours > 0
                                                ? $durationHours . '시간' . ($durationRemainder > 0 ? ' ' . $durationRemainder . '분' : '')
                                                : $durationRemainder . '분';
                                            ?>
                                            <option value="<?= $durationIndex ?>" <?= (int) $template['durationIndex'] === $durationIndex ? 'selected' : '' ?>><?= e($durationLabel) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <fieldset class="importance-choice-group">
                                        <legend>중요도</legend>
                                        <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                                            <label><input type="radio" name="importance" value="<?= $importance ?>" <?= $template['importance'] === $importance ? 'checked' : '' ?>><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <label class="form-label" for="<?= e($editPrefix) ?>Goal">연결할 목표</label>
                                    <select class="input" id="<?= e($editPrefix) ?>Goal" name="goal_id"><option value="">목표 연결 없음</option><?php foreach (($goalOptions ?? []) as $goal): ?><option value="<?= e((string) $goal['id']) ?>" <?= (int) ($template['goalId'] ?? 0) === (int) $goal['id'] ? 'selected' : '' ?>><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option><?php endforeach; ?></select>
                                    <button type="submit" class="btn btn-primary">수정 저장</button>
                                </form>
                            </details>
                            <form method="post" action="/plan/block-template/delete" data-confirm="계획 블록 템플릿을 삭제할까요? 이미 복사한 일정은 유지됩니다."><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="block_template_id" value="<?= e((string) $template['id']) ?>"><button type="submit" class="btn btn-ghost">삭제</button></form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="plan-list-section" aria-label="저장된 하루 템플릿">
            <?php if (empty($plans)): ?>
                <div class="plan-empty"><strong>아직 저장된 하루 템플릿이 없습니다.</strong><p class="muted">계획 블록을 시간대에 배치해 반복 가능한 하루 흐름을 만들어보세요.</p></div>
            <?php else: ?>
                <ul class="plan-list">
                    <?php foreach ($plans as $plan): ?>
                        <li class="plan-list-item">
                            <div class="plan-list-main"><strong><?= e((string) $plan['name']) ?></strong><span><?= e((string) $plan['timeRange']) ?> · <?= e((string) $plan['blockCount']) ?>개 블록</span><?php if (!empty($plan['goalTitles'])): ?><div class="plan-goal-list" aria-label="연결된 목표"><?php foreach ($plan['goalTitles'] as $goalTitle): ?><span><?= e((string) $goalTitle) ?></span><?php endforeach; ?></div><?php endif; ?></div>
                            <div class="plan-list-actions">
                                <a class="btn btn-secondary" href="/plan/show?id=<?= e((string) $plan['id']) ?>">상세</a><a class="btn btn-secondary" href="/plan/edit?id=<?= e((string) $plan['id']) ?>">수정</a>
                                <form method="post" action="/plan/copy"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>"><button type="submit" class="btn btn-secondary">복사</button></form>
                                <form method="post" action="/plan/delete" data-confirm="하루 템플릿을 삭제할까요? 캘린더와 회고 기록에는 영향을 주지 않습니다."><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>"><button type="submit" class="btn btn-ghost">삭제</button></form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <form class="plan-floating-add-form" method="get" action="/plan/new"><button type="submit" class="btn btn-primary plan-floating-add">하루 템플릿 추가</button></form>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let allowSubmit = false;
    form.addEventListener('submit', async (event) => {
        if (allowSubmit) { allowSubmit = false; return; }
        event.preventDefault();
        const message = form.dataset.confirm || '계속 진행할까요?';
        const confirmed = window.LifeFlowUI && typeof window.LifeFlowUI.confirm === 'function'
            ? await window.LifeFlowUI.confirm({ title: '확인', message, confirmText: '진행', cancelText: '취소' })
            : confirm(message);
        if (confirmed) { allowSubmit = true; form.requestSubmit(); }
    });
});
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
