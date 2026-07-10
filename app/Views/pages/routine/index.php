<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$today = date('Y-m-d');
$selectedDuration = (int) ($old['duration_days'] ?? $defaultDurationDays);
$selectedGoalId = (int) ($old['goal_id'] ?? 0);
?>

<main class="page routine-page">
    <section class="routine-header">
        <div>
            <p class="eyebrow">Routine</p>
            <h1 class="page-title">루틴</h1>
            <p class="muted">매일의 습관 실행 여부를 공백, O, X 순서로 가볍게 기록합니다.</p>
        </div>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="routine-status-guide" aria-label="루틴 상태 안내">
        <span class="guide-empty">공백</span>
        <span class="guide-done">O</span>
        <span class="guide-failed">X</span>
    </section>

    <section class="routine-list-section" aria-label="루틴 목록">
        <?php if (empty($routines)): ?>
            <div class="routine-empty">
                <strong>아직 만든 루틴이 없습니다.</strong>
                <p class="muted">7일부터 60일까지 기간을 정하고 오늘부터 작은 잔디를 채워보세요.</p>
            </div>
        <?php else: ?>
            <ul class="routine-list">
                <?php foreach ($routines as $routine): ?>
                    <?php
                    $routineId = (int) $routine['id'];
                    $todayState = (string) ($routine['todayState'] ?? '');
                    ?>
                    <li class="routine-card" data-routine-card="<?= e((string) $routineId) ?>">
                        <div class="routine-card-main">
                            <div>
                                <strong><?= e((string) $routine['name']) ?></strong>
                                <span><?= e((string) $routine['startDate']) ?> ~ <?= e((string) $routine['endDate']) ?></span>
                                <?php if (!empty($routine['goalTitle'])): ?>
                                    <span class="routine-goal-label">목표 · <?= e((string) $routine['goalTitle']) ?></span>
                                <?php endif; ?>
                            </div>
                            <form method="post" action="/routine/toggle" data-routine-toggle-form>
                                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                                <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                                <input type="hidden" name="date" value="<?= e($today) ?>">
                                <label class="routine-today-control">
                                    <span>오늘 상태</span>
                                    <button
                                        type="submit"
                                        class="routine-check-button <?= $todayState === 'O' ? 'is-done' : ($todayState === 'X' ? 'is-failed' : '') ?>"
                                        data-routine-state-button
                                        data-routine-id="<?= e((string) $routineId) ?>"
                                        data-routine-date="<?= e($today) ?>"
                                        title="<?= $todayState === '' ? '미진행' : ($todayState === 'O' ? '완료' : '미완료') ?>"
                                        aria-label="오늘 루틴 상태 변경"
                                    ><?= $todayState === '' ? ' ' : e($todayState) ?></button>
                                </label>
                            </form>
                        </div>

                        <div class="routine-progress">
                            <span><b data-routine-done-count="<?= e((string) $routineId) ?>"><?= e((string) $routine['doneCount']) ?></b>/<?= e((string) $routine['durationDays']) ?>일</span>
                            <span><b data-routine-progress-percent="<?= e((string) $routineId) ?>"><?= e((string) $routine['progressPercent']) ?></b>%</span>
                        </div>
                        <div class="routine-lawn" data-routine-lawn="<?= e((string) $routineId) ?>" aria-label="누적 실행 잔디">
                            <?php foreach ($routine['lawnCells'] as $filled): ?>
                                <span class="<?= $filled ? 'is-filled' : '' ?>" aria-hidden="true"></span>
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
            <div class="routine-sheet-header">
                <strong id="routineDetailTitle<?= e((string) $routineId) ?>"><?= e((string) $routine['name']) ?> 상세</strong>
                <button type="button" class="ui-close-button" data-routine-close aria-label="닫기">×</button>
            </div>
            <p class="muted routine-detail-hint">시작일부터 오늘까지 날짜별 루틴 상태를 보여줍니다. 기간이 길어지면 이 목록만 스크롤됩니다.</p>
            <?php if (empty($routine['dailyLogs'])): ?>
                <div class="routine-empty compact">
                    <strong>아직 확인할 날짜가 없습니다.</strong>
                    <p class="muted">시작일이 되면 날짜별 상태를 바꿀 수 있습니다.</p>
                </div>
            <?php else: ?>
                <div class="routine-log-list" aria-label="날짜별 루틴 상태">
                    <?php foreach ($routine['dailyLogs'] as $log): ?>
                        <?php $state = (string) ($log['state'] ?? ''); ?>
                        <form method="post" action="/routine/toggle" class="routine-log-row" data-routine-toggle-form>
                            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                            <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
                            <input type="hidden" name="date" value="<?= e((string) $log['date']) ?>">
                            <span><?= e((string) $log['date']) ?></span>
                            <button
                                type="submit"
                                class="routine-check-button <?= $state === 'O' ? 'is-done' : ($state === 'X' ? 'is-failed' : '') ?>"
                                data-routine-state-button
                                data-routine-id="<?= e((string) $routineId) ?>"
                                data-routine-date="<?= e((string) $log['date']) ?>"
                                title="<?= $state === '' ? '미진행' : ($state === 'O' ? '완료' : '미완료') ?>"
                                aria-label="<?= e((string) $log['date']) ?> 루틴 상태 변경"
                            ><?= $state === '' ? ' ' : e($state) ?></button>
                        </form>
                    <?php endforeach; ?>
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

                <div class="routine-form-grid">
                    <div>
                        <label class="form-label" for="routineStartDate<?= e((string) $routineId) ?>">시작일</label>
                        <input class="input" id="routineStartDate<?= e((string) $routineId) ?>" name="start_date" type="date" value="<?= e((string) $routine['startDate']) ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="routineDuration<?= e((string) $routineId) ?>">기간</label>
                        <select class="input" id="routineDuration<?= e((string) $routineId) ?>" name="duration_days">
                            <?php foreach ($durationOptions as $days): ?>
                                <option value="<?= e((string) $days) ?>" <?= (int) $routine['durationDays'] === (int) $days ? 'selected' : '' ?>>
                                    <?= e((string) $days) ?>일
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

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
            <form id="routineDelete<?= e((string) $routineId) ?>" method="post" action="/routine/delete" data-confirm="루틴을 삭제할까요? 기존 실행 기록은 회고 참고용으로 숨김 처리됩니다.">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <input type="hidden" name="routine_id" value="<?= e((string) $routineId) ?>">
            </form>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
