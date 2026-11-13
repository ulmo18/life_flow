<?php require __DIR__ . '/../../layouts/header.php'; ?>
<main class="page tag-page">
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>
    <section class="tag-list-section ui-list-surface" aria-label="태그 목록">
        <table class="tag-table ui-list-table">
            <caption class="sr-only">일정 태그 목록</caption>
            <thead><tr><th scope="col">태그</th><th scope="col">사용 여부</th></tr></thead>
            <tbody>
            <?php foreach (($tagData['tags'] ?? []) as $tag): ?>
                <tr><td>
                    <?php if (!empty($tag['is_system'])): ?>
                        <div class="tag-card-title tag-system-title"><span><strong><?= e((string) $tag['name']) ?></strong><svg class="tag-system-icon" viewBox="0 0 24 24" role="img" aria-label="기본 태그"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span></div>
                    <?php else: ?>
                        <button type="button" class="tag-edit-open tag-card-title" data-tag-open="tagEdit<?= e((string) $tag['id']) ?>" aria-label="<?= e((string) $tag['name']) ?> 태그 수정">
                            <span class="tag-swatch" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;" aria-hidden="true"></span><strong><?= e((string) $tag['name']) ?></strong><span class="tag-edit-label">수정</span>
                        </button>
                    <?php endif; ?>
                </td><td>
                    <?php $enabled = (int) ($tag['is_enabled'] ?? 1) === 1; ?>
                    <form method="post" action="/tags/toggle">
                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="tag_id" value="<?= e((string) $tag['id']) ?>">
                        <input type="hidden" name="enabled" value="<?= $enabled ? '0' : '1' ?>">
                        <button type="submit" class="tag-visibility-toggle <?= $enabled ? 'is-enabled' : '' ?>" role="switch" aria-checked="<?= $enabled ? 'true' : 'false' ?>" aria-label="<?= e((string) $tag['name']) ?> 태그 <?= $enabled ? '비활성화' : '활성화' ?>"><span aria-hidden="true"></span></button>
                    </form>
                </td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <button type="button" class="btn btn-primary daily-floating-action" data-tag-open="tagCreate">태그 추가</button>
    <?php $editingTag = null; require __DIR__ . '/_form-sheet.php'; ?>
    <?php foreach (($tagData['tags'] ?? []) as $tag): ?>
        <?php if (empty($tag['is_system'])): ?>
            <?php $editingTag = $tag; require __DIR__ . '/_form-sheet.php'; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</main>
<script>
(() => {
    document.querySelectorAll('[data-tag-sheet]').forEach(sheet => {
        const opener = document.querySelector('[data-tag-open="' + sheet.id + '"]');
        let previousOverflow = '';
        const open = () => {
            if (sheet.open) return;
            previousOverflow = document.body.style.overflow;
            sheet.showModal();
            document.body.style.overflow = 'hidden';
        };
        opener.addEventListener('click', open);
        sheet.querySelector('[data-tag-close]').addEventListener('click', () => sheet.close());
        sheet.addEventListener('click', event => {
            if (event.target !== sheet) return;
            const rect = sheet.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) sheet.close();
        });
        sheet.addEventListener('close', () => { document.body.style.overflow = previousOverflow; opener.focus(); });
        const syncViewport = () => {
            const viewport = window.visualViewport;
            sheet.style.setProperty('--tag-viewport-height', (viewport ? viewport.height : window.innerHeight) + 'px');
            sheet.style.bottom = viewport ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop) + 'px' : '0px';
        };
        window.visualViewport?.addEventListener('resize', syncViewport);
        window.visualViewport?.addEventListener('scroll', syncViewport);
        syncViewport();
        if (sheet.dataset.reopen === 'true') open();
    });
})();
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    let allowSubmit = false;

    form.addEventListener('submit', async (event) => {
        if (allowSubmit) {
            return;
        }

        event.preventDefault();
        const ownerSheet = form.closest('dialog');
        const reopen = Boolean(ownerSheet && ownerSheet.open);
        if (reopen) {
            // Native dialogs are above ordinary modal layers; dismiss before confirmation.
            await new Promise(resolve => {
                ownerSheet.addEventListener('close', resolve, { once: true });
                ownerSheet.close();
            });
        }
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
        } else if (reopen) {
            document.querySelector('[data-tag-open="' + ownerSheet.id + '"]').click();
        }
    });
});
</script>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
