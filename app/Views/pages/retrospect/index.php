<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$date = (string) $retrospect['date'];
$isToday = !empty($retrospect['isToday']);
$isFuture = !empty($retrospect['isFuture']);
$isEmptyHistory = !empty($retrospect['isEmptyHistory']);
$report = $retrospect['report'] ?? null;
$isSubmitted = is_array($report) && (string) ($report['status'] ?? '') === 'submitted';
$texts = $retrospect['texts'] ?? ['today_review' => '', 'today_thoughts' => '', 'tomorrow_plan' => ''];
$summary = $retrospect['summary'] ?? [];
$settings = $retrospect['settings'] ?? [];
$canEditToday = $isToday && !$isFuture;
$todayDate = (string) ($retrospect['todayDate'] ?? date('Y-m-d'));
$selectedView = (string) ($selectedView ?? 'daily');
?>

<main class="page retrospect-page">
    <h1 class="sr-only">회고</h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <nav class="retrospect-view-switch" aria-label="회고 보기">
        <a href="/retrospect?date=<?= e($date) ?>" <?= $selectedView === 'daily' ? 'aria-current="page"' : '' ?>>일일 회고</a>
        <a href="/retrospect?view=goals" <?= $selectedView === 'goals' ? 'aria-current="page"' : '' ?>>목표 회고</a>
    </nav>

    <?php if ($selectedView === 'goals'): ?>
        <section class="retrospect-goal-review" aria-label="목표별 실행 피드백">
            <?php if (empty($goalReview['goals'])): ?>
                <div class="retrospect-empty-state">
                    <strong>회고할 목표가 없습니다.</strong>
                    <p>목표를 만들고 계획이나 루틴을 연결하면 실행 흐름을 확인할 수 있습니다.</p>
                </div>
            <?php else: ?>
                <?php foreach ($goalReview['goals'] as $goal): ?>
                    <article class="retrospect-goal-card">
                        <div class="retrospect-goal-card-title">
                            <span><?= e((string) $goal['type']) ?></span>
                            <strong><?= e((string) $goal['title']) ?></strong>
                            <small><?= e((string) $goal['statusLabel']) ?></small>
                        </div>
                        <div class="retrospect-goal-metrics">
                            <span>계획 <?= e((string) $goal['planExecutionRate']) ?>% (<?= e((string) $goal['executedPlanCount']) ?>/<?= e((string) $goal['planCount']) ?>)</span>
                            <span>루틴 <?= e((string) $goal['routineDoneDayCount']) ?>일 완료</span>
                            <span>실제 일정 <?= e((string) $goal['actualEventCount']) ?>건</span>
                        </div>
                        <p><?= e((string) $goal['feedback']) ?></p>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section class="retrospect-section">
            <h2>완료된 루틴</h2>
            <p class="muted">기간을 마쳤거나 조기에 마무리한 루틴의 최종 결과입니다.</p>
            <?php if (empty($goalReview['routineHistory'])): ?>
                <p class="retrospect-empty">완료된 루틴이 없습니다.</p>
            <?php else: ?>
                <ul class="retrospect-routine-history">
                    <?php foreach ($goalReview['routineHistory'] as $routine): ?>
                        <li>
                            <div><strong><?= e((string) $routine['name']) ?></strong><span><?= e((string) $routine['statusLabel']) ?></span></div>
                            <span><b data-retrospect-history-done="<?= e((string) $routine['id']) ?>"><?= e((string) $routine['doneCount']) ?></b>/<?= e((string) $routine['durationDays']) ?>일 · <b data-retrospect-history-rate="<?= e((string) $routine['id']) ?>"><?= e((string) $routine['achievementRate']) ?></b>%</span>
                            <small><?= e((string) $routine['startDate']) ?> ~ <?= e((string) $routine['endDate']) ?></small>
                            <details>
                                <summary>날짜별 기록</summary>
                                <div class="routine-period-tracker retrospect-routine-period-tracker">
                                    <?php foreach ($routine['periodGroups'] as $month): ?>
                                        <section class="routine-period-group" aria-label="<?= e((string) $month['label']) ?> 실천 기록">
                                            <strong><?= e((string) $month['label']) ?></strong>
                                            <div class="routine-period-grid">
                                                <?php foreach ($month['cells'] as $cell): ?>
                                                    <?php $historyState = (string) ($cell['state'] ?? ''); ?>
                                                    <form method="post" action="/routine/toggle" data-retrospect-routine-toggle-form>
                                                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                                        <input type="hidden" name="return_to" value="retrospect_goals">
                                                        <input type="hidden" name="date" value="<?= e((string) $cell['date']) ?>">
                                                        <input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>">
                                                        <button
                                                            type="submit"
                                                            class="routine-period-cell <?= $historyState === 'O' ? 'is-done' : ($historyState === 'X' ? 'is-failed' : '') ?>"
                                                            data-routine-toggle-control
                                                            data-routine-id="<?= e((string) $routine['id']) ?>"
                                                            data-routine-date="<?= e((string) $cell['date']) ?>"
                                                            data-routine-control-label="<?= e((string) $routine['name']) ?> 상태 변경"
                                                            data-routine-marker-style="period"
                                                            data-state="<?= e($historyState) ?>"
                                                            title="<?= e((string) $cell['date']) ?> <?= $historyState === 'O' ? '완료' : ($historyState === 'X' ? '미완료' : '미기록') ?>"
                                                            aria-label="<?= e((string) $routine['name']) ?> 상태 변경, <?= $historyState === 'O' ? '완료' : ($historyState === 'X' ? '미완료' : '미기록') ?>"
                                                        ><small><?= e((string) $cell['day']) ?></small><i data-routine-state-marker aria-hidden="true"><?= $historyState === 'O' ? '●' : ($historyState === 'X' ? '×' : '') ?></i></button>
                                                    </form>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                    <div class="routine-period-legend" aria-label="완료 루틴 기록 상태 안내">
                                        <span class="is-done">완료</span>
                                        <span class="is-failed">미완료</span>
                                        <span>미기록</span>
                                    </div>
                                </div>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php else: ?>

    <div class="retrospect-toolbar" aria-label="회고 빠른 이동">
        <?php if (!$isToday): ?>
            <form class="retrospect-toolbar-form" method="get" action="/retrospect">
                <input type="hidden" name="date" value="<?= e($todayDate) ?>">
                <input type="hidden" name="sort" value="<?= e((string) $retrospect['sort']) ?>">
                <button type="submit" class="btn btn-secondary">오늘로 이동</button>
            </form>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary" data-retrospect-open-date>날짜 선택</button>
        <button type="button" class="btn btn-secondary" data-retrospect-open-settings>
            <?= e((string) ($settings['autoPublishLabel'] ?? '자동 발행 끔')) ?>
        </button>
    </div>

    <section class="retrospect-date-bar" aria-label="회고 날짜 탐색">
        <a class="retrospect-date-button" href="/retrospect?date=<?= e((string) $retrospect['prevDate']) ?>&sort=<?= e((string) $retrospect['sort']) ?>" aria-label="이전 날짜">&lsaquo;</a>
        <div>
            <strong><?= e((string) $retrospect['dateTitle']) ?></strong>
            <span><?= e((string) $retrospect['dateSubTitle']) ?> · <?= e((string) $retrospect['statusLabel']) ?></span>
        </div>
        <a class="retrospect-date-button" href="/retrospect?date=<?= e((string) $retrospect['nextDate']) ?>&sort=<?= e((string) $retrospect['sort']) ?>" aria-label="다음 날짜">&rsaquo;</a>
    </section>

    <?php if ($isEmptyHistory): ?>
        <section class="retrospect-empty-state" aria-live="polite">
            <strong>발행된 회고가 없습니다.</strong>
            <p>해당 날짜에는 저장된 회고 기록이 없어 날짜 탐색만 제공합니다.</p>
        </section>
    <?php else: ?>
        <section class="retrospect-score-card" aria-label="오늘의 실행 요약" data-retrospect-score-line>
            <strong class="retrospect-score-title">오늘의 성적</strong>
            <div class="retrospect-score-metrics">
                <article>
                    <span>계획 달성률</span>
                    <b data-plan-rate><?= e((string) ($summary['planAchievementRate'] ?? 0)) ?>%</b>
                    <small><?= e((string) ($summary['planLinkedCount'] ?? 0)) ?>/<?= e((string) ($summary['planTotalCount'] ?? 0)) ?>개 연결</small>
                </article>
                <article>
                    <span>루틴 달성률</span>
                    <b data-routine-rate><?= e((string) ($summary['routineAchievementRate'] ?? 0)) ?>%</b>
                    <small><span data-routine-score><?= e((string) ($summary['routineDoneCount'] ?? 0)) ?>/<?= e((string) ($summary['routineTotalCount'] ?? 0)) ?></span>개 완료</small>
                </article>
                <article>
                    <span>실제 시간</span>
                    <b><?= e((string) ($summary['linkedActualTimeLabel'] ?? '0분')) ?></b>
                    <small>계획과 연결된 일정</small>
                </article>
            </div>
        </section>

        <section class="retrospect-records" aria-labelledby="retrospectRecordsTitle">
            <div class="retrospect-records-header">
                <div>
                    <h2 id="retrospectRecordsTitle">오늘의 기록</h2>
                    <p>필요한 항목만 펼쳐 흐름을 확인합니다.</p>
                </div>
            </div>

            <details class="retrospect-record-group" open>
                <summary><span>루틴</span><b><?= e((string) count($retrospect['routineItems'])) ?>개</b></summary>
                <div class="retrospect-record-content">
                    <?php if (empty($retrospect['routineItems'])): ?>
                        <p class="retrospect-empty">확인할 루틴이 없습니다.</p>
                    <?php else: ?>
                        <ul class="retrospect-routine-list" data-retrospect-record-list>
                            <?php foreach ($retrospect['routineItems'] as $routineIndex => $routine): ?>
                                <?php $state = (string) ($routine['state'] ?? ''); ?>
                                <li class="<?= $routineIndex >= 4 ? 'retrospect-collapsible-item' : '' ?>" data-retrospect-routine-item>
                                    <span><?= e((string) $routine['name']) ?></span>
                                    <?php if ($isToday && !$isFuture): ?>
                                        <form method="post" action="/routine/toggle" data-retrospect-routine-toggle-form>
                                            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                            <input type="hidden" name="return_to" value="retrospect">
                                            <input type="hidden" name="date" value="<?= e($date) ?>">
                                            <input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>">
                                            <button
                                                type="submit"
                                                class="routine-state-control <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?>"
                                                data-routine-toggle-control
                                                data-retrospect-routine-state
                                                data-routine-id="<?= e((string) $routine['id']) ?>"
                                                data-routine-date="<?= e($date) ?>"
                                                data-routine-control-label="<?= e((string) $routine['name']) ?> 상태 변경"
                                                data-state="<?= e($state) ?>"
                                                title="<?= $state === 'O' ? '완료' : ($state === 'X' ? '미완료' : '미기록') ?>"
                                                aria-label="<?= e((string) $routine['name']) ?> 상태 변경, <?= $state === 'O' ? '완료' : ($state === 'X' ? '미완료' : '미기록') ?>"
                                            >
                                                <span data-routine-state-marker aria-hidden="true"><?= $state === 'O' ? '✓' : ($state === 'X' ? '×' : '') ?></span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <strong><?= e((string) ($routine['stateLabel'] ?? '미기록')) ?></strong>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($retrospect['routineItems']) > 4): ?>
                            <button type="button" class="retrospect-more-button" data-retrospect-more>전체 보기</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </details>

            <details class="retrospect-record-group">
                <summary><span>계획</span><b><?= e((string) count($retrospect['planItems'])) ?>개</b></summary>
                <div class="retrospect-record-content">
                    <?php if (empty($retrospect['planItems'])): ?>
                        <p class="retrospect-empty">선택된 계획이 없습니다.</p>
                    <?php else: ?>
                        <ol class="retrospect-plan-list" data-retrospect-record-list>
                            <?php foreach ($retrospect['planItems'] as $planIndex => $plan): ?>
                                <li class="<?= !empty($plan['is_linked']) ? 'is-linked ' : '' ?><?= $planIndex >= 4 ? 'retrospect-collapsible-item' : '' ?>">
                                    <span><b><?= e((string) $plan['importance']) ?></b><?= e((string) $plan['title']) ?></span>
                                    <small><?= e((string) $plan['timeRange']) ?> · <?= !empty($plan['is_linked']) ? '실행 연결' : '미연결' ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if (count($retrospect['planItems']) > 4): ?>
                            <button type="button" class="retrospect-more-button" data-retrospect-more>전체 보기</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </details>

            <details class="retrospect-record-group">
                <summary><span>실제 일정</span><b><?= e((string) count($retrospect['actualItems'])) ?>개</b></summary>
                <div class="retrospect-record-content">
                    <div class="retrospect-sort-tabs" aria-label="일정 정렬">
                        <a href="/retrospect?date=<?= e($date) ?>&sort=time" <?= (string) $retrospect['sort'] === 'time' ? 'aria-current="page"' : '' ?>>시간순</a>
                        <a href="/retrospect?date=<?= e($date) ?>&sort=tag" <?= (string) $retrospect['sort'] === 'tag' ? 'aria-current="page"' : '' ?>>태그순</a>
                    </div>
                    <?php if (empty($retrospect['actualItems'])): ?>
                        <p class="retrospect-empty">기록된 실제 일정이 없습니다.</p>
                    <?php else: ?>
                        <ol class="retrospect-event-list" data-retrospect-record-list>
                            <?php foreach ($retrospect['actualItems'] as $eventIndex => $event): ?>
                                <li class="<?= $eventIndex >= 4 ? 'retrospect-collapsible-item' : '' ?>" style="--event-color: <?= e((string) $event['tag_color']) ?>; --event-text-color: <?= e((string) ($event['tag_text_color'] ?? '#FFFFFF')) ?>;">
                                    <span class="retrospect-event-title">
                                        <?php if (!empty($event['is_linked'])): ?>
                                            <b class="retrospect-importance"><?= e((string) ($event['plan_importance'] ?? 'D')) ?></b>
                                        <?php endif; ?>
                                        <?= e((string) $event['title']) ?>
                                    </span>
                                    <small><?= e((string) ($event['durationLabel'] ?? '0분')) ?>(<?= e((string) $event['timeRange']) ?>)</small>
                                    <?php if (!empty($event['memo'])): ?>
                                        <p class="retrospect-event-memo"><?= nl2br(e((string) $event['memo'])) ?></p>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if (count($retrospect['actualItems']) > 4): ?>
                            <button type="button" class="retrospect-more-button" data-retrospect-more>전체 보기</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </details>

            <details class="retrospect-record-group">
                <summary><span>메모</span><b><?= e((string) count($retrospect['memoItems'])) ?>개</b></summary>
                <div class="retrospect-record-content">
                    <?php if (empty($retrospect['memoItems'])): ?>
                        <p class="retrospect-empty">작성한 메모가 없습니다.</p>
                    <?php else: ?>
                        <ul class="retrospect-memo-list" data-retrospect-record-list>
                            <?php foreach ($retrospect['memoItems'] as $memoIndex => $memo): ?>
                                <li class="<?= $memoIndex >= 4 ? 'retrospect-collapsible-item' : '' ?>"><?= nl2br(e((string) $memo['content'])) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($retrospect['memoItems']) > 4): ?>
                            <button type="button" class="retrospect-more-button" data-retrospect-more>전체 보기</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </details>
        </section>

        <section class="retrospect-section">
            <h2>오늘의 회고</h2>
            <p class="muted">오늘의 나의 감정, 이벤트, 내가 배운 점 등을 기억나는 대로 써보자</p>
            <?php if ($canEditToday): ?>
                <form class="retrospect-form" id="retrospectMemoForm" method="post" action="/retrospect/draft">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">

                    <label class="form-label" for="todayReview">오늘의 잘한 점(Keep)</label>
                    <textarea class="input retrospect-textarea" id="todayReview" name="today_review" maxlength="2000" rows="3" data-retrospect-autosize><?= e((string) $texts['today_review']) ?></textarea>

                    <label class="form-label" for="todayThoughts">오늘의 아쉬운 점(Problem)</label>
                    <textarea class="input retrospect-textarea" id="todayThoughts" name="today_thoughts" maxlength="2000" rows="3" data-retrospect-autosize><?= e((string) $texts['today_thoughts']) ?></textarea>

                    <label class="form-label" for="tomorrowPlan">내일을 위해 개선할 점(Try)</label>
                    <textarea class="input retrospect-textarea" id="tomorrowPlan" name="tomorrow_plan" maxlength="2000" rows="3" data-retrospect-autosize><?= e((string) $texts['tomorrow_plan']) ?></textarea>

                    <?php if (!$isSubmitted): ?>
                        <button type="submit" class="btn btn-secondary">임시 저장</button>
                    <?php endif; ?>
                </form>
            <?php elseif ($isSubmitted): ?>
                <div class="retrospect-published-texts">
                    <article>
                        <strong>오늘의 잘한 점(Keep)</strong>
                        <p><?= nl2br(e((string) $texts['today_review'])) ?: '작성된 내용이 없습니다.' ?></p>
                    </article>
                    <article>
                        <strong>오늘의 아쉬운 점(Problem)</strong>
                        <p><?= nl2br(e((string) $texts['today_thoughts'])) ?: '작성된 내용이 없습니다.' ?></p>
                    </article>
                    <article>
                        <strong>내일을 위해 개선할 점(Try)</strong>
                        <p><?= nl2br(e((string) $texts['tomorrow_plan'])) ?: '작성된 내용이 없습니다.' ?></p>
                    </article>
                </div>
                <form id="retrospectRepublishForm" method="post" action="/retrospect/republish" data-confirm="현재 캘린더와 루틴 기록을 기준으로 회고 스냅샷을 다시 만들까요? 작성한 회고 문장은 유지됩니다.">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php if ($selectedView === 'daily' && $canEditToday): ?>
    <button type="submit" class="btn btn-primary retrospect-floating-publish" form="retrospectMemoForm" formaction="/retrospect/publish">
        <?= $isSubmitted ? '회고 재발행' : '회고 발행' ?>
    </button>
<?php elseif ($selectedView === 'daily' && $isSubmitted): ?>
    <button type="submit" class="btn btn-primary retrospect-floating-publish" form="retrospectRepublishForm">
        회고 재발행
    </button>
<?php endif; ?>

<div class="retrospect-local-layer" data-retrospect-settings-layer hidden>
    <button type="button" class="retrospect-local-overlay" data-retrospect-close-settings aria-label="자동 발행 설정 닫기"></button>
    <section class="retrospect-sheet" aria-modal="true" role="dialog" aria-labelledby="retrospectSettingsTitle">
        <div class="retrospect-sheet-header">
            <strong id="retrospectSettingsTitle">자동 회고 발행</strong>
            <button type="button" class="ui-close-button" data-retrospect-close-settings aria-label="닫기">×</button>
        </div>
        <form class="retrospect-settings-form" method="post" action="/retrospect/settings">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e($date) ?>">
            <label class="retrospect-toggle-row">
                <input type="checkbox" name="auto_publish_enabled" value="1" <?= !empty($settings['autoPublishEnabled']) ? 'checked' : '' ?>>
                <span>설정한 시간이 지나면 오늘 회고를 자동 발행합니다.</span>
            </label>
            <label class="form-label" for="autoPublishTime">자동 발행 시간</label>
            <input class="input" id="autoPublishTime" name="auto_publish_time" type="time" value="<?= e((string) ($settings['autoPublishTime'] ?? '22:00')) ?>">
            <button type="submit" class="btn btn-primary">설정 저장</button>
        </form>
    </section>
</div>

<div class="retrospect-local-layer" data-retrospect-date-layer hidden>
    <button type="button" class="retrospect-local-overlay" data-retrospect-close-date aria-label="날짜 선택 닫기"></button>
    <section class="retrospect-sheet" aria-modal="true" role="dialog" aria-labelledby="retrospectDateTitle">
        <div class="retrospect-sheet-header">
            <strong id="retrospectDateTitle">특정 날짜 회고 보기</strong>
            <button type="button" class="ui-close-button" data-retrospect-close-date aria-label="닫기">×</button>
        </div>
        <form class="retrospect-settings-form" method="get" action="/retrospect">
            <label class="form-label" for="retrospectDate">날짜 선택</label>
            <input class="input" id="retrospectDate" name="date" type="date" value="<?= e($date) ?>">
            <input type="hidden" name="sort" value="<?= e((string) $retrospect['sort']) ?>">
            <button type="submit" class="btn btn-primary">회고 보기</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
