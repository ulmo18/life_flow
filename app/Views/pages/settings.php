<?php $title = '설정'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title">설정</h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="section">
        <h2>화면 모드</h2>
        <form class="settings-theme-form" method="post" action="/settings/theme" data-theme-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="settings-theme-option">
                <input type="radio" name="theme" value="light" <?= ($theme ?? 'light') === 'light' ? 'checked' : '' ?>>
                <span>라이트 모드</span>
            </label>
            <label class="settings-theme-option">
                <input type="radio" name="theme" value="dark" <?= ($theme ?? 'light') === 'dark' ? 'checked' : '' ?>>
                <span>다크 모드</span>
            </label>
            <noscript><button type="submit" class="btn btn-primary">화면 모드 저장</button></noscript>
        </form>
    </section>

    <section class="section">
        <h2>알림 설정</h2>
        <?php $notificationSettings = $notificationSettings ?? []; ?>
        <div class="settings-notification-actions">
            <button type="button" class="btn btn-secondary" data-notification-permission>앱 알림 권한 요청</button>
            <button type="button" class="btn btn-ghost" data-notification-test>테스트 알림 보내기</button>
        </div>
        <form class="settings-notification-form" method="post" action="/settings/notifications">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">

            <label class="settings-toggle-option">
                <input type="checkbox" name="notification_enabled" value="1" <?= !empty($notificationSettings['notification_enabled']) ? 'checked' : '' ?>>
                <span>앱 푸시 알림 사용</span>
            </label>

            <div class="settings-notification-grid">
                <label class="settings-time-option">
                    <span>아침 회고 알림</span>
                    <input type="checkbox" name="retrospect_morning_enabled" value="1" <?= !empty($notificationSettings['retrospect_morning_enabled']) ? 'checked' : '' ?>>
                    <input class="input" name="retrospect_morning_time" type="time" value="<?= e((string) ($notificationSettings['retrospect_morning_time'] ?? '07:00')) ?>">
                </label>
                <?php if (!empty($errors['retrospect_morning_time'])): ?>
                    <p class="field-error"><?= e((string) $errors['retrospect_morning_time']) ?></p>
                <?php endif; ?>

                <label class="settings-time-option">
                    <span>저녁 회고 알림</span>
                    <input type="checkbox" name="retrospect_evening_enabled" value="1" <?= !empty($notificationSettings['retrospect_evening_enabled']) ? 'checked' : '' ?>>
                    <input class="input" name="retrospect_evening_time" type="time" value="<?= e((string) ($notificationSettings['retrospect_evening_time'] ?? '20:00')) ?>">
                </label>
                <?php if (!empty($errors['retrospect_evening_time'])): ?>
                    <p class="field-error"><?= e((string) $errors['retrospect_evening_time']) ?></p>
                <?php endif; ?>

                <label class="settings-time-option">
                    <span>선택 루틴 알림</span>
                    <input type="checkbox" name="routine_reminder_enabled" value="1" <?= !empty($notificationSettings['routine_reminder_enabled']) ? 'checked' : '' ?>>
                    <input class="input" name="routine_reminder_time" type="time" value="<?= e((string) ($notificationSettings['routine_reminder_time'] ?? '14:00')) ?>">
                </label>
                <?php if (!empty($errors['routine_reminder_time'])): ?>
                    <p class="field-error"><?= e((string) $errors['routine_reminder_time']) ?></p>
                <?php endif; ?>

                <label class="settings-toggle-option">
                    <input type="checkbox" name="calendar_plan_reminder_enabled" value="1" <?= !empty($notificationSettings['calendar_plan_reminder_enabled']) ? 'checked' : '' ?>>
                    <span>캘린더 선택 계획 시작 알림</span>
                </label>

                <label class="settings-time-option">
                    <span>목표 마감 알림</span>
                    <input type="checkbox" name="goal_deadline_reminder_enabled" value="1" <?= !empty($notificationSettings['goal_deadline_reminder_enabled']) ? 'checked' : '' ?>>
                    <input class="input" name="goal_deadline_time" type="time" value="<?= e((string) ($notificationSettings['goal_deadline_time'] ?? '12:00')) ?>">
                </label>
                <?php if (!empty($errors['goal_deadline_time'])): ?>
                    <p class="field-error"><?= e((string) $errors['goal_deadline_time']) ?></p>
                <?php endif; ?>

                <label class="settings-toggle-option">
                    <input type="checkbox" name="goal_deadline_day_before_enabled" value="1" <?= !empty($notificationSettings['goal_deadline_day_before_enabled']) ? 'checked' : '' ?>>
                    <span>목표 마감 하루 전 알림</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">알림 설정 저장</button>
        </form>
        <p><a class="link" href="/notification-guide">알림 권한 안내 보기</a></p>
    </section>

    <section class="section">
        <h2>정책 및 계정 관리</h2>
        <ul class="list">
            <li><a class="link" href="/privacy-policy">개인정보처리방침</a></li>
            <li><a class="link" href="/terms">이용약관</a></li>
            <li><a class="link" href="/contact">문의하기</a></li>
            <li>
                <form class="settings-logout-form" method="post" action="/logout">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <button type="submit" class="btn btn-ghost">로그아웃</button>
                </form>
            </li>
            <li><a class="link" href="/withdraw">회원탈퇴</a></li>
        </ul>
    </section>
</main>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
