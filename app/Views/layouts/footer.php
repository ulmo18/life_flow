    <?php if (!($showAppChrome ?? false)): ?>
        <footer class="site-footer">
            <div class="site-footer-inner muted">
                © <?= date('Y') ?> LifeFlow
            </div>
        </footer>
    <?php endif; ?>
    <?php if ($showAppChrome ?? false): ?>
        <nav class="bottom-navigator" aria-label="<?= e(t('nav.primary')) ?>">
            <a class="bottom-nav-item" href="/retrospect" <?= ($currentPath ?? '') === '/retrospect' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">▥</span>
                <span><?= e(t('nav.retrospect')) ?></span>
            </a>
            <a class="bottom-nav-item" href="/routine" <?= ($currentPath ?? '') === '/routine' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">✓</span>
                <span><?= e(t('nav.routine')) ?></span>
            </a>
            <a class="bottom-nav-item bottom-nav-energy" href="/execution-energy/history" <?= ($currentPath ?? '') === '/execution-energy/history' ? 'aria-current="page"' : '' ?> data-energy-orb data-energy-date="<?= e(date('Y-m-d')) ?>" data-energy-cache-key="<?= e('life-flow.energy.' . (string) ($_SESSION['user_id'] ?? 0)) ?>" data-ui-tooltip="실행력 사용 이력" aria-label="실행력 사용 이력">
                <span class="bottom-nav-energy-orb" aria-hidden="true">
                    <span class="bottom-nav-energy-liquid"></span>
                    <span class="bottom-nav-energy-bubbles"><i></i><i></i><i></i></span>
                    <strong data-energy-orb-value>…</strong>
                    <span class="bottom-nav-energy-glare"></span>
                </span>
                <span class="bottom-nav-energy-label"><?= e(t('nav.execution_energy')) ?></span>
            </a>
            <a class="bottom-nav-item" href="/todo" <?= ($currentPath ?? '') === '/todo' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">□</span>
                <span><?= e(t('nav.todo')) ?></span>
            </a>
            <a class="bottom-nav-item" href="/calendar" <?= ($currentPath ?? '') === '/calendar' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">▦</span>
                <span><?= e(t('nav.today')) ?></span>
            </a>
        </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../components/toast.php'; ?>
<div class="ui-layer" id="lifeFlowUiLayer" hidden>
    <div class="ui-overlay" data-ui-close></div>
    <section class="ui-panel ui-modal" id="lifeFlowModal" aria-modal="true" role="dialog" hidden>
        <div class="ui-panel-header">
            <strong class="ui-panel-title" id="lifeFlowModalTitle"></strong>
            <button type="button" class="ui-close-button" data-ui-close aria-label="<?= e(t('common.close')) ?>">×</button>
        </div>
        <p class="ui-panel-body" id="lifeFlowModalBody"></p>
        <div class="ui-panel-actions">
            <button type="button" class="btn btn-ghost" data-ui-cancel><?= e(t('common.cancel')) ?></button>
            <button type="button" class="btn btn-primary" data-ui-confirm><?= e(t('common.confirm')) ?></button>
        </div>
    </section>
    <section class="ui-panel ui-sheet" id="lifeFlowSheet" aria-modal="true" role="dialog" hidden>
        <div class="ui-panel-header">
            <strong class="ui-panel-title" id="lifeFlowSheetTitle"></strong>
            <button type="button" class="ui-close-button" data-ui-close aria-label="<?= e(t('common.close')) ?>">×</button>
        </div>
        <form class="ui-sheet-form" id="lifeFlowSheetForm">
            <div class="ui-field">
                <label class="form-label" for="lifeFlowSheetInput" id="lifeFlowSheetLabel"></label>
                <input class="input" id="lifeFlowSheetInput" type="text" maxlength="80" autocomplete="off">
            </div>
            <fieldset class="importance-choice-group" id="lifeFlowSheetImportanceGroup" hidden>
                <legend>중요도</legend>
                <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                    <label><input type="radio" name="life_flow_sheet_importance" value="<?= $importance ?>" <?= $importance === 'D' ? 'checked' : '' ?>><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                <?php endforeach; ?>
            </fieldset>
            <div class="ui-field" id="lifeFlowSheetGoalGroup" hidden>
                <label class="form-label" for="lifeFlowSheetGoal">목표</label>
                <select class="input" id="lifeFlowSheetGoal">
                    <option value="">연결하지 않음</option>
                </select>
            </div>
            <p class="field-error" id="lifeFlowSheetError" hidden></p>
            <div class="ui-panel-actions">
                <button type="button" class="btn btn-ghost" data-ui-cancel><?= e(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary" data-ui-sheet-submit><?= e(t('common.confirm')) ?></button>
            </div>
        </form>
    </section>
</div>
<script src="/assets/js/components/toast.js"></script>
<?php
$uiJsPath = dirname(__DIR__, 3) . '/public/assets/js/components/ui.js';
$uiJsVersion = is_file($uiJsPath) ? filemtime($uiJsPath) : false;
?>
<script src="/assets/js/components/ui.js<?= $uiJsVersion === false ? '' : '?v=' . e((string) $uiJsVersion) ?>"></script>
<script src="/assets/js/components/android-bridge.js"></script>
<?php if ($showAppChrome ?? false): ?>
    <script src="/assets/js/components/app-layout.js"></script>
    <script src="/assets/js/components/execution-energy-orb.js"></script>
<?php endif; ?>
<?php foreach (($pageScripts ?? []) as $scriptPath): ?>
    <script src="<?= e((string) $scriptPath) ?>"></script>
<?php endforeach; ?>
</body>
</html>
