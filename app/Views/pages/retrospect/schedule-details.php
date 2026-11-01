<div class="retrospect-local-layer" data-schedule-detail-layer hidden><button type="button" class="retrospect-local-overlay" data-detail-close aria-label="일정 상세 닫기"></button><section class="retrospect-sheet retrospect-detail-sheet" role="dialog" aria-labelledby="scheduleDetailTitle"><div class="retrospect-sheet-header"><strong id="scheduleDetailTitle">일정 상세</strong><button type="button" class="ui-close-button" data-detail-close aria-label="닫기">×</button></div>
    <?php foreach (($retrospect['scheduleItems'] ?? []) as $index => $item): ?>
        <article data-detail-panel="schedule-detail-<?= e((string) $index) ?>" hidden>
            <h2><?= e((string) $item['title']) ?></h2>
            <dl>
                <dt>연결 목표</dt><dd><?= e((string) ($item['goalTitle'] !== '' ? $item['goalTitle'] : '없음')) ?></dd>
                <dt>계획 일정</dt><dd><?= e((string) ($item['planTimeRange'] !== '' ? $item['title'] . ' · ' . $item['planTimeRange'] : (!empty($item['linkAmbiguous']) ? '연결 블록 미확인' : '계획 외 일정'))) ?></dd>
                <?php if (!empty($item['linkedActuals'])): ?>
                    <?php foreach ($item['linkedActuals'] as $actual): ?>
                        <dt>실제 일정</dt><dd><?= e((string) $actual['title']) ?> · <?= e((string) $actual['timeRange']) ?><?php if ($actual['tagName'] !== ''): ?> · <span class="retrospect-tag-line" style="--tag-color:<?= e((string) $actual['tagColor']) ?>"><?= e((string) $actual['tagName']) ?></span><?php endif; ?><?php if ($actual['energyValue'] !== null): ?> · <?= $actual['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $actual['energyValue']) ?><?php endif; ?><?php if ($actual['memo'] !== ''): ?><br><?= nl2br(e((string) $actual['memo'])) ?><?php endif; ?></dd>
                    <?php endforeach; ?>
                <?php else: ?>
                    <dt>실제 일정</dt><dd><?= e((string) ($item['actualTimeRange'] !== '' ? $item['actualTitle'] . ' · ' . $item['actualTimeRange'] : '연결되지 않음')) ?></dd>
                    <?php if ($item['tagName'] !== ''): ?><dt>일정 태그</dt><dd><span class="retrospect-tag-line" style="--tag-color:<?= e((string) $item['tagColor']) ?>"><?= e((string) $item['tagName']) ?></span></dd><?php endif; ?>
                    <?php if ($item['energyValue'] !== null): ?><dt>실행력</dt><dd><?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?></dd><?php endif; ?>
                    <?php if ($item['actualMemo'] !== ''): ?><dt>실제 일정 메모</dt><dd><?= nl2br(e((string) $item['actualMemo'])) ?></dd><?php endif; ?>
                <?php endif; ?>
                <?php if ($item['startDifferenceLabel'] !== '' && count($item['linkedActuals'] ?? []) <= 1): ?><dt>시작 차이</dt><dd><?= e((string) $item['startDifferenceLabel']) ?></dd><dt>종료 차이</dt><dd><?= e((string) $item['endDifferenceLabel']) ?></dd><dt>소요시간 차이</dt><dd><?= e((string) $item['durationDifferenceLabel']) ?></dd><?php endif; ?>
            </dl>
        </article>
    <?php endforeach; ?>
</section></div>

