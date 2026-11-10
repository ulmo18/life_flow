<?php
/** @var string $activityFieldPrefix */
/** @var array<int, array<string, mixed>> $activityGoalOptions */
?>
<div class="form-group">
    <label class="form-label" for="<?= e($activityFieldPrefix) ?>Title">일정명</label>
    <input class="input" id="<?= e($activityFieldPrefix) ?>Title" name="title" type="text" maxlength="80" placeholder="<?= e($activityPlaceholder ?? '오늘 할 일을 입력하세요') ?>" autocomplete="off" required>
</div>
<fieldset class="calendar-activity-importance">
    <legend>중요도</legend>
    <?php foreach (['A', 'B', 'C', 'D'] as $importance): ?>
        <label><input type="radio" name="importance" value="<?= e($importance) ?>" <?= $importance === 'D' ? 'checked' : '' ?>><span><?= e($importance) ?></span></label>
    <?php endforeach; ?>
</fieldset>
<div class="form-group">
    <label class="form-label" for="<?= e($activityFieldPrefix) ?>Goal">목표 연결</label>
    <select class="input" id="<?= e($activityFieldPrefix) ?>Goal" name="goal_id">
        <option value="">연결하지 않음</option>
        <?php foreach ($activityGoalOptions as $goal): ?>
            <option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<input type="hidden" name="schedule_type" value="unscheduled">
<p class="form-hint"><?= e($activityHelp ?? '시간이 없는 오늘의 계획 일정으로 등록됩니다.') ?></p>
