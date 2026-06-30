<?php $title = '개인정보처리방침'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="page">
    <h1 class="page-title">개인정보처리방침</h1>

    <section class="section">
        <h2>1. 수집하는 개인정보 항목</h2>
        <ul class="list">
            <li>회원 식별 정보: 이메일, 닉네임</li>
            <li>인증 정보: 암호화된 비밀번호 해시</li>
            <li>접속/보안 정보: 로그인 시도 이력(IP, 시각, 성공/실패 여부)</li>
            <li>알림 관련 정보(선택): 웹 푸시 알림 토큰 및 디바이스 유형</li>
        </ul>
    </section>

    <section class="section">
        <h2>2. 개인정보 이용 목적</h2>
        <p class="muted">회원 인증, 서비스 제공, 보안 모니터링, 알림 제공(동의 시)을 위해 개인정보를 이용합니다.</p>
    </section>

    <section class="section">
        <h2>3. 보관 및 파기</h2>
        <p class="muted">관계 법령 또는 내부 정책에 따라 필요한 기간 동안 보관하며, 탈퇴 요청 시 지체 없이 비활성화 및 파기 절차를 진행합니다.</p>
    </section>

    <section class="section">
        <h2>4. 이용자 권리</h2>
        <p class="muted">이용자는 언제든지 열람, 정정, 삭제(탈퇴) 요청을 할 수 있으며, 문의하기 페이지를 통해 요청할 수 있습니다.</p>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
