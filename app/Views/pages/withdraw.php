<?php $title = '회원탈퇴'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="page">
    <h1 class="page-title">회원탈퇴</h1>

    <?php if (!empty($errors['general'])): ?>
        <p style="color:red;"><?= e($errors['general']) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors['csrf'])): ?>
        <p style="color:red;"><?= e($errors['csrf']) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors['confirm'])): ?>
        <p style="color:red;"><?= e($errors['confirm']) ?></p>
    <?php endif; ?>

    <section class="section">
        <h2>탈퇴 전 확인</h2>
        <ul class="list">
            <li>탈퇴 시 계정은 즉시 비활성화(is_active=0) 처리됩니다.</li>
            <li>추후 복구/완전 삭제 정책은 운영 정책에 따라 별도 진행됩니다.</li>
            <li>탈퇴 요청 관련 문의는 <a class="link" href="/contact">문의하기</a>를 이용하세요.</li>
        </ul>
    </section>

    <form method="post" action="/withdraw">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <label>
            <input type="checkbox" name="confirm_withdraw" value="1" required>
            안내 내용을 확인했으며 계정 비활성화에 동의합니다.
        </label>
        <div style="margin-top: 12px;">
            <button type="submit" class="btn-logout">회원탈퇴 진행</button>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
