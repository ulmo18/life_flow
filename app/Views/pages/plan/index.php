<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page plan-page">
    <section class="plan-header">
        <div>
            <p class="eyebrow">Plan</p>
            <h1 class="page-title">계획 리스트</h1>
        </div>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="plan-list-section" aria-label="저장된 계획">
        <?php if (empty($plans)): ?>
            <div class="plan-empty">
                <strong>아직 저장된 계획이 없습니다.</strong>
                <p class="muted">계획을 추가하면 이곳에서 확인, 수정, 복사, 삭제할 수 있습니다.</p>
            </div>
        <?php else: ?>
            <ul class="plan-list">
                <?php foreach ($plans as $plan): ?>
                    <li class="plan-list-item">
                        <div class="plan-list-main">
                            <strong><?= e((string) $plan['name']) ?></strong>
                            <span>
                                <?= e((string) $plan['timeRange']) ?>
                                · <?= e((string) $plan['blockCount']) ?>개 블록
                            </span>
                            <?php if (!empty($plan['goalTitles'])): ?>
                                <div class="plan-goal-list" aria-label="연결된 목표">
                                    <?php foreach ($plan['goalTitles'] as $goalTitle): ?>
                                        <span><?= e((string) $goalTitle) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="plan-list-actions">
                            <a class="btn btn-secondary" href="/plan/show?id=<?= e((string) $plan['id']) ?>">상세</a>
                            <a class="btn btn-secondary" href="/plan/edit?id=<?= e((string) $plan['id']) ?>">수정</a>
                            <form method="post" action="/plan/copy">
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>">
                                <button type="submit" class="btn btn-secondary">복사</button>
                            </form>
                            <form method="post" action="/plan/delete" data-confirm="계획을 삭제하시겠습니까? 캘린더와 회고 기록에는 영향을 주지 않습니다.">
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>">
                                <button type="submit" class="btn btn-ghost">삭제</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <form class="plan-floating-add-form" method="get" action="/plan/new">
        <button type="submit" class="btn btn-primary plan-floating-add">계획 추가</button>
    </form>
</main>

<script>
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let allowSubmit = false;

    form.addEventListener('submit', async (event) => {
        if (allowSubmit) {
            allowSubmit = false;
            return;
        }

        event.preventDefault();
        const message = form.dataset.confirm || '계속 진행하시겠습니까?';
        const confirmed = window.LifeFlowUI && typeof window.LifeFlowUI.confirm === 'function'
            ? await window.LifeFlowUI.confirm({
                title: '확인',
                message,
                confirmText: '진행',
                cancelText: '취소',
            })
            : confirm(message);

        if (confirmed) {
            allowSubmit = true;
            form.requestSubmit();
        }
    });
});
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
