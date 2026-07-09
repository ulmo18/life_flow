<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$selectedGoalType = (string) ($old['goal_type'] ?? 'monthly');
$selectedParentId = (int) ($old['parent_goal_id'] ?? 0);
$today = date('Y-m-d');

$statusClass = static function (string $status): string {
    return match ($status) {
        'completed' => 'is-completed',
        'paused' => 'is-paused',
        'archived' => 'is-archived',
        default => 'is-active',
    };
};

$renderGoalTypeRadios = static function (
    array $goalTypes,
    string $selected,
    string $namePrefix,
    string $inputName = 'goal_type'
): void {
    ?>
    <div class="goal-type-radio-group" data-goal-type-group>
        <?php foreach ($goalTypes as $value => $label): ?>
            <?php $id = $namePrefix . ucfirst((string) $value); ?>
            <label class="goal-type-radio">
                <input
                    id="<?= e($id) ?>"
                    type="radio"
                    name="<?= e($inputName) ?>"
                    value="<?= e((string) $value) ?>"
                    <?= $selected === (string) $value ? 'checked' : '' ?>
                    data-goal-type-radio
                >
                <span><?= e((string) $label) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
};
?>

<main class="page goal-page">
    <section class="goal-hero">
        <div>
            <p class="eyebrow">Goal</p>
            <h1 class="page-title">목표</h1>
            <p class="muted">버킷리스트부터 한달 목표까지, 의도한 삶을 작게 쪼개 실행할 수 있게 정리합니다.</p>
        </div>
        <button type="button" class="btn btn-primary" data-goal-open="create">목표 추가</button>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <section class="goal-intro-card" aria-label="목표 사용 안내">
        <strong>목표는 꼭 위에서부터 채우지 않아도 괜찮아요.</strong>
        <p class="muted">가볍게 한달 목표만 만들 수도 있고, 큰 목표를 연간, 분기, 한달 목표로 연결해 성공 확률을 높일 수도 있습니다.</p>
    </section>

    <section class="goal-list-section" aria-label="목표 목록">
        <?php if (empty($goals)): ?>
            <div class="goal-empty">
                <strong>아직 만든 목표가 없습니다.</strong>
                <p class="muted">오늘의 작은 시도부터 적어보면, 계획과 루틴을 연결할 기준점이 생깁니다.</p>
                <button type="button" class="btn btn-secondary" data-goal-open="create">첫 목표 만들기</button>
            </div>
        <?php else: ?>
            <ul class="goal-list">
                <?php foreach ($goals as $goal): ?>
                    <?php
                    $goalId = (int) $goal['id'];
                    $linkedPlans = $goal['linkedPlans'] ?? [];
                    $linkedRoutines = $goal['linkedRoutines'] ?? [];
                    ?>
                    <li class="goal-card">
                        <div class="goal-card-top">
                            <div class="goal-path">
                                <span class="goal-type-badge"><?= e((string) $goal['goalTypeLabel']) ?></span>
                                <?php if (!empty($goal['parentTitle'])): ?>
                                    <span class="goal-path-arrow" aria-hidden="true">→</span>
                                    <span class="goal-parent-chip">
                                        <?= e((string) $goal['parentGoalTypeLabel']) ?> · <?= e((string) $goal['parentTitle']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="goal-status-badge <?= e($statusClass((string) $goal['status'])) ?>">
                                <?= e((string) $goal['statusLabel']) ?>
                            </span>
                        </div>

                        <div class="goal-card-title">
                            <strong><?= e((string) $goal['title']) ?></strong>
                            <span><?= e((string) $goal['periodLabel']) ?></span>
                        </div>

                        <?php if ((string) ($goal['behaviorNote'] ?? '') !== ''): ?>
                            <p class="goal-action-note"><?= e((string) $goal['behaviorNote']) ?></p>
                        <?php else: ?>
                            <p class="goal-action-hint">생각을 행동으로 옮기기 위한 리마인드를 적어두면 좋아요.</p>
                        <?php endif; ?>

                        <div class="goal-linked-wrap">
                            <div class="goal-linked-list">
                                <strong>연결된 계획</strong>
                                <?php if (empty($linkedPlans)): ?>
                                    <span class="goal-linked-empty">아직 연결된 계획이 없습니다.</span>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ($linkedPlans as $plan): ?>
                                            <li>
                                                <a href="/plan/show?id=<?= e((string) $plan['planGroupId']) ?>">
                                                    <?= e((string) $plan['planName']) ?>
                                                </a>
                                                <span><?= e((string) $plan['blockTitle']) ?> · <?= e((string) $plan['timeRange']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            <div class="goal-linked-list">
                                <strong>연결된 루틴</strong>
                                <?php if (empty($linkedRoutines)): ?>
                                    <span class="goal-linked-empty">아직 연결된 루틴이 없습니다.</span>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ($linkedRoutines as $routine): ?>
                                            <li>
                                                <span><?= e((string) $routine['name']) ?></span>
                                                <small><?= e((string) $routine['periodLabel']) ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="goal-card-actions">
                            <button type="button" class="btn btn-secondary" data-goal-open="edit-<?= e((string) $goalId) ?>">수정</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<div class="goal-local-layer" data-goal-layer hidden>
    <button type="button" class="goal-local-overlay" data-goal-close aria-label="목표 창 닫기"></button>

    <section class="goal-sheet" data-goal-sheet="create" hidden aria-modal="true" role="dialog" aria-labelledby="goalCreateTitle">
        <div class="goal-sheet-header">
            <strong id="goalCreateTitle">목표 추가</strong>
            <button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button>
        </div>

        <form class="goal-form" method="post" action="/goal">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">

            <label class="form-label" for="goalTitle">목표명</label>
            <input class="input" id="goalTitle" name="title" type="text" maxlength="80" value="<?= e((string) ($old['title'] ?? '')) ?>" required>
            <?php if (!empty($errors['title'])): ?>
                <p class="field-error"><?= e((string) $errors['title']) ?></p>
            <?php endif; ?>

            <div class="goal-field">
                <span class="form-label">목표구분</span>
                <?php $renderGoalTypeRadios($goalTypes, $selectedGoalType, 'goalCreate'); ?>
            </div>

            <label class="form-label" for="goalParent">상위 목표</label>
            <select class="input" id="goalParent" name="parent_goal_id">
                <option value="">연결하지 않음</option>
                <?php foreach ($parentOptions as $parent): ?>
                    <option value="<?= e((string) $parent['id']) ?>" <?= $selectedParentId === (int) $parent['id'] ? 'selected' : '' ?>>
                        <?= e((string) $parent['goalTypeLabel']) ?> · <?= e((string) $parent['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['parent_goal_id'])): ?>
                <p class="field-error"><?= e((string) $errors['parent_goal_id']) ?></p>
            <?php endif; ?>

            <label class="form-label" for="goalBehaviorNote">행동 리마인드</label>
            <textarea class="input" id="goalBehaviorNote" name="behavior_note" rows="3" maxlength="300" placeholder="생각을 행동으로 옮기기 위해 기억할 문장을 적어보세요."><?= e((string) ($old['behavior_note'] ?? ($old['behavior_how'] ?? ''))) ?></textarea>

            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </section>

    <?php foreach ($goals as $goal): ?>
        <?php $goalId = (int) $goal['id']; ?>
        <section class="goal-sheet" data-goal-sheet="edit-<?= e((string) $goalId) ?>" hidden aria-modal="true" role="dialog" aria-labelledby="goalEditTitle<?= e((string) $goalId) ?>">
            <div class="goal-sheet-header">
                <strong id="goalEditTitle<?= e((string) $goalId) ?>">목표 수정</strong>
                <button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button>
            </div>

            <form class="goal-form" method="post" action="/goal/update">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>">

                <label class="form-label" for="goalTitle<?= e((string) $goalId) ?>">목표명</label>
                <input class="input" id="goalTitle<?= e((string) $goalId) ?>" name="title" type="text" maxlength="80" value="<?= e((string) $goal['title']) ?>" required>

                <div class="goal-field">
                    <span class="form-label">목표구분</span>
                    <?php $renderGoalTypeRadios($goalTypes, (string) $goal['goalType'], 'goalEdit' . $goalId); ?>
                </div>

                <div class="goal-form-grid">
                    <div>
                        <label class="form-label" for="goalStatus<?= e((string) $goalId) ?>">상태</label>
                        <select class="input" id="goalStatus<?= e((string) $goalId) ?>" name="status">
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= e((string) $value) ?>" <?= $goal['status'] === (string) $value ? 'selected' : '' ?>>
                                    <?= e((string) $label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="goalParent<?= e((string) $goalId) ?>">상위 목표</label>
                        <select class="input" id="goalParent<?= e((string) $goalId) ?>" name="parent_goal_id">
                            <option value="">연결하지 않음</option>
                            <?php foreach ($parentOptions as $parent): ?>
                                <?php if ((int) $parent['id'] === $goalId) {
                                    continue;
                                } ?>
                                <option value="<?= e((string) $parent['id']) ?>" <?= (int) ($goal['parentGoalId'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $parent['goalTypeLabel']) ?> · <?= e((string) $parent['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="goal-period-fields" data-goal-period-fields>
                    <div class="goal-form-grid">
                        <div>
                            <label class="form-label" for="goalStartDate<?= e((string) $goalId) ?>">시작일</label>
                            <input class="input" id="goalStartDate<?= e((string) $goalId) ?>" name="period_start_date" type="date" value="<?= e((string) ($goal['periodStartDate'] ?? $today)) ?>" data-goal-start-date>
                        </div>
                        <div>
                            <label class="form-label" for="goalEndDate<?= e((string) $goalId) ?>">종료일</label>
                            <input class="input" id="goalEndDate<?= e((string) $goalId) ?>" name="period_end_date" type="date" value="<?= e((string) ($goal['periodEndDate'] ?? $today)) ?>" data-goal-end-date>
                        </div>
                    </div>
                </div>

                <label class="form-label" for="goalBehaviorNote<?= e((string) $goalId) ?>">행동 리마인드</label>
                <textarea class="input" id="goalBehaviorNote<?= e((string) $goalId) ?>" name="behavior_note" rows="3" maxlength="300"><?= e((string) $goal['behaviorNote']) ?></textarea>

                <div class="goal-edit-actions">
                    <button type="submit" class="btn btn-primary">저장</button>
                    <button type="submit" class="btn btn-ghost" form="goalDelete<?= e((string) $goalId) ?>">삭제</button>
                </div>
            </form>

            <form id="goalDelete<?= e((string) $goalId) ?>" method="post" action="/goal/delete" data-confirm="목표를 삭제할까요? 연결된 하위 목표와 계획, 루틴 연결은 해제됩니다.">
                <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                <input type="hidden" name="goal_id" value="<?= e((string) $goalId) ?>">
            </form>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
