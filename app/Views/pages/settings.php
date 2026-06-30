<?php $title = '설정'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title">설정</h1>

    <section class="section">
        <h2>계정 정보</h2>
        <ul class="list">
            <li>사용자 ID: <?= e((string) ($_SESSION['user_id'] ?? '')) ?></li>
            <li class="muted">이메일/닉네임 상세는 추후 연동 예정</li>
        </ul>
    </section>

    <section class="section">
        <h2>알림 설정</h2>
        <p class="muted">푸시 알림 토글 및 권한 상태가 이곳에 표시될 예정입니다.</p>
        <p><a class="link" href="/notification-guide">알림 권한 안내 보기</a></p>
    </section>

    <section class="section">
        <h2>정책 및 계정 관리</h2>
        <ul class="list">
            <li><a class="link" href="/privacy-policy">개인정보처리방침</a></li>
            <li><a class="link" href="/terms">이용약관</a></li>
            <li><a class="link" href="/contact">문의하기</a></li>
            <li><a class="link" href="/withdraw">회원탈퇴</a></li>
        </ul>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
