<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$pendingItems = $todo['pendingItems'] ?? [];
$completedItems = $todo['completedItems'] ?? [];
$renderTodoItems = static function (array $items): void {
    foreach ($items as $item): ?>
        <li class="card todo-item <?= !empty($item['isCompleted']) ? 'is-completed' : '' ?>" data-todo-item="<?= e((string) $item['id']) ?>">
            <div class="todo-item-copy">
                <div class="todo-item-title">
                    <span class="todo-importance" aria-label="중요도 <?= e((string) $item['importance']) ?>"><?= e((string) $item['importance']) ?></span>
                    <strong><?= e((string) $item['title']) ?></strong>
                </div>
                <span class="todo-time"><?= e((string) $item['timeRange']) ?></span>
                <?php if ($item['goalTitle'] !== null): ?>
                    <small><?= e(t('todo.goal', ['goal' => $item['goalTitle']])) ?></small>
                <?php endif; ?>
            </div>
            <div class="todo-item-actions">
                <button type="button" class="btn btn-secondary todo-edit-button" data-todo-edit-open
                    data-item-id="<?= e((string) $item['id']) ?>"
                    data-title="<?= e((string) $item['title']) ?>"
                    data-importance="<?= e((string) $item['importance']) ?>"
                    data-goal-id="<?= $item['goalId'] === null ? '' : e((string) $item['goalId']) ?>"
                    data-schedule-type="<?= $item['startIndex'] === null ? 'unscheduled' : 'timed' ?>"
                    data-start-index="<?= $item['startIndex'] === null ? '' : e((string) $item['startIndex']) ?>"
                    data-end-index="<?= $item['endIndex'] === null ? '' : e((string) $item['endIndex']) ?>"
                    data-linked="<?= !empty($item['isCompleted']) ? '1' : '0' ?>"
                ><?= $item['startIndex'] === null ? '시간 설정' : '수정' ?></button>
                <?php if (!empty($item['isCompleted'])): ?>
                    <span class="todo-completed-label">✓ <?= e(t('todo.completed')) ?></span>
                <?php else: ?>
                    <button type="button" class="btn btn-primary" data-todo-complete
                        data-item-id="<?= e((string) $item['id']) ?>"
                        data-start-index="<?= $item['startIndex'] === null ? '' : e((string) $item['startIndex']) ?>"
                        data-end-index="<?= $item['endIndex'] === null ? '' : e((string) $item['endIndex']) ?>"
                    ><?= e(t('todo.complete')) ?></button>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach;
};
?>

<main class="page todo-page" data-todo-page data-message-fallback="<?= e(t('todo.invalid')) ?>">
    <div class="todo-heading">
        <div><h1><?= e(t('todo.title')) ?></h1><p class="muted"><?= e((string) $todo['date']) ?></p></div>
        <a class="btn btn-secondary compact-header-action" href="/calendar"><?= e(t('nav.today')) ?></a>
    </div>

    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><span data-toast-message="<?= e((string) $errors['general']) ?>" hidden></span><?php endif; ?>

    <section class="todo-group" aria-labelledby="todoPendingTitle">
        <div class="todo-group-heading"><h2 id="todoPendingTitle">미완료</h2><span><?= e((string) count($pendingItems)) ?></span></div>
        <?php if ($pendingItems === []): ?>
            <div class="card todo-group-empty">
                <strong><?= empty($completedItems) ? e(t('todo.empty')) : '오늘의 할 일을 모두 완료했어요.' ?></strong>
                <?php if (empty($completedItems)): ?><p><?= e(t('todo.empty_help')) ?></p><button class="btn btn-primary" type="button" data-todo-create-open>할 일 추가</button><?php endif; ?>
            </div>
        <?php else: ?><ul class="todo-list"><?php $renderTodoItems($pendingItems); ?></ul><?php endif; ?>
    </section>

    <section class="todo-group" aria-labelledby="todoCompletedTitle">
        <div class="todo-group-heading"><h2 id="todoCompletedTitle">완료</h2><span><?= e((string) count($completedItems)) ?></span></div>
        <?php if ($completedItems === []): ?>
            <p class="card todo-group-empty">완료한 할 일이 여기에 차곡차곡 쌓여요. 하나씩 시작해봐요.</p>
        <?php else: ?><ul class="todo-list"><?php $renderTodoItems($completedItems); ?></ul><?php endif; ?>
    </section>
</main>

<button class="btn btn-primary daily-floating-action" type="button" data-todo-create-open>할 일 추가</button>

<div class="todo-layer" data-todo-create-layer hidden>
    <button type="button" class="todo-overlay" data-todo-create-close aria-label="닫기"></button>
    <section class="todo-sheet" role="dialog" aria-modal="true" aria-labelledby="todoCreateTitle">
        <div class="todo-sheet-header"><strong id="todoCreateTitle">할 일 추가</strong><button type="button" class="ui-close-button" data-todo-create-close aria-label="닫기">×</button></div>
        <form class="todo-create-form" method="post" action="/todo">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="todoCreateTitleInput">일정명</label>
            <input class="input" id="todoCreateTitleInput" name="title" type="text" maxlength="80" placeholder="오늘 할 일을 입력하세요" autocomplete="off" required>
            <p class="muted">시간이 없는 오늘의 계획 일정으로 등록됩니다.</p>
            <button class="btn btn-primary" type="submit">할 일 추가</button>
        </form>
    </section>
</div>

<div class="todo-layer" data-todo-edit-layer hidden>
    <button type="button" class="todo-overlay" data-todo-edit-close aria-label="닫기"></button>
    <section class="todo-sheet" role="dialog" aria-modal="true" aria-labelledby="todoEditTitle">
        <div class="todo-sheet-header"><strong id="todoEditTitle">할 일 수정</strong><button type="button" class="ui-close-button" data-todo-edit-close aria-label="닫기">×</button></div>
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
            <label class="form-label" for="todoEditGoal">연결할 목표</label>
            <select class="input" id="todoEditGoal" name="goal_id">
                <option value="">목표 연결 없음</option>
                <?php foreach (($todo['goalOptions'] ?? []) as $goal): ?>
                    <option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option>
                <?php endforeach; ?>
            </select>
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
            <button class="btn btn-primary" type="submit">수정 저장</button>
        </form>
    </section>
</div>

<div class="todo-layer" data-todo-layer hidden>
    <button type="button" class="todo-overlay" data-todo-close aria-label="<?= e(t('common.close')) ?>"></button>
    <section class="todo-sheet" role="dialog" aria-modal="true" aria-labelledby="todoTimeTitle">
        <div class="todo-sheet-header"><strong id="todoTimeTitle"><?= e(t('todo.time_title')) ?></strong><button type="button" class="ui-close-button" data-todo-close aria-label="<?= e(t('common.close')) ?>">×</button></div>
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
            <button type="submit" class="btn btn-primary"><?= e(t('todo.save_complete')) ?></button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
