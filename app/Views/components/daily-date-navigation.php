<?php
$context = is_array($dailyDateContext ?? null) ? $dailyDateContext : [];
$contextDate = (string) ($context['date'] ?? date('Y-m-d'));
$contextDateValue = new DateTimeImmutable($contextDate);
$weekdayLabels = [
    'ko' => ['일', '월', '화', '수', '목', '금', '토'],
    'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'ja' => ['日', '月', '火', '水', '木', '金', '土'],
];
$contextLocale = \App\Core\I18n::normalizeLocale((string) ($_SESSION['locale_preference'] ?? 'ko'));
$contextWeekday = $weekdayLabels[$contextLocale][(int) $contextDateValue->format('w')];
$contextDateLabel = $contextLocale === 'ko'
    ? $contextDateValue->format('Y년 n월 j일') . ' ' . $contextWeekday . '요일'
    : ($contextLocale === 'ja'
        ? $contextDateValue->format('Y年n月j日') . ' (' . $contextWeekday . ')'
        : $contextDateValue->format('M j, Y') . ' · ' . $contextWeekday);
$contextPath = (string) ($context['path'] ?? '/');
$contextView = (string) ($context['view'] ?? '');
$viewQuery = $contextView !== '' && $contextView !== 'daily' ? '&view=' . rawurlencode($contextView) : '';
$contextMode = (string) ($context['mode'] ?? '');
$modeQuery = $contextMode !== '' ? '&mode=' . rawurlencode($contextMode) : '';
?>
<section class="daily-date-context" data-daily-context-date="<?= e($contextDate) ?>" aria-label="<?= e((string) ($context['title'] ?? '날짜')) ?> 날짜 탐색">
    <div class="daily-date-swipe" data-daily-date-swipe data-prev-date="<?= e((string) ($context['prevDate'] ?? $contextDate)) ?>" data-next-date="<?= e((string) ($context['nextDate'] ?? $contextDate)) ?>">
    <form class="daily-date-picker" method="get" action="<?= e($contextPath) ?>">
        <span class="daily-date-value <?= e((string) ($dailyDateClass ?? '')) ?>" aria-hidden="true"><?= e($contextDateValue->format('n.j')) ?></span><span class="daily-date-weekday" aria-hidden="true"><?= e($contextWeekday) ?></span><span class="daily-date-caret" aria-hidden="true">▾</span>
        <?php if ($viewQuery !== ''): ?><input type="hidden" name="view" value="<?= e($contextView) ?>"><?php endif; ?>
        <?php if ($contextMode !== ''): ?><input type="hidden" name="mode" value="<?= e($contextMode) ?>"><?php endif; ?>
        <label class="sr-only" for="<?= e((string) ($context['inputId'] ?? 'dailyContextDate')) ?>">날짜 선택</label>
        <input id="<?= e((string) ($context['inputId'] ?? 'dailyContextDate')) ?>" name="date" type="date" value="<?= e($contextDate) ?>" data-today="<?= e((string) ($context['todayDate'] ?? date('Y-m-d'))) ?>" data-daily-date-picker aria-label="<?= e($contextDateLabel) ?> 날짜 선택">
    </form>
    <a class="daily-date-arrow" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['prevDate'] ?? $contextDate)) ?><?= e($viewQuery . $modeQuery) ?>" aria-label="이전 날짜">&lsaquo;</a>
    <a class="daily-date-arrow" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['nextDate'] ?? $contextDate)) ?><?= e($viewQuery . $modeQuery) ?>" aria-label="다음 날짜">&rsaquo;</a>
    <?php if (empty($context['isToday'])): ?>
        <a class="daily-today-link" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['todayDate'] ?? date('Y-m-d'))) ?><?= e($viewQuery . $modeQuery) ?>"><?= e(t('nav.today')) ?></a>
    <?php endif; ?>
    </div>
    <?php require __DIR__ . '/daily-context-actions.php'; ?>
</section>
