<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page plan-page">
    <h1 class="sr-only"><?= e(t('nav.recurring_plan')) ?></h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>
    <?php foreach (($errors ?? []) as $error): ?>
        <?php if (is_string($error) && $error !== ''): ?>
            <span data-toast-message="<?= e($error) ?>" hidden></span>
            <?php break; ?>
        <?php endif; ?>
    <?php endforeach; ?>

        <section class="plan-list-section" aria-label="저장된 하루 템플릿">
            <?php if (empty($plans)): ?>
                <div class="ui-list-surface ui-empty-state"><strong>아직 저장된 하루 템플릿이 없습니다.</strong><p class="muted">할 일 블록을 시간대에 배치해 반복 가능한 하루 흐름을 만들어보세요.</p></div>
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

</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
