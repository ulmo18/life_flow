<?php
$title = '로그인';
$hideNav = true;
require __DIR__ . '/../layouts/header.php';
?>

<main class="auth-page">
    <section class="auth-card" aria-labelledby="login-title">
        <h2 id="login-title" class="auth-title">로그인</h2>
        <p class="auth-subtitle">LifeFlow에 다시 오신 것을 환영합니다.</p>

        <?php if (!empty($flashSuccess)): ?>
            <p class="msg msg-success" role="status">✅ <?= e((string) $flashSuccess) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <p class="msg msg-error" role="alert">⚠️ <?= e($errors['general']) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['csrf'])): ?>
            <p class="msg msg-error" role="alert">⚠️ <?= e($errors['csrf']) ?></p>
        <?php endif; ?>

        <form class="form" method="post" action="/login" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label" for="email">이메일</label>
                <input
                    id="email"
                    class="input<?= !empty($errors['email']) ? ' is-error' : '' ?>"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="name@company.com"
                    value="<?= e((string) ($old['email'] ?? '')) ?>"
                    aria-invalid="<?= !empty($errors['email']) ? 'true' : 'false' ?>"
                    aria-describedby="email-error"
                >
                <?php if (!empty($errors['email'])): ?>
                    <p class="field-error" id="email-error">⚠️ <?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">비밀번호</label>
                <input
                    id="password"
                    class="input<?= !empty($errors['password']) ? ' is-error' : '' ?>"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="비밀번호 입력"
                    aria-invalid="<?= !empty($errors['password']) ? 'true' : 'false' ?>"
                    aria-describedby="password-error"
                >
                <?php if (!empty($errors['password'])): ?>
                    <p class="field-error" id="password-error">⚠️ <?= e($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="checkbox-row">
                <input id="remember_me" type="checkbox" name="remember_me" value="1">
                <label class="checkbox-label" for="remember_me">로그인 유지</label>
            </div>

            <button type="submit" class="btn btn-primary">로그인</button>
        </form>

        <div class="auth-divider" aria-hidden="true"><span>또는</span></div>

        <div class="social-login-stack">
            <?php if (($recentLoginMethod ?? null) === 'google'): ?>
                <div class="social-login-bubble" role="status" aria-live="polite">
                    최근에 로그인했어요
                </div>
            <?php endif; ?>

            <?php if (!empty($googleConfigured)): ?>
                <a class="btn btn-social" href="/auth/google/login.php" role="button">
                    <span class="social-icon" aria-hidden="true">G</span>
                    <span>Google로 로그인</span>
                </a>
            <?php else: ?>
                <button class="btn btn-social" type="button" disabled>Google 로그인 (설정 필요)</button>
            <?php endif; ?>
        </div>

        <p class="auth-link-text">계정이 없으신가요? <a class="link" href="/register">회원가입</a></p>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
