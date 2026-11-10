<fieldset class="calendar-time-fields calendar-actual-time-range" <?= $actualTimePrefix === 'calendarEdit' ? 'data-calendar-edit-time-fields' : 'data-calendar-time-fields' ?>>
    <legend><span>시간</span><output class="calendar-actual-duration" data-actual-duration for="<?= e($actualTimePrefix) ?>StartIndex <?= e($actualTimePrefix) ?>EndIndex" aria-live="polite"></output></legend>
    <?php foreach (['start' => ['시작', 0, 143], 'end' => ['종료', 1, 144]] as $edge => [$label, $first, $last]): ?>
    <label class="calendar-actual-time-control" for="<?= e($actualTimePrefix . ucfirst($edge)) ?>Index">
        <span class="calendar-actual-time-label"><?= e($label) ?></span>
        <select class="input" name="<?= e($edge) ?>_index" id="<?= e($actualTimePrefix . ucfirst($edge)) ?>Index" required>
            <?php for ($index = $first; $index <= $last; $index++): ?><option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?>
        </select>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    </label>
    <?php endforeach; ?>
</fieldset>
