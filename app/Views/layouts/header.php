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
?>
</head>
<body class="<?= $showAppChrome ? 'has-app-chrome-body' : '' ?>">
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
                <strong>LifeFlow</strong>
                <button class="aside-close" type="button" aria-label="메뉴 닫기" data-menu-close>×</button>
            </div>
            <nav class="aside-nav" aria-label="관리 메뉴">
                <a href="/dashboard" <?= $currentPath === '/dashboard' ? 'aria-current="page"' : '' ?>>대시보드</a>
                <a href="/calendar" <?= $currentPath === '/calendar' ? 'aria-current="page"' : '' ?>>캘린더</a>
                <a href="/settings" <?= $currentPath === '/settings' ? 'aria-current="page"' : '' ?>>설정</a>
            </nav>
            <form class="aside-logout" method="post" action="/logout">
                <input type="hidden" name="_csrf_token" value="<?= e(\App\Core\Csrf::token()) ?>">
                <button type="submit" class="btn btn-ghost">로그아웃</button>
            </form>
        </aside>
    <?php endif; ?>
