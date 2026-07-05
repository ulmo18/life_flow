<?php $title = '대시보드'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title">대시보드</h1>

    <section class="section dashboard-hero">
        <div>
            <p class="eyebrow">오늘의 첫 화면</p>
            <h2>캘린더에서 하루 흐름을 확인하세요</h2>
            <p class="muted">현재는 캘린더 테스트 기능으로 이동하며, 추후 계획/실제 기록 요약이 이곳에 표시됩니다.</p>
        </div>
        <a class="btn btn-primary dashboard-action" href="/calendar">캘린더 열기</a>
    </section>

    <section class="section">
        <h2>빠른 메뉴</h2>
        <div class="card-grid">
            <a class="placeholder-card dashboard-menu-card" href="/calendar">
                <strong>캘린더</strong>
                <span class="muted">10분 단위 일정 테스트</span>
            </a>
            <a class="placeholder-card dashboard-menu-card" href="/settings">
                <strong>설정</strong>
                <span class="muted">계정 및 알림 설정</span>
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
