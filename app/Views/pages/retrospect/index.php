<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$date = (string) $retrospect['date'];
$isToday = !empty($retrospect['isToday']);
$canEdit = !empty($retrospect['canEdit']);
$isEmptyHistory = !empty($retrospect['isEmptyHistory']);
$isFuture = !empty($retrospect['isFuture']);
$report = $retrospect['report'] ?? null;
$isSubmitted = is_array($report) && (string) ($report['status'] ?? '') === 'submitted';
$texts = $retrospect['texts'] ?? ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
$recordComposition = $retrospect['recordComposition'] ?? ['total' => 0, 'routine' => 0, 'plan' => 0, 'actual' => 0, 'memo' => 0];
$settings = $retrospect['settings'] ?? [];
$todayDate = (string) ($retrospect['todayDate'] ?? date('Y-m-d'));
$selectedView = (string) ($selectedView ?? 'daily');
$executionEnergy = $executionEnergy ?? [];
$energyDay = $executionEnergy['day'] ?? [];
$conditionLabels = [
    'sleep_state' => ['수면', 1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음'],
    'fatigue_state' => ['피로', 1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음'],
    'mood_state' => ['기분', 1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음'],
    'stress_state' => ['스트레스', 1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음'],
];
$conditionParts = [];
foreach ($conditionLabels as $field => $labels) { $value = $energyDay[$field] ?? null; if ($value !== null && isset($labels[(int) $value])) $conditionParts[] = $labels[0] . ' ' . $labels[(int) $value]; }
$burdenLabels = ['lower' => '예상보다 낮음', 'similar' => '예상과 비슷함', 'higher' => '예상보다 높음'];
if (isset($burdenLabels[(string) ($energyDay['burden_feedback'] ?? '')])) $conditionParts[] = '체감 부담 ' . $burdenLabels[(string) $energyDay['burden_feedback']];
?>

<main class="page retrospect-page" data-retrospect-page data-date="<?= e($date) ?>">
    <h1 class="sr-only">회고</h1>
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <div class="retrospect-primary-context">
        <nav class="retrospect-view-switch" aria-label="회고 보기">
            <a href="/retrospect?date=<?= e($date) ?>" <?= $selectedView === 'daily' ? 'aria-current="page"' : '' ?>>일일 회고</a>
            <a href="/retrospect?view=history" <?= $selectedView === 'history' ? 'aria-current="page"' : '' ?>>발행 이력</a>
            <a href="/retrospect?view=goals" <?= $selectedView === 'goals' ? 'aria-current="page"' : '' ?>>목표 회고</a>
        </nav>
        <?php require __DIR__ . '/../../components/daily-context-actions.php'; ?>
    </div>

    <?php if ($selectedView === 'history'): ?>
        <section class="retrospect-history" aria-labelledby="retrospectHistoryTitle">
            <h2 id="retrospectHistoryTitle">발행한 회고</h2>
            <?php if (empty($history['items'])): ?>
                <p class="retrospect-empty">아직 발행한 회고가 없습니다.</p>
            <?php else: ?>
                <ul class="retrospect-history-list">
                    <?php foreach ($history['items'] as $item): ?>
                        <li><a href="/retrospect/report?date=<?= e((string) $item['date']) ?>"><span><strong><?= e((string) $item['dateTitle']) ?></strong> <?= e((string) $item['dateSubTitle']) ?></span><small>계획 <?= e((string) $item['planAchievementRate']) ?>% · 루틴 <?= e((string) $item['routineAchievementRate']) ?>%</small><p><?= e((string) ($item['snippet'] !== '' ? $item['snippet'] : '작성된 KPT 내용이 없습니다.')) ?></p></a></li>
                    <?php endforeach; ?>
                </ul>
                <nav class="retrospect-pagination" aria-label="발행 이력 페이지">
                    <?php if (!empty($history['hasPrevious'])): ?><a class="btn btn-secondary" href="/retrospect?view=history&page=<?= e((string) ((int) $history['page'] - 1)) ?>">이전</a><?php endif; ?>
                    <?php if (!empty($history['hasNext'])): ?><a class="btn btn-secondary" href="/retrospect?view=history&page=<?= e((string) ((int) $history['page'] + 1)) ?>">다음</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php elseif ($selectedView === 'goals'): ?>
        <section class="retrospect-goal-review" aria-label="목표별 실행 피드백">
            <?php if (empty($goalReview['goals'])): ?>
                <div class="retrospect-empty-state"><strong>회고할 목표가 없습니다.</strong><p>목표를 만들고 계획이나 루틴을 연결하면 실행 흐름을 확인할 수 있습니다.</p></div>
            <?php else: ?>
                <?php foreach ($goalReview['goals'] as $goal): ?>
                    <article class="retrospect-goal-card"><div class="retrospect-goal-card-title"><span><?= e((string) $goal['type']) ?></span><strong><?= e((string) $goal['title']) ?></strong><small><?= e((string) $goal['statusLabel']) ?></small></div><div class="retrospect-goal-metrics"><span>계획 <?= e((string) $goal['planExecutionRate']) ?>%</span><span>루틴 <?= e((string) $goal['routineDoneDayCount']) ?>일</span><span>실제 일정 <?= e((string) $goal['actualEventCount']) ?>건</span></div><p><?= e((string) $goal['feedback']) ?></p></article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section class="retrospect-history" aria-labelledby="completedRoutineTitle">
            <h2 id="completedRoutineTitle">완료된 루틴</h2>
            <?php if (empty($goalReview['routineHistory'])): ?>
                <p class="retrospect-empty">완료된 루틴이 없습니다.</p>
            <?php else: ?>
                <ul class="retrospect-history-list">
                    <?php foreach ($goalReview['routineHistory'] as $routine): ?>
                        <li><a href="/manage/routines"><span><strong><?= e((string) $routine['name']) ?></strong> <?= e((string) $routine['statusLabel']) ?></span><small><?= e((string) $routine['startDate']) ?> ~ <?= e((string) $routine['endDate']) ?></small><p><?= e((string) $routine['doneCount']) ?>/<?= e((string) $routine['durationDays']) ?>일 · <?= e((string) $routine['achievementRate']) ?>%</p></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="retrospect-toolbar" aria-label="회고 빠른 이동">
            <?php if (!$isToday): ?><a class="btn btn-secondary" href="/retrospect?date=<?= e($todayDate) ?>">오늘로 이동</a><?php endif; ?>
            <button type="button" class="btn btn-secondary" data-retrospect-open-date>날짜 선택</button>
            <button type="button" class="btn btn-secondary" data-retrospect-open-settings><?= e((string) ($settings['autoPublishLabel'] ?? '자동 발행 끔')) ?></button>
        </div>
        <section class="retrospect-date-bar" aria-label="회고 날짜 탐색"><a class="retrospect-date-button" href="/retrospect?date=<?= e((string) $retrospect['prevDate']) ?>" aria-label="이전 날짜">&lsaquo;</a><div><strong><?= e((string) $retrospect['dateTitle']) ?></strong><span><?= e((string) $retrospect['dateSubTitle']) ?> · <?= e((string) $retrospect['statusLabel']) ?></span></div><a class="retrospect-date-button" href="/retrospect?date=<?= e((string) $retrospect['nextDate']) ?>" aria-label="다음 날짜">&rsaquo;</a></section>

        <?php if ($isEmptyHistory): ?>
            <?php if ($isFuture): ?>
                <section class="retrospect-empty-state retrospect-future-state"><strong><?= e(t('retrospect.future_title')) ?></strong><p><?= e(t((string) ($retrospect['futureMessageKey'] ?? 'retrospect.future_message_1'))) ?></p></section>
            <?php else: ?>
                <section class="retrospect-empty-state"><strong>발행된 회고가 없습니다.</strong><p>오늘과 어제의 회고만 새로 작성할 수 있습니다.</p></section>
            <?php endif; ?>
        <?php else: ?>
            <section class="retrospect-composition-card" aria-label="오늘 기록 비중">
                <?php if ((int) $recordComposition['total'] > 0): ?>
                    <div class="retrospect-composition-bar" role="img" aria-label="루틴 <?= e((string) $recordComposition['routine']) ?>%, 계획 <?= e((string) $recordComposition['plan']) ?>%, 일정 <?= e((string) $recordComposition['actual']) ?>%, 메모 <?= e((string) $recordComposition['memo']) ?>%"><span class="is-routine" style="--share:<?= e((string) $recordComposition['routine']) ?>%"></span><span class="is-plan" style="--share:<?= e((string) $recordComposition['plan']) ?>%"></span><span class="is-actual" style="--share:<?= e((string) $recordComposition['actual']) ?>%"></span><span class="is-memo" style="--share:<?= e((string) $recordComposition['memo']) ?>%"></span></div>
                    <div class="retrospect-composition-legend"><span><i class="is-routine"></i>루틴 <b><?= e((string) $recordComposition['routine']) ?>%</b></span><span><i class="is-plan"></i>계획 <b><?= e((string) $recordComposition['plan']) ?>%</b></span><span><i class="is-actual"></i>일정 <b><?= e((string) $recordComposition['actual']) ?>%</b></span><span><i class="is-memo"></i>메모 <b><?= e((string) $recordComposition['memo']) ?>%</b></span></div>
                <?php else: ?>
                    <p class="retrospect-composition-empty">오늘 기록이 아직 없어요.</p>
                <?php endif; ?>
            </section>
            <section class="retrospect-energy-feedback" aria-label="하루 시작 실행력"><div><strong>시작 실행력 <?= e((string) ($executionEnergy['startCapacity'] ?? 0)) ?></strong><span>기본 <?= e((string) ($energyDay['base_capacity_snapshot'] ?? 500)) ?> · 보정 <?= (int) ($executionEnergy['morningAdjustment'] ?? 0) >= 0 ? '+' : '' ?><?= e((string) ($executionEnergy['morningAdjustment'] ?? 0)) ?></span></div><p><?= $conditionParts === [] ? '오늘의 컨디션을 입력하지 않아 기본 실행력으로 시작했어요.' : e(implode(' · ', $conditionParts)) ?></p><small>오늘 소모 <?= e((string) ($executionEnergy['consumed'] ?? 0)) ?> · 회복 <?= e((string) ($executionEnergy['recovered'] ?? 0)) ?> · <?= e((string) ($executionEnergy['feedbackMessage'] ?? '')) ?></small></section>

            <section class="retrospect-daily-card">
                <div class="retrospect-record-tabs" role="tablist" aria-label="하루 기록"><button type="button" role="tab" aria-selected="true" data-record-tab="routine">루틴 <b><?= e((string) count($retrospect['routineItems'])) ?></b></button><button type="button" role="tab" aria-selected="false" data-record-tab="schedule">일정 <b><?= e((string) count($retrospect['scheduleItems'])) ?></b></button><button type="button" role="tab" aria-selected="false" data-record-tab="memo">메모 <b><?= e((string) count($retrospect['memoItems'])) ?></b></button></div>
                <div class="retrospect-tab-panel" role="tabpanel" data-record-panel="routine">
                    <?php if (empty($retrospect['routineItems'])): ?><div class="retrospect-empty-action"><p>오늘 확인할 루틴이 없어요.</p><a class="btn btn-secondary" href="/manage/routines">루틴 만들기</a></div>
                    <?php else: ?>
                        <div class="retrospect-table-wrap">
                            <table class="retrospect-record-table retrospect-routine-table">
                                <thead><tr><th scope="col">루틴명</th><th scope="col">연속 달성</th><th scope="col">루틴 완료 체크</th></tr></thead>
                                <tbody>
                                    <?php foreach ($retrospect['routineItems'] as $index => $routine): ?>
                                        <?php $state = (string) ($routine['state'] ?? ''); ?>
                                        <tr>
                                            <td><button type="button" class="retrospect-table-link" data-detail-open="routine-detail-<?= e((string) $index) ?>"><?= e((string) $routine['name']) ?></button></td>
                                            <td class="retrospect-table-streak"><?php if ((int) ($routine['streakCount'] ?? 0) >= 3): ?>🔥 <?= e((string) $routine['streakCount']) ?>일<?php else: ?>-<?php endif; ?></td>
                                            <td class="retrospect-table-state"><?php if ($isToday): ?><form method="post" action="/routine/toggle" data-retrospect-routine-toggle-form><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="return_to" value="retrospect"><input type="hidden" name="date" value="<?= e($date) ?>"><input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>"><button type="submit" class="routine-state-control <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?>" data-routine-toggle-control data-retrospect-routine-state data-state="<?= e($state) ?>" aria-label="<?= e((string) $routine['name']) ?> 상태 변경"><span data-routine-state-marker aria-hidden="true"><?= $state === 'O' ? '✓' : ($state === 'X' ? '×' : '') ?></span></button></form><?php else: ?><strong class="retrospect-state-label"><?= e((string) $routine['stateLabel']) ?></strong><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="retrospect-tab-panel" role="tabpanel" data-record-panel="schedule" hidden>
                    <div class="retrospect-sort-tabs" aria-label="일정 정렬"><a href="/retrospect?date=<?= e($date) ?>&sort=importance#schedule" <?= $retrospect['sort'] === 'importance' ? 'aria-current="page"' : '' ?>>중요도순</a><a href="/retrospect?date=<?= e($date) ?>&sort=time#schedule" <?= $retrospect['sort'] === 'time' ? 'aria-current="page"' : '' ?>>시간순</a></div>
                    <?php if (empty($retrospect['scheduleItems'])): ?><div class="retrospect-empty-action"><p>이 날짜에 계획된 일정이 없어요.</p><a class="btn btn-secondary" href="/calendar?date=<?= e($date) ?>">일정 추가</a></div>
                    <?php else: ?>
                        <div class="retrospect-table-wrap">
                            <table class="retrospect-record-table retrospect-schedule-table">
                                <thead><tr><th scope="col">상태</th><th scope="col">일정</th><th scope="col">시간</th><th scope="col">차이</th><th scope="col">실행력</th></tr></thead>
                                <tbody>
                                <?php foreach ($retrospect['scheduleItems'] as $index => $item): ?>
                                    <?php
                                    $hasActual = (string) $item['actualTimeRange'] !== '';
                                    $differenceLabel = $item['kind'] === 'actual'
                                        ? '계획 외'
                                        : (!empty($item['hasDifference'])
                                            ? '🕒 ' . (string) $item['durationDifferenceLabel']
                                            : (!empty($item['isLinked']) ? '연결' : '미연결'));
                                    ?>
                                    <tr class="<?= $item['kind'] === 'actual' ? 'is-unplanned' : '' ?> <?= $hasActual ? 'has-actual' : '' ?>"<?= $hasActual ? ' style="--tag-color:' . e((string) $item['tagColor']) . '"' : '' ?>>
                                        <td><span class="retrospect-link-state" aria-label="<?= $item['kind'] === 'actual' ? '계획 외 일정' : (!empty($item['isLinked']) ? '실제 일정 연결됨' : '실제 일정 미연결') ?>"><?= $item['kind'] === 'actual' ? '•' : (!empty($item['isLinked']) ? '☑' : '☐') ?></span></td>
                                        <td>
                                            <button type="button" class="retrospect-table-link retrospect-schedule-title" data-detail-open="schedule-detail-<?= e((string) $index) ?>">
                                                <span><?php if ($item['kind'] === 'plan'): ?><b><?= e((string) $item['importance']) ?></b><?php endif; ?><span class="<?= !empty($item['isLongDuration']) ? 'duration-highlight' : '' ?>"><?= e((string) $item['title']) ?></span></span>
                                                <small><?= e((string) ($hasActual ? $item['actualTimeRange'] : $item['planTimeRange'])) ?> · <?= e($differenceLabel) ?></small>
                                            </button>
                                        </td>
                                        <td class="retrospect-table-time"><?= e((string) ($hasActual ? $item['actualTimeRange'] : $item['planTimeRange'])) ?></td>
                                        <td class="retrospect-table-difference"><?= e($differenceLabel) ?></td>
                                        <td class="retrospect-table-energy"><?php if ($item['energyValue'] === null): ?><span class="energy-change is-none">—</span><?php else: ?><span class="energy-change <?= $item['energyEffectType'] === 'recover' ? 'is-recover' : 'is-consume' ?>" aria-label="실행력 <?= e((string) $item['energyValue']) ?> <?= $item['energyEffectType'] === 'recover' ? '회복' : '소모' ?>"><?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?></span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="retrospect-tab-panel" role="tabpanel" data-record-panel="memo" hidden>
                    <?php if (empty($retrospect['memoItems'])): ?><p class="retrospect-empty">이 날짜에 작성한 메모가 없어요.</p>
                    <?php else: ?>
                        <ul class="retrospect-memo-list">
                            <?php foreach ($retrospect['memoItems'] as $index => $memo): ?>
                                <li><button type="button" data-detail-open="memo-detail-<?= e((string) $index) ?>">
                                    <time><?= e((string) $memo['timeLabel']) ?></time>
                                    <span class="retrospect-memo-copy"><small><?= e((string) $memo['sourceLabel']) ?><?= (string) ($memo['scheduleTitle'] ?? '') !== '' ? ' · ' . e((string) $memo['scheduleTitle']) : '' ?></small><span><?= e((string) preg_replace('/\s+/u', ' ', (string) $memo['content'])) ?></span></span>
                                </button></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>
            <?php if (!$canEdit && $isSubmitted): ?><section class="retrospect-published-kpt"><h2>KPT 회고</h2><article><strong>Keep</strong><p><?= nl2br(e((string) $texts['today_review'])) ?: '작성된 내용이 없습니다.' ?></p></article><article><strong>Problem</strong><p><?= nl2br(e((string) $texts['today_thoughts'])) ?: '작성된 내용이 없습니다.' ?></p></article><article><strong>Try</strong><p><?= nl2br(e((string) $texts['tomorrow_plan'])) ?: '작성된 내용이 없습니다.' ?></p></article></section><?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php if ($selectedView === 'daily' && $canEdit): ?>
    <button type="button" class="btn btn-primary daily-floating-action retrospect-floating-review" data-kpt-open><?= $isSubmitted ? '회고 수정' : '회고하기' ?></button>
    <section class="retrospect-kpt-sheet" data-kpt-sheet hidden aria-labelledby="kptSheetTitle">
        <form id="retrospectKptForm" method="post" action="/retrospect/draft" data-kpt-form data-published="<?= $isSubmitted ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <header>
                <div class="retrospect-kpt-heading">
                    <div class="retrospect-kpt-meta"><small data-kpt-progress>1 / 3</small><time data-kpt-timer datetime="PT10M" aria-label="회고 남은 시간 10분">10:00</time></div>
                    <strong id="kptSheetTitle">KPT 회고</strong>
                    <span data-kpt-save-status aria-live="polite"></span>
                    <span class="retrospect-kpt-timer-message" data-kpt-timer-message aria-live="polite"></span>
                </div>
                <div class="retrospect-kpt-actions">
                    <button type="button" class="btn btn-secondary" data-kpt-close>닫기</button><button type="button" class="btn btn-secondary" data-kpt-collapse>기록 보기</button><button type="button" class="btn btn-secondary" data-kpt-previous>이전</button><button type="button" class="btn btn-primary" data-kpt-next>다음</button><button type="submit" class="btn btn-primary" formaction="/retrospect/publish" data-kpt-publish hidden><?= $isSubmitted ? '재발행' : '발행' ?></button>
                </div>
            </header>
            <div class="retrospect-kpt-body">
                <section data-kpt-step="0"><label for="todayReview">Keep</label><p>오늘 잘 유지했고 앞으로도 계속하고 싶은 것은 무엇인가요?</p><textarea class="input" id="todayReview" name="today_review" maxlength="2000" rows="6"><?= e((string) $texts['today_review']) ?></textarea></section>
                <section data-kpt-step="1" hidden><label for="todayThoughts">Problem</label><p>아쉽거나 흐름을 방해한 문제는 무엇이었나요?</p><textarea class="input" id="todayThoughts" name="today_thoughts" maxlength="2000" rows="6"><?= e((string) $texts['today_thoughts']) ?></textarea></section>
                <section data-kpt-step="2" hidden><label for="tomorrowPlan">Try</label><p>다음 하루에 작게 시도해볼 변화는 무엇인가요?</p><textarea class="input" id="tomorrowPlan" name="tomorrow_plan" maxlength="2000" rows="6"><?= e((string) $texts['tomorrow_plan']) ?></textarea></section>
            </div>
        </form>
    </section>
<?php endif; ?>

<div class="retrospect-local-layer" data-routine-detail-layer hidden><button type="button" class="retrospect-local-overlay" data-detail-close aria-label="루틴 상세 닫기"></button><section class="retrospect-sheet retrospect-detail-sheet" role="dialog" aria-labelledby="routineDetailTitle"><div class="retrospect-sheet-header"><strong id="routineDetailTitle">루틴 상세</strong><button type="button" class="ui-close-button" data-detail-close aria-label="닫기">×</button></div>
    <?php foreach (($retrospect['routineItems'] ?? []) as $index => $routine): ?><article data-detail-panel="routine-detail-<?= e((string) $index) ?>" hidden><h2><?= e((string) $routine['name']) ?></h2><p>연결 목표: <?= e((string) ($routine['goalTitle'] !== '' ? $routine['goalTitle'] : '없음')) ?></p><p><?= e((string) ($routine['startDate'] ?? '')) ?> ~ <?= e((string) ($routine['endDate'] ?? '')) ?></p><div class="retrospect-routine-calendar"><div class="retrospect-weekdays"><span>월</span><span>화</span><span>수</span><span>목</span><span>금</span><span>토</span><span>일</span></div><?php foreach (($routine['periodGroups'] ?? []) as $month): ?><section><strong><?= e((string) $month['label']) ?></strong><div class="retrospect-calendar-grid"><?php for ($blank = 0; $blank < (int) $month['leadingBlankCount']; $blank++): ?><i></i><?php endfor; ?><?php foreach ($month['cells'] as $cell): ?><span class="<?= $cell['state'] === 'O' ? 'is-done' : ($cell['state'] === 'X' ? 'is-failed' : '') ?>" title="<?= e((string) $cell['date']) ?>"><?= e((string) $cell['day']) ?></span><?php endforeach; ?></div></section><?php endforeach; ?></div></article><?php endforeach; ?>
</section></div>

<div class="retrospect-local-layer" data-schedule-detail-layer hidden><button type="button" class="retrospect-local-overlay" data-detail-close aria-label="일정 상세 닫기"></button><section class="retrospect-sheet retrospect-detail-sheet" role="dialog" aria-labelledby="scheduleDetailTitle"><div class="retrospect-sheet-header"><strong id="scheduleDetailTitle">일정 상세</strong><button type="button" class="ui-close-button" data-detail-close aria-label="닫기">×</button></div>
    <?php foreach (($retrospect['scheduleItems'] ?? []) as $index => $item): ?><article data-detail-panel="schedule-detail-<?= e((string) $index) ?>" hidden><h2><?= e((string) $item['title']) ?></h2><dl><dt>연결 목표</dt><dd><?= e((string) ($item['goalTitle'] !== '' ? $item['goalTitle'] : '없음')) ?></dd><dt>계획 일정</dt><dd><?= e((string) ($item['planTimeRange'] !== '' ? $item['title'] . ' · ' . $item['planTimeRange'] : '계획 외 일정')) ?></dd><dt>실제 일정</dt><dd><?= e((string) ($item['actualTimeRange'] !== '' ? $item['actualTitle'] . ' · ' . $item['actualTimeRange'] : '연결되지 않음')) ?></dd><?php if ($item['tagName'] !== ''): ?><dt>일정 태그</dt><dd><span class="retrospect-tag-line" style="--tag-color:<?= e((string) $item['tagColor']) ?>"><?= e((string) $item['tagName']) ?></span></dd><?php endif; ?><?php if ($item['energyValue'] !== null): ?><dt>실행력</dt><dd><?= e((string) ($item['energyGroupName'] !== '' ? $item['energyGroupName'] . ' · ' : '')) ?><?= $item['energyEffectType'] === 'recover' ? '+' : '−' ?><?= e((string) $item['energyValue']) ?> <?= $item['energyEffectType'] === 'recover' ? '회복' : '소모' ?></dd><?php endif; ?><?php if ($item['startDifferenceLabel'] !== ''): ?><dt>시작 차이</dt><dd><?= e((string) $item['startDifferenceLabel']) ?></dd><dt>종료 차이</dt><dd><?= e((string) $item['endDifferenceLabel']) ?></dd><dt>소요시간 차이</dt><dd><?= e((string) $item['durationDifferenceLabel']) ?></dd><?php endif; ?><?php if ($item['actualMemo'] !== ''): ?><dt>실제 일정 메모</dt><dd><?= nl2br(e((string) $item['actualMemo'])) ?></dd><?php endif; ?></dl></article><?php endforeach; ?>
</section></div>

<div class="retrospect-local-layer" data-memo-detail-layer hidden><button type="button" class="retrospect-local-overlay" data-detail-close aria-label="메모 상세 닫기"></button><section class="retrospect-sheet retrospect-detail-sheet" role="dialog" aria-labelledby="memoDetailTitle"><div class="retrospect-sheet-header"><strong id="memoDetailTitle">메모 상세</strong><button type="button" class="ui-close-button" data-detail-close aria-label="닫기">×</button></div>
    <?php foreach (($retrospect['memoItems'] ?? []) as $index => $memo): ?><article data-detail-panel="memo-detail-<?= e((string) $index) ?>" hidden><h2><?= e((string) $memo['timeLabel']) ?> <?= e((string) $memo['sourceLabel']) ?></h2><?php if ((string) ($memo['scheduleTitle'] ?? '') !== ''): ?><p class="retrospect-memo-source">연결 일정 · <?= e((string) $memo['scheduleTitle']) ?></p><?php endif; ?><p class="retrospect-memo-full"><?= nl2br(e((string) $memo['content'])) ?></p><?php if ((string) ($memo['source'] ?? '') === 'schedule'): ?><a class="btn btn-secondary" href="/calendar?date=<?= e($date) ?>">일정에서 보기</a><?php endif; ?></article><?php endforeach; ?>
</section></div>

<div class="retrospect-local-layer" data-retrospect-settings-layer hidden><button type="button" class="retrospect-local-overlay" data-retrospect-close-settings aria-label="자동 발행 설정 닫기"></button><section class="retrospect-sheet" role="dialog" aria-labelledby="retrospectSettingsTitle"><div class="retrospect-sheet-header"><strong id="retrospectSettingsTitle">자동 회고 발행</strong><button type="button" class="ui-close-button" data-retrospect-close-settings>×</button></div><form class="retrospect-settings-form" method="post" action="/retrospect/settings"><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="date" value="<?= e($date) ?>"><label class="retrospect-toggle-row"><input type="checkbox" name="auto_publish_enabled" value="1" <?= !empty($settings['autoPublishEnabled']) ? 'checked' : '' ?>><span>설정한 시간에 오늘 회고를 자동 발행합니다.</span></label><label class="form-label" for="autoPublishTime">자동 발행 시간</label><input class="input" id="autoPublishTime" name="auto_publish_time" type="time" value="<?= e((string) ($settings['autoPublishTime'] ?? '22:00')) ?>"><button type="submit" class="btn btn-primary">설정 저장</button></form></section></div>
<div class="retrospect-local-layer" data-retrospect-date-layer hidden><button type="button" class="retrospect-local-overlay" data-retrospect-close-date aria-label="날짜 선택 닫기"></button><section class="retrospect-sheet" role="dialog" aria-labelledby="retrospectDateTitle"><div class="retrospect-sheet-header"><strong id="retrospectDateTitle">특정 날짜 회고 보기</strong><button type="button" class="ui-close-button" data-retrospect-close-date>×</button></div><form class="retrospect-settings-form" method="get" action="/retrospect"><label class="form-label" for="retrospectDate">날짜 선택</label><input class="input" id="retrospectDate" name="date" type="date" value="<?= e($date) ?>"><button type="submit" class="btn btn-primary">회고 보기</button></form></section></div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
