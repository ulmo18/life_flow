<?php require __DIR__ . '/../../layouts/header.php'; ?>
<?php $report = $publishedReport; $adjacent = $report['adjacent']; $embeddedReport = false; ?>
<main class="page retrospect-report-page">
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <nav class="report-archive-nav" aria-label="발행 회고 탐색">
        <a class="btn btn-secondary" href="/retrospect?view=history">발행 이력</a>
        <span>
            <?php if ($adjacent['previous'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['previous']) ?>">이전 발행일</a><?php endif; ?>
            <?php if ($adjacent['next'] !== null): ?><a class="btn btn-secondary" href="/retrospect/report?date=<?= e((string) $adjacent['next']) ?>">다음 발행일</a><?php endif; ?>
        </span>
    </nav>
    <?php require __DIR__ . '/report-content.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
