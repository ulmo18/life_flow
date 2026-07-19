    <?php if (!($showAppChrome ?? false)): ?>
        <footer class="site-footer">
            <div class="site-footer-inner muted">
                © <?= date('Y') ?> LifeFlow
            </div>
        </footer>
    <?php endif; ?>
    <?php if ($showAppChrome ?? false): ?>
        <nav class="bottom-navigator" aria-label="주요 기능">
            <a class="bottom-nav-item" href="/retrospect" <?= ($currentPath ?? '') === '/retrospect' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">▥</span>
                <span>Retrospect</span>
            </a>
            <?php $isPlanNavActive = ($currentPath ?? '') === '/plan' || strpos((string) ($currentPath ?? ''), '/plan/') === 0; ?>
            <a class="bottom-nav-item" href="/plan" <?= $isPlanNavActive ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">□</span>
                <span>Plan</span>
            </a>
            <a class="bottom-nav-item" href="/routine" <?= ($currentPath ?? '') === '/routine' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">✓</span>
                <span>Routine</span>
            </a>
            <a class="bottom-nav-item" href="/calendar" <?= ($currentPath ?? '') === '/calendar' ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon" aria-hidden="true">▦</span>
                <span>Calendar</span>
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
            <button type="button" class="ui-close-button" data-ui-close aria-label="닫기">×</button>
        </div>
        <p class="ui-panel-body" id="lifeFlowModalBody"></p>
        <div class="ui-panel-actions">
            <button type="button" class="btn btn-ghost" data-ui-cancel>취소</button>
            <button type="button" class="btn btn-primary" data-ui-confirm>확인</button>
        </div>
    </section>
    <section class="ui-panel ui-sheet" id="lifeFlowSheet" aria-modal="true" role="dialog" hidden>
        <div class="ui-panel-header">
            <strong class="ui-panel-title" id="lifeFlowSheetTitle"></strong>
            <button type="button" class="ui-close-button" data-ui-close aria-label="닫기">×</button>
        </div>
        <form class="ui-sheet-form" id="lifeFlowSheetForm">
            <div class="ui-field">
                <label class="form-label" for="lifeFlowSheetInput" id="lifeFlowSheetLabel"></label>
                <input class="input" id="lifeFlowSheetInput" type="text" maxlength="80" autocomplete="off">
            </div>
            <div class="ui-field" id="lifeFlowSheetImportanceGroup" hidden>
                <label class="form-label" for="lifeFlowSheetImportance">중요도</label>
                <select class="input" id="lifeFlowSheetImportance">
                    <option value="A">A - 중요하고 긴급</option>
                    <option value="B">B - 중요하지만 긴급하지 않음</option>
                    <option value="C">C - 긴급하지만 중요하지 않음</option>
                    <option value="D" selected>D - 중요하지도 긴급하지도 않음</option>
                </select>
            </div>
            <div class="ui-field" id="lifeFlowSheetGoalGroup" hidden>
                <label class="form-label" for="lifeFlowSheetGoal">목표</label>
                <select class="input" id="lifeFlowSheetGoal">
                    <option value="">연결하지 않음</option>
                </select>
            </div>
            <p class="field-error" id="lifeFlowSheetError" hidden></p>
            <div class="ui-panel-actions">
                <button type="button" class="btn btn-ghost" data-ui-cancel>취소</button>
                <button type="submit" class="btn btn-primary" data-ui-sheet-submit>확인</button>
            </div>
        </form>
    </section>
</div>
<script src="/assets/js/components/toast.js"></script>
<script src="/assets/js/components/ui.js"></script>
<script src="/assets/js/components/android-bridge.js"></script>
<?php if ($showAppChrome ?? false): ?>
    <script src="/assets/js/components/app-layout.js"></script>
<?php endif; ?>
<?php foreach (($pageScripts ?? []) as $scriptPath): ?>
    <script src="<?= e((string) $scriptPath) ?>"></script>
<?php endforeach; ?>
</body>
</html>
