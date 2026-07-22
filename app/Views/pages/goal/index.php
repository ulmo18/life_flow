<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$selectedGoalType = (string) ($old['goal_type'] ?? 'monthly');
$selectedParentId = (int) ($old['parent_goal_id'] ?? 0);
$selectedStatus = (string) ($selectedStatus ?? 'active');
$selectedView = (string) ($selectedView ?? 'cards');
$selectedGoalTypeFilter = $selectedGoalTypeFilter ?? null;
$today = date('Y-m-d');

$statusClass = static function (string $status): string {
    return match ($status) {
        'completed' => 'is-completed',
        'paused' => 'is-paused',
        'archived' => 'is-archived',
        default => 'is-active',
    };
};

$statusFilterOrder = ['active', 'completed', 'archived', 'paused'];

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

$renderGoalTree = static function (array $nodes, int $depth = 0) use (&$renderGoalTree): void {
    ?>
    <ol class="goal-tree" <?= $depth > 0 ? 'data-goal-tree-child' : '' ?>>
        <?php foreach ($nodes as $node): ?>
            <?php
            $goalId = (int) $node['id'];
            $children = $node['children'] ?? [];
            ?>
            <li class="goal-tree-item" style="--goal-tree-depth: <?= e((string) $depth) ?>;">
                <button type="button" class="goal-tree-node" data-goal-open="edit-<?= e((string) $goalId) ?>" aria-label="<?= e((string) $node['title']) ?> 목표 수정">
                    <span class="goal-type-badge"><?= e((string) $node['goalTypeLabel']) ?></span>
                    <strong><?= e((string) $node['title']) ?></strong>
                </button>
                <?php if (!empty($children)): ?>
                    <?php $renderGoalTree($children, $depth + 1); ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php
};
?>

<main class="page goal-page">
    <section class="goal-hero">
        <div>
            <p class="eyebrow">Goal</p>
            <div class="goal-title-row">
                <h1 class="page-title">목표</h1>
                <button type="button" class="goal-info-button" data-goal-open="guide" aria-label="목표 사용 안내 열기">!</button>
            </div>
            <p class="muted">큰 생각부터 작은 실행까지, 지금 보고 싶은 목표만 가볍게 확인해요.</p>
        </div>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <nav class="goal-view-switch" aria-label="목표 보기 방식">
        <a href="/goal?status=<?= e($selectedStatus) ?>&type=<?= e((string) ($selectedGoalTypeFilter ?? '')) ?>&view=cards" <?= $selectedView === 'cards' ? 'aria-current="page"' : '' ?>>카드 보기</a>
        <a href="/goal?view=tree" <?= $selectedView === 'tree' ? 'aria-current="page"' : '' ?>>트리 보기</a>
    </nav>

    <?php if ($selectedView === 'cards'): ?>
    <nav class="goal-status-filter" aria-label="목표 상태 필터">
        <?php foreach ($statusFilterOrder as $statusValue): ?>
            <?php if (!array_key_exists($statusValue, $statusOptions)) {
                continue;
            } ?>
            <a
                class="goal-status-filter-link"
                href="/goal?status=<?= e($statusValue) ?>&type=<?= e((string) ($selectedGoalTypeFilter ?? '')) ?>&view=cards"
                <?= $selectedStatus === $statusValue ? 'aria-current="page"' : '' ?>
            >
                <?= e((string) $statusOptions[$statusValue]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <nav class="goal-status-filter goal-type-filter" aria-label="목표 구분 필터">
        <a
            class="goal-status-filter-link"
            href="/goal?status=<?= e($selectedStatus) ?>&view=cards"
            <?= $selectedGoalTypeFilter === null ? 'aria-current="page"' : '' ?>
        >전체</a>
        <?php foreach ($goalTypes as $typeValue => $typeLabel): ?>
            <a
                class="goal-status-filter-link"
                href="/goal?status=<?= e($selectedStatus) ?>&type=<?= e((string) $typeValue) ?>&view=cards"
                <?= $selectedGoalTypeFilter === (string) $typeValue ? 'aria-current="page"' : '' ?>
            ><?= e((string) $typeLabel) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($selectedView === 'tree'): ?>
        <section class="goal-tree-section" aria-label="진행 중 목표 트리">
            <div class="goal-tree-heading">
                <strong>진행 중 목표 구조</strong>
                <span>상위 목표부터 하위 실행 목표까지 한 번에 봅니다.</span>
            </div>
            <?php if (empty($activeGoalTree)): ?>
                <div class="goal-empty">
                    <strong>진행 중인 목표가 없습니다.</strong>
                    <p class="muted">하단의 목표 추가 버튼으로 지금 이어갈 목표를 만들어보세요.</p>
                </div>
            <?php else: ?>
                <?php $renderGoalTree($activeGoalTree); ?>
            <?php endif; ?>
        </section>
    <?php else: ?>
    <section class="goal-list-section" aria-label="목표 목록">
        <?php if (empty($goals)): ?>
            <div class="goal-empty">
                <strong>이 상태의 목표가 없습니다.</strong>
                <p class="muted">하단의 목표 추가 버튼으로 지금 떠오른 생각부터 적어보세요.</p>
            </div>
        <?php else: ?>
            <ul class="goal-list">
                <?php foreach ($goals as $goal): ?>
                    <?php
                    $goalId = (int) $goal['id'];
                    $linkedPlans = $goal['linkedPlans'] ?? [];
                    $linkedRoutines = $goal['linkedRoutines'] ?? [];
                    $progress = $goal['periodProgress'] ?? ['visible' => false, 'percent' => 0, 'label' => '', 'state' => 'none'];
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

                        <?php if (!empty($progress['visible'])): ?>
                            <div class="goal-progress is-<?= e((string) $progress['state']) ?>">
                                <div class="goal-progress-meta">
                                    <span><?= e((string) $progress['label']) ?></span>
                                    <strong><?= e((string) $progress['percent']) ?>%</strong>
                                </div>
                                <div
                                    class="goal-progress-track"
                                    role="progressbar"
                                    aria-label="<?= e((string) $progress['label']) ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="<?= e((string) $progress['percent']) ?>"
                                >
                                    <span style="width: <?= e((string) $progress['percent']) ?>%;"></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ((string) ($goal['behaviorNote'] ?? '') !== '' || !empty($linkedPlans) || !empty($linkedRoutines)): ?>
                        <details class="goal-linked-details">
                            <summary>
                                <span>상세 정보</span>
                                <?php if (!empty($linkedPlans) || !empty($linkedRoutines)): ?>
                                    <small>계획 <?= e((string) count($linkedPlans)) ?> · 루틴 <?= e((string) count($linkedRoutines)) ?></small>
                                <?php endif; ?>
                            </summary>
                            <div class="goal-linked-wrap">
                                <?php if ((string) ($goal['behaviorNote'] ?? '') !== ''): ?>
                                    <p class="goal-action-note"><?= e((string) $goal['behaviorNote']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($linkedPlans)): ?>
                                <div class="goal-linked-list">
                                    <strong>연결된 계획</strong>
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
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($linkedRoutines)): ?>
                                <div class="goal-linked-list">
                                    <strong>연결된 루틴</strong>
                                        <ul>
                                            <?php foreach ($linkedRoutines as $routine): ?>
                                                <li>
                                                    <span><?= e((string) $routine['name']) ?></span>
                                                    <small><?= e((string) $routine['periodLabel']) ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </details>
                        <?php endif; ?>

                        <div class="goal-card-actions">
                            <button type="button" class="btn btn-secondary" data-goal-open="edit-<?= e((string) $goalId) ?>">수정</button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <button type="button" class="btn btn-primary goal-floating-add" data-goal-open="create">목표 추가</button>
</main>

<div class="goal-local-layer" data-goal-layer hidden>
    <button type="button" class="goal-local-overlay" data-goal-close aria-label="목표 창 닫기"></button>

    <section class="goal-sheet" data-goal-sheet="guide" hidden aria-modal="true" role="dialog" aria-labelledby="goalGuideTitle">
        <div class="goal-sheet-header">
            <strong id="goalGuideTitle">목표 사용 안내</strong>
            <button type="button" class="ui-close-button" data-goal-close aria-label="닫기">×</button>
        </div>
        <div class="goal-guide">
            <p>목표는 꼭 위에서부터 채우지 않아도 괜찮아요.</p>
            <p>지금 떠오른 월간 목표 하나만 적어도 충분하고, 나중에 연간 목표나 버킷리스트로 연결해도 됩니다.</p>
            <p>생각이 꼬리를 물 때는 큰 목표를 먼저 완성하려 하기보다, 지금 할 수 있는 작은 실행을 목표로 남겨두세요.</p>
        </div>
    </section>

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
                <span class="form-label">목표 구분</span>
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
                    <span class="form-label">목표 구분</span>
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

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
