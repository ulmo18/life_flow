<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page plan-page">
    <section class="section">
        <p class="eyebrow">Plan</p>
        <h1 class="page-title">Plan 기능을 사용할 수 없습니다.</h1>
        <p class="muted">
            Plan 기능은 MySQL 환경에서만 사용할 수 있습니다.
            현재 DB 드라이버가 sqlite로 설정되어 있다면 `DB_CONNECTION=mysql`과 MySQL 접속 정보를 먼저 설정해주세요.
        </p>
    </section>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
