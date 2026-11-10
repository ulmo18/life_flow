<?php $title = t('settings.title'); ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page">
    <h1 class="page-title"><?= e(t('settings.title')) ?></h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="section">
        <h2><?= e(t('settings.language')) ?></h2>
        <p class="muted"><?= e(t('settings.language_help')) ?></p>
        <form class="settings-localization-form form" method="post" action="/settings/localization">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <div class="form-group">
            <label class="form-label" for="settingsLocale"><?= e(t('settings.language')) ?></label>
            <select class="input" id="settingsLocale" name="locale" <?= !empty($errors['locale']) ? 'aria-invalid="true" aria-describedby="settingsLocaleError"' : '' ?>>
                <option value="ko" <?= ($locale ?? 'ko') === 'ko' ? 'selected' : '' ?>>한국어</option>
                <option value="en" <?= ($locale ?? 'ko') === 'en' ? 'selected' : '' ?>>English</option>
                <option value="ja" <?= ($locale ?? 'ko') === 'ja' ? 'selected' : '' ?>>日本語</option>
            </select>
            <?php if (!empty($errors['locale'])): ?>
                <p class="field-error" id="settingsLocaleError"><?= e((string) $errors['locale']) ?></p>
            <?php endif; ?>
            </div>

            <h3><?= e(t('settings.region')) ?></h3>
            <div class="form-group">
            <label class="form-label" for="settingsTimezone"><?= e(t('settings.timezone')) ?></label>
            <select class="input" id="settingsTimezone" name="timezone" data-timezone-select aria-describedby="settingsTimezoneHelp<?= !empty($errors['timezone']) ? ' settingsTimezoneError' : '' ?>" <?= !empty($errors['timezone']) ? 'aria-invalid="true"' : '' ?>>
                <?php foreach (($timezoneOptions ?? []) as $timezoneOption): ?>
                    <option value="<?= e((string) $timezoneOption) ?>" <?= ($timezone ?? 'Asia/Seoul') === $timezoneOption ? 'selected' : '' ?>><?= e((string) $timezoneOption) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="form-hint" id="settingsTimezoneHelp"><?= e(t('settings.timezone_help')) ?></p>
            <?php if (!empty($errors['timezone'])): ?>
                <p class="field-error" id="settingsTimezoneError"><?= e((string) $errors['timezone']) ?></p>
            <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary"><?= e(t('settings.save_preferences')) ?></button>
        </form>
    </section>

    <section class="section">
        <h2><?= e(t('settings.display')) ?></h2>
        <form class="settings-theme-form" method="post" action="/settings/theme" data-theme-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="settings-theme-option">
                <input type="radio" name="theme" value="light" <?= ($theme ?? 'light') === 'light' ? 'checked' : '' ?>>
                <span><?= e(t('settings.light')) ?></span>
            </label>
            <label class="settings-theme-option">
                <input type="radio" name="theme" value="dark" <?= ($theme ?? 'light') === 'dark' ? 'checked' : '' ?>>
                <span><?= e(t('settings.dark')) ?></span>
            </label>
            <noscript><button type="submit" class="btn btn-primary"><?= e(t('settings.display_save')) ?></button></noscript>
        </form>
    </section>

    <section class="section">
        <h2 id="notificationSettings"><?= e(t('settings.notifications')) ?></h2>
        <?php $notificationSettings = $notificationSettings ?? []; ?>
        <div class="settings-notification-actions">
            <button type="button" class="btn btn-secondary" data-notification-permission><?= e(t('settings.permission')) ?></button>
            <button type="button" class="btn btn-ghost" data-notification-test><?= e(t('settings.test_notification')) ?></button>
        </div>
        <form class="settings-notification-form" method="post" action="/settings/notifications">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">

            <label class="settings-toggle-option">
                <input type="checkbox" name="notification_enabled" value="1" <?= !empty($notificationSettings['notification_enabled']) ? 'checked' : '' ?>>
                <span><?= e(t('settings.push_enabled')) ?></span>
            </label>

            <div class="settings-notification-grid">
                <div class="settings-time-option">
                    <label class="settings-time-toggle"><span id="morningReminderLabel"><?= e(t('settings.morning_review')) ?></span>
                    <input type="checkbox" name="retrospect_morning_enabled" value="1" <?= !empty($notificationSettings['retrospect_morning_enabled']) ? 'checked' : '' ?>>
                    </label>
                    <input class="input" name="retrospect_morning_time" type="time" value="<?= e((string) ($notificationSettings['retrospect_morning_time'] ?? '07:00')) ?>" aria-labelledby="morningReminderLabel" <?= !empty($errors['retrospect_morning_time']) ? 'aria-invalid="true" aria-describedby="morningReminderError"' : '' ?>>
                <?php if (!empty($errors['retrospect_morning_time'])): ?>
                    <p class="field-error" id="morningReminderError"><?= e((string) $errors['retrospect_morning_time']) ?></p>
                <?php endif; ?>
                </div>

                <div class="settings-time-option">
                    <label class="settings-time-toggle"><span id="eveningReminderLabel"><?= e(t('settings.evening_review')) ?></span>
                    <input type="checkbox" name="retrospect_evening_enabled" value="1" <?= !empty($notificationSettings['retrospect_evening_enabled']) ? 'checked' : '' ?>>
                    </label>
                    <input class="input" name="retrospect_evening_time" type="time" value="<?= e((string) ($notificationSettings['retrospect_evening_time'] ?? '20:00')) ?>" aria-labelledby="eveningReminderLabel" <?= !empty($errors['retrospect_evening_time']) ? 'aria-invalid="true" aria-describedby="eveningReminderError"' : '' ?>>
                <?php if (!empty($errors['retrospect_evening_time'])): ?>
                    <p class="field-error" id="eveningReminderError"><?= e((string) $errors['retrospect_evening_time']) ?></p>
                <?php endif; ?>
                </div>

                <div class="settings-time-option">
                    <label class="settings-time-toggle"><span id="routineReminderLabel"><?= e(t('settings.routine_reminder')) ?></span>
                    <input type="checkbox" name="routine_reminder_enabled" value="1" <?= !empty($notificationSettings['routine_reminder_enabled']) ? 'checked' : '' ?>>
                    </label>
                    <input class="input" name="routine_reminder_time" type="time" value="<?= e((string) ($notificationSettings['routine_reminder_time'] ?? '14:00')) ?>" aria-labelledby="routineReminderLabel" <?= !empty($errors['routine_reminder_time']) ? 'aria-invalid="true" aria-describedby="routineReminderError"' : '' ?>>
                <?php if (!empty($errors['routine_reminder_time'])): ?>
                    <p class="field-error" id="routineReminderError"><?= e((string) $errors['routine_reminder_time']) ?></p>
                <?php endif; ?>
                </div>

                <label class="settings-toggle-option">
                    <input type="checkbox" name="calendar_plan_reminder_enabled" value="1" <?= !empty($notificationSettings['calendar_plan_reminder_enabled']) ? 'checked' : '' ?>>
                    <span><?= e(t('settings.plan_reminder')) ?></span>
                </label>

                <div class="settings-time-option">
                    <label class="settings-time-toggle"><span id="goalReminderLabel"><?= e(t('settings.goal_deadline')) ?></span>
                    <input type="checkbox" name="goal_deadline_reminder_enabled" value="1" <?= !empty($notificationSettings['goal_deadline_reminder_enabled']) ? 'checked' : '' ?>>
                    </label>
                    <input class="input" name="goal_deadline_time" type="time" value="<?= e((string) ($notificationSettings['goal_deadline_time'] ?? '12:00')) ?>" aria-labelledby="goalReminderLabel" <?= !empty($errors['goal_deadline_time']) ? 'aria-invalid="true" aria-describedby="goalReminderError"' : '' ?>>
                <?php if (!empty($errors['goal_deadline_time'])): ?>
                    <p class="field-error" id="goalReminderError"><?= e((string) $errors['goal_deadline_time']) ?></p>
                <?php endif; ?>
                </div>

                <label class="settings-toggle-option">
                    <input type="checkbox" name="goal_deadline_day_before_enabled" value="1" <?= !empty($notificationSettings['goal_deadline_day_before_enabled']) ? 'checked' : '' ?>>
                    <span><?= e(t('settings.goal_day_before')) ?></span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary"><?= e(t('settings.notification_save')) ?></button>
        </form>
        <p><a class="link" href="/notification-guide"><?= e(t('settings.notification_guide')) ?></a></p>
    </section>

    <section class="section">
        <h2><?= e(t('settings.account')) ?></h2>
        <ul class="list">
            <li><a class="link" href="/privacy-policy"><?= e(t('settings.privacy')) ?></a></li>
            <li><a class="link" href="/terms"><?= e(t('settings.terms')) ?></a></li>
            <li><a class="link" href="/contact"><?= e(t('settings.contact')) ?></a></li>
            <li>
                <form class="settings-logout-form" method="post" action="/logout">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <button type="submit" class="btn btn-ghost"><?= e(t('settings.logout')) ?></button>
                </form>
            </li>
            <li><a class="link" href="/withdraw"><?= e(t('settings.withdraw')) ?></a></li>
        </ul>
    </section>
</main>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
