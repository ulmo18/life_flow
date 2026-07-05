<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="calendar-page plan-editor-page">
    <section class="calendar-header">
        <div>
            <p class="eyebrow">Plan</p>
            <h1 class="calendar-date"><?= e((string) ($heading ?? '계획 추가')) ?></h1>
            <p class="calendar-subtitle">블록을 드래그한 뒤 이름과 중요도를 입력합니다.</p>
        </div>
        <a class="calendar-icon-button" href="/plan" aria-label="계획 리스트로 이동">←</a>
    </section>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <?php if (!empty($sourcePlan)): ?>
        <div class="msg msg-info">
            수정 저장 시 기존 계획은 숨김 처리되고 새 버전으로 저장됩니다. 기존 캘린더와 목표 연결은 이전 버전을 계속 참조합니다.
        </div>
    <?php endif; ?>

    <form class="plan-editor-form" method="post" action="<?= e((string) ($formAction ?? '/plan')) ?>" id="planEditorForm">
        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
        <?php if (!empty($sourcePlan)): ?>
            <input type="hidden" name="source_plan_group_id" value="<?= e((string) $sourcePlan['id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="blocks" id="planBlocksInput" value="<?= e((string) ($old['blocks'] ?? '[]')) ?>">

        <div class="form-group plan-name-field">
            <label class="form-label" for="planName">계획 그룹명</label>
            <input
                class="input<?= !empty($errors['name']) ? ' is-error' : '' ?>"
                id="planName"
                name="name"
                type="text"
                maxlength="80"
                value="<?= e((string) ($old['name'] ?? '')) ?>"
                placeholder="예: 평일"
                required
            >
            <?php if (!empty($errors['name'])): ?>
                <p class="field-error"><?= e((string) $errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors['blocks'])): ?>
            <div class="msg msg-error"><?= e((string) $errors['blocks']) ?></div>
        <?php endif; ?>

        <section class="daygrid-wrap" aria-label="계획 일정 그리드">
            <div class="daygrid" id="planDaygrid">
                <?php for ($hour = 0; $hour < 24; $hour++): ?>
                    <div class="daygrid-row">
                        <div class="hour-label"><?= sprintf('%02d:00', $hour) ?></div>
                        <div class="cells">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <div
                                    class="cell"
                                    data-index="<?= ($hour * 6) + $i ?>"
                                    data-time="<?= sprintf('%02d:%02d', $hour, $i * 10) ?>"
                                ></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endfor; ?>

                <div class="event-layer" id="planEditorLayer"></div>
            </div>
        </section>

        <button type="submit" class="btn btn-primary plan-floating-save">계획 저장</button>
    </form>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
