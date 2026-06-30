<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'LifeFlow') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <header class="site-header">
        <div class="site-header-inner">
            <h1 class="site-brand">LifeFlow</h1>
            <?php if (!($hideNav ?? false)): ?>
                <nav class="nav" aria-label="주요 메뉴">
                    <a href="/dashboard">대시보드</a>
                    <a href="/settings">설정</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
