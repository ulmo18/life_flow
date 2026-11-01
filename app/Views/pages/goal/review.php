<?php require __DIR__ . '/../../layouts/header.php'; ?>
<main class="page goal-page retrospect-page">
    <h1 class="page-title">목표 회고</h1>
    <nav class="goal-mode-switch" aria-label="목표 범위">
        <a href="/goal">지금 집중</a><a href="/goal?mode=all">전체 목표</a><a href="/goal/review" aria-current="page">목표 회고</a>
    </nav>
        <section class="retrospect-goal-review" aria-label="목표별 실행 피드백">
            <p class="retrospect-date-status">전체 기간의 목표 회고</p>
            <?php if (empty($goalReview['goals'])): ?>
                <div class="retrospect-empty-state"><strong>회고할 목표가 없습니다.</strong><p>목표를 만들고 계획이나 루틴을 연결하면 실행 흐름을 확인할 수 있습니다.</p></div>
    <?php else: ?>
                <?php foreach ($goalReview['goals'] as $goal): ?>
                    <article class="retrospect-goal-card"><div class="retrospect-goal-card-title"><span><?= e((string) $goal['type']) ?></span><strong><?= e((string) $goal['title']) ?></strong><small><?= e((string) $goal['statusLabel']) ?></small></div><div class="retrospect-goal-metrics"><span>계획 <?= e((string) $goal['planExecutionRate']) ?>%</span><span>루틴 <?= e((string) $goal['routineDoneDayCount']) ?>일</span><span>실제 일정 <?= e((string) $goal['actualEventCount']) ?>건</span></div><p><?= e((string) $goal['feedback']) ?></p></article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section class="retrospect-history" aria-labelledby="completedRoutineTitle">
            <h2 id="completedRoutineTitle">완료된 루틴</h2>
            <?php if (empty($goalReview['routineHistory'])): ?>
                <p class="retrospect-empty">완료된 루틴이 없습니다.</p>
    <?php else: ?>
                <ul class="retrospect-history-list">
                    <?php foreach ($goalReview['routineHistory'] as $routine): ?>
                        <li><a href="/manage/routines"><span><strong><?= e((string) $routine['name']) ?></strong> <?= e((string) $routine['statusLabel']) ?></span><small><?= e((string) $routine['startDate']) ?> ~ <?= e((string) $routine['endDate']) ?></small><p><?= e((string) $routine['doneCount']) ?>/<?= e((string) $routine['durationDays']) ?>일 · <?= e((string) $routine['achievementRate']) ?>%</p></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>