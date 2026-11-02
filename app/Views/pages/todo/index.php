<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$activityItems = $todo['activityItems'] ?? [];
$blockTemplates = $todo['blockTemplates'] ?? [];
$enabledTemplates = array_values(array_filter($blockTemplates, static fn(array $template): bool => !empty($template['isEnabled'])));
$disabledTemplates = array_values(array_filter($blockTemplates, static fn(array $template): bool => empty($template['isEnabled'])));
$planStatuses = ['pending' => '미정', 'completed' => 'V 완료', 'cancelled' => 'X 취소', 'postponed' => '→ 연기', 'in_progress' => '· 진행 중', 'external' => '○ 위임'];
$statusSymbols = ['pending' => '—', 'completed' => 'V', 'cancelled' => 'X', 'postponed' => '→', 'in_progress' => '·', 'external' => '○'];
$statusSupported = !empty($todo['todoStatusSupported']);
$isRecurringTab = ($activeTab ?? 'items') === 'recurring';
$goalFlag = '<svg class="todo-goal-flag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V4m0 1c5-3 9 3 14 0v10c-5 3-9-3-14 0"/></svg>';
$renderTodoItems = static function (array $items) use ($canEdit, $planStatuses, $statusSymbols, $statusSupported, $goalFlag): void {
    foreach ($items as $item):
        ?>
        <tr class="todo-item <?= !empty($item['isLongDuration']) ? 'is-long-duration' : '' ?>" data-todo-item="<?= e((string) $item['id']) ?>" data-sort-importance="<?= e((string) $item['importance']) ?>">
            <td><div class="todo-item-copy">
                <div class="todo-item-title">
                    <span class="todo-importance" aria-label="중요도 <?= e((string) $item['importance']) ?>"><?= e((string) $item['importance']) ?></span>
                    <?php if ($canEdit && empty($item['sourceUnavailable'])): ?>
                    <button type="button" class="todo-title-button" data-todo-edit-open data-item-id="<?= e((string) $item['id']) ?>" data-title="<?= e((string) $item['title']) ?>" data-importance="<?= e((string) $item['importance']) ?>" data-goal-id="<?= $item['goalId'] === null ? '' : e((string) $item['goalId']) ?>" aria-label="<?= e($item['title']) ?><?= $item['goalId'] !== null ? ', 목표 연결됨' : '' ?> 할 일 수정"><span class="todo-title-content"><span class="todo-title-text <?= !empty($item['isLongDuration']) ? 'duration-highlight' : '' ?>"><?= e((string) $item['title']) ?></span><?= $item['goalId'] !== null ? $goalFlag : '' ?></span></button>
                    <?php else: ?><strong class="todo-title-content"><span class="todo-title-text <?= !empty($item['isLongDuration']) ? 'duration-highlight' : '' ?>"><?= e((string) $item['title']) ?></span><?php if ($item['goalId'] !== null): ?><?= $goalFlag ?><span class="sr-only">목표 연결됨</span><?php endif; ?></strong><?php endif; ?>
                    <?php if (!empty($item['isLongDuration'])): ?><span class="sr-only">1시간 초과 계획</span><?php endif; ?>
                </div>
                <?php if (!empty($item['sourceUnavailable'])): ?><small>원본을 사용할 수 없는 계획 기록</small><?php endif; ?>
            </div></td>
            <td class="todo-status-cell"><button type="button" class="todo-block-status" data-todo-block-status data-item-id="<?= e((string) $item['id']) ?>" data-status-editable="<?= $statusSupported && empty($item['sourceUnavailable']) ? '1' : '0' ?>" data-saved-status="<?= e($item['todoStatus']) ?>" data-block-title="<?= e($item['title']) ?>" data-block-time="" aria-label="<?= e($item['title']) ?> 상태: <?= e($planStatuses[$item['todoStatus']] ?? '미정') ?>, 상태와 연결 기록 보기"><span data-todo-status-symbol aria-hidden="true"><?= e($statusSymbols[$item['todoStatus']] ?? '—') ?></span></button>
                <template data-todo-record-template>
                <?php if ($item['planBlocks'] === []): ?><p class="todo-tree-empty">배치된 계획이 없어요.</p>
                <?php else: ?><ul class="todo-tree">
                    <?php foreach ($item['planBlocks'] as $block): ?>
                    <li class="todo-tree-plan">
                        <div class="todo-tree-line"><strong>계획</strong> <span><?= e($block['timeRange']) ?> (<?= $block['minutes'] ?>분)</span><?php if ($block['title'] !== $item['title']): ?><span><?= e($block['title']) ?></span><?php endif; ?></div>
                        <?php if ($block['actuals'] === []): ?><div class="todo-tree-actual todo-tree-empty">실제 기록 없음</div>
                        <?php else: ?><ul class="todo-tree-actuals">
                            <?php foreach ($block['actuals'] as $actual): ?>
                            <?php $difference = $actual['differenceMinutes']; $differenceText = $difference > 0 ? '+' . $difference : (string) $difference; $differenceLabel = $difference > 0 ? '계획보다 ' . $difference . '분 많음' : ($difference < 0 ? '계획보다 ' . abs($difference) . '분 적음' : '계획과 같음'); ?>
                            <li class="todo-tree-actual"><strong>실제</strong> <span class="todo-actual-time"><?php if ($actual['date'] !== null): ?><span title="<?= e($actual['date']) ?>"><?= e(substr($actual['date'], 5)) ?> </span><?php endif; ?><?= e($actual['timeRange']) ?><?= $actual['minutes'] !== null ? ' (' . $actual['minutes'] . '분)' : '' ?></span> <?php if ($difference !== null): ?><span class="todo-time-difference <?= $difference < 0 ? 'is-negative' : ($difference > 0 ? 'is-positive' : '') ?>" aria-label="<?= e($differenceLabel) ?>"><svg aria-hidden="true" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> <?= e($differenceText) ?>분</span><?php endif; ?><?php if ($actual['title'] !== $block['title']): ?> <span class="todo-actual-title" title="<?= e($actual['title']) ?>"><?= e($actual['title']) ?></span><?php endif; ?></li>
                            <?php endforeach; ?>
                        </ul><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul><?php endif; ?>
                <?php foreach ($item['unassignedActuals'] as $actual): ?><div class="todo-tree-actual">실제 <?= $actual['date'] !== null ? e($actual['date']) . ' ' : '' ?><?= e($actual['timeRange']) ?> (<?= $actual['minutes'] ?>분) · 계획 연결 미지정<?php if ($actual['title'] !== $item['title']): ?> · <?= e($actual['title']) ?><?php endif; ?></div><?php endforeach; ?>
                <?php if ($item['goalTitle'] !== null): ?>
                    <small><?= e(t('todo.goal', ['goal' => $item['goalTitle']])) ?></small>
                <?php endif; ?>
                </template>
            </td>
        </tr>
    <?php endforeach;
};
?>

<main class="page todo-page" data-todo-page data-message-fallback="<?= e(t('todo.invalid')) ?>">
    <?php $dailyDateContext = ['title' => t('todo.title'), 'path' => '/todo', 'date' => $todo['date'], 'prevDate' => $prevDate, 'nextDate' => $nextDate, 'todayDate' => $todayDate, 'isToday' => $isToday, 'inputId' => 'todoDate']; require __DIR__ . '/../../components/daily-date-navigation.php'; ?>

    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><span data-toast-message="<?= e((string) $errors['general']) ?>" hidden></span><?php endif; ?>

    <div class="todo-list-tabs" role="tablist" aria-label="목록 선택">
        <button type="button" role="tab" id="todoItemsTab" aria-selected="<?= $isRecurringTab ? 'false' : 'true' ?>" aria-controls="todoItemsPanel" <?= $isRecurringTab ? 'tabindex="-1"' : '' ?> data-todo-list-tab="items">할 일 <span><?= count($activityItems) ?></span></button>
        <button type="button" role="tab" id="todoRecurringTab" aria-selected="<?= $isRecurringTab ? 'true' : 'false' ?>" aria-controls="todoRecurringPanel" <?= !$isRecurringTab ? 'tabindex="-1"' : '' ?> data-todo-list-tab="recurring">반복 할 일 <span><?= count($enabledTemplates) ?></span></button>
    </div>
    <section class="todo-group" id="todoItemsPanel" role="tabpanel" aria-labelledby="todoItemsTab" data-todo-list-panel="items" <?= $isRecurringTab ? 'hidden' : '' ?>>
        <header class="todo-group-heading"><h2>할 일</h2><span><?= count($activityItems) ?>개</span></header>
        <div class="todo-sort" role="group" aria-label="할 일 정렬">
            <button type="button" data-todo-sort="importance" aria-pressed="true">중요도순</button>
            <button type="button" data-todo-sort="status" aria-pressed="false">상태순</button>
        </div>
        <div class="todo-table-wrap">
        <?php if ($activityItems === []): ?><p class="todo-group-empty">등록한 할 일이 없어요.</p>
        <?php else: ?><table class="todo-table"><caption class="sr-only">할 일 목록</caption><thead><tr><th scope="col">할 일</th><th scope="col" class="todo-status-cell">상태</th></tr></thead><tbody data-todo-sort-list><?php $renderTodoItems($activityItems); ?></tbody></table><?php endif; ?>
        </div>
    </section>
    <section class="todo-group" id="todoRecurringPanel" role="tabpanel" aria-labelledby="todoRecurringTab" data-todo-list-panel="recurring" <?= !$isRecurringTab ? 'hidden' : '' ?>>
        <?php if (!$canEdit): ?><p class="todo-group-empty">반복 할 일을 오늘의 할 일로 사용하려면 오늘 날짜로 이동해 주세요.</p><?php endif; ?>
        <div class="todo-template-picker">
            <div class="todo-template-groups">
                <?php foreach (['사용' => $enabledTemplates, '미사용' => $disabledTemplates] as $groupName => $templates): ?>
                <section class="todo-template-group" aria-label="<?= e($groupName) ?> 반복 할 일">
                    <?php if ($groupName === '미사용'): ?><details class="todo-template-disclosure"><summary>미사용 <span><?= count($templates) ?>개</span></summary><?php else: ?>
                    <h3>사용 <span><?= count($templates) ?>개</span></h3><?php endif; ?>
                    <div class="todo-table-wrap">
                    <?php if ($templates === []): ?><p class="todo-group-empty"><?= $groupName === '사용' ? '사용 중인 항목이 없어요.' : '미사용 항목이 없어요.' ?></p><?php else: ?>
                    <table class="todo-table"><caption class="sr-only"><?= e($groupName) ?> 반복 할 일 목록</caption><thead><tr><th scope="col">반복 할 일</th><th scope="col" class="todo-recurring-action-cell"><?= $groupName === '사용' ? '사용' : '' ?></th></tr></thead><tbody>
                    <?php foreach ($templates as $template): ?>
                        <tr class="todo-template-row <?= empty($template['isEnabled']) ? 'is-disabled' : '' ?>">
                            <td><div class="todo-item-title">
                            <span class="todo-importance" aria-label="중요도 <?= e((string) $template['importance']) ?>"><?= e((string) $template['importance']) ?></span>
                            <button type="button" class="todo-title-button" data-todo-recurring-edit data-template-id="<?= e((string) $template['id']) ?>" data-title="<?= e((string) $template['title']) ?>" data-importance="<?= e((string) $template['importance']) ?>" data-goal-id="<?= $template['goalId'] === null ? '' : e((string) $template['goalId']) ?>" data-duration="<?= e((string) $template['durationIndex']) ?>" data-enabled="<?= !empty($template['isEnabled']) ? '1' : '0' ?>" aria-label="<?= e((string) $template['title']) ?><?= $template['goalId'] !== null ? ', 목표 연결됨' : '' ?> 반복 할 일 수정"><span class="todo-title-content"><span class="todo-title-text"><?= e((string) $template['title']) ?></span><?= $template['goalId'] !== null ? $goalFlag : '' ?></span></button>
                            </div></td><td class="todo-recurring-action-cell">
                            <?php if (!empty($template['isEnabled'])): ?>
                            <form method="post" action="/todo" data-todo-template-form>
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="plan_block_template_id" value="<?= e((string) $template['id']) ?>">
                                <button type="submit" class="todo-block-status todo-template-use" <?= !$canEdit || !empty($template['isUsedOnDate']) ? 'disabled' : '' ?> aria-label="<?= e((string) $template['title']) ?> <?= !empty($template['isUsedOnDate']) ? '할 일에 추가됨' : '사용하기' ?>" title="<?= !empty($template['isUsedOnDate']) ? '추가됨' : '사용하기' ?>"><span aria-hidden="true"><?= !empty($template['isUsedOnDate']) ? '✓' : '+' ?></span></button>
                            </form>
                            <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table><?php endif; ?>
                    </div>
                    <?php if ($groupName === '미사용'): ?></details><?php endif; ?>
                </section>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>

<?php if ($canEdit): ?><button class="btn btn-primary daily-floating-action" type="button" data-todo-create-open data-todo-add-tab="items" <?= $isRecurringTab ? 'hidden' : '' ?>>할 일 추가</button><?php endif; ?>
<button class="btn btn-primary daily-floating-action" type="button" data-todo-recurring-open data-todo-add-tab="recurring" <?= !$isRecurringTab ? 'hidden' : '' ?>>반복 할 일 추가</button>

<div class="todo-layer" data-todo-status-layer hidden>
    <button type="button" class="todo-overlay" data-todo-status-close aria-label="닫기"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoStatusTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoStatusTitle">상태와 연결 기록</strong><button type="button" class="ui-close-button" data-todo-status-close aria-label="닫기">×</button></div>
        <p class="todo-status-block-title" data-todo-status-title></p>
        <div class="todo-status-options" role="group" aria-label="할 일 상태 선택">
            <?php foreach ($planStatuses as $value => $label): ?>
                <button type="button" data-todo-status-option="<?= e($value) ?>" aria-pressed="false"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
        <p class="todo-status-note" data-todo-status-note>이 할 일의 상태를 변경합니다.</p>
        <section class="todo-status-record" aria-labelledby="todoStatusRecordTitle"><h3 id="todoStatusRecordTitle">연결 기록</h3><div data-todo-status-record></div></section>
    </section>
</div>

<div class="todo-layer" data-todo-create-layer hidden>
    <button type="button" class="todo-overlay" data-todo-create-close aria-label="닫기"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoCreateSheetTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoCreateSheetTitle">할 일 추가</strong><button type="button" class="ui-close-button" data-todo-create-close aria-label="닫기">×</button></div>


        <form class="todo-create-form" method="post" action="/todo">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <?php $activityFieldPrefix = 'todoCreate'; $activityGoalOptions = $todo['goalOptions'] ?? []; require __DIR__ . '/../../components/activity-create-fields.php'; ?>
            <div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">할 일 추가</button></div>
        </form>
    </section>
</div>

<div class="todo-layer" data-todo-recurring-layer hidden>
    <button type="button" class="todo-overlay" data-todo-recurring-close aria-label="닫기"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoRecurringCreateTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoRecurringCreateTitle">반복 할 일 추가</strong><button type="button" class="ui-close-button" data-todo-recurring-close aria-label="닫기">×</button></div>
        <form class="todo-create-form" method="post" action="/todo/recurring" data-todo-recurring-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="block_template_id" data-recurring-template-id>
            <input type="hidden" name="duration_index" data-recurring-duration value="6">
            <button type="button" class="btn btn-secondary todo-recurring-enabled" data-recurring-enabled hidden></button>
            <label class="form-label" for="todoRecurringName">할 일명</label>
            <input class="input" id="todoRecurringName" name="title" type="text" maxlength="80" required>
            <fieldset class="calendar-activity-importance"><legend>중요도</legend>
                <?php foreach (['A', 'B', 'C', 'D'] as $importance): ?><label><input type="radio" name="importance" value="<?= e($importance) ?>" <?= $importance === 'D' ? 'checked' : '' ?>><span><?= e($importance) ?></span></label><?php endforeach; ?>
            </fieldset>
            <label class="form-label" for="todoRecurringGoal">목표 연결</label>
            <select class="input" id="todoRecurringGoal" name="goal_id"><option value="">연결하지 않음</option>
                <?php foreach (($todo['goalOptions'] ?? []) as $goal): ?><option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option><?php endforeach; ?>
            </select>
            <div class="ui-form-sheet-actions todo-edit-actions"><button type="button" class="btn btn-ghost todo-delay-button" data-recurring-delete aria-describedby="recurringDeleteHoldHelp" hidden><span>반복 할 일 삭제</span></button><button class="btn btn-primary" type="submit" data-recurring-submit>반복 할 일 추가</button><span class="sr-only" id="recurringDeleteHoldHelp">삭제하려면 버튼을 1.5초 이상 누르세요. 키보드는 스페이스 또는 엔터를 길게 누르세요.</span></div>
        </form>
    </section>
</div>

<div class="todo-layer" data-todo-edit-layer hidden>
    <button type="button" class="todo-overlay" data-todo-edit-close aria-label="닫기"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoEditTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoEditTitle">할 일 수정</strong><button type="button" class="ui-close-button" data-todo-edit-close aria-label="닫기">×</button></div>
        <form class="todo-edit-form" method="post" action="/todo/update" data-todo-edit-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="daily_plan_item_id" data-todo-edit-id>
            <label class="form-label" for="todoEditTitleInput">일정명</label>
            <input class="input" id="todoEditTitleInput" name="title" type="text" maxlength="80" required>
            <fieldset class="importance-choice-group">
                <legend>중요도</legend>
                <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                    <label><input type="radio" name="importance" value="<?= $importance ?>"><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                <?php endforeach; ?>
            </fieldset>
            <details class="todo-goal-link-picker ui-progressive-disclosure" data-todo-goal-picker>
                <summary><span>목표와 연결</span><strong data-todo-goal-summary>연결하기 ›</strong></summary>
                <fieldset class="todo-goal-options">
                    <legend class="sr-only">연결할 목표 선택</legend>
                    <label><input type="radio" name="goal_id" value="" data-goal-label=""><span>연결하지 않음</span></label>
                    <?php foreach (($todo['goalOptions'] ?? []) as $goal): ?>
                        <?php $goalLabel = (string) ($goal['label'] ?? $goal['title'] ?? '목표'); ?>
                        <label><input type="radio" name="goal_id" value="<?= e((string) $goal['id']) ?>" data-goal-label="<?= e($goalLabel) ?>"><span><?= e($goalLabel) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
            </details>
            <div class="ui-form-sheet-actions todo-edit-actions"><button type="button" class="btn btn-ghost todo-delay-button" data-todo-delete aria-describedby="todoDeleteHoldHelp"><span>할 일 삭제</span></button><button class="btn btn-primary" type="submit">수정 저장</button><span class="sr-only" id="todoDeleteHoldHelp">삭제하려면 버튼을 1.5초 이상 누르세요. 키보드는 스페이스 또는 엔터를 길게 누르세요.</span></div>
        </form>
    </section>
</div>

<div class="todo-layer" data-todo-layer hidden>
    <button type="button" class="todo-overlay" data-todo-close aria-label="<?= e(t('common.close')) ?>"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoTimeTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoTimeTitle"><?= e(t('todo.time_title')) ?></strong><button type="button" class="ui-close-button" data-todo-close aria-label="<?= e(t('common.close')) ?>">×</button></div>
        <p class="muted" data-todo-time-message><?= e(t('todo.time_help')) ?></p>
        <form method="post" action="/todo/complete" data-todo-time-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="daily_plan_item_id" data-todo-item-id>
            <div class="todo-time-fields">
                <label for="todoStartIndex"><span><?= e(t('todo.start')) ?></span><select class="input" id="todoStartIndex" name="start_index">
                    <?php for ($index = 0; $index < 144; $index++): ?><option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?>
                </select></label>
                <label for="todoEndIndex"><span><?= e(t('todo.end')) ?></span><select class="input" id="todoEndIndex" name="end_index">
                    <?php for ($index = 1; $index <= 144; $index++): ?><option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?>
                </select></label>
            </div>
            <div class="ui-form-sheet-actions"><button type="submit" class="btn btn-primary"><?= e(t('todo.save_complete')) ?></button></div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
