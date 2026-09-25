<?php if (!empty($availableTagPalettes)): ?>
    <button type="button" class="calendar-tag-add-toggle" data-quick-tag-toggle>+ 태그 추가</button>
    <div class="calendar-quick-tag" data-quick-tag-create hidden>
        <label for="calendarQuickTagName<?= e($quickTagPrefix) ?>">새 태그명</label>
        <input
            class="input"
            id="calendarQuickTagName<?= e($quickTagPrefix) ?>"
            type="text"
            maxlength="24"
            autocomplete="off"
            data-quick-tag-name
        >
        <div class="calendar-quick-tag-palettes" role="radiogroup" aria-label="새 태그 색상">
            <?php foreach ($availableTagPalettes as $paletteIndex => $palette): ?>
                <label style="--tag-color: <?= e((string) $palette['color_hex']) ?>;">
                    <input
                        type="radio"
                        name="quick_tag_palette_<?= e($quickTagPrefix) ?>"
                        value="<?= e((string) $palette['id']) ?>"
                        data-quick-tag-palette
                        <?= $paletteIndex === 0 ? 'checked' : '' ?>
                    >
                    <span aria-hidden="true"></span>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="field-error" data-quick-tag-error hidden></p>
        <div class="calendar-quick-tag-actions">
            <button type="button" class="btn btn-ghost" data-quick-tag-cancel>취소</button>
            <button type="button" class="btn btn-secondary" data-quick-tag-submit>추가</button>
        </div>
    </div>
<?php endif; ?>
