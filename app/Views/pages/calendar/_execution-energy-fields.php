<?php
$energyFieldPrefix = isset($energyFieldPrefix) ? (string) $energyFieldPrefix : 'calendarEnergy';
$energyDefaultType = isset($energyDefaultType) ? (string) $energyDefaultType : 'consume';
$energyTypeName = 'execution_energy_type';
?>
<fieldset class="calendar-energy-links" data-energy-fields data-energy-direction="<?= e($energyDefaultType) ?>">
    <legend class="sr-only">실행력 변화 설정</legend>
    <span class="calendar-energy-field-label">변화</span>
    <div class="calendar-energy-effect" role="radiogroup" aria-label="실행력 변화">
        <?php foreach (['consume' => '− 감소', 'none' => '0 유지', 'recover' => '+ 증가'] as $type => $label): ?>
            <label>
                <input type="radio" name="<?= e($energyTypeName) ?>" value="<?= e($type) ?>" <?= $energyDefaultType === $type ? 'checked' : '' ?>>
                <span><?= e($label) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="calendar-energy-amount-panel" data-energy-amount-panel <?= $energyDefaultType === 'none' ? 'hidden' : '' ?>>
        <span class="calendar-energy-field-label">변화량</span>
        <div class="calendar-energy-amounts" role="radiogroup" aria-label="실행력 변화량">
            <?php foreach ([10, 25, 50, 75, 100] as $value): ?>
                <label>
                    <input type="radio" name="execution_energy_amount" value="<?= $value ?>" <?= $value === 25 ? 'checked' : '' ?>>
                    <span data-energy-amount-option data-energy-amount-value="<?= $value ?>"><?= $energyDefaultType === 'recover' ? '+' : '−' ?><?= $value ?></span>
                </label>
            <?php endforeach; ?>
            <label>
                <input type="radio" name="execution_energy_amount" value="custom">
                <span>직접 입력</span>
            </label>
        </div>
        <label class="calendar-energy-custom" data-energy-custom hidden>
            <span>직접 입력 변화량</span>
            <span class="calendar-energy-custom-control">
                <b data-energy-custom-prefix aria-hidden="true"><?= $energyDefaultType === 'recover' ? '+' : '−' ?></b>
                <input class="input" id="<?= e($energyFieldPrefix) ?>CustomValue" name="execution_energy_custom_value" type="number" min="5" max="1000" step="5" inputmode="numeric" placeholder="5~1000" aria-label="실행력 변화량 직접 입력" disabled>
            </span>
        </label>
    </div>
    <p class="calendar-form-hint" data-energy-hint>이 일정은 실행력을 −25 사용합니다.</p>
</fieldset>
