<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$selectedType = (string) ($memoData['selectedType'] ?? 'short');
$isTrash = !empty($memoData['isTrash']);
$query = (string) ($memoData['query'] ?? '');
$counts = $memoData['counts'] ?? ['short' => 0, 'long' => 0];
?>

<main class="page memo-page">
    <section class="memo-header">
        <div>
            <p class="eyebrow">Memo</p>
            <h1 class="page-title"><?= $isTrash ? '메모 휴지통' : '메모' ?></h1>
        </div>
        <?php if ($isTrash): ?>
            <a class="memo-trash-button" href="/memo" aria-label="메모 목록으로 돌아가기">←</a>
        <?php else: ?>
            <a class="memo-trash-button" href="/memo?trash=1" aria-label="휴지통 열기">&#128465;</a>
        <?php endif; ?>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <?php if (!$isTrash): ?>
        <nav class="memo-type-tabs" aria-label="메모 길이 필터">
            <a href="/memo?type=short" <?= $selectedType === 'short' ? 'aria-current="page"' : '' ?>>짧은 메모 (<?= e((string) ($counts['short'] ?? 0)) ?>)</a>
            <a href="/memo?type=long" <?= $selectedType === 'long' ? 'aria-current="page"' : '' ?>>긴 메모 (<?= e((string) ($counts['long'] ?? 0)) ?>)</a>
        </nav>
    <?php endif; ?>

    <form class="memo-search" method="get" action="/memo">
        <?php if ($isTrash): ?>
            <input type="hidden" name="trash" value="1">
        <?php else: ?>
            <input type="hidden" name="type" value="<?= e($selectedType) ?>">
        <?php endif; ?>
        <label class="sr-only" for="memoSearch">메모 검색</label>
        <input class="input" id="memoSearch" name="q" type="search" maxlength="100" value="<?= e($query) ?>" placeholder="메모 내용 검색">
        <button type="submit" class="btn btn-secondary">검색</button>
    </form>

    <?php if ($isTrash): ?>
        <div class="memo-trash-toolbar">
            <p>삭제한 메모는 30일 뒤 자동으로 영구 삭제됩니다.</p>
            <?php if (!empty($memoData['items'])): ?>
                <form method="post" action="/memo/trash/empty" data-memo-empty-trash-form>
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <button type="submit" class="btn btn-ghost">휴지통 비우기</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($memoData['items'])): ?>
        <section class="empty-state">
            <h2>
                <?php if ($isTrash): ?>
                    휴지통이 비어 있습니다.
                <?php elseif ($selectedType === 'long'): ?>
                    아직 긴 메모가 없습니다.
                <?php else: ?>
                    아직 짧은 메모가 없습니다.
                <?php endif; ?>
            </h2>
            <p>
                <?php if ($isTrash): ?>
                    삭제한 메모가 이곳에 최신순으로 표시됩니다.
                <?php elseif ($query !== ''): ?>
                    검색어를 바꾸거나 검색을 초기화해보세요.
                <?php else: ?>
                    떠오른 생각을 빠르게 기록해보세요.
                <?php endif; ?>
            </p>
        </section>
    <?php else: ?>
        <section class="memo-list" aria-label="<?= $isTrash ? '삭제한 메모 목록' : '메모 목록' ?>">
            <?php foreach ($memoData['items'] as $memo): ?>
                <article class="memo-card <?= (int) ($memo['length'] ?? 0) >= 300 ? 'is-long' : 'is-short' ?>">
                    <textarea hidden data-memo-content-value><?= e((string) $memo['content']) ?></textarea>
                    <p><?= nl2br(e((string) $memo['content'])) ?></p>
                    <div class="memo-card-meta">
                        <time><?= e((string) ($isTrash ? $memo['deletedLabel'] : $memo['updatedLabel'])) ?></time>
                        <span><?= e((string) $memo['length']) ?>자</span>
                    </div>
                    <?php if ($isTrash && !empty($memo['expiresLabel'])): ?>
                        <small class="memo-expiry-label"><?= e((string) $memo['expiresLabel']) ?> 자동 삭제</small>
                    <?php endif; ?>
                    <div class="memo-card-actions">
                        <?php if ($isTrash): ?>
                            <form method="post" action="/memo/restore">
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="memo_id" value="<?= e((string) $memo['id']) ?>">
                                <button type="submit" class="btn btn-secondary">복원</button>
                            </form>
                        <?php else: ?>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-memo-edit-open
                                data-memo-id="<?= e((string) $memo['id']) ?>"
                            >수정</button>
                            <form method="post" action="/memo/delete" data-memo-delete-form>
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="memo_id" value="<?= e((string) $memo['id']) ?>">
                                <input type="hidden" name="type" value="<?= e($selectedType) ?>">
                                <button type="submit" class="btn btn-ghost">삭제</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!$isTrash): ?>
        <button type="button" class="memo-add-fab" data-memo-add-open aria-label="메모 추가">+</button>
    <?php endif; ?>
</main>

<?php if (!$isTrash): ?>
<div class="memo-local-layer" data-memo-layer hidden>
    <div class="memo-overlay" data-memo-close></div>
    <section class="memo-sheet" data-memo-add-sheet hidden aria-modal="true" role="dialog" aria-labelledby="memoAddTitle">
        <form method="post" action="/memo">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <div class="memo-sheet-header">
                <strong id="memoAddTitle">메모 작성</strong>
                <button type="button" class="ui-close-button" data-memo-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="memoAddContent">메모</label>
            <textarea class="input" id="memoAddContent" name="content" maxlength="10000" rows="10" required><?= e((string) ($old['content'] ?? '')) ?></textarea>
            <?php if (!empty($errors['content'])): ?><p class="field-error"><?= e((string) $errors['content']) ?></p><?php endif; ?>
            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </section>
    <section class="memo-sheet" data-memo-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="memoEditTitle">
        <form method="post" action="/memo/update">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="memo_id" id="memoEditId">
            <input type="hidden" name="type" value="<?= e($selectedType) ?>">
            <div class="memo-sheet-header">
                <strong id="memoEditTitle">메모 수정</strong>
                <button type="button" class="ui-close-button" data-memo-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="memoEditContent">메모</label>
            <textarea class="input" id="memoEditContent" name="content" maxlength="10000" rows="10" required></textarea>
            <button type="submit" class="btn btn-primary">수정 저장</button>
        </form>
    </section>
</div>
<?php if (!empty($editMemoId)): ?>
    <textarea hidden data-memo-edit-error data-memo-id="<?= e((string) $editMemoId) ?>"><?= e((string) ($old['content'] ?? '')) ?></textarea>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
