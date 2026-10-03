<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page routine-today-page">
    <div class="routine-today-heading">
        <div>
            <h1><?= e(t('routine.today_title')) ?></h1>
            <p class="muted"><?= e((string) $date) ?></p>
        </div>
        <a class="btn btn-secondary" href="/manage/routines"><?= e(t('routine.manage_link')) ?></a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>
    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <?php if (empty($routines)): ?>
        <section class="card routine-today-empty">
            <strong><?= e(t('routine.today_empty')) ?></strong>
            <a class="btn btn-primary" href="/manage/routines"><?= e(t('routine.manage_link')) ?></a>
        </section>
    <?php else: ?>
        <ul class="routine-today-list">
            <?php foreach ($routines as $routine): ?>
                <?php $state = (string) ($routine['state'] ?? ''); ?>
                <li class="card">
                    <strong><?= e((string) $routine['name']) ?></strong>
                    <form method="post" action="/routine/toggle" data-routine-today-toggle>
                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="return_to" value="routine">
                        <input type="hidden" name="date" value="<?= e((string) $date) ?>">
                        <input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>">
                        <button type="submit" class="routine-state-control <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?>" data-state="<?= e($state) ?>" aria-label="<?= e((string) $routine['name']) ?>">
                            <span data-routine-state-marker aria-hidden="true"><?= $state === 'O' ? '✓' : ($state === 'X' ? '×' : '') ?></span>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
