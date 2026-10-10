<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$unfinishedRoutines = array_values(array_filter($routines, static fn(array $routine): bool => (string) ($routine['state'] ?? '') !== 'O'));
$completedRoutines = array_values(array_filter($routines, static fn(array $routine): bool => (string) ($routine['state'] ?? '') === 'O'));
$renderRoutineRows = static function (array $items) use ($csrfToken, $date): void {
    foreach ($items as $routine): $state = (string) ($routine['state'] ?? ''); $streakCount = (int) ($routine['streakCount'] ?? 0); ?>
        <tr data-routine-today-row data-routine-state="<?= e($state) ?>">
            <td><strong><?= e((string) $routine['name']) ?></strong><small data-routine-today-streak <?= $streakCount < 3 ? 'hidden' : '' ?>>🔥 <?= e((string) $streakCount) ?>일 연속 완료</small></td>
            <td><form method="post" action="/routine/toggle" data-routine-today-toggle><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="return_to" value="routine"><input type="hidden" name="date" value="<?= e((string) $date) ?>"><input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>"><button type="submit" class="routine-state-control <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?>" data-state="<?= e($state) ?>" aria-label="<?= e((string) $routine['name']) ?>"><span data-routine-state-marker aria-hidden="true"><?= $state === 'O' ? '✓' : ($state === 'X' ? '×' : '') ?></span></button></form></td>
        </tr>
    <?php endforeach;
};
?>

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
        <section class="routine-today-group" data-routine-group="unfinished"><header><strong>미완료 루틴</strong><span data-routine-group-count><?= e((string) count($unfinishedRoutines)) ?>개</span></header><div class="routine-today-table-wrap"><table><tbody data-routine-group-body><?php $renderRoutineRows($unfinishedRoutines); ?></tbody></table><p data-routine-group-empty <?= !empty($unfinishedRoutines) ? 'hidden' : '' ?>>오늘의 루틴을 모두 완성했어요. 이 흐름이 내일의 힘이 될 거예요.</p></div></section>
        <section class="routine-today-group" data-routine-group="completed"><header><strong>완료 루틴</strong><span data-routine-group-count><?= e((string) count($completedRoutines)) ?>개</span></header><div class="routine-today-table-wrap"><table><tbody data-routine-group-body><?php $renderRoutineRows($completedRoutines); ?></tbody></table><p data-routine-group-empty <?= !empty($completedRoutines) ? 'hidden' : '' ?>>첫 루틴을 완료하며 오늘의 흐름을 시작해보세요.</p></div></section>
    <?php endif; ?>
</main>

<button class="btn btn-primary daily-floating-action" type="button" data-routine-quick-open>루틴 추가</button>

<div class="routine-quick-layer" data-routine-quick-layer hidden>
    <button type="button" class="routine-quick-overlay" data-routine-quick-close aria-label="닫기"></button>
    <section class="routine-quick-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="routineQuickTitle">
        <div class="routine-quick-header ui-form-sheet-header">
            <strong id="routineQuickTitle">루틴 추가</strong>
            <button type="button" class="ui-close-button" data-routine-quick-close aria-label="닫기">×</button>
        </div>
        <form method="post" action="/routine/quick" data-routine-quick-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <label class="form-label" for="routineQuickName">루틴명</label>
            <input class="input" id="routineQuickName" name="name" type="text" maxlength="60" placeholder="매일 이어갈 루틴을 입력하세요" autocomplete="off" required>
            <p class="muted">오늘부터 7일 동안 진행되며 기간·목표·알림은 루틴 관리에서 변경할 수 있어요.</p>
            <div class="ui-form-sheet-actions"><button type="submit" class="btn btn-primary">루틴 추가</button></div>
        </form>
    </section>
</div>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
