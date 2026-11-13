<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="calendar-page plan-detail-page">
    <a class="btn btn-secondary plan-back-button" href="/plan?view=days">← 뒤로가기</a>
    <h1 class="sr-only"><?= e((string) $plan['name']) ?></h1>

    <div class="plan-detail-actions">
        <form
            method="post"
            action="/plan/delete"
        >
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="plan_group_id" value="<?= e((string) $plan['id']) ?>">
            <button type="button" class="btn btn-ghost plan-delay-delete" data-plan-delay-delete aria-describedby="planDeleteHelp"><span>템플릿 삭제</span></button><span class="sr-only" id="planDeleteHelp">1.5초 이상 눌러 삭제하세요.</span>
        </form>
        <a class="btn btn-secondary" href="/plan/edit?id=<?= e((string) $plan['id']) ?>">템플릿 수정</a>
    </div>

    <section class="daygrid-wrap calendar-timetable-panel is-plan plan-template-timetable" aria-label="하루 템플릿 시간표">
        <div class="daygrid">
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row">
                    <div class="hour-label"><?= sprintf('%02d', $hour) ?></div>
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
                            class="event plan-event plan-template-event importance-<?= e(strtolower((string) $block['importance'])) ?>"
                            style="--row: <?= e((string) $row) ?>; --col: <?= e((string) $col) ?>; --span: <?= e((string) $span) ?>;"
                            data-ui-tooltip="<?= e((string) $block['title']) ?>"
                            aria-label="<?= e((string) $block['title']) ?> <?= e((string) $block['timeRange']) ?>"
                        >
                            <span class="calendar-importance-badge is-neutral"><?= e((string) $block['importanceBadge']) ?></span>
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
                    <span><?= e((string) $block['timeRange']) ?> · <?= e((string) $block['importanceLabel']) ?></span>
                    <?php if (!empty($block['goalTitle'])): ?>
                        <span class="plan-block-goal">목표 · <?= e((string) $block['goalTitle']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
