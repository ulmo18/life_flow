<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="page routine-today-page">
    <div class="routine-today-heading">
        <div>
            <h1><?= e(t('routine.today_title')) ?></h1>
            <p class="muted"><?= e((string) $date) ?></p>
        </div>
        <a class="btn btn-secondary compact-header-action" href="/manage/routines"><?= e(t('routine.manage_link')) ?></a>
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
                    <div class="routine-today-copy">
                        <strong><?= e((string) $routine['name']) ?></strong>
                        <?php $streakCount = (int) ($routine['streakCount'] ?? 0); ?>
                        <small data-routine-today-streak <?= $streakCount < 3 ? 'hidden' : '' ?>>🔥 <?= e((string) $streakCount) ?>일 연속 완료</small>
                    </div>
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

<button class="btn btn-primary daily-floating-action" type="button" data-routine-quick-open>루틴 추가</button>

<div class="routine-quick-layer" data-routine-quick-layer hidden>
    <button type="button" class="routine-quick-overlay" data-routine-quick-close aria-label="닫기"></button>
    <section class="routine-quick-sheet" role="dialog" aria-modal="true" aria-labelledby="routineQuickTitle">
        <div class="routine-quick-header">
            <strong id="routineQuickTitle">루틴 추가</strong>
            <button type="button" class="ui-close-button" data-routine-quick-close aria-label="닫기">×</button>
        </div>
        <form method="post" action="/routine/quick" data-routine-quick-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="routineQuickName">루틴명</label>
            <input class="input" id="routineQuickName" name="name" type="text" maxlength="60" placeholder="매일 이어갈 루틴을 입력하세요" autocomplete="off" required>
            <p class="muted">오늘부터 7일 동안 진행되며 기간·목표·알림은 루틴 관리에서 변경할 수 있어요.</p>
            <button type="submit" class="btn btn-primary">루틴 추가</button>
        </form>
    </section>
</div>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
