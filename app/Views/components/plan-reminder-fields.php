<details class="plan-reminder ui-disclosure" data-plan-reminder data-clock-display="<?= e($reminderClockDisplay ?? 'full') ?>" data-block-id="<?= e((string) ($planReminder['blockId'] ?? 0)) ?>" data-supported="<?= !empty($planReminder['supported']) ? '1' : '0' ?>" data-reminder-timing="<?= e(json_encode($planReminder['timing'] ?? [], JSON_UNESCAPED_UNICODE)) ?>" data-master-enabled="<?= !empty($notificationSettings['notification_enabled']) ? '1' : '0' ?>" data-calendar-enabled="<?= !empty($notificationSettings['calendar_plan_reminder_enabled']) ? '1' : '0' ?>">
    <summary class="plan-reminder-summary"><span class="plan-reminder-label"><?php if (!empty($reminderWithIcon)): ?><span class="calendar-plan-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span><?php endif; ?>알림 설정</span><span data-reminder-summary><?= empty($notificationSettings['notification_enabled']) || empty($notificationSettings['calendar_plan_reminder_enabled']) ? '전역 알림 꺼짐' : (empty($planReminder['enabled']) ? '꺼짐' : ((int) ($planReminder['minutesBefore'] ?? 5) === 0 ? '시작할 때' : (int) ($planReminder['minutesBefore'] ?? 5) . '분 전')) ?></span></summary>
    <p class="form-hint" data-reminder-clock aria-live="polite"></p>
    <input type="hidden" data-reminder-csrf value="<?= e((string) $csrfToken) ?>">
    <input type="checkbox" hidden data-reminder-enabled <?= !empty($planReminder['enabled']) ? 'checked' : '' ?> aria-label="이 계획 블록 알림">
    <input type="hidden" data-reminder-minutes value="<?= e((string) ($planReminder['minutesBefore'] ?? 5)) ?>">
    <div class="plan-reminder-gates" data-reminder-gates <?= !empty($notificationSettings['notification_enabled']) && !empty($notificationSettings['calendar_plan_reminder_enabled']) ? 'hidden' : '' ?>>
        <span>전체 알림 <?= !empty($notificationSettings['notification_enabled']) ? '켜짐' : '꺼짐' ?> · 계획 알림 <?= !empty($notificationSettings['calendar_plan_reminder_enabled']) ? '켜짐' : '꺼짐' ?></span>
        <a href="/settings#notificationSettings">알림 설정으로 이동 ↗</a>
    </div>
    <div data-reminder-options <?= empty($notificationSettings['notification_enabled']) || empty($notificationSettings['calendar_plan_reminder_enabled']) ? 'hidden' : '' ?>>
        <div class="plan-reminder-choices" role="group" aria-label="알림 시점">
            <button type="button" class="ui-choice-button" data-reminder-choice="off" aria-pressed="<?= empty($planReminder['enabled']) ? 'true' : 'false' ?>">끄기</button>
            <?php foreach ($reminderMinuteOptions as $minutes): ?>
                <button type="button" class="ui-choice-button" data-reminder-choice="<?= $minutes ?>" aria-pressed="<?= !empty($planReminder['enabled']) && (int) ($planReminder['minutesBefore'] ?? 5) === $minutes ? 'true' : 'false' ?>"><?= $minutes === 0 ? '시작할 때' : $minutes . '분 전' ?></button>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary" data-plan-reminder-save <?= empty($planReminder['supported']) ? 'disabled' : '' ?>>알림 저장</button>
    </div>
    <p class="form-hint" data-reminder-message role="status" aria-live="polite"><?= empty($planReminder['supported']) ? '현재 이 계획의 알림을 설정할 수 없습니다.' : '' ?></p>
</details>
