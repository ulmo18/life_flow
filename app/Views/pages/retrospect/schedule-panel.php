                <div class="retrospect-tab-panel" role="tabpanel" data-record-panel="schedule" hidden>
                    <div class="retrospect-sort-tabs" aria-label="일정 정렬"><a href="/retrospect?date=<?= e($date) ?><?= $editQuery ?>&amp;sort=importance#schedule" <?= $retrospect['sort'] === 'importance' ? 'aria-current="page"' : '' ?>>중요도순</a><a href="/retrospect?date=<?= e($date) ?><?= $editQuery ?>&amp;sort=time#schedule" <?= $retrospect['sort'] === 'time' ? 'aria-current="page"' : '' ?>>시간순</a><a href="/retrospect?date=<?= e($date) ?><?= $editQuery ?>&amp;sort=tag#schedule" <?= $retrospect['sort'] === 'tag' ? 'aria-current="page"' : '' ?>>태그별</a></div>
                    <p class="msg msg-error" data-schedule-sort-error role="status" hidden></p>
                    <?php if (empty($retrospect['scheduleItems'])): ?><div class="retrospect-empty-action"><p>이 날짜에 계획된 일정이 없어요.</p><a class="btn btn-secondary" href="/calendar?date=<?= e($date) ?>">일정 추가</a></div>
                    <?php else: ?>
                        <div class="retrospect-table-wrap">
                            <table class="retrospect-record-table retrospect-schedule-table">
                                <thead><tr><th scope="col">상태</th><th scope="col">일정</th><th scope="col">시간</th><th scope="col">차이</th><th scope="col">실행력</th></tr></thead>
                                <tbody>
                                <?php $previousTagGroup = null; foreach ($retrospect['scheduleItems'] as $index => $item): ?>
                                    <?php
                                    $tagGroupKey = ($item['tagGroupKind'] ?? '') . ':' . ($item['tagGroupLabel'] ?? '');
                                    if ($retrospect['sort'] === 'tag' && $tagGroupKey !== $previousTagGroup):
                                        $previousTagGroup = $tagGroupKey;
                                    ?>
                                        <tr class="retrospect-tag-group"><th class="tag-group-wide" scope="row" colspan="5"><span class="retrospect-tag-group-content"><span><?= e((string) $item['tagGroupLabel']) ?></span><span>실제 합계 <?= e((string) $item['tagTotalLabel']) ?></span></span></th><th class="tag-group-compact" scope="row" colspan="3"><span class="retrospect-tag-group-content"><span><?= e((string) $item['tagGroupLabel']) ?></span><span>실제 합계 <?= e((string) $item['tagTotalLabel']) ?></span></span></th></tr>
                                    <?php endif;
                                    $hasActual = (string) $item['actualTimeRange'] !== '';
                                    $hasMultipleActuals = (int) ($item['linkedActualCount'] ?? 0) > 1;
                                    $differenceLabel = $item['kind'] === 'actual'
                                        ? (!empty($item['linkAmbiguous']) ? '연결 블록 미확인' : '계획 외')
                                        : ($hasMultipleActuals ? '실제 일정 여러 건' : (!empty($item['hasDifference'])
                                            ? '🕒 ' . (string) $item['durationDifferenceLabel']
                                            : (!empty($item['isLinked']) ? '연결' : '미연결')));
                                    ?>
                                    <tr class="<?= $item['kind'] === 'actual' ? 'is-unplanned' : '' ?> <?= $hasActual ? 'has-actual' : '' ?>" data-composition-category="<?= $item['kind'] === 'plan' ? 'plan' : '' ?><?= $hasActual ? ' actual' : '' ?>"<?= $hasActual ? ' style="--tag-color:' . e((string) $item['tagColor']) . '"' : '' ?>>
                                        <td><span class="retrospect-link-state" aria-label="<?= $item['kind'] === 'actual' ? (!empty($item['linkAmbiguous']) ? '연결 블록 미확인' : '계획 외 일정') : (!empty($item['isLinked']) ? '실제 일정 연결됨' : '실제 일정 미연결') ?>"><?= $item['kind'] === 'actual' ? '•' : (!empty($item['isLinked']) ? '☑' : '☐') ?></span></td>
                                        <td>
                                            <button type="button" class="retrospect-table-link retrospect-schedule-title" data-detail-open="schedule-detail-<?= e((string) $index) ?>">
                                                <span><?php if ($item['kind'] === 'plan'): ?><b><?= e((string) $item['importance']) ?></b><?php endif; ?><span class="<?= !empty($item['isLongDuration']) ? 'duration-highlight' : '' ?>"><?= e((string) $item['title']) ?></span></span>
                                                <small><?= e((string) ($hasActual ? $item['actualTimeRange'] : $item['planTimeRange'])) ?> · <?= e($differenceLabel) ?></small>
                                            </button>
                                        </td>
                                        <td class="retrospect-table-time"><?= e((string) ($hasMultipleActuals ? $item['planTimeRange'] : ($hasActual ? $item['actualTimeRange'] : $item['planTimeRange']))) ?></td>
                                        <td class="retrospect-table-difference"><?= $item['kind'] === 'plan' && !empty($item['isLinked']) ? '연결(' . e((string) ($item['linkedActualCount'] ?? 1)) . ')' : e($differenceLabel) ?></td>
                                        <td class="retrospect-table-energy"><?php if ($hasMultipleActuals): ?><span class="energy-change is-none">상세</span><?php elseif ($item['energyValue'] === null): ?><span class="energy-change is-none">—</span><?php else: ?><span class="energy-change <?= $item['energyEffectType'] === 'recover' ? 'is-recover' : 'is-consume' ?>" aria-label="실행력 <?= e((string) $item['energyValue']) ?> <?= $item['energyEffectType'] === 'recover' ? '회복' : '소모' ?>"><?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?></span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
