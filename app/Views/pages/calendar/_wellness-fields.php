<?php
$wellnessFieldPrefix = (string) ($wellnessFieldPrefix ?? 'calendarWellness');
$wellnessTypes = $calendar['wellnessTypes'] ?? [];
?>
<fieldset class="calendar-wellness-fields" data-wellness-fields>
    <legend class="sr-only">생활 기록 종류와 상세 내용</legend>
    <div class="calendar-wellness-types">
        <label><input type="radio" name="wellness_type" value="" data-summary-label="기록 안 함" checked><span>기록 안 함</span></label>
        <?php foreach ($wellnessTypes as $type => $meta): ?>
            <label>
                <input type="radio" name="wellness_type" value="<?= e((string) $type) ?>" data-summary-label="<?= e((string) $meta['icon']) ?> <?= e((string) $meta['label']) ?>">
                <span><i aria-hidden="true"><?= e((string) $meta['icon']) ?></i><?= e((string) $meta['label']) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="calendar-wellness-details" data-wellness-details hidden>
        <label class="form-label" for="<?= e($wellnessFieldPrefix) ?>Detail">내용</label>
        <input class="input" id="<?= e($wellnessFieldPrefix) ?>Detail" name="wellness_detail" type="text" maxlength="160" placeholder="먹은 음식이나 운동 내용을 간단히 적어보세요">
        <fieldset class="calendar-wellness-rating">
            <legend>체감 상태 (선택)</legend>
            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                <label><input type="radio" name="wellness_rating" value="<?= $rating ?>"><span><?= $rating ?></span></label>
            <?php endfor; ?>
        </fieldset>
        <label class="form-label" for="<?= e($wellnessFieldPrefix) ?>Note">생활 메모 (선택)</label>
        <textarea class="input" id="<?= e($wellnessFieldPrefix) ?>Note" name="wellness_note" maxlength="1000" rows="3"></textarea>
    </div>
</fieldset>
