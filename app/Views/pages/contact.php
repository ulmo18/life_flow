<?php $title = '문의하기'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="page">
    <h1 class="page-title">문의하기</h1>

    <section class="section">
        <h2>문의 채널</h2>
        <p>서비스 이용 중 불편사항, 개인정보 관련 요청, 계정 삭제 요청은 아래 이메일로 접수해 주세요.</p>
        <p><a class="link" href="mailto:<?= e($supportEmail) ?>"><?= e($supportEmail) ?></a></p>
    </section>

    <section class="section">
        <h2>처리 안내</h2>
        <ul class="list">
            <li>일반 문의: 영업일 기준 3일 이내 회신</li>
            <li>개인정보/탈퇴 요청: 본인 확인 후 순차 처리</li>
        </ul>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
