<?php
$appCssPath = dirname(__DIR__, 3) . '/public/assets/css/app.css';
$appCssVersion = is_file($appCssPath) ? filemtime($appCssPath) : false;
$uiCssPath = dirname(__DIR__, 3) . '/public/assets/css/components/ui.css';
$uiCssVersion = is_file($uiCssPath) ? filemtime($uiCssPath) : false;
$toastCssPath = dirname(__DIR__, 3) . '/public/assets/css/components/toast.css';
$toastCssVersion = is_file($toastCssPath) ? filemtime($toastCssPath) : false;
$localePreference = \App\Core\I18n::normalizeLocale((string) ($_SESSION['locale_preference'] ?? 'ko'));
?>
<!doctype html>
<html lang="<?= e($localePreference) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'LifeFlow') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css<?= $appCssVersion === false ? '' : '?v=' . e((string) $appCssVersion) ?>">
    <link rel="stylesheet" href="/assets/css/components/ui.css<?= $uiCssVersion === false ? '' : '?v=' . e((string) $uiCssVersion) ?>">
    <link rel="stylesheet" href="/assets/css/components/toast.css<?= $toastCssVersion === false ? '' : '?v=' . e((string) $toastCssVersion) ?>">
    <?php foreach (($pageStyles ?? []) as $stylePath): ?>
        <link rel="stylesheet" href="<?= e((string) $stylePath) ?>">
    <?php endforeach; ?>
<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$showAppChrome = !($hideNav ?? false) && isset($_SESSION['user_id']);
$asideNickname = trim((string) ($_SESSION['user_nickname'] ?? ''));
$asideEmail = trim((string) ($_SESSION['user_email'] ?? ''));
$asideDisplayName = $asideNickname !== '' ? $asideNickname : '회원 #' . (string) ($_SESSION['user_id'] ?? '');
$isCalendarActive = $currentPath === '/calendar';
$isPlanActive = $currentPath === '/plan' || strpos($currentPath, '/plan/') === 0;
$isRoutineActive = $currentPath === '/routine';
$isRoutineManageActive = $currentPath === '/manage/routines';
$isRetrospectActive = $currentPath === '/retrospect';
$isGoalActive = $currentPath === '/goal';
$isMemoActive = $currentPath === '/memo';
$isExecutionEnergyActive = $currentPath === '/execution-energy';
$isSearchActive = $currentPath === '/search';
$isDailyPrimaryPage = in_array($currentPath, ['/calendar', '/routine', '/todo', '/retrospect'], true);
$themePreference = (string) ($_SESSION['theme_preference'] ?? 'light');
$themePreference = $themePreference === 'dark' ? 'dark' : 'light';
$headerContextLabel = 'LifeFlow';
if ($showAppChrome) {
    $headerContextLabel = match (true) {
        $currentPath === '/calendar' => t('nav.schedule'),
        $currentPath === '/todo' => t('nav.todo'),
        $currentPath === '/routine' => t('nav.routine'),
        strpos($currentPath, '/retrospect') === 0 => t('nav.retrospect'),
        strpos($currentPath, '/manage/routines') === 0 => t('nav.routine_manage'),
        strpos($currentPath, '/plan') === 0 => t('nav.plan'),
        strpos($currentPath, '/goal') === 0 => t('nav.goal'),
        strpos($currentPath, '/execution-energy') === 0 => t('nav.execution_energy_manage'),
        strpos($currentPath, '/memo') === 0 => t('nav.memo'),
        $currentPath === '/search' => t('nav.search'),
        $currentPath === '/tags' => t('nav.tags'),
        $currentPath === '/dashboard' => t('nav.dashboard'),
        $currentPath === '/settings' => t('common.settings'),
        default => (string) ($title ?? 'LifeFlow'),
    };
}
?>
</head>
<body class="<?= $showAppChrome ? 'has-app-chrome-body' : '' ?><?= $showAppChrome && $isDailyPrimaryPage ? ' daily-primary-page' : '' ?>" data-theme="<?= e($themePreference) ?>">
<div class="app-shell<?= $showAppChrome ? ' has-app-chrome' : '' ?>">
    <header class="site-header<?= $showAppChrome && $isDailyPrimaryPage ? ' is-daily-primary-header' : '' ?>" data-site-header>
        <div class="site-header-inner">
            <?php if ($showAppChrome): ?><div class="site-brand"><?= e($headerContextLabel) ?></div><?php else: ?><h1 class="site-brand"><a href="/dashboard">LifeFlow</a></h1><?php endif; ?>
            <?php if ($showAppChrome): ?>
                <?php if (is_array($headerGuidance ?? null) && !empty($headerGuidance['message'])): ?>
                    <div class="site-header-guidance">
                        <?php if (($headerGuidance['kind'] ?? '') === 'retrospect' && !empty($headerGuidance['href'])): ?>
                            <a href="<?= e((string) $headerGuidance['href']) ?>">
                                <span><?= e((string) $headerGuidance['message']) ?></span>
                                <span aria-hidden="true">&rsaquo;</span>
                            </a>
                        <?php else: ?>
                            <button
                                type="button"
                                data-calendar-header-action="<?= e((string) ($headerGuidance['kind'] ?? '')) ?>"
                                data-plan-item-id="<?= e((string) ($headerGuidance['planItemId'] ?? '')) ?>"
                                data-plan-title="<?= e((string) ($headerGuidance['planTitle'] ?? '')) ?>"
                                data-start-index="<?= e((string) ($headerGuidance['startIndex'] ?? '')) ?>"
                                data-end-index="<?= e((string) ($headerGuidance['endIndex'] ?? '')) ?>"
                            >
                                <span><?= e((string) $headerGuidance['message']) ?></span>
                                <span aria-hidden="true">&rsaquo;</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <form class="header-search-panel" id="headerSearchPanel" method="get" action="/search" data-header-search-panel data-minimum-message="<?= e(t('search.minimum')) ?>" hidden>
                    <label class="sr-only" for="headerSearchQuery"><?= e(t('nav.search')) ?></label>
                    <input class="input" id="headerSearchQuery" name="q" type="search" minlength="2" maxlength="80" placeholder="<?= e(t('search.placeholder')) ?>" value="<?= $isSearchActive ? e(trim((string) ($_GET['q'] ?? ''))) : '' ?>" data-header-search-input required>
                    <button class="header-search-submit" type="submit">
                        <span><?= e(t('search.submit')) ?></span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 5h5v5M19 5l-9 9M18 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                    </button>
                    <button class="header-search-close" type="button" aria-label="<?= e(t('search.close')) ?>" data-header-search-close>×</button>
                </form>
                <div class="site-header-actions">
                    <button class="header-search-toggle" type="button" aria-label="<?= e(t('search.open')) ?>" aria-controls="headerSearchPanel" aria-expanded="false" data-header-search-toggle>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg>
                    </button>
                    <button
                        class="menu-toggle"
                        type="button"
                        aria-label="<?= e(t('common.menu_open')) ?>"
                        aria-controls="appAside"
                        aria-expanded="false"
                        data-menu-toggle
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($showAppChrome): ?>
        <div class="app-overlay" data-menu-close hidden></div>
        <aside class="app-aside" id="appAside" aria-label="<?= e(t('nav.primary')) ?>" aria-hidden="true">
            <div class="app-aside-header">
                <a
                    class="aside-settings-link"
                    href="/settings"
                    aria-label="<?= e(t('common.settings')) ?>"
                    <?= $currentPath === '/settings' ? 'aria-current="page"' : '' ?>
                >&#9881;</a>
                <button class="aside-close" type="button" aria-label="<?= e(t('common.menu_close')) ?>" data-menu-close>×</button>
            </div>
            <section class="aside-user" aria-label="로그인 회원 정보">
                <span class="aside-user-avatar" aria-hidden="true"><?= e(mb_substr($asideDisplayName, 0, 1)) ?></span>
                <div class="aside-user-text">
                    <strong><?= e($asideDisplayName) ?></strong>
                    <?php if ($asideEmail !== ''): ?>
                        <small><?= e($asideEmail) ?></small>
                    <?php else: ?>
                        <small><?= e(t('common.signed_in')) ?></small>
                    <?php endif; ?>
                </div>
            </section>
            <nav class="aside-nav" aria-label="<?= e(t('nav.primary')) ?>">
                <div class="aside-nav-group">
                    <a href="/dashboard" <?= $currentPath === '/dashboard' ? 'aria-current="page"' : '' ?>><?= e(t('nav.dashboard')) ?></a>
                </div>
                <div class="aside-nav-group">
                    <span class="aside-nav-label"><?= e(t('nav.manage')) ?></span>
                    <a href="/plan" <?= $isPlanActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.plan')) ?></a>
                    <a href="/manage/routines" <?= $isRoutineManageActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.routine_manage')) ?></a>
                    <a href="/goal" <?= $isGoalActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.goal')) ?></a>
                    <a href="/execution-energy" <?= $isExecutionEnergyActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.execution_energy_manage')) ?></a>
                </div>
                <div class="aside-nav-group">
                    <span class="aside-nav-label"><?= e(t('nav.tools')) ?></span>
                    <a href="/memo" <?= $isMemoActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.memo')) ?></a>
                    <a href="/search" <?= $isSearchActive ? 'aria-current="page"' : '' ?>><?= e(t('nav.search')) ?></a>
                    <a href="/tags" <?= $currentPath === '/tags' ? 'aria-current="page"' : '' ?>><?= e(t('nav.tags')) ?></a>
                </div>
            </nav>
        </aside>
    <?php endif; ?>
