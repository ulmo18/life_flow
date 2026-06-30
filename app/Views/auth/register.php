<?php
$title = '회원가입';
$hideNav = true;
require __DIR__ . '/../layouts/header.php';
?>

<main class="auth-page">
    <section class="auth-card" aria-labelledby="register-title">
        <h2 id="register-title" class="auth-title">회원가입</h2>
        <p class="auth-subtitle">
            <?= !empty($googleRegistration)
                ? 'Google 계정 정보를 확인하고 가입을 완료해 주세요.'
                : '기본 계정을 만들고 대시보드를 시작하세요.' ?>
        </p>

        <?php if (!empty($errors['general'])): ?>
            <p class="msg msg-error" role="alert">⚠️ <?= e($errors['general']) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['csrf'])): ?>
            <p class="msg msg-error" role="alert">⚠️ <?= e($errors['csrf']) ?></p>
        <?php endif; ?>

        <form class="form" method="post" action="/register" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="nickname">닉네임</label>
                <input id="nickname" class="input<?= !empty($errors['nickname']) ? ' is-error' : '' ?>" type="text" name="nickname" required maxlength="50" placeholder="표시할 이름" value="<?= e((string) ($old['nickname'] ?? '')) ?>">
                <?php if (!empty($errors['nickname'])): ?>
                    <p class="field-error">⚠️ <?= e($errors['nickname']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">이메일</label>
                <input id="email" class="input<?= !empty($errors['email']) ? ' is-error' : '' ?>" type="email" name="email" required autocomplete="email" placeholder="name@company.com" value="<?= e((string) ($old['email'] ?? '')) ?>"<?= !empty($googleRegistration) ? ' readonly' : '' ?>>
                <?php if (!empty($errors['email'])): ?>
                    <p class="field-error">⚠️ <?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (empty($googleRegistration)): ?>
                <div class="form-group">
                    <label class="form-label" for="password">비밀번호</label>
                    <input id="password" class="input<?= !empty($errors['password']) ? ' is-error' : '' ?>" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="최소 8자">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="field-error">⚠️ <?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">비밀번호 확인</label>
                    <input id="password_confirm" class="input<?= !empty($errors['password_confirm']) ? ' is-error' : '' ?>" type="password" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="비밀번호 다시 입력">
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <p class="field-error">⚠️ <?= e($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <div class="checkbox-row">
                    <input id="terms_agreed" type="checkbox" name="terms_agreed" value="1" required<?= !empty($old['terms_agreed']) ? ' checked' : '' ?>>
                    <label class="checkbox-label" for="terms_agreed">
                        <a class="link" href="/terms" target="_blank" rel="noopener noreferrer">회원가입 약관</a>에 동의합니다. (필수)
                    </label>
                </div>
                <?php if (!empty($errors['terms_agreed'])): ?>
                    <p class="field-error">⚠️ <?= e($errors['terms_agreed']) ?></p>
                <?php endif; ?>

                <div class="checkbox-row">
                    <input id="privacy_agreed" type="checkbox" name="privacy_agreed" value="1"<?= !empty($old['privacy_agreed']) ? ' checked' : '' ?>>
                    <label class="checkbox-label" for="privacy_agreed">
                        <a class="link" href="/privacy-policy" target="_blank" rel="noopener noreferrer">개인정보 수집 및 이용</a>에 동의합니다. (선택)
                    </label>
                </div>

                <p class="helper-text">
                    <?= !empty($googleRegistration)
                        ? '동의 후 Google 계정으로 바로 로그인됩니다.'
                        : '가입 완료 후 로그인 페이지로 이동합니다. (자동 로그인 미적용)' ?>
                </p>
            </div>

            <button type="submit" class="btn btn-primary">회원가입</button>
        </form>

        <?php if (empty($googleRegistration)): ?>
            <div class="auth-divider" aria-hidden="true"><span>또는</span></div>

            <?php if (!empty($googleConfigured)): ?>
                <a class="btn btn-social" href="/auth/google/login.php" role="button">
                    <span class="social-icon" aria-hidden="true">G</span>
                    <span>Google로 시작하기</span>
                </a>
            <?php else: ?>
                <button class="btn btn-social" type="button" disabled>Google 회원가입 (설정 필요)</button>
            <?php endif; ?>
        <?php endif; ?>

        <p class="auth-link-text">이미 계정이 있으신가요? <a class="link" href="/login">로그인</a></p>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
