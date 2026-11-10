<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$formatMinutes = static function (int $minutes): string {
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    return $hours > 0 ? $hours . '시간' . ($remaining > 0 ? ' ' . $remaining . '분' : '') : $remaining . '분';
};
?>
<main class="page goal-page retrospect-page">
    <h1 class="sr-only">목표 회고</h1>
    <nav class="ui-segmented-tabs goal-mode-switch" aria-label="목표 범위">
        <a href="/goal">지금 집중</a><a href="/goal?mode=all">전체 목표</a><a href="/goal/review" aria-current="page">목표 회고</a>
    </nav>
    <?php $goalTabTitle = '목표 회고'; $goalTabHelp = '완료된 목표를 선택하면 연결된 계획, 실제 일정과 루틴의 활동 기록을 확인할 수 있어요.'; $showGoalToggle = false; require __DIR__ . '/tab-heading.php'; ?>
    <section class="goal-review-list" aria-label="완료된 목표">
        <div class="goal-record-table-wrap ui-list-surface">
            <?php if (empty($goalReview['goals'])): ?><p class="goal-record-empty ui-list-empty">완료된 목표가 없습니다.</p><?php else: ?>
            <table class="goal-record-table goal-review-table ui-list-table"><caption class="sr-only">완료된 목표 목록</caption><thead><tr><th scope="col">완료된 목표</th><th scope="col">기록</th></tr></thead><tbody>
                <?php foreach ($goalReview['goals'] as $goal): ?>
                <tr><td><button type="button" class="goal-review-open" data-goal-review-open="<?= e((string) $goal['id']) ?>"><span><?= e((string) $goal['title']) ?></span><small><?= e((string) $goal['type']) ?></small></button></td><td><button type="button" class="goal-review-action" data-goal-review-open="<?= e((string) $goal['id']) ?>" aria-label="<?= e((string) $goal['title']) ?> 활동 기록 보기">›</button></td></tr>
                <?php endforeach; ?>
            </tbody></table><?php endif; ?>
        </div>
    </section>
</main>
<div class="goal-local-layer" data-goal-review-layer hidden>
    <button type="button" class="goal-local-overlay" data-goal-review-close aria-label="목표 회고 닫기"></button>
    <?php foreach (($goalReview['goals'] ?? []) as $goal): ?>
    <section class="goal-detail-panel goal-review-sheet" data-goal-review-sheet="<?= e((string) $goal['id']) ?>" role="dialog" aria-modal="true" aria-labelledby="goalReviewTitle<?= e((string) $goal['id']) ?>" hidden>
        <div class="goal-detail-header"><div><small>완료된 목표</small><strong id="goalReviewTitle<?= e((string) $goal['id']) ?>"><?= e((string) $goal['title']) ?></strong></div><button type="button" class="ui-close-button" data-goal-review-close aria-label="닫기">×</button></div>
        <?php require __DIR__ . '/review-content.php'; ?>
    </section>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
