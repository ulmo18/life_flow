<?php $title = '대시보드'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title">대시보드</h1>

    <section class="section dashboard-hero">
        <div>
            <p class="eyebrow">오늘의 첫 화면</p>
            <h2>일정에서 하루 흐름을 확인하세요</h2>
            <p class="muted">날짜별 계획과 실제 기록을 일정에서 함께 확인할 수 있습니다.</p>
        </div>
        <div class="action-row">
            <a class="btn btn-secondary dashboard-action" href="/prototype/day-flow/">하루흐름 통합 시제품으로 이동</a>
            <a class="btn btn-primary dashboard-action" href="/calendar">일정 열기</a>
            <a class="btn btn-secondary dashboard-action" href="/prototype/timeblock-planner/">타임블로킹 시제품</a>
            <a class="btn btn-secondary dashboard-action" href="/prototype/actual-timeblock-planner/">실제 일정 기록 시제품</a>
        </div>
    </section>

    <section class="section">
        <h2>빠른 메뉴</h2>
        <div class="card-grid">
            <a class="placeholder-card dashboard-menu-card" href="/calendar">
                <strong>일정</strong>
                <span class="muted">날짜별 계획과 실제 기록</span>
            </a>
            <a class="placeholder-card dashboard-menu-card" href="/settings">
                <strong>설정</strong>
                <span class="muted">계정 및 알림 설정</span>
            </a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
