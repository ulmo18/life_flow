<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$date = (string) $retrospect['date'];
$isToday = !empty($retrospect['isToday']);
$canEdit = !empty($retrospect['canEdit']);
$isEmptyHistory = !empty($retrospect['isEmptyHistory']);
$showEmptyGuide = !empty($retrospect['showEmptyGuide']);
$isFuture = !empty($retrospect['isFuture']);
$report = $retrospect['report'] ?? null;
$isSubmitted = is_array($report) && (string) ($report['status'] ?? '') === 'submitted';
$isEditingPublished = !empty($retrospect['isEditingPublished']);
$showPublishedCard = $isSubmitted && !$isEditingPublished && !$isFuture;
$editQuery = $isEditingPublished ? '&amp;edit=1' : '';
$texts = $retrospect['texts'] ?? ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
$recordComposition = $retrospect['recordComposition'] ?? ['total' => 0, 'routine' => 0, 'plan' => 0, 'actual' => 0, 'memo' => 0];
$todayDate = (string) ($retrospect['todayDate'] ?? date('Y-m-d'));
$selectedView = (string) ($selectedView ?? 'daily');
?>

<main class="page retrospect-page" data-retrospect-page data-date="<?= e($date) ?>">
    <h1 class="sr-only">회고</h1>
    <div class="retrospect-primary-context<?= $selectedView === 'history' ? ' is-history' : '' ?>">
        <?php if ($selectedView === 'daily'): ?>
        <?php $dailyDateContext = ['title' => '회고', 'path' => '/retrospect', 'date' => $date, 'prevDate' => $retrospect['prevDate'], 'nextDate' => $retrospect['nextDate'], 'todayDate' => $todayDate, 'isToday' => $isToday, 'inputId' => 'retrospectContextDate', 'view' => $selectedView]; require __DIR__ . '/../../components/daily-date-navigation.php'; ?>
        <?php else: ?>
        <div class="retrospect-history-header"><strong>회고</strong><?php require __DIR__ . '/../../components/daily-context-actions.php'; ?></div>
        <?php endif; ?>
        <nav class="retrospect-view-switch" aria-label="회고 보기">
            <a href="/retrospect?date=<?= e($date) ?>" <?= $selectedView === 'daily' ? 'aria-current="page"' : '' ?>>일일 회고</a>
            <a href="/retrospect?view=history&amp;date=<?= e($date) ?>" <?= $selectedView === 'history' ? 'aria-current="page"' : '' ?>>발행 이력</a>

        </nav>
    </div>
    <?php if (!empty($flashSuccess)): ?><span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span><?php endif; ?>
    <?php if (!empty($errors['general'])): ?><div class="msg msg-error"><?= e((string) $errors['general']) ?></div><?php endif; ?>

    <?php if ($selectedView === 'history'): ?>
        <section class="retrospect-history" aria-labelledby="retrospectHistoryTitle">
            <h2 id="retrospectHistoryTitle" class="sr-only">발행 이력</h2>
            <form method="get" action="/retrospect" class="retrospect-history-month-search">
                    <input type="hidden" name="view" value="history">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <label class="sr-only" for="historyMonth">발행 회고 검색 월</label>
                    <input id="historyMonth" class="input" type="month" name="month" value="<?= e((string) (($history['month'] ?? '') !== '' ? $history['month'] : substr($todayDate, 0, 7))) ?>" required>
                    <button type="button" class="input retrospect-month-trigger" data-history-month-open aria-haspopup="dialog" aria-controls="historyMonthDialog" hidden></button>
                    <button class="btn btn-secondary" type="submit">월별 보기</button>
                    <?php if (!empty($history['month'])): ?><a class="btn btn-secondary" href="/retrospect?view=history&amp;date=<?= e($date) ?>&amp;month=">전체</a><?php endif; ?>
            </form>
            <dialog id="historyMonthDialog" class="lf-date-dialog retrospect-month-dialog" aria-labelledby="historyMonthTitle">
                <header class="lf-date-header"><strong id="historyMonthTitle">연·월 선택</strong><button type="button" class="lf-date-close" data-history-month-close aria-label="닫기">×</button></header>
                <div class="lf-date-body">
                    <div class="lf-date-navigation"><button type="button" data-history-year-prev aria-label="이전 연도">‹</button><strong data-history-year></strong><button type="button" data-history-year-next aria-label="다음 연도">›</button></div>
                    <div class="lf-date-grid retrospect-month-grid" data-history-month-grid></div>
                </div>
            </dialog>
            <p class="retrospect-date-status"><?= empty($history['month']) ? '전체 기간의 발행 이력' : e((string) $history['month']) . ' 발행 이력' ?></p>
            <?php if (empty($history['items'])): ?>
                <p class="retrospect-empty"><?= empty($history['month']) ? '아직 발행한 회고가 없습니다.' : '선택한 달에 발행된 회고가 없습니다.' ?></p>
            <?php else: ?>
                <ul class="retrospect-history-list retrospect-newspaper-list">
                    <?php $historyMonth = null; ?>
                    <?php foreach ($history['items'] as $item): ?>
                        <?php if ($historyMonth !== $item['monthLabel']): $historyMonth = $item['monthLabel']; ?>
                            <li class="retrospect-newspaper-month"><?= e((string) $historyMonth) ?></li>
                        <?php endif; ?>
                        <li class="retrospect-newspaper-item"><a href="/retrospect/report?date=<?= e((string) $item['date']) ?>">
                            <span class="retrospect-newspaper-date"><strong><?= e((string) $item['dateTitle']) ?></strong><span><?= e((string) $item['dateSubTitle']) ?></span><span class="retrospect-newspaper-arrow" aria-hidden="true">→</span></span>
                            <span class="retrospect-newspaper-kpt">
                                <?php foreach (['Keep' => 'today_review', 'Problem' => 'today_thoughts', 'Try' => 'tomorrow_plan'] as $label => $key): ?>
                                    <?php $previewText = trim((string) ($item['texts'][$key] ?? '')); ?>
                                    <span class="retrospect-newspaper-line"><b><?= e($label) ?></b><span><?= e($previewText !== '' ? $previewText : '작성 없음') ?></span></span>
                                <?php endforeach; ?>
                            </span>
                            <small><?= e((string) ($item['planAchievementLabel'] === '계획 없음' ? '계획 없음' : '계획 ' . $item['planAchievementLabel'])) ?> · 루틴 <?= e((string) $item['routineAchievementRate']) ?>%</small>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
                <nav class="retrospect-pagination" aria-label="발행 이력 페이지">
                    <?php if (!empty($history['hasPrevious'])): ?><a class="btn btn-secondary" href="/retrospect?view=history&amp;date=<?= e($date) ?>&amp;month=<?= e((string) ($history['month'] ?? '')) ?>&amp;page=<?= e((string) ((int) $history['page'] - 1)) ?>">이전</a><?php endif; ?>
                    <?php if (!empty($history['hasNext'])): ?><a class="btn btn-secondary" href="/retrospect?view=history&amp;date=<?= e($date) ?>&amp;month=<?= e((string) ($history['month'] ?? '')) ?>&amp;page=<?= e((string) ((int) $history['page'] + 1)) ?>">다음</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php if ($showEmptyGuide): ?>
            <section class="retrospect-empty-action"><p>아직 회고할 기록이 없어요.<br>일정에서 하루를 기록해 보세요.</p><a class="btn btn-secondary" href="/calendar?date=<?= e($date) ?>">일정으로 이동</a></section>
        <?php elseif ($showPublishedCard): ?>
            <a class="retrospect-past-paper" href="/retrospect/report?date=<?= e($date) ?>">
                <span class="retrospect-past-paper-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14v15H5zM8 8h4v4H8zM14 8h2M14 11h2M8 15h8M8 17h8"/></svg></span>
                <span class="retrospect-past-paper-copy"><strong>지난 회고</strong><span><?= e(str_replace('-', '.', $date)) ?>의 기록</span></span>
                <span class="retrospect-past-paper-action">발행 회고 보기 <span aria-hidden="true">→</span></span>
            </a>
            <?php if ($canEdit): ?><a class="btn btn-secondary retrospect-edit-published" href="/retrospect?date=<?= e($date) ?>&amp;edit=1">회고 수정</a><?php endif; ?>
        <?php elseif ($isEmptyHistory): ?>
            <?php if ($isFuture): ?>
                <section class="retrospect-empty-state retrospect-future-state"><strong><?= e(t('retrospect.future_title')) ?></strong><p><?= e(t((string) ($retrospect['futureMessageKey'] ?? 'retrospect.future_message_1'))) ?></p></section>
            <?php else: ?>
                <section class="retrospect-empty-state retrospect-unpublished-state"><strong>발행된 회고가 없습니다.</strong><p>오늘과 어제의 회고만 새로 작성할 수 있습니다.</p></section>
            <?php endif; ?>
        <?php else: ?>
            <section class="retrospect-composition-card" aria-label="기록 유형별 비중">
                <div class="retrospect-composition-heading"><h2 class="retrospect-composition-title">기록 유형별 비중</h2><button type="button" class="retrospect-composition-info" data-composition-info aria-label="기록 비중 안내" aria-expanded="false" aria-controls="retrospectCompositionHelp" aria-describedby="retrospectCompositionHelp"><span aria-hidden="true">i</span></button><span id="retrospectCompositionHelp" class="retrospect-composition-help" role="tooltip" hidden>메모가 있는 실제 일정은 실제와 메모에 각각 포함됩니다.</span></div>
                <?php if ((int) $recordComposition['total'] > 0): ?>
                    <div class="retrospect-composition-bar" role="img" aria-label="루틴 <?= e((string) $recordComposition['routine']) ?>%, 계획 <?= e((string) $recordComposition['plan']) ?>%, 일정 <?= e((string) $recordComposition['actual']) ?>%, 메모 <?= e((string) $recordComposition['memo']) ?>%"><span class="is-routine" style="--share:<?= e((string) $recordComposition['routine']) ?>%"></span><span class="is-plan" style="--share:<?= e((string) $recordComposition['plan']) ?>%"></span><span class="is-actual" style="--share:<?= e((string) $recordComposition['actual']) ?>%"></span><span class="is-memo" style="--share:<?= e((string) $recordComposition['memo']) ?>%"></span></div>
                    <div class="retrospect-composition-legend">
                        <button type="button" data-composition-target="routine" aria-pressed="false"><span><i class="is-routine"></i>루틴</span><b><?= e((string) count($retrospect['routineItems'])) ?>건 · <?= e((string) $recordComposition['routine']) ?>%</b></button>
                        <button type="button" data-composition-target="plan" aria-pressed="false"><span><i class="is-plan"></i>계획</span><b><?= e((string) count($retrospect['planItems'])) ?>건 · <?= e((string) $recordComposition['plan']) ?>%</b></button>
                        <button type="button" data-composition-target="actual" aria-pressed="false"><span><i class="is-actual"></i>실제</span><b><?= e((string) count($retrospect['actualItems'])) ?>건 · <?= e((string) $recordComposition['actual']) ?>%</b></button>
                        <button type="button" data-composition-target="memo" aria-pressed="false"><span><i class="is-memo"></i>메모</span><b><?= e((string) count($retrospect['memoItems'])) ?>건 · <?= e((string) $recordComposition['memo']) ?>%</b></button>
                    </div>
                <?php else: ?>
                    <p class="retrospect-composition-empty">오늘 기록이 아직 없어요.</p>
                <?php endif; ?>
            </section>
            <section class="retrospect-daily-card">
                <div class="retrospect-record-tabs" role="tablist" aria-label="하루 기록"><button type="button" role="tab" aria-selected="true" data-record-tab="routine">루틴 <b><?= e((string) count($retrospect['routineItems'])) ?></b></button><button type="button" role="tab" aria-selected="false" data-record-tab="schedule">일정 <b><?= e((string) count($retrospect['scheduleItems'])) ?></b></button><button type="button" role="tab" aria-selected="false" data-record-tab="memo">메모 <b><?= e((string) count($retrospect['memoItems'])) ?></b></button></div>
                <div class="retrospect-tab-panel" role="tabpanel" data-record-panel="routine">
                    <?php if (empty($retrospect['routineItems'])): ?><div class="retrospect-empty-action"><p>오늘 확인할 루틴이 없어요.</p><a class="btn btn-secondary" href="/manage/routines">루틴 만들기</a></div>
                    <?php else: ?>
                        <div class="retrospect-table-wrap">
                            <table class="retrospect-record-table retrospect-routine-table">
                                <thead><tr><th scope="col">루틴명</th><th scope="col">연속</th><th scope="col">완료</th></tr></thead>
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
                <?php require __DIR__ . '/schedule-panel.php'; ?>
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

        <?php endif; ?>
    <?php endif; ?>
</main>

<?php if ($selectedView === 'daily' && $canEdit && !$showEmptyGuide && !$showPublishedCard): ?>
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
                    <button type="button" class="btn btn-secondary" data-kpt-close hidden>닫기</button><button type="button" class="btn btn-secondary" data-kpt-collapse>기록 보기</button><button type="button" class="btn btn-secondary" data-kpt-previous hidden>이전</button><button type="button" class="btn btn-primary" data-kpt-next>다음</button><button type="submit" class="btn btn-primary" formaction="/retrospect/publish" data-kpt-publish hidden><?= $isSubmitted ? '재발행' : '발행' ?></button>
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

<?php require __DIR__ . '/schedule-details.php'; ?>

<div class="retrospect-local-layer" data-memo-detail-layer hidden><button type="button" class="retrospect-local-overlay" data-detail-close aria-label="메모 상세 닫기"></button><section class="retrospect-sheet retrospect-detail-sheet" role="dialog" aria-labelledby="memoDetailTitle"><div class="retrospect-sheet-header"><strong id="memoDetailTitle">메모 상세</strong><button type="button" class="ui-close-button" data-detail-close aria-label="닫기">×</button></div>
    <?php foreach (($retrospect['memoItems'] ?? []) as $index => $memo): ?><article data-detail-panel="memo-detail-<?= e((string) $index) ?>" hidden><h2><?= e((string) $memo['timeLabel']) ?> <?= e((string) $memo['sourceLabel']) ?></h2><?php if ((string) ($memo['scheduleTitle'] ?? '') !== ''): ?><p class="retrospect-memo-source">연결 일정 · <?= e((string) $memo['scheduleTitle']) ?></p><?php endif; ?><p class="retrospect-memo-full"><?= nl2br(e((string) $memo['content'])) ?></p><?php if ((string) ($memo['source'] ?? '') === 'schedule'): ?><a class="btn btn-secondary" href="/calendar?date=<?= e($date) ?>">일정에서 보기</a><?php endif; ?></article><?php endforeach; ?>
</section></div>


<?php require __DIR__ . '/../../layouts/footer.php'; ?>
