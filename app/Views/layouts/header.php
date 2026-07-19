<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'LifeFlow') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/components/ui.css">
    <link rel="stylesheet" href="/assets/css/components/toast.css">
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
$isRetrospectActive = $currentPath === '/retrospect';
$isGoalActive = $currentPath === '/goal';
$isMemoActive = $currentPath === '/memo';
$themePreference = (string) ($_SESSION['theme_preference'] ?? 'light');
$themePreference = $themePreference === 'dark' ? 'dark' : 'light';
?>
</head>
<body class="<?= $showAppChrome ? 'has-app-chrome-body' : '' ?>" data-theme="<?= e($themePreference) ?>">
<div class="app-shell<?= $showAppChrome ? ' has-app-chrome' : '' ?>">
    <header class="site-header">
        <div class="site-header-inner">
            <h1 class="site-brand">LifeFlow</h1>
            <?php if ($showAppChrome): ?>
                <button
                    class="menu-toggle"
                    type="button"
                    aria-label="메뉴 열기"
                    aria-controls="appAside"
                    aria-expanded="false"
                    data-menu-toggle
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($showAppChrome): ?>
        <div class="app-overlay" data-menu-close hidden></div>
        <aside class="app-aside" id="appAside" aria-label="앱 메뉴" aria-hidden="true">
            <div class="app-aside-header">
                <a
                    class="aside-settings-link"
                    href="/settings"
                    aria-label="설정"
                    <?= $currentPath === '/settings' ? 'aria-current="page"' : '' ?>
                >&#9881;</a>
                <button class="aside-close" type="button" aria-label="메뉴 닫기" data-menu-close>×</button>
            </div>
            <section class="aside-user" aria-label="로그인 회원 정보">
                <span class="aside-user-avatar" aria-hidden="true"><?= e(mb_substr($asideDisplayName, 0, 1)) ?></span>
                <div class="aside-user-text">
                    <strong><?= e($asideDisplayName) ?></strong>
                    <?php if ($asideEmail !== ''): ?>
                        <small><?= e($asideEmail) ?></small>
                    <?php else: ?>
                        <small>로그인 중</small>
                    <?php endif; ?>
                </div>
            </section>
            <nav class="aside-nav" aria-label="관리 메뉴">
                <div class="aside-nav-group">
                    <a href="/dashboard" <?= $currentPath === '/dashboard' ? 'aria-current="page"' : '' ?>>대시보드</a>
                </div>
                <div class="aside-nav-group">
                    <a href="/calendar" <?= $isCalendarActive ? 'aria-current="page"' : '' ?>>캘린더</a>
                    <a href="/plan" <?= $isPlanActive ? 'aria-current="page"' : '' ?>>계획</a>
                    <a href="/routine" <?= $isRoutineActive ? 'aria-current="page"' : '' ?>>루틴</a>
                    <a href="/retrospect" <?= $isRetrospectActive ? 'aria-current="page"' : '' ?>>회고</a>
                    <a href="/goal" <?= $isGoalActive ? 'aria-current="page"' : '' ?>>목표</a>
                    <a href="/memo" <?= $isMemoActive ? 'aria-current="page"' : '' ?>>메모</a>
                </div>
                <div class="aside-nav-group">
                    <a href="/tags" <?= $currentPath === '/tags' ? 'aria-current="page"' : '' ?>>일정 태그 관리</a>
                </div>
            </nav>
        </aside>
    <?php endif; ?>
