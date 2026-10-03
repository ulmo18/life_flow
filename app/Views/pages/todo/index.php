<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main
    class="page todo-page"
    data-todo-page
    data-message-fallback="<?= e(t('todo.invalid')) ?>"
>
    <div class="todo-heading">
        <div>
            <h1><?= e(t('todo.title')) ?></h1>
            <p class="muted"><?= e((string) $todo['date']) ?></p>
        </div>
        <a class="btn btn-secondary" href="/calendar"><?= e(t('nav.today')) ?></a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <?php if (empty($todo['items'])): ?>
        <section class="card todo-empty">
            <strong><?= e(t('todo.empty')) ?></strong>
            <p class="muted"><?= e(t('todo.empty_help')) ?></p>
            <a class="btn btn-primary" href="/calendar"><?= e(t('todo.add')) ?></a>
        </section>
    <?php else: ?>
        <ul class="todo-list">
            <?php foreach ($todo['items'] as $item): ?>
                <li class="card todo-item <?= !empty($item['isCompleted']) ? 'is-completed' : '' ?>" data-todo-item="<?= e((string) $item['id']) ?>">
                    <div class="todo-item-copy">
                        <div class="todo-item-title">
                            <span class="todo-importance" aria-label="<?= e((string) $item['importance']) ?>"><?= e((string) $item['importance']) ?></span>
                            <strong><?= e((string) $item['title']) ?></strong>
                        </div>
                        <span class="todo-time"><?= e((string) $item['timeRange']) ?></span>
                        <?php if ($item['goalTitle'] !== null): ?>
                            <small><?= e(t('todo.goal', ['goal' => $item['goalTitle']])) ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['isCompleted'])): ?>
                        <span class="todo-completed-label">✓ <?= e(t('todo.completed')) ?></span>
                    <?php else: ?>
                        <button
                            type="button"
                            class="btn btn-primary"
                            data-todo-complete
                            data-item-id="<?= e((string) $item['id']) ?>"
                            data-start-index="<?= $item['startIndex'] === null ? '' : e((string) $item['startIndex']) ?>"
                            data-end-index="<?= $item['endIndex'] === null ? '' : e((string) $item['endIndex']) ?>"
                        ><?= e(t('todo.complete')) ?></button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<div class="todo-layer" data-todo-layer hidden>
    <button type="button" class="todo-overlay" data-todo-close aria-label="<?= e(t('common.close')) ?>"></button>
    <section class="todo-sheet" role="dialog" aria-modal="true" aria-labelledby="todoTimeTitle">
        <div class="todo-sheet-header">
            <strong id="todoTimeTitle"><?= e(t('todo.time_title')) ?></strong>
            <button type="button" class="ui-close-button" data-todo-close aria-label="<?= e(t('common.close')) ?>">×</button>
        </div>
        <p class="muted" data-todo-time-message><?= e(t('todo.time_help')) ?></p>
        <form method="post" action="/todo/complete" data-todo-time-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="daily_plan_item_id" data-todo-item-id>
            <div class="todo-time-fields">
                <label for="todoStartIndex">
                    <span><?= e(t('todo.start')) ?></span>
                    <select class="input" id="todoStartIndex" name="start_index">
                        <?php for ($index = 0; $index < 144; $index++): ?>
                            <option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label for="todoEndIndex">
                    <span><?= e(t('todo.end')) ?></span>
                    <select class="input" id="todoEndIndex" name="end_index">
                        <?php for ($index = 1; $index <= 144; $index++): ?>
                            <option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <button type="submit" class="btn btn-primary"><?= e(t('todo.save_complete')) ?></button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
