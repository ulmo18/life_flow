<?php
$summary = $report['summary'];
$texts = $report['texts'];
$composition = $report['recordComposition'];
$formatMinutes = static function (int $minutes): string {
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    return $hours > 0 ? $hours . '시간' . ($remaining > 0 ? ' ' . $remaining . '분' : '') : $remaining . '분';
};
?>
<article class="daily-report-paper<?= !empty($embeddedReport) ? ' is-embedded' : '' ?>">
    <header class="report-masthead">
        <p>Life Flow Daily Archive</p>
        <h1><?= e((string) $report['dateTitle']) ?></h1>
        <strong><?= e((string) $report['dateSubTitle']) ?>의 발행 회고</strong>
        <small><?= e((string) $report['submittedAt']) ?> 발행</small>
    </header>
    <section class="report-summary-strip" aria-label="하루 요약">
        <span><small>계획 달성</small><b><?= e((string) $summary['planAchievementLabel']) ?></b></span>
        <span><small>루틴 달성</small><b><?= e((string) $summary['routineAchievementRate']) ?>%</b></span>
        <span><small>연결 실행</small><b><?= e((string) $summary['linkedActualTimeLabel']) ?></b></span>
    </section>
    <?php if ((int) $composition['total'] > 0): ?>
        <section class="report-composition" aria-label="기록 비중">
            <div><i class="is-routine" style="--share:<?= e((string) $composition['routine']) ?>%"></i><i class="is-plan" style="--share:<?= e((string) $composition['plan']) ?>%"></i><i class="is-actual" style="--share:<?= e((string) $composition['actual']) ?>%"></i><i class="is-memo" style="--share:<?= e((string) $composition['memo']) ?>%"></i></div>
            <p>루틴 <?= e((string) $composition['routine']) ?>% · 계획 <?= e((string) $composition['plan']) ?>% · 일정 <?= e((string) $composition['actual']) ?>% · 메모 <?= e((string) $composition['memo']) ?>%</p>
        </section>
    <?php endif; ?>
    <aside class="report-editorial" aria-label="오늘의 문장"><span>THE DAILY NOTE</span><p>“<?= e((string) ($report['editorialLine'] ?? '오늘의 기록은 내일을 위한 작은 이정표가 됩니다.')) ?>”</p></aside>
    <details class="report-section report-fold report-kpt" open>
        <summary><h2>오늘의 회고</h2></summary>
        <div>
            <article><h3><small>KEEP</small><span>이어갈 것</span></h3><p><?= nl2br(e((string) $texts['today_review'])) ?: '작성된 내용이 없습니다.' ?></p></article>
            <article><h3><small>PROBLEM</small><span>돌아볼 것</span></h3><p><?= nl2br(e((string) $texts['today_thoughts'])) ?: '작성된 내용이 없습니다.' ?></p></article>
            <article><h3><small>TRY</small><span>다음에 시도할 것</span></h3><p><?= nl2br(e((string) $texts['tomorrow_plan'])) ?: '작성된 내용이 없습니다.' ?></p></article>
        </div>
    </details>
    <?php if (!empty($report['goalReviews'])): ?>
        <details class="report-section report-fold report-goal-review" open>
            <summary><h2>목표 회고</h2></summary>
            <?php foreach ($report['goalReviews'] as $goalReview): ?>
                <article class="report-goal-detail">
                    <h3><?= e((string) $goalReview['title']) ?></h3>
                    <?php $goal = $goalReview; require __DIR__ . '/../goal/review-content.php'; ?>
                </article>
            <?php endforeach; ?>
        </details>
    <?php endif; ?>
    <?php require __DIR__ . '/feedback.php'; ?>
    <?php require __DIR__ . '/report-schedule.php'; ?>
    <?php $reviewedGoalIds = array_column($report['goalReviews'] ?? [], 'id'); $otherMilestones = array_filter($report['milestones'] ?? [], static fn(array $item): bool => $item['item_type'] !== 'goal' || !in_array((int) $item['source_id'], $reviewedGoalIds, true)); ?>
    <?php if ($otherMilestones !== []): ?>
        <section class="report-section report-milestones"><h2>완료 소식</h2><?php foreach ($otherMilestones as $milestone): ?><article><small><?= (string) $milestone['item_type'] === 'goal' ? '목표 완료' : '루틴 완료' ?></small><h3><?= e((string) $milestone['title_snapshot']) ?></h3><p><?= e((string) $milestone['feedback_snapshot']) ?></p></article><?php endforeach; ?></section>
    <?php endif; ?>
    <div class="report-columns">
        <details class="report-section report-fold" open><summary><h2>루틴</h2></summary><?php if (empty($report['routineItems'])): ?><p class="report-empty">기록된 루틴이 없습니다.</p><?php else: ?><ul class="report-simple-list"><?php foreach ($report['routineItems'] as $routine): ?><li><span class="report-routine-state" aria-label="<?= (string) $routine['state'] === 'O' ? '완료' : ((string) $routine['state'] === 'X' ? '미완료' : '기록 없음') ?>"><?= (string) $routine['state'] === 'O' ? '✓' : ((string) $routine['state'] === 'X' ? '×' : '·') ?></span><strong><?= e((string) $routine['name']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?></details>
        <details class="report-section report-fold" open><summary><h2>메모</h2></summary><?php if (empty($report['memoItems'])): ?><p class="report-empty">기록된 메모가 없습니다.</p><?php else: ?><ul class="report-memo-list"><?php foreach ($report['memoItems'] as $memo): ?><li><time><?= e((string) $memo['timeLabel']) ?></time><div><small><?= e((string) $memo['sourceLabel']) ?></small><p><?= nl2br(e((string) $memo['content'])) ?></p></div></li><?php endforeach; ?></ul><?php endif; ?></details>
    </div>
</article>
