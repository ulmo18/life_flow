<section id="report-schedule" class="report-section report-schedule" aria-label="일정">
    <h2>일정</h2>
    <p>계획 <?= e((string) ($summary['plannedTimeLabel'] ?? '0분')) ?> · 실제 <?= e((string) ($summary['actualTimeLabel'] ?? '0분')) ?> · 계획 외 <?= e((string) ($summary['unplannedActualTimeLabel'] ?? '0분')) ?></p>
    <?php if (empty($report['scheduleItems'])): ?><p class="report-empty">기록된 일정이 없습니다.</p><?php else: ?>
        <ol class="report-schedule-unified">
        <?php foreach ($report['scheduleItems'] as $item): ?>
            <li<?= $item['kind'] === 'actual' && !empty($item['tagPresent']) ? ' class="has-tag" style="--report-tag-color:' . e((string) $item['tagColor']) . '"' : '' ?>>
                <div class="report-schedule-title">
                    <?php if ($item['kind'] === 'plan'): ?><span class="report-importance importance-<?= e(strtolower((string) $item['importance'])) ?>" aria-label="중요도 <?= e((string) $item['importance']) ?>"><?= e((string) $item['importance']) ?></span><?php endif; ?>
                    <strong><?= e((string) $item['title']) ?></strong>
                </div>
                <?php if ($item['kind'] === 'plan'): ?>
                    <p>계획 <?= e((string) $item['planTimeRange']) ?><?php if (($item['planDurationLabel'] ?? null) !== null): ?> · <?= e((string) $item['planDurationLabel']) ?><?php endif; ?></p>
                    <?php if (empty($item['linkedActuals'])): ?><p>연결된 실제 일정 없음</p><?php else: ?>
                        <?php if (count($item['linkedActuals']) > 1): ?><p>연결된 실제 기록 합 <?= e((string) $item['linkedActualTimeLabel']) ?></p><?php endif; ?>
                        <?php foreach ($item['linkedActuals'] as $actual): ?>
                            <p class="report-schedule-actual<?= !empty($actual['tagPresent']) ? ' has-tag' : '' ?>"<?php if (!empty($actual['tagPresent'])): ?> style="--report-tag-color:<?= e((string) $actual['tagColor']) ?>"<?php endif; ?>>
                                <?php if (!empty($actual['tagPresent'])): ?><span class="sr-only">태그 <?= e((string) $actual['tagName']) ?> · </span><?php endif; ?>
                                실제 <?= e((string) $actual['timeRange']) ?> · <?= e((string) $actual['durationLabel']) ?> · <?= e((string) $actual['title']) ?><?php if ($actual['energyValue'] !== null): ?> · 실행력 <?= $actual['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $actual['energyValue']) ?><?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="report-schedule-actual">
                        <?php if (!empty($item['tagPresent'])): ?><span class="sr-only">태그 <?= e((string) $item['tagName']) ?> · </span><?php endif; ?>
                        실제 <?= e((string) $item['actualTimeRange']) ?> · <?= e((string) $item['actualDurationLabel']) ?> · <?= !empty($item['linkAmbiguous']) ? '연결 블록 미확인' : '계획 외 일정' ?><?php if ($item['energyValue'] !== null): ?> · 실행력 <?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?><?php endif; ?>
                    </p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
