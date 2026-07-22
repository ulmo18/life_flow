<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$today = date('Y-m-d');
$selectedDuration = (int) ($old['duration_days'] ?? $defaultDurationDays);
$selectedGoalId = (int) ($old['goal_id'] ?? 0);
$routineSummary = $routineSummary ?? [
    'activeCount' => 0,
    'weekDoneCount' => 0,
    'weekTotalCount' => 0,
    'weekAchievementRate' => 0,
    'streakCount' => 0,
];
?>

<main class="page routine-page">
    <h1 class="sr-only">루틴</h1>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="routine-week-summary" aria-labelledby="routineWeekSummaryTitle">
        <h2 id="routineWeekSummaryTitle">이번 주 루틴 요약</h2>
        <div class="routine-week-metrics">
            <article>
                <span>전체 루틴</span>
                <strong><b data-routine-page-active><?= e((string) $routineSummary['activeCount']) ?></b>개</strong>
            </article>
            <article>
                <span>이번 주 달성률</span>
                <strong><b data-routine-page-rate><?= e((string) $routineSummary['weekAchievementRate']) ?></b>%</strong>
                <small><b data-routine-page-done><?= e((string) $routineSummary['weekDoneCount']) ?></b>/<b data-routine-page-total><?= e((string) $routineSummary['weekTotalCount']) ?></b>회</small>
            </article>
            <article>
                <span>연속 성공</span>
                <strong><b data-routine-page-streak><?= e((string) $routineSummary['streakCount']) ?></b>일</strong>
                <small>활성 루틴 중 최장</small>
            </article>
        </div>
    </section>

    <section class="routine-list-section" aria-label="루틴 목록">
        <?php if (empty($routines)): ?>
            <div class="routine-empty">
                <strong>아직 만든 루틴이 없습니다.</strong>
                <p class="muted">1일부터 60일까지 짧은 실천을 시작하고, 필요하면 기간을 연장해보세요.</p>
            </div>
        <?php else: ?>
            <ul class="routine-list">
                <?php foreach ($routines as $routine): ?>
                    <?php
                    $routineId = (int) $routine['id'];
                    ?>
                    <li class="routine-card" data-routine-card="<?= e((string) $routineId) ?>">
                        <div class="routine-card-main">
                            <div class="routine-card-title">
                                <strong><?= e((string) $routine['name']) ?></strong>
                                <span class="routine-streak-label" data-routine-streak="<?= e((string) $routineId) ?>" <?= (string) $routine['streakLabel'] === '' ? 'hidden' : '' ?>><?= e((string) $routine['streakLabel']) ?></span>
                            </div>
                            <?php if (!empty($routine['goalTitle'])): ?>
                                <span class="routine-goal-label" title="<?= e((string) $routine['goalTitle']) ?>"><span>목표 · <?= e((string) $routine['goalTitle']) ?></span></span>
                            <?php endif; ?>
                        </div>

                        <div class="routine-progress">
                            <span><b data-routine-done-count="<?= e((string) $routineId) ?>"><?= e((string) $routine['doneCount']) ?></b>/<?= e((string) $routine['durationDays']) ?>일</span>
                            <span><b data-routine-progress-percent="<?= e((string) $routineId) ?>"><?= e((string) $routine['progressPercent']) ?></b>%</span>
                        </div>
                        <div class="routine-progress-track" aria-hidden="true">
                            <span data-routine-progress-bar="<?= e((string) $routineId) ?>" style="width: <?= e((string) $routine['progressPercent']) ?>%;"></span>
                        </div>
                        <div class="routine-tracker-header">
                            <strong>실천 기록</strong>
                            <small>오늘 칸을 눌러 채워보세요.</small>
                        </div>
                        <div class="routine-tracker" data-routine-tracker="<?= e((string) $routineId) ?>" aria-label="루틴 실천 기록">
                            <?php foreach ($routine['trackerCells'] as $cell): ?>
                                <?php
                                $cellState = (string) ($cell['state'] ?? '');
                                $cellClasses = trim(
                                    ($cellState === 'O' ? 'is-done ' : ($cellState === 'X' ? 'is-failed ' : ''))
                                    . (!empty($cell['isToday']) ? 'is-today ' : '')
                                    . (!empty($cell['isFuture']) ? 'is-future' : '')
                                );
                                $cellLabel = (string) $cell['date'] . ' ' . ($cellState === 'O' ? '완료' : ($cellState === 'X' ? '미완료' : (!empty($cell['isFuture']) ? '예정' : '미기록')));
                                ?>
                                <?php if (!empty($cell['isToday'])): ?>
                                    <form method="post" action="/routine/toggle" data-routine-toggle-form>
                                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                        <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                                        <input type="hidden" name="date" value="<?= e((string) $cell['date']) ?>">
                                        <button
                                            type="submit"
                                            class="routine-state-control routine-tracker-cell <?= e($cellClasses) ?>"
                                            data-routine-state-button
                                            data-routine-state-cell
                                            data-routine-state-display="tracker"
                                            data-routine-control-label="오늘 루틴 상태 변경"
                                            data-routine-id="<?= e((string) $routineId) ?>"
                                            data-routine-date="<?= e((string) $cell['date']) ?>"
                                            title="<?= e($cellLabel) ?>"
                                            aria-label="오늘 루틴 상태 변경, <?= e($cellLabel) ?>"
                                        ><span data-routine-state-marker aria-hidden="true"><?= $cellState === 'O' ? '✓' : ($cellState === 'X' ? '×' : '') ?></span></button>
                                    </form>
                                <?php else: ?>
                                    <span
                                        class="routine-tracker-cell <?= e($cellClasses) ?>"
                                        data-routine-state-cell
                                        data-routine-id="<?= e((string) $routineId) ?>"
                                        data-routine-date="<?= e((string) $cell['date']) ?>"
                                        title="<?= e($cellLabel) ?>"
                                        aria-label="<?= e($cellLabel) ?>"
                                    ><span aria-hidden="true"></span></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="routine-card-actions">
                            <button type="button" class="btn btn-secondary" data-routine-open="detail-<?= e((string) $routineId) ?>">상세</button>
                            <button type="button" class="btn btn-ghost" data-routine-open="edit-<?= e((string) $routineId) ?>">수정</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <button type="button" class="btn btn-primary routine-floating-add" data-routine-open="create">루틴 추가</button>
</main>

<div class="routine-local-layer" data-routine-layer hidden>
    <button type="button" class="routine-local-overlay" data-routine-close aria-label="루틴 창 닫기"></button>

    <section class="routine-sheet" data-routine-sheet="create" hidden aria-modal="true" role="dialog" aria-labelledby="routineCreateTitle">
        <div class="routine-sheet-header">
            <strong id="routineCreateTitle">루틴 추가</strong>
            <button type="button" class="ui-close-button" data-routine-close aria-label="닫기">×</button>
        </div>
        <form class="routine-form" method="post" action="/routine">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">

            <label class="form-label" for="routineName">습관명</label>
            <input class="input" id="routineName" name="name" type="text" maxlength="60" value="<?= e((string) ($old['name'] ?? '')) ?>" required>
            <?php if (!empty($errors['name'])): ?>
                <p class="field-error"><?= e((string) $errors['name']) ?></p>
            <?php endif; ?>

            <div class="routine-form-grid">
                <div>
                    <label class="form-label" for="routineStartDate">시작일</label>
                    <input class="input" id="routineStartDate" name="start_date" type="date" value="<?= e((string) ($old['start_date'] ?? $today)) ?>" required>
                </div>
                <div>
                    <label class="form-label" for="routineDuration">기간</label>
                    <select class="input" id="routineDuration" name="duration_days">
                        <?php foreach ($durationOptions as $days): ?>
                            <option value="<?= e((string) $days) ?>" <?= $selectedDuration === (int) $days ? 'selected' : '' ?>>
                                <?= e((string) $days) ?>일
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if (!empty($errors['duration_days'])): ?>
                <p class="field-error"><?= e((string) $errors['duration_days']) ?></p>
            <?php endif; ?>

            <label class="form-label" for="routineGoal">목표</label>
            <select class="input" id="routineGoal" name="goal_id">
                <option value="">연결하지 않음</option>
                <?php foreach (($goalOptions ?? []) as $goal): ?>
                    <option value="<?= e((string) $goal['id']) ?>" <?= $selectedGoalId === (int) $goal['id'] ? 'selected' : '' ?>>
                        <?= e((string) $goal['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['goal_id'])): ?>
                <p class="field-error"><?= e((string) $errors['goal_id']) ?></p>
            <?php endif; ?>

            <label class="routine-reminder-option">
                <input type="checkbox" name="reminder_enabled" value="1" <?= !empty($old['reminder_enabled']) ? 'checked' : '' ?>>
                <span>중간 루틴 리마인드 사용</span>
                <input class="input" name="reminder_time" type="time" value="<?= e((string) ($old['reminder_time'] ?? $defaultReminderTime)) ?>">
            </label>
            <?php if (!empty($errors['reminder_time'])): ?>
                <p class="field-error"><?= e((string) $errors['reminder_time']) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </section>

    <?php foreach ($routines as $routine): ?>
        <?php $routineId = (int) $routine['id']; ?>

        <section class="routine-sheet" data-routine-sheet="detail-<?= e((string) $routineId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="routineDetailTitle<?= e((string) $routineId) ?>">
            <div class="routine-sheet-header is-sticky">
                <strong id="routineDetailTitle<?= e((string) $routineId) ?>"><?= e((string) $routine['name']) ?></strong>
                <button type="button" class="ui-close-button" data-routine-close aria-label="닫기">×</button>
            </div>
            <p class="muted routine-detail-hint">시작일부터 종료일까지 월별로 이어진 실천 기록입니다. 오늘과 과거의 칸을 누르면 공백, O, X 순서로 수정할 수 있습니다.</p>
            <?php if (empty($routine['periodGroups'])): ?>
                <div class="routine-empty compact">
                    <strong>아직 확인할 날짜가 없습니다.</strong>
                    <p class="muted">시작일이 되면 날짜별 상태를 바꿀 수 있습니다.</p>
                </div>
            <?php else: ?>
                <div class="routine-period-tracker">
                    <?php foreach ($routine['periodGroups'] as $month): ?>
                        <section class="routine-period-group" aria-label="<?= e((string) $month['label']) ?> 실천 기록">
                            <strong><?= e((string) $month['label']) ?></strong>
                            <div class="routine-period-grid">
                            <?php foreach ($month['cells'] as $cell): ?>
                                <?php $state = (string) ($cell['state'] ?? ''); ?>
                                <?php if (!empty($cell['canEdit'])): ?>
                                    <form method="post" action="/routine/toggle" data-routine-toggle-form>
                                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                        <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                                        <input type="hidden" name="date" value="<?= e((string) $cell['date']) ?>">
                                        <button
                                            type="submit"
                                            class="routine-period-cell <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?> <?= !empty($cell['isToday']) ? 'is-today' : '' ?>"
                                            data-routine-state-button
                                            data-routine-state-display="period"
                                            data-routine-id="<?= e((string) $routineId) ?>"
                                            data-routine-date="<?= e((string) $cell['date']) ?>"
                                            title="<?= $state === '' ? '미기록' : ($state === 'O' ? '완료' : '미완료') ?>"
                                            aria-label="<?= e((string) $cell['date']) ?> 루틴 상태 변경"
                                        ><small><?= e((string) $cell['day']) ?></small><i aria-hidden="true"><?= $state === 'O' ? '●' : ($state === 'X' ? '×' : '') ?></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="routine-period-cell is-future" title="<?= e((string) $cell['date']) ?> 예정" aria-label="<?= e((string) $cell['date']) ?> 예정">
                                        <small><?= e((string) $cell['day']) ?></small><i aria-hidden="true"></i>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                    <div class="routine-period-legend" aria-label="상세 기록 상태 안내">
                        <span class="is-done">완료</span>
                        <span class="is-failed">미완료</span>
                        <span>미기록</span>
                        <span class="is-future">예정</span>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="routine-sheet" data-routine-sheet="edit-<?= e((string) $routineId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="routineEditTitle<?= e((string) $routineId) ?>">
            <div class="routine-sheet-header">
                <strong id="routineEditTitle<?= e((string) $routineId) ?>">루틴 수정</strong>
                <button type="button" class="ui-close-button" data-routine-close aria-label="닫기">×</button>
            </div>
            <form class="routine-form" method="post" action="/routine/update">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">

                <label class="form-label" for="routineName<?= e((string) $routineId) ?>">습관명</label>
                <input class="input" id="routineName<?= e((string) $routineId) ?>" name="name" type="text" maxlength="60" value="<?= e((string) $routine['name']) ?>" required>

                <div class="routine-period-summary">
                    <span>수행 기간</span>
                    <strong><?= e((string) $routine['startDate']) ?> ~ <?= e((string) $routine['endDate']) ?> · <?= e((string) $routine['durationDays']) ?>일</strong>
                    <small>시작 후 기간은 줄일 수 없으며, 필요할 때 연장할 수 있습니다.</small>
                </div>
                <input type="hidden" name="start_date" value="<?= e((string) $routine['startDate']) ?>">
                <input type="hidden" name="duration_days" value="<?= e((string) $routine['durationDays']) ?>">

                <label class="form-label" for="routineGoal<?= e((string) $routineId) ?>">목표</label>
                <select class="input" id="routineGoal<?= e((string) $routineId) ?>" name="goal_id">
                    <option value="">연결하지 않음</option>
                    <?php foreach (($goalOptions ?? []) as $goal): ?>
                        <option value="<?= e((string) $goal['id']) ?>" <?= (int) ($routine['goalId'] ?? 0) === (int) $goal['id'] ? 'selected' : '' ?>>
                            <?= e((string) $goal['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="routine-reminder-option">
                    <input type="checkbox" name="reminder_enabled" value="1" <?= !empty($routine['reminderEnabled']) ? 'checked' : '' ?>>
                    <span>중간 루틴 리마인드 사용</span>
                    <input class="input" name="reminder_time" type="time" value="<?= e((string) ($routine['reminderTime'] ?: $defaultReminderTime)) ?>">
                </label>

                <div class="routine-edit-actions">
                    <button type="submit" class="btn btn-primary">저장</button>
                    <button type="submit" class="btn btn-ghost" form="routineDelete<?= e((string) $routineId) ?>">삭제</button>
                </div>
            </form>
            <div class="routine-lifecycle-actions">
                <?php if ((int) $routine['durationDays'] < 365): ?>
                    <form method="post" action="/routine/extend">
                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                        <label class="form-label" for="routineExtension<?= e((string) $routineId) ?>">기간 연장</label>
                        <div class="routine-extension-row">
                            <input class="input" id="routineExtension<?= e((string) $routineId) ?>" name="extension_days" type="number" min="1" max="<?= e((string) (365 - (int) $routine['durationDays'])) ?>" value="<?= e((string) min(7, 365 - (int) $routine['durationDays'])) ?>" inputmode="numeric" required>
                            <button type="submit" class="btn btn-secondary">연장</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="routine-finish-row">
                    <form method="post" action="/routine/finish" data-confirm="이 루틴을 완료로 마무리할까요? 기록은 회고에 보존됩니다.">
                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-secondary">완료로 마무리</button>
                    </form>
                    <form method="post" action="/routine/finish" data-confirm="이 루틴을 중단할까요? 지금까지의 기록은 회고에 보존됩니다.">
                        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                        <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                        <input type="hidden" name="status" value="stopped">
                        <button type="submit" class="btn btn-ghost">중단</button>
                    </form>
                </div>
            </div>
            <form id="routineDelete<?= e((string) $routineId) ?>" method="post" action="/routine/delete" data-confirm="루틴을 삭제할까요? 기존 실행 기록은 회고 참고용으로 숨김 처리됩니다.">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
            </form>
        </section>
    <?php endforeach; ?>
</div>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
