<?php
$summary = $report['summary'];
$texts = $report['texts'];
$composition = $report['recordComposition'];
?>
<article class="daily-report-paper<?= !empty($embeddedReport) ? ' is-embedded' : '' ?>">
    <header class="report-masthead">
        <p>Life Flow Daily Archive</p>
        <h1><?= e((string) $report['dateTitle']) ?></h1>
        <strong><?= e((string) $report['dateSubTitle']) ?>의 발행 회고</strong>
        <small><?= e((string) $report['submittedAt']) ?> 발행</small>
        <?php if (empty($embeddedReport) && !empty($report['canEdit'])): ?><a class="btn btn-secondary" href="/retrospect?date=<?= e((string) $report['date']) ?>&amp;edit=1">회고 수정</a><?php endif; ?>
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
    <section class="report-section report-kpt">
        <h2>오늘의 회고</h2>
        <div>
            <article><h3><small>KEEP</small><span>이어갈 것</span></h3><p><?= nl2br(e((string) $texts['today_review'])) ?: '작성된 내용이 없습니다.' ?></p></article>
            <article><h3><small>PROBLEM</small><span>돌아볼 것</span></h3><p><?= nl2br(e((string) $texts['today_thoughts'])) ?: '작성된 내용이 없습니다.' ?></p></article>
            <article><h3><small>TRY</small><span>다음에 시도할 것</span></h3><p><?= nl2br(e((string) $texts['tomorrow_plan'])) ?: '작성된 내용이 없습니다.' ?></p></article>
        </div>
    </section>
    <?php require __DIR__ . '/feedback.php'; ?>
    <?php require __DIR__ . '/report-schedule.php'; ?>
    <?php if (!empty($report['milestones'])): ?>
        <section class="report-section report-milestones"><h2>완료 소식</h2><?php foreach ($report['milestones'] as $milestone): ?><article><small><?= (string) $milestone['item_type'] === 'goal' ? '목표 완료' : '루틴 완료' ?></small><h3><?= e((string) $milestone['title_snapshot']) ?></h3><p><?= e((string) $milestone['feedback_snapshot']) ?></p></article><?php endforeach; ?></section>
    <?php endif; ?>
    <div class="report-columns">
        <section class="report-section"><h2>루틴</h2><?php if (empty($report['routineItems'])): ?><p class="report-empty">기록된 루틴이 없습니다.</p><?php else: ?><ul class="report-simple-list"><?php foreach ($report['routineItems'] as $routine): ?><li><span><?= (string) $routine['state'] === 'O' ? '✓' : ((string) $routine['state'] === 'X' ? '×' : '·') ?></span><strong><?= e((string) $routine['name']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?></section>
        <section class="report-section"><h2>메모</h2><?php if (empty($report['memoItems'])): ?><p class="report-empty">기록된 메모가 없습니다.</p><?php else: ?><ul class="report-memo-list"><?php foreach ($report['memoItems'] as $memo): ?><li><time><?= e((string) $memo['timeLabel']) ?></time><div><small><?= e((string) $memo['sourceLabel']) ?></small><p><?= nl2br(e((string) $memo['content'])) ?></p></div></li><?php endforeach; ?></ul><?php endif; ?></section>
    </div>
</article>
