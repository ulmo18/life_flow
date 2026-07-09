<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page tag-page">
    <section class="tag-page-header">
        <div>
            <p class="eyebrow">Tags</p>
            <h1 class="page-title">일정 태그 관리</h1>
        </div>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="section tag-form-section">
        <h2>태그 추가</h2>
        <form class="tag-form" method="post" action="/tags">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="tagName">태그명</label>
            <input class="input" id="tagName" name="name" type="text" maxlength="24" value="<?= e((string) ($old['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?>
                <p class="field-error"><?= e((string) $errors['name']) ?></p>
            <?php endif; ?>

            <fieldset class="tag-palette-grid">
                <legend>색상</legend>
                <?php foreach (($tagData['palettes'] ?? []) as $palette): ?>
                    <?php $disabled = !empty($palette['isUsed']); ?>
                    <label class="tag-palette-option <?= $disabled ? 'is-disabled' : '' ?>" style="--tag-color: <?= e((string) $palette['color_hex']) ?>;">
                        <input type="radio" name="palette_id" value="<?= e((string) $palette['id']) ?>" <?= $disabled ? 'disabled' : '' ?> required>
                        <span aria-hidden="true"></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <?php if (!empty($errors['palette'])): ?>
                <p class="field-error"><?= e((string) $errors['palette']) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">태그 추가</button>
        </form>
    </section>

    <section class="tag-list-section" aria-label="태그 목록">
        <?php foreach (($tagData['tags'] ?? []) as $tag): ?>
            <article class="tag-card">
                <?php if (!empty($tag['is_system'])): ?>
                    <div class="tag-card-title">
                        <span class="tag-swatch" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;" aria-hidden="true"></span>
                        <strong><?= e((string) $tag['name']) ?></strong>
                        <small>기본</small>
                    </div>
                <?php else: ?>
                    <details class="tag-edit-panel">
                        <summary class="tag-card-title">
                            <span class="tag-swatch" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;" aria-hidden="true"></span>
                            <strong><?= e((string) $tag['name']) ?></strong>
                            <span class="tag-edit-label">수정</span>
                        </summary>
                        <form class="tag-form compact" method="post" action="/tags/update">
                            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                            <input type="hidden" name="tag_id" value="<?= e((string) $tag['id']) ?>">
                            <label class="form-label" for="tagName<?= e((string) $tag['id']) ?>">태그명</label>
                            <input class="input" id="tagName<?= e((string) $tag['id']) ?>" name="name" type="text" maxlength="24" value="<?= e((string) $tag['name']) ?>" required>

                            <fieldset class="tag-palette-grid">
                                <legend>색상</legend>
                                <?php foreach (($tagData['palettes'] ?? []) as $palette): ?>
                                    <?php
                                    $isCurrent = (int) ($tag['palette_id'] ?? 0) === (int) $palette['id'];
                                    $disabled = !$isCurrent && !empty($palette['isUsed']);
                                    ?>
                                    <label class="tag-palette-option <?= $disabled ? 'is-disabled' : '' ?>" style="--tag-color: <?= e((string) $palette['color_hex']) ?>;">
                                        <input
                                            type="radio"
                                            name="palette_id"
                                            value="<?= e((string) $palette['id']) ?>"
                                            <?= $isCurrent ? 'checked' : '' ?>
                                            <?= $disabled ? 'disabled' : '' ?>
                                            required
                                        >
                                        <span aria-hidden="true"></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <button type="submit" class="btn btn-secondary">저장</button>
                        </form>

                        <form method="post" action="/tags/delete" data-confirm="태그를 삭제할까요? 기존 일정의 태그 연결은 해제됩니다.">
                            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                            <input type="hidden" name="tag_id" value="<?= e((string) $tag['id']) ?>">
                            <button type="submit" class="btn btn-ghost">삭제</button>
                        </form>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<script>
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let allowSubmit = false;

    form.addEventListener('submit', async (event) => {
        if (allowSubmit) {
            return;
        }

        event.preventDefault();
        const confirmed = window.LifeFlowUI && typeof window.LifeFlowUI.confirm === 'function'
            ? await window.LifeFlowUI.confirm({
                title: '확인',
                message: form.dataset.confirm || '계속 진행할까요?',
                confirmText: '진행',
                cancelText: '취소',
            })
            : confirm(form.dataset.confirm || '계속 진행할까요?');

        if (confirmed) {
            allowSubmit = true;
            form.requestSubmit();
        }
    });
});
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
