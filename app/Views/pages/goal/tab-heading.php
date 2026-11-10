<?php /** @var string $goalTabTitle */ /** @var string $goalTabHelp */ ?>
<div class="goal-map-toolbar goal-tab-heading">
    <div class="goal-tab-label"><h2><?= e($goalTabTitle) ?></h2><details class="goal-map-info"><summary aria-label="<?= e($goalTabTitle) ?> 사용 안내"><span aria-hidden="true">!</span></summary><div class="goal-map-tooltip"><?= e($goalTabHelp) ?></div></details></div>
    <?php if (!empty($showGoalToggle)): ?><button type="button" class="goal-expand-toggle" data-goal-toggle-all aria-pressed="false"><span>전체 펼치기</span><span class="goal-expand-track" aria-hidden="true"></span></button><?php endif; ?>
</div>
