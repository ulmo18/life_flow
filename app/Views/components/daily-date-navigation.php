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
?>
<section class="daily-date-context" aria-label="<?= e((string) ($context['title'] ?? '날짜')) ?> 날짜 탐색">
    <a class="daily-date-arrow" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['prevDate'] ?? $contextDate)) ?>" aria-label="이전 날짜">&lsaquo;</a>
    <form class="daily-date-picker" method="get" action="<?= e($contextPath) ?>">
        <strong><?= e((string) ($context['title'] ?? '')) ?></strong>
        <span><?= e($contextDateLabel) ?></span>
        <label class="sr-only" for="<?= e((string) ($context['inputId'] ?? 'dailyContextDate')) ?>">날짜 선택</label>
        <input id="<?= e((string) ($context['inputId'] ?? 'dailyContextDate')) ?>" name="date" type="date" value="<?= e($contextDate) ?>" data-daily-date-picker>
    </form>
    <a class="daily-date-arrow" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['nextDate'] ?? $contextDate)) ?>" aria-label="다음 날짜">&rsaquo;</a>
    <?php if (empty($context['isToday'])): ?>
        <a class="daily-today-link" href="<?= e($contextPath) ?>?date=<?= e((string) ($context['todayDate'] ?? date('Y-m-d'))) ?>"><?= e(t('nav.today')) ?></a>
    <?php endif; ?>
    <?php require __DIR__ . '/daily-context-actions.php'; ?>
</section>
