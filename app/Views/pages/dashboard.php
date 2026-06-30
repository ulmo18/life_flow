<?php $title = '대시보드'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title">환영합니다 👋</h1>
    <section class="section">
        <p>LifeFlow에 로그인하셨습니다.</p>
        <p class="muted">추후 이곳에 개인화된 핵심 콘텐츠가 표시됩니다.</p>
    </section>

    <section class="section">
        <h2>콘텐츠 영역 (Placeholder)</h2>
        <div class="card-grid">
            <article class="placeholder-card">카드 Placeholder 1</article>
            <article class="placeholder-card">카드 Placeholder 2</article>
            <article class="placeholder-card">카드 Placeholder 3</article>
        </div>
    </section>

    <section class="section">
        <h2>알림 권한 안내</h2>
        <p class="muted">알림 권한 흐름은 설정 또는 아래 링크에서 확인할 수 있습니다.</p>
        <p><a class="link" href="/notification-guide">알림 권한 안내로 이동</a></p>
    </section>

    <form method="post" action="/logout">
        <input type="hidden" name="_csrf_token" value="<?= e(\App\Core\Csrf::token()) ?>">
        <button type="submit" class="btn btn-ghost">로그아웃</button>
    </form>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
