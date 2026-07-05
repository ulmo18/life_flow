<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="calendar-page">
    <section class="calendar-header">
        <div>
            <h1 class="calendar-date"><?= e((string) $calendar['dateTitle']) ?></h1>
            <p class="calendar-subtitle"><?= e((string) $calendar['dateSubTitle']) ?></p>
        </div>
        <a class="calendar-icon-button" href="/dashboard" aria-label="대시보드로 이동">←</a>
    </section>

    <div class="calendar-legend" aria-label="일정 범례">
        <span class="plan">계획</span>
        <span class="actual">실제</span>
    </div>

    <section class="daygrid-wrap" aria-label="일간 캘린더">
        <div class="daygrid" id="daygrid">
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row">
                    <div class="hour-label"><?= sprintf('%02d:00', $hour) ?></div>
                    <div class="cells">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div
                                class="cell"
                                data-index="<?= ($hour * 6) + $i ?>"
                                data-time="<?= sprintf('%02d:%02d', $hour, $i * 10) ?>"
                            ></div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>

            <div class="event-layer" id="planLayer">
                <?php foreach (($calendar['planSegments'] ?? []) as $segment): ?>
                    <div
                        class="event plan-event"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                    >
                        <?= e((string) $segment['title']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="event-layer" id="actualLayer">
                <?php foreach (($calendar['actualSegments'] ?? []) as $segment): ?>
                    <div
                        class="event actual-event"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                    >
                        <?= e((string) $segment['title']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
