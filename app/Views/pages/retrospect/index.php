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
$canEditToday = $isToday && !$isSubmitted && !$isFuture;
$todayDate = (string) ($retrospect['todayDate'] ?? date('Y-m-d'));
?>

<main class="page retrospect-page">
    <section class="retrospect-hero">
        <div>
            <p class="eyebrow">Retrospect</p>
            <h1 class="page-title">회고</h1>
            <p class="muted">오늘을 돌아보고 내일의 방향을 가볍게 정리합니다.</p>
        </div>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

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
        <section class="retrospect-score-grid" aria-label="오늘의 요약">
            <article class="retrospect-score-card">
                <span>계획 달성률</span>
                <strong><?= e((string) ($summary['planAchievementRate'] ?? 0)) ?>%</strong>
                <small><?= e((string) ($summary['planLinkedCount'] ?? 0)) ?>/<?= e((string) ($summary['planTotalCount'] ?? 0)) ?>개 연결</small>
            </article>
            <article class="retrospect-score-card">
                <span>루틴 달성률</span>
                <strong><?= e((string) ($summary['routineAchievementRate'] ?? 0)) ?>%</strong>
                <small><?= e((string) ($summary['routineDoneCount'] ?? 0)) ?>/<?= e((string) ($summary['routineTotalCount'] ?? 0)) ?>개 완료</small>
            </article>
            <article class="retrospect-score-card">
                <span>실제 시간</span>
                <strong><?= e((string) ($summary['linkedActualTimeLabel'] ?? '0분')) ?></strong>
                <small>계획과 연결된 실제 일정</small>
            </article>
        </section>

        <section class="retrospect-section">
            <div class="retrospect-section-header">
                <div>
                    <h2>오늘의 루틴</h2>
                    <p class="muted">오늘 활성화된 루틴과 기록 상태입니다.</p>
                </div>
                <?php if ($isToday): ?>
                    <form class="retrospect-calendar-edit-form" method="get" action="/calendar">
                        <input type="hidden" name="date" value="<?= e($date) ?>">
                        <button type="submit" class="btn btn-ghost">
                            캘린더에서 수정 <span class="retrospect-external-icon" aria-hidden="true">&#8599;</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if (empty($retrospect['routineItems'])): ?>
                <p class="retrospect-empty">확인할 루틴이 없습니다.</p>
            <?php else: ?>
                <ul class="retrospect-routine-list">
                    <?php foreach ($retrospect['routineItems'] as $routine): ?>
                        <?php $state = (string) ($routine['state'] ?? ''); ?>
                        <li>
                            <span><?= e((string) $routine['name']) ?></span>
                            <strong class="<?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : 'is-empty') ?>">
                                <?= e((string) ($routine['stateLabel'] ?? '미기록')) ?>
                            </strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="retrospect-section">
            <div class="retrospect-section-header">
                <div>
                    <h2>오늘의 일정</h2>
                    <p class="muted">실제로 기록한 일정을 기준으로 보여줍니다.</p>
                </div>
                <div class="retrospect-sort-tabs" aria-label="일정 정렬">
                    <a href="/retrospect?date=<?= e($date) ?>&sort=time" <?= (string) $retrospect['sort'] === 'time' ? 'aria-current="page"' : '' ?>>시간순</a>
                    <a href="/retrospect?date=<?= e($date) ?>&sort=tag" <?= (string) $retrospect['sort'] === 'tag' ? 'aria-current="page"' : '' ?>>태그순</a>
                </div>
            </div>
            <?php if (empty($retrospect['actualItems'])): ?>
                <p class="retrospect-empty">기록된 실제 일정이 없습니다.</p>
            <?php else: ?>
                <ol class="retrospect-event-list">
                    <?php foreach ($retrospect['actualItems'] as $event): ?>
                        <li style="--event-color: <?= e((string) $event['tag_color']) ?>;">
                            <span class="retrospect-event-title">
                                <?php if (!empty($event['is_linked'])): ?>
                                    <b class="retrospect-importance"><?= e((string) ($event['plan_importance'] ?? 'D')) ?></b>
                                <?php endif; ?>
                                <?= e((string) $event['title']) ?>
                            </span>
                            <small><?= e((string) ($event['durationLabel'] ?? '0분')) ?>(<?= e((string) $event['timeRange']) ?>)</small>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <section class="retrospect-section">
            <h2>오늘의 회고</h2>
            <p class="muted">오늘의 나의 감정, 이벤트, 내가 배운 점 등을 기억나는 대로 써보자</p>
            <?php if ($canEditToday): ?>
                <form class="retrospect-form" id="retrospectMemoForm" method="post" action="/retrospect/draft">
                    <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                    <input type="hidden" name="date" value="<?= e($date) ?>">

                    <label class="form-label" for="todayReview">오늘의 잘한 점(Keep)</label>
                    <textarea class="input retrospect-textarea" id="todayReview" name="today_review" maxlength="2000" rows="5"><?= e((string) $texts['today_review']) ?></textarea>

                    <label class="form-label" for="todayThoughts">오늘의 아쉬운 점(Problem)</label>
                    <textarea class="input retrospect-textarea" id="todayThoughts" name="today_thoughts" maxlength="2000" rows="5"><?= e((string) $texts['today_thoughts']) ?></textarea>

                    <label class="form-label" for="tomorrowPlan">내일을 위해 개선할 점(Try)</label>
                    <textarea class="input retrospect-textarea" id="tomorrowPlan" name="tomorrow_plan" maxlength="2000" rows="5"><?= e((string) $texts['tomorrow_plan']) ?></textarea>

                    <button type="submit" class="btn btn-secondary">메모 저장</button>
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
</main>

<?php if ($canEditToday): ?>
    <button type="submit" class="btn btn-primary retrospect-floating-publish" form="retrospectMemoForm" formaction="/retrospect/publish">
        회고 발행
    </button>
<?php elseif ($isSubmitted): ?>
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
