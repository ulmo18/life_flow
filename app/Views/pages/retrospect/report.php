<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php
$report = $publishedReport;
$summary = $report['summary'];
$texts = $report['texts'];
$composition = $report['recordComposition'];
$adjacent = $report['adjacent'];
?>
<main class="page retrospect-report-page">
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <nav class="report-archive-nav" aria-label="발행 회고 탐색">
        <a class="btn btn-secondary" href="/retrospect?view=history">발행 이력</a>
        <span>
            <?php if ($adjacent['previous'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['previous']) ?>">이전 발행일</a><?php endif; ?>
            <?php if ($adjacent['next'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['next']) ?>">다음 발행일</a><?php endif; ?>
        </span>
    </nav>

    <article class="daily-report-paper">
        <header class="report-masthead">
            <p>Life Flow Daily Archive</p>
            <h1><?= e((string) $report['dateTitle']) ?></h1>
            <strong><?= e((string) $report['dateSubTitle']) ?>의 발행 회고</strong>
            <small><?= e((string) $report['submittedAt']) ?> 발행</small>
            <?php if (!empty($report['canEdit'])): ?><a class="btn btn-secondary" href="/retrospect?date=<?= e((string) $report['date']) ?>">회고 수정</a><?php endif; ?>
        </header>

        <section class="report-summary-strip" aria-label="하루 요약">
            <span><small>계획 달성</small><b><?= e((string) $summary['planAchievementRate']) ?>%</b></span>
            <span><small>루틴 달성</small><b><?= e((string) $summary['routineAchievementRate']) ?>%</b></span>
            <span><small>연결 실행</small><b><?= e((string) $summary['linkedActualTimeLabel']) ?></b></span>
        </section>

        <?php if ((int) $composition['total'] > 0): ?>
            <section class="report-composition" aria-label="기록 비중">
                <div><i class="is-routine" style="--share:<?= e((string) $composition['routine']) ?>%"></i><i class="is-plan" style="--share:<?= e((string) $composition['plan']) ?>%"></i><i class="is-actual" style="--share:<?= e((string) $composition['actual']) ?>%"></i><i class="is-memo" style="--share:<?= e((string) $composition['memo']) ?>%"></i></div>
                <p>루틴 <?= e((string) $composition['routine']) ?>% · 계획 <?= e((string) $composition['plan']) ?>% · 일정 <?= e((string) $composition['actual']) ?>% · 메모 <?= e((string) $composition['memo']) ?>%</p>
            </section>
        <?php endif; ?>

        <section class="report-section"><h2>계획과 실제 비교</h2><p>기록된 시간 기준 · 계획 <?= e((string) ($summary['plannedMinutes'] ?? 0)) ?>분 · 실제 <?= e((string) ($summary['actualMinutes'] ?? 0)) ?>분 · 계획 외 <?= e((string) ($summary['unplannedActualMinutes'] ?? 0)) ?>분</p><?php foreach (($report['activityComparisons'] ?? []) as $activity): ?><details><summary><?= e((string) $activity['title']) ?> · 계획 <?= e((string) $activity['plannedMinutes']) ?>분 / 실제 <?= e((string) $activity['actualMinutes']) ?>분 / 차이 <?= (int) $activity['differenceMinutes'] > 0 ? '+' : '' ?><?= e((string) $activity['differenceMinutes']) ?>분</summary><p>계획: <?php foreach ($activity['plans'] as $plan): ?><?= e((string) $plan['timeRange']) ?><?= !empty($plan['hasActual']) ? '' : ' (실제 기록 없음)' ?> · <?php endforeach; ?></p><p>실제: <?php if (empty($activity['actuals'])): ?>실제 기록 없음<?php else: ?><?php foreach ($activity['actuals'] as $actual): ?><?= e((string) $actual['timeRange']) ?> · <?php endforeach; ?><?php endif; ?></p></details><?php endforeach; ?><?php if (!empty($report['unplannedActualItems'])): ?><details><summary>계획 외 실제 활동</summary><?php foreach ($report['unplannedActualItems'] as $actual): ?><p><?= e((string) $actual['timeRange']) ?> · <?= e((string) $actual['title']) ?></p><?php endforeach; ?></details><?php endif; ?></section>

        <?php if (!empty($report['milestones'])): ?>
            <section class="report-section report-milestones"><h2>오늘의 완료 소식</h2><?php foreach ($report['milestones'] as $milestone): ?><article><small><?= (string) $milestone['item_type'] === 'goal' ? '목표 완료' : '루틴 완료' ?></small><h3><?= e((string) $milestone['title_snapshot']) ?></h3><p><?= e((string) $milestone['feedback_snapshot']) ?></p></article><?php endforeach; ?></section>
        <?php endif; ?>

        <section class="report-section report-kpt"><h2>KPT</h2><div><article><small>KEEP</small><h3>이어갈 것</h3><p><?= nl2br(e((string) $texts['today_review'])) ?: '작성된 내용이 없습니다.' ?></p></article><article><small>PROBLEM</small><h3>돌아볼 것</h3><p><?= nl2br(e((string) $texts['today_thoughts'])) ?: '작성된 내용이 없습니다.' ?></p></article><article><small>TRY</small><h3>다음에 시도할 것</h3><p><?= nl2br(e((string) $texts['tomorrow_plan'])) ?: '작성된 내용이 없습니다.' ?></p></article></div></section>

        <section class="report-section"><h2>하루의 일정</h2><?php if (empty($report['scheduleItems'])): ?><p class="report-empty">기록된 일정이 없습니다.</p><?php else: ?><ol class="report-schedule-list"><?php foreach ($report['scheduleItems'] as $item): ?><li><time><?= e((string) ($item['actualTimeRange'] !== '' ? $item['actualTimeRange'] : $item['planTimeRange'])) ?></time><div><strong><?= e((string) $item['title']) ?></strong><small><?= $item['kind'] === 'actual' ? '계획 외 실제 일정' : (!empty($item['isLinked']) ? '계획과 실제 일정 연결' : '계획 일정') ?></small></div><?php if ($item['energyValue'] !== null): ?><b class="report-energy is-<?= e((string) $item['energyEffectType']) ?>"><?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?></b><?php endif; ?></li><?php endforeach; ?></ol><?php endif; ?></section>

        <div class="report-columns">
            <section class="report-section"><h2>루틴</h2><?php if (empty($report['routineItems'])): ?><p class="report-empty">기록된 루틴이 없습니다.</p><?php else: ?><ul class="report-simple-list"><?php foreach ($report['routineItems'] as $routine): ?><li><span><?= (string) $routine['state'] === 'O' ? '✓' : ((string) $routine['state'] === 'X' ? '×' : '·') ?></span><strong><?= e((string) $routine['name']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?></section>
            <section class="report-section"><h2>메모</h2><?php if (empty($report['memoItems'])): ?><p class="report-empty">기록된 메모가 없습니다.</p><?php else: ?><ul class="report-memo-list"><?php foreach ($report['memoItems'] as $memo): ?><li><time><?= e((string) $memo['timeLabel']) ?></time><div><small><?= e((string) $memo['sourceLabel']) ?></small><p><?= nl2br(e((string) $memo['content'])) ?></p></div></li><?php endforeach; ?></ul><?php endif; ?></section>
        </div>
    </article>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
