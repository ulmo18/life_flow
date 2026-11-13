<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php $report = $publishedReport; $adjacent = $report['adjacent']; $embeddedReport = false; ?>
<main class="page retrospect-report-page">
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <div class="retrospect-primary-context ui-menu-context">
        <?php $todayDate = date('Y-m-d'); $todayContext = new DateTimeImmutable($todayDate); $dailyDateClass = ''; $dailyDateContext = ['title' => '회고', 'path' => '/retrospect', 'date' => $todayDate, 'prevDate' => $todayContext->modify('-1 day')->format('Y-m-d'), 'nextDate' => $todayContext->modify('+1 day')->format('Y-m-d'), 'todayDate' => $todayDate, 'isToday' => true, 'preserveSessionDate' => true, 'inputId' => 'reportContextDate', 'view' => 'history']; require __DIR__ . '/../../components/daily-date-navigation.php'; ?>
        <nav class="ui-segmented-tabs retrospect-view-switch" aria-label="회고 보기">
            <a href="/retrospect" data-report-context-link>일일 회고</a>
            <a href="/retrospect?view=history" data-report-context-link aria-current="page">발행 이력</a>
        </nav>
    </div>
    <nav class="report-archive-nav" aria-label="발행 회고 탐색">
        <a class="btn btn-secondary" href="/retrospect?view=history" data-report-context-link><span aria-hidden="true">☰</span> 발행 이력</a>
        <span>
            <?php if ($adjacent['previous'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['previous']) ?>"><span aria-hidden="true">←</span> 이전 발행일</a><?php endif; ?>
            <?php if ($adjacent['next'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['next']) ?>">다음 발행일 <span aria-hidden="true">→</span></a><?php endif; ?>
        </span>
    </nav>
    <?php require __DIR__ . '/report-content.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
