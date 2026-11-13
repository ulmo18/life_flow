<?php $title = t('settings.title'); ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php
$notificationSettings = $notificationSettings ?? [];
$iconPaths = [
 'globe' => '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
 'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
 'moon' => '<path d="M20 15A9 9 0 0 1 9 4a9 9 0 1 0 11 11Z"/>',
 'bell' => '<path d="M6 9a6 6 0 0 1 12 0v5l2 3H4l2-3Zm4 12h4"/>',
 'review' => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 8h6m-6 4h6m-6 4h3"/>',
 'routine' => '<path d="M4 9a8 8 0 0 1 14-3l2 2m0-5v5h-5M20 15A8 8 0 0 1 6 18l-2-2m0 5v-5h5"/>',
 'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18"/>',
 'flag' => '<path d="M5 21V3c5-3 9 4 14 0v10c-5 4-9-3-14 0"/>',
 'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Zm0 4v10"/>',
 'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
 'logout' => '<path d="M10 4H4v16h6m4-12 4 4-4 4m-6-4h10"/>',
 'trash' => '<path d="M3 6h18M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7"/>',
];
$settingsIcon = static fn(string $name): string => '<svg class="settings-icon" viewBox="0 0 24 24" aria-hidden="true">' . ($iconPaths[$name] ?? $iconPaths['review']) . '</svg>';
$reminderRows = [
 ['retrospect_morning', 'morning_review', 'morning_help', 'review', '07:00'],
 ['retrospect_evening', 'evening_review', 'evening_help', 'moon', '20:00'],
 ['routine_reminder', 'routine_reminder', 'routine_help', 'routine', '14:00'],
 ['calendar_plan_reminder', 'plan_reminder', 'plan_help', 'calendar', null],
 ['goal_deadline_reminder', 'goal_deadline', 'goal_help', 'flag', '12:00'],
 ['goal_deadline_day_before', 'goal_day_before', 'day_before_help', 'flag', null],
];
?>
<main class="page settings-page" data-settings-page data-option-close="<?= e(t('common.close')) ?>" data-option-search="<?= e(t('settings.option_search')) ?>" data-option-empty="<?= e(t('settings.option_empty')) ?>" data-permission-sent="<?= e(t('settings.permission_sent')) ?>" data-test-sent="<?= e(t('settings.test_sent')) ?>" data-bridge-failed="<?= e(t('settings.bridge_failed')) ?>" data-status-unsaved="<?= e(t('settings.unsaved')) ?>" data-status-saved="<?= e(t('settings.saved_state')) ?>" data-app-only="<?= e(t('settings.app_only')) ?>" data-permission-unknown="<?= e(t('settings.permission_unknown')) ?>" data-theme-saving="<?= e(t('settings.saving')) ?>" data-theme-saved="<?= e(t('settings.auto_saved')) ?>">
    <h1 class="sr-only"><?= e(t('settings.title')) ?></h1>
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error" role="alert"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <details class="settings-section ui-disclosure" aria-labelledby="settingsBasicTitle" <?= !empty($hasLocalizationDraft) || !empty($errors['locale']) || !empty($errors['timezone']) ? 'open' : '' ?>><summary><span><strong id="settingsBasicTitle"><?= e(t('settings.basic')) ?></strong><small><?= e(t('settings.basic_help')) ?></small></span></summary><div class="settings-section-body">
        <form method="post" action="/settings/localization" class="settings-localization-form" data-settings-save-form data-settings-draft="<?= !empty($hasLocalizationDraft) ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <div class="settings-list">
                <div class="settings-select-row">
                    <?= $settingsIcon('globe') ?><label for="settingsLocale"><?= e(t('settings.language')) ?></label>
                    <select class="input" id="settingsLocale" name="locale" data-select-sheet <?= !empty($errors['locale']) ? 'aria-invalid="true" aria-describedby="settingsLocaleError"' : '' ?>>
                        <?php foreach (['ko'=>'한국어','en'=>'English','ja'=>'日本語'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= ($locale ?? 'ko') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($errors['locale'])): ?><p class="field-error settings-row-error" id="settingsLocaleError"><?= e((string) $errors['locale']) ?></p><?php endif; ?>
                <div class="settings-select-row">
                    <?= $settingsIcon('clock') ?><label for="settingsTimezone"><?= e(t('settings.timezone')) ?></label>
                    <select class="input" id="settingsTimezone" name="timezone" data-timezone-select data-select-sheet aria-describedby="settingsTimezoneHelp<?= !empty($errors['timezone']) ? ' settingsTimezoneError' : '' ?>" <?= !empty($errors['timezone']) ? 'aria-invalid="true"' : '' ?>>
                        <?php foreach (($timezoneOptions ?? []) as $option): ?><option value="<?= e((string) $option) ?>" <?= ($timezone ?? 'Asia/Seoul') === $option ? 'selected' : '' ?>><?= e((string) $option) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($errors['timezone'])): ?><p class="field-error settings-row-error" id="settingsTimezoneError"><?= e((string) $errors['timezone']) ?></p><?php endif; ?>
            </div>
            <p class="form-hint" id="settingsTimezoneHelp"><?= e(t('settings.timezone_help')) ?></p>
            <div class="settings-save"><span role="status" data-settings-save-status><?= e(t(!empty($hasLocalizationDraft) ? 'settings.unsaved' : 'settings.saved_state')) ?></span><button class="btn btn-primary" type="submit"><?= e(t('settings.save_preferences')) ?></button></div>
        </form>
    </div></details>

    <details class="settings-section ui-disclosure" aria-labelledby="settingsDisplayTitle" ><summary><span><strong id="settingsDisplayTitle"><?= e(t('settings.display')) ?></strong><small><?= e(t('settings.display_help')) ?></small></span></summary><div class="settings-section-body">
        <form method="post" action="/settings/theme" class="settings-theme-form" data-theme-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <div class="settings-theme-choices" role="group" aria-labelledby="settingsDisplayTitle">
                <?php foreach (['light'=>'sun','dark'=>'moon'] as $value=>$icon): ?>
                <label class="settings-theme-choice"><input class="sr-only" type="radio" name="theme" value="<?= e($value) ?>" <?= ($theme ?? 'light') === $value ? 'checked' : '' ?>><span class="ui-choice-button"><?= $settingsIcon($icon) ?><?= e(t('settings.' . $value)) ?></span></label>
                <?php endforeach; ?>
            </div>
            <p class="form-hint" role="status" data-theme-status><?= e(t('settings.auto_save_help')) ?></p>
            <noscript><button type="submit" class="btn btn-primary"><?= e(t('settings.display_save')) ?></button></noscript>
        </form>
    </div></details>

    <details class="settings-section ui-disclosure" aria-labelledby="notificationSettings" <?= !empty($hasNotificationDraft) ? 'open' : '' ?>><summary><span><strong id="notificationSettings"><?= e(t('settings.notifications')) ?></strong><small><?= e(t('settings.notification_help')) ?></small></span></summary><div class="settings-section-body">
        <div class="settings-permission settings-list">
            <?= $settingsIcon('bell') ?><div><strong><?= e(t('settings.permission_title')) ?></strong><small data-permission-status role="status"><?= e(t('settings.permission_unknown')) ?></small></div>
            <div class="settings-permission-actions"><button class="btn btn-secondary" type="button" data-notification-permission hidden><?= e(t('settings.permission')) ?></button><button class="btn btn-ghost" type="button" data-notification-test disabled><?= e(t('settings.test_notification')) ?></button></div>
        </div>
        <form method="post" action="/settings/notifications" class="settings-notification-form" data-settings-save-form data-settings-draft="<?= !empty($hasNotificationDraft) ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <div class="settings-list">
                <div class="settings-reminder-row settings-master-row">
                    <?= $settingsIcon('bell') ?><label class="settings-row-copy" for="notification_enabled"><strong><?= e(t('settings.push_enabled')) ?></strong></label>
                    <input class="settings-switch" id="notification_enabled" name="notification_enabled" type="checkbox" role="switch" value="1" <?= !empty($notificationSettings['notification_enabled']) ? 'checked' : '' ?>>
                </div>
                <p class="settings-master-hint" data-master-off hidden><?= e(t('settings.master_off')) ?></p>
                <?php foreach ($reminderRows as [$prefix,$labelKey,$helpKey,$icon,$defaultTime]):
                    $enabledField = $prefix . '_enabled';
                    $timeField = $prefix === 'goal_deadline_reminder' ? 'goal_deadline_time' : $prefix . '_time';
                ?>
                <div class="settings-reminder-row" data-settings-reminder>
                    <?= $settingsIcon($icon) ?><label class="settings-row-copy" for="<?= e($enabledField) ?>"><strong><?= e(t('settings.' . $labelKey)) ?></strong><small><?= e(t('settings.' . $helpKey)) ?></small></label>
                    <input class="settings-switch" id="<?= e($enabledField) ?>" name="<?= e($enabledField) ?>" type="checkbox" role="switch" value="1" <?= !empty($notificationSettings[$enabledField]) ? 'checked' : '' ?> data-reminder-enabled>
                    <?php if ($defaultTime !== null): ?>
                    <div class="settings-time-control"><?= $settingsIcon('clock') ?><label class="sr-only" for="<?= e($timeField) ?>"><?= e(t('settings.' . $labelKey)) ?> <?= e(t('settings.time_label')) ?></label><input class="input" id="<?= e($timeField) ?>" type="time" name="<?= e($timeField) ?>" value="<?= e((string) ($notificationSettings[$timeField] ?? $defaultTime)) ?>" data-reminder-time data-time-picker data-time-label="<?= e(t('settings.' . $labelKey)) ?>" <?= !empty($errors[$timeField]) ? 'aria-invalid="true" aria-describedby="' . e($timeField) . '_error"' : '' ?>></div>
                    <?php if (!empty($errors[$timeField])): ?><p class="field-error settings-reminder-error" id="<?= e($timeField) ?>_error"><?= e((string) $errors[$timeField]) ?></p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="settings-save"><span role="status" data-settings-save-status><?= e(t(!empty($hasNotificationDraft) ? 'settings.unsaved' : 'settings.saved_state')) ?></span><button type="submit" class="btn btn-primary"><?= e(t('settings.notification_save')) ?></button></div>
        </form>
        <a class="settings-guide" href="/notification-guide"><?= $settingsIcon('review') ?><span><?= e(t('settings.notification_guide')) ?></span><span aria-hidden="true">›</span></a>
    </div></details>

    <details class="settings-section ui-disclosure" aria-labelledby="settingsSupportTitle" ><summary><span><strong id="settingsSupportTitle"><?= e(t('settings.support')) ?></strong><small><?= e(t('settings.support_help')) ?></small></span></summary><div class="settings-section-body">
        <div class="settings-list">
            <?php foreach ([['/privacy-policy','shield','privacy'],['/terms','review','terms'],['/contact','mail','contact']] as [$href,$icon,$label]): ?><a class="settings-action-row" href="<?= e($href) ?>"><?= $settingsIcon($icon) ?><span><?= e(t('settings.' . $label)) ?></span><span aria-hidden="true">›</span></a><?php endforeach; ?>
        </div>
    </div></details>
    <details class="settings-section ui-disclosure" aria-labelledby="settingsAccountTitle" ><summary><span><strong id="settingsAccountTitle"><?= e(t('settings.account_title')) ?></strong><small><?= e(t('settings.account_help')) ?></small></span></summary><div class="settings-section-body">
        <div class="settings-list">
            <form method="post" action="/logout"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><button class="settings-action-row" type="submit"><?= $settingsIcon('logout') ?><span><?= e(t('settings.logout')) ?></span><span aria-hidden="true">›</span></button></form>
            <a class="settings-action-row settings-danger" href="/withdraw"><?= $settingsIcon('trash') ?><span><?= e(t('settings.withdraw')) ?></span><span aria-hidden="true">›</span></a>
        </div>
    </div></details>
</main>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
