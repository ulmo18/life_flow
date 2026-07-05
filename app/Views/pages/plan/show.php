<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="calendar-page plan-detail-page">
    <section class="calendar-header">
        <div>
            <p class="eyebrow">Plan</p>
            <h1 class="calendar-date"><?= e((string) $plan['name']) ?></h1>
            <p class="calendar-subtitle">v<?= e((string) $plan['versionNo']) ?> · <?= count($plan['blocks']) ?>개 블록</p>
        </div>
        <a class="calendar-icon-button" href="/plan" aria-label="계획 리스트로 이동">←</a>
    </section>

    <div class="plan-detail-actions">
        <a class="btn btn-secondary" href="/plan/edit?id=<?= e((string) $plan['id']) ?>">계획 수정</a>
        <a class="btn btn-ghost" href="/plan">목록으로</a>
    </div>

    <section class="daygrid-wrap" aria-label="계획 상세 그리드">
        <div class="daygrid">
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row">
                    <div class="hour-label"><?= sprintf('%02d:00', $hour) ?></div>
                    <div class="cells">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="cell"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>

            <div class="event-layer">
                <?php foreach ($plan['blocks'] as $block): ?>
                    <?php
                    $start = (int) $block['startIndex'];
                    $end = (int) $block['endIndex'];
                    for ($i = $start; $i < $end;):
                        $row = (int) floor($i / 6);
                        $col = $i % 6;
                        $span = min(6 - $col, $end - $i);
                    ?>
                        <div
                            class="event plan-editor-event importance-<?= e(strtolower((string) $block['importance'])) ?>"
                            style="--row: <?= e((string) $row) ?>; --col: <?= e((string) $col) ?>; --span: <?= e((string) $span) ?>;"
                            data-ui-tooltip="<?= e((string) $block['title']) ?>"
                            aria-label="<?= e((string) $block['title']) ?> <?= e((string) $block['timeRange']) ?>"
                        >
                            <span class="importance-badge"><?= e((string) $block['importanceBadge']) ?></span>
                            <span class="event-title"><?= e((string) $block['title']) ?></span>
                        </div>
                    <?php
                        $i += $span;
                    endfor;
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="plan-block-summary" aria-label="계획 블록 목록">
        <h2>블록 목록</h2>
        <ul class="plan-block-list">
            <?php foreach ($plan['blocks'] as $block): ?>
                <li>
                    <div class="plan-block-row">
                        <span class="importance-badge"><?= e((string) $block['importanceBadge']) ?></span>
                        <strong><?= e((string) $block['title']) ?></strong>
                    </div>
                    <span><?= e((string) $block['timeRange']) ?> · template #<?= e((string) $block['templateId']) ?> · <?= e((string) $block['importanceLabel']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
