<?php
$energyFieldPrefix = isset($energyFieldPrefix) ? (string) $energyFieldPrefix : 'calendarEnergy';
$energyDefaultType = ($energyDefaultType ?? 'consume') === 'none' ? 'none' : 'consume';
?>
<fieldset class="calendar-energy-compact" data-energy-fields>
    <legend class="sr-only">실행력 변화 설정</legend>
    <input type="hidden" name="execution_energy_type" value="<?= e($energyDefaultType) ?>" data-energy-type>
    <input type="hidden" name="execution_energy_amount" value="25" data-energy-amount>
    <input type="hidden" name="execution_energy_custom_value" value="" data-energy-value>
    <input type="hidden" name="execution_energy_compact" value="1">
    <div class="calendar-energy-controls" data-energy-controls>
        <div class="calendar-energy-direction"><button type="button" data-energy-mode>직접 입력</button><div><button type="button" data-energy-direction="recover" aria-label="실행력 증가" aria-pressed="false"><span>+</span></button><button type="button" data-energy-direction="consume" aria-label="실행력 감소" aria-pressed="false"><span>−</span></button></div></div>
        <div class="calendar-energy-input-area">
            <div data-energy-slider-panel>
                <label class="sr-only" for="<?= e($energyFieldPrefix) ?>Slider">실행력 변화량</label>
                <input type="range" id="<?= e($energyFieldPrefix) ?>Slider" data-energy-slider min="0" max="100" step="25" value="25" aria-valuetext="25">
                <div class="calendar-energy-ticks" aria-label="실행력 눈금">
                    <?php foreach ([0, 25, 50, 75, 100] as $value): ?><button type="button" data-energy-tick="<?= $value ?>" aria-label="변화량 <?= $value ?>"><?= $value ?></button><?php endforeach; ?>
                </div>
            </div>
            <label class="calendar-energy-direct" data-energy-direct hidden><span data-energy-sign aria-hidden="true">+</span><span class="sr-only">실행력 변화량 직접 입력</span><input class="input" type="text" inputmode="numeric" autocomplete="off" aria-describedby="<?= e($energyFieldPrefix) ?>EnergyHint" data-energy-direct-input></label>
        </div>
        <p class="calendar-energy-hint" id="<?= e($energyFieldPrefix) ?>EnergyHint" data-energy-hint>5단위로 5~100까지 입력할 수 있어요.</p>
        <p class="calendar-energy-message" data-energy-message aria-live="polite">방향을 선택해 주세요</p>
    </div>
</fieldset>
