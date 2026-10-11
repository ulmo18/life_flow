<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$pendingItems = $todo['pendingItems'] ?? [];
$completedItems = $todo['completedItems'] ?? [];
$renderTodoItems = static function (array $items) use ($canEdit): void {
    foreach ($items as $item):
        $isCompleted = !empty($item['isCompleted']);
        $isUnscheduled = $item['startIndex'] === null;
        ?>
        <tr class="todo-item <?= $isCompleted ? 'is-completed' : '' ?> <?= !empty($item['isLongDuration']) ? 'is-long-duration' : '' ?>" data-todo-item="<?= e((string) $item['id']) ?>">
            <td><div class="todo-item-copy">
                <div class="todo-item-title">
                    <span class="todo-importance" aria-label="중요도 <?= e((string) $item['importance']) ?>"><?= e((string) $item['importance']) ?></span>
                    <strong class="<?= !empty($item['isLongDuration']) ? 'duration-highlight' : '' ?>"><?= e((string) $item['title']) ?></strong>
                    <?php if (!empty($item['isLongDuration'])): ?><span class="sr-only">1시간 이상 계획</span><?php endif; ?>
                </div>
                <div class="todo-item-meta"><span class="todo-time"><?= e((string) $item['timeRange']) ?></span><?php if (!$isCompleted && $isUnscheduled): ?><button type="button" class="todo-time-link" data-todo-edit-proxy>시간 설정</button><?php endif; ?></div>
                <?php if ($item['goalTitle'] !== null): ?>
                    <small><?= e(t('todo.goal', ['goal' => $item['goalTitle']])) ?></small>
                <?php endif; ?>
            </div></td>
            <?php if ($canEdit): ?><td><div class="todo-item-actions">
                <?php if (!$isCompleted): ?>
                    <button type="button" class="btn btn-primary todo-complete-action" data-todo-complete
                        data-item-id="<?= e((string) $item['id']) ?>"
                        data-start-index="<?= $item['startIndex'] === null ? '' : e((string) $item['startIndex']) ?>"
                        data-end-index="<?= $item['endIndex'] === null ? '' : e((string) $item['endIndex']) ?>"
                    ><?= e(t('todo.complete')) ?></button>
                <?php endif; ?>
                <?php if ($canEdit): ?><div class="todo-item-menu">
                    <button type="button" class="todo-menu-toggle" data-todo-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="<?= e((string) $item['title']) ?> 메뉴 열기">⋮</button>
                    <div class="todo-menu-popover" role="menu" data-todo-menu hidden>
                        <button type="button" role="menuitem" data-todo-edit-open
                            data-item-id="<?= e((string) $item['id']) ?>"
                            data-title="<?= e((string) $item['title']) ?>"
                            data-importance="<?= e((string) $item['importance']) ?>"
                            data-goal-id="<?= $item['goalId'] === null ? '' : e((string) $item['goalId']) ?>"
                            data-schedule-type="<?= $isUnscheduled ? 'unscheduled' : 'timed' ?>"
                            data-start-index="<?= $isUnscheduled ? '' : e((string) $item['startIndex']) ?>"
                            data-end-index="<?= $isUnscheduled ? '' : e((string) $item['endIndex']) ?>"
                            data-linked="<?= $isCompleted ? '1' : '0' ?>"
                        >할 일 수정</button>
                    </div>
                </div><?php endif; ?>
            </div></td><?php endif; ?>
        </tr>
    <?php endforeach;
};
?>

<main class="page todo-page" data-todo-page data-message-fallback="<?= e(t('todo.invalid')) ?>">
    <?php $dailyDateContext = ['title' => t('todo.title'), 'path' => '/todo', 'date' => $todo['date'], 'prevDate' => $prevDate, 'nextDate' => $nextDate, 'todayDate' => $todayDate, 'isToday' => $isToday, 'inputId' => 'todoDate']; require __DIR__ . '/../../components/daily-date-navigation.php'; ?>
    <div class="todo-tools"><a class="btn btn-secondary compact-header-action" href="/calendar?date=<?= e((string) $todo['date']) ?>&mode=plan"><?= $isToday ? e(t('nav.schedule')) : '일정에서 보기·수정' ?></a></div>

    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><span data-toast-message="<?= e((string) $errors['general']) ?>" hidden></span><?php endif; ?>

    <section class="todo-group" aria-labelledby="todoPendingTitle">
        <header class="todo-group-heading"><h2 id="todoPendingTitle">미완료 할 일</h2><span><?= e((string) count($pendingItems)) ?>개</span></header>
        <div class="todo-table-wrap">
        <?php if ($pendingItems === []): ?>
            <p class="todo-group-empty"><?= empty($completedItems) ? ($isToday ? e(t('todo.empty_help')) : e(t('todo.empty'))) : ($isToday ? '오늘의 할 일을 모두 완료했어요. 이 흐름을 이어가 보세요.' : '선택한 날짜의 할 일을 모두 완료했어요.') ?></p>
        <?php else: ?><table class="todo-table"><caption class="sr-only">미완료 할 일 목록</caption><tbody><?php $renderTodoItems($pendingItems); ?></tbody></table><?php endif; ?>
        </div>
    </section>

    <section class="todo-group" aria-labelledby="todoCompletedTitle">
        <header class="todo-group-heading"><h2 id="todoCompletedTitle">완료 할 일</h2><span><?= e((string) count($completedItems)) ?>개</span></header>
        <div class="todo-table-wrap">
        <?php if ($completedItems === []): ?>
            <p class="todo-group-empty">완료한 할 일이 여기에 차곡차곡 쌓여요. 하나씩 시작해봐요.</p>
        <?php else: ?><table class="todo-table"><caption class="sr-only">완료한 할 일 목록</caption><tbody><?php $renderTodoItems($completedItems); ?></tbody></table><?php endif; ?>
        </div>
    </section>
</main>

<?php if ($canEdit): ?><button class="btn btn-primary daily-floating-action" type="button" data-todo-create-open>할 일 추가</button><?php endif; ?>

<div class="todo-layer" data-todo-create-layer hidden>
    <button type="button" class="todo-overlay" data-todo-create-close aria-label="닫기"></button>
    <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="todoCreateTitle">
        <div class="todo-sheet-header ui-form-sheet-header"><strong id="todoCreateTitle">할 일 추가</strong><button type="button" class="ui-close-button" data-todo-create-close aria-label="닫기">×</button></div>
        <form class="todo-create-form" method="post" action="/todo">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="todoCreateTitleInput">일정명</label>
            <input class="input" id="todoCreateTitleInput" name="title" type="text" maxlength="80" placeholder="오늘 할 일을 입력하세요" autocomplete="off" required>
            <p class="muted">시간이 없는 오늘의 계획 일정으로 등록됩니다.</p>
            <div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">할 일 추가</button></div>
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
            <fieldset class="todo-schedule-choice">
                <legend>시간 설정</legend>
                <label><input type="radio" name="schedule_type" value="unscheduled" data-todo-edit-schedule-type><span>시간 미정</span></label>
                <label><input type="radio" name="schedule_type" value="timed" data-todo-edit-schedule-type><span>시간 설정</span></label>
            </fieldset>
            <div class="todo-time-fields" data-todo-edit-time-fields hidden>
                <label for="todoEditStartIndex"><span>시작시간</span><select class="input" id="todoEditStartIndex" name="start_index">
                    <?php for ($index = 0; $index < 144; $index++): ?><option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?>
                </select></label>
                <label for="todoEditEndIndex"><span>종료시간</span><select class="input" id="todoEditEndIndex" name="end_index">
                    <?php for ($index = 1; $index <= 144; $index++): ?><option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?>
                </select></label>
            </div>
            <div data-todo-link-action hidden>
                <label class="form-label" for="todoEditLinkAction">연결된 실제 일정 처리</label>
                <select class="input" id="todoEditLinkAction" name="link_action">
                    <option value="keep">계획 일정만 변경</option><option value="sync">실제 일정도 함께 변경</option><option value="detach">연결을 끊고 계획 일정만 변경</option>
                </select>
            </div>
            <div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">수정 저장</button></div>
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
