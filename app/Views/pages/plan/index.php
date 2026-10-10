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

            <?php if (empty($blockTemplates)): ?>
                <div class="plan-empty"><strong>저장된 계획 블록이 없습니다.</strong></div>
            <?php else: ?>
                <ul class="plan-block-template-list">
                    <?php foreach ($blockTemplates as $template): ?>
                        <li class="plan-block-template-card">
                            <div class="plan-block-template-summary-row">
                                <div class="plan-block-template-summary">
                                    <span class="importance-badge importance-<?= e(strtolower((string) $template['importance'])) ?>"><?= e((string) $template['importance']) ?></span>
                                    <div><strong><?= e((string) $template['title']) ?></strong><small><?= e((string) $template['durationLabel']) ?><?= $template['goalTitle'] !== '' ? ' · ' . e((string) $template['goalTitle']) : '' ?></small></div>
                                </div>
                                <div class="plan-block-template-actions">
                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        data-plan-block-edit
                                        data-template-id="<?= e((string) $template['id']) ?>"
                                        data-template-title="<?= e((string) $template['title']) ?>"
                                        data-template-duration="<?= e((string) $template['durationIndex']) ?>"
                                        data-template-importance="<?= e((string) $template['importance']) ?>"
                                        data-template-goal="<?= $template['goalId'] === null ? '' : e((string) $template['goalId']) ?>"
                                    >수정</button>
                                    <form method="post" action="/plan/block-template/delete" data-confirm-title="계획 블록 삭제" data-confirm="‘<?= e((string) $template['title']) ?>’ 계획 블록을 삭제할까요? 이미 복사한 일정은 유지됩니다." data-confirm-text="삭제">
                                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                        <input type="hidden" name="block_template_id" value="<?= e((string) $template['id']) ?>">
                                        <button type="submit" class="btn btn-ghost">삭제</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <button type="button" class="btn btn-primary plan-block-floating-add" data-plan-block-create>계획 블록 추가</button>

        <div
            class="plan-block-local-layer"
            data-plan-block-layer
            data-reopen-mode="<?= e((string) ($blockFormOld['mode'] ?? '')) ?>"
            data-reopen-id="<?= e((string) ($blockFormOld['id'] ?? '')) ?>"
            data-reopen-title="<?= e((string) ($blockFormOld['title'] ?? '')) ?>"
            data-reopen-duration="<?= e((string) ($blockFormOld['duration'] ?? '')) ?>"
            data-reopen-importance="<?= e((string) ($blockFormOld['importance'] ?? '')) ?>"
            data-reopen-goal="<?= e((string) ($blockFormOld['goal'] ?? '')) ?>"
            hidden
        >
            <button type="button" class="plan-block-local-overlay" data-plan-block-close aria-label="계획 블록 입력창 닫기"></button>
            <section class="plan-block-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="planBlockSheetTitle">
                <div class="plan-block-sheet-header ui-form-sheet-header">
                    <strong id="planBlockSheetTitle" data-plan-block-sheet-title>계획 블록 추가</strong>
                    <button type="button" class="ui-close-button" data-plan-block-close aria-label="닫기">×</button>
                </div>
                <form class="plan-block-template-form" method="post" action="/plan/block-template" data-plan-block-form>
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <input type="hidden" name="block_template_id" value="" data-plan-block-id disabled>
                    <label class="form-label" for="planBlockTemplateTitle">계획 블록명</label>
                    <input class="input" id="planBlockTemplateTitle" name="title" type="text" maxlength="80" required data-plan-block-title>
                    <label class="form-label" for="planBlockTemplateDuration">기본 소요 시간</label>
                    <select class="input" id="planBlockTemplateDuration" name="duration_index" required data-plan-block-duration>
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
                    <label class="form-label" for="planBlockTemplateGoal">연결할 목표</label>
                    <select class="input" id="planBlockTemplateGoal" name="goal_id" data-plan-block-goal>
                        <option value="">목표 연결 없음</option>
                        <?php foreach (($goalOptions ?? []) as $goal): ?><option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option><?php endforeach; ?>
                    </select>
                    <div class="ui-form-sheet-actions"><button type="submit" class="btn btn-primary" data-plan-block-submit>계획 블록 저장</button></div>
                </form>
            </section>
        </div>
    <?php else: ?>
        <section class="plan-list-section" aria-label="저장된 하루 템플릿">
            <?php if (empty($plans)): ?>
                <div class="plan-empty"><strong>아직 저장된 하루 템플릿이 없습니다.</strong><p class="muted">계획 블록을 시간대에 배치해 반복 가능한 하루 흐름을 만들어보세요.</p></div>
            <?php else: ?>
                <ul class="plan-list">
                    <?php foreach ($plans as $plan): ?>
                        <li class="plan-list-item">
                            <div class="plan-list-heading">
                                <a class="plan-list-name" href="/plan/show?id=<?= e((string) $plan['id']) ?>"><?= e((string) $plan['name']) ?></a>
                                <form
                                    class="plan-list-copy-form"
                                    method="post"
                                    action="/plan/copy"
                                    data-confirm-title="하루 템플릿 복사"
                                    data-confirm="‘<?= e((string) $plan['name']) ?>’ 하루 템플릿을 복사할까요? 복사본은 원본과 별도로 수정할 수 있습니다."
                                    data-confirm-text="복사"
                                >
                                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                    <input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>">
                                    <button
                                        type="submit"
                                        class="plan-list-copy-button"
                                        aria-label="<?= e((string) $plan['name']) ?> 하루 템플릿 복사"
                                        title="하루 템플릿 복사"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <rect x="8" y="8" width="11" height="11" rx="2"></rect>
                                            <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <details class="plan-list-blocks">
                                <summary><span class="plan-blocks-closed">블록 목록 보기 · <?= e((string) count($plan['blocks'] ?? [])) ?>개</span><span class="plan-blocks-open">블록 목록 닫기 · <?= e((string) count($plan['blocks'] ?? [])) ?>개</span></summary>
                                <ul>
                                    <?php foreach (($plan['blocks'] ?? []) as $block): ?>
                                        <li>
                                            <span class="importance-badge importance-<?= e(strtolower((string) $block['importance'])) ?>"><?= e((string) $block['importanceBadge']) ?></span>
                                            <strong title="<?= e((string) $block['title']) ?>"><?= e((string) $block['title']) ?></strong>
                                            <time><?= e((string) $block['timeRange']) ?></time>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <form class="plan-floating-add-form" method="get" action="/plan/new"><button type="submit" class="btn btn-primary plan-floating-add">하루 템플릿 추가</button></form>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
