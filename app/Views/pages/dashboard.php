<?php $title = '대시보드'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="page dashboard-page">
    <h1 class="sr-only">대시보드</h1>

    <section class="dashboard-status-card" aria-labelledby="dashboardStatusTitle">
        <div class="dashboard-status-heading"><h2 id="dashboardStatusTitle"><?= e(t('dashboard.overview')) ?></h2><time datetime="<?= e($dashboard['date']) ?>"><?= e($dashboard['date']) ?></time></div>
        <div class="dashboard-status-grid">
            <a class="dashboard-status" href="/retrospect?date=<?= e($dashboard['date']) ?>">
                <span><?= e(t('nav.retrospect')) ?></span>
                <strong class="dashboard-review-state"><?= e(t('dashboard.review_' . $dashboard['retrospect']['state'])) ?></strong>
                <progress max="1" value="<?= $dashboard['retrospect']['state'] === 'submitted' ? '1' : '0' ?>" aria-label="<?= e(t('nav.retrospect')) ?>"></progress>
            </a>
            <?php foreach (['routine'=>'nav.routine_manage', 'todo'=>'nav.todo'] as $kind=>$label): $status = $dashboard[$kind]; ?>
            <a class="dashboard-status" href="/<?= e($kind) ?>?date=<?= e($dashboard['date']) ?>">
                <span><?= e(t($label)) ?></span><strong><?= e((string) $status['done']) ?><small> / <?= e((string) $status['total']) ?></small></strong>
                <span class="dashboard-status-hint"><?= e(t($status['total'] === 0 ? 'dashboard.no_items' : 'dashboard.completed')) ?></span>
                <progress max="<?= max(1, (int) $status['total']) ?>" value="<?= (int) $status['done'] ?>" aria-label="<?= e(t($label)) ?> <?= e(t('dashboard.completed')) ?>"></progress>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php $event = $dashboard['nearestEvent']; ?>
    <a class="dashboard-nearest-event" href="/calendar?date=<?= e($dashboard['date']) ?>&amp;focus=current" aria-label="<?= e(t('dashboard.nearest')) ?><?= $event ? ': ' . e($event['title']) : '' ?>">
        <?php if ($event): ?><span class="dashboard-event-state"><?= e(t('dashboard.event_' . $event['state'])) ?></span><time><?= e($event['startLabel']) ?></time><span class="dashboard-event-title"><?= e($event['title']) ?></span><span class="dashboard-event-kind"><?= e(t('dashboard.' . $event['kind'])) ?></span><?php else: ?><span class="dashboard-event-title"><?= e(t('dashboard.no_events')) ?></span><?php endif; ?>
        <span aria-hidden="true">›</span>
    </a>

    <section class="section dashboard-apps" aria-labelledby="dashboardAppsTitle">
        <h2 id="dashboardAppsTitle">앱</h2>
        <ul class="dashboard-app-grid" aria-label="앱 목록">
            <?php foreach (($dashboardApps ?? []) as $app): ?>
                <li>
                    <a class="dashboard-app" href="<?= e((string) $app['url']) ?>" aria-label="<?= e((string) $app['name']) ?> 게임 열기">
                        <span class="dashboard-app-icon" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="m24 6 16 13-7 21H15L8 19 24 6Z"/><path d="m8 19 16 6 16-6M24 6v19l-9 15m9-15 9 15"/></svg></span>
                        <span class="dashboard-app-name"><?= e((string) $app['name']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
