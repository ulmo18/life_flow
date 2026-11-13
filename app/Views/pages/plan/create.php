<?php require __DIR__ . '/../../layouts/header.php'; ?>

<main class="calendar-page plan-editor-page">
    <a class="btn btn-secondary plan-back-button" href="<?= !empty($sourcePlan) ? '/plan/show?id=' . e((string) $sourcePlan['id']) : '/plan?view=days' ?>">← 뒤로가기</a>
    <h1 class="sr-only"><?= e((string) ($heading ?? '하루 템플릿 추가')) ?></h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>


    <form class="plan-editor-form" method="post" action="<?= e((string) ($formAction ?? '/plan')) ?>" id="planEditorForm">
        <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
        <?php if (!empty($sourcePlan)): ?>
            <input type="hidden" name="source_plan_group_id" value="<?= e((string) $sourcePlan['id']) ?>">
        <?php endif; ?>
        <input type="hidden" name="blocks" id="planBlocksInput" value="<?= e((string) ($old['blocks'] ?? '[]')) ?>">
        <script type="application/json" id="planGoalOptionsJson">
            <?= json_encode($goalOptions ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>
        </script>
        <script type="application/json" id="planBlockTemplatesJson">
            <?= json_encode($blockTemplates ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>
        </script>

        <div class="form-group plan-name-field">
            <label class="form-label" for="planName">하루 템플릿 이름</label>
            <input
                class="input<?= !empty($errors['name']) ? ' is-error' : '' ?>"
                id="planName"
                name="name"
                type="text"
                maxlength="80"
                value="<?= e((string) ($old['name'] ?? '')) ?>"
                placeholder="예: 평일"
                <?= !empty($errors['name']) ? 'aria-invalid="true" aria-describedby="planNameError"' : '' ?>
                required
            >
            <?php if (!empty($errors['name'])): ?>
                <p class="field-error" id="planNameError"><?= e((string) $errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($errors['blocks'])): ?>
            <div class="msg msg-error"><?= e((string) $errors['blocks']) ?></div>
        <?php endif; ?>



        <section class="daygrid-wrap calendar-timetable-panel is-plan plan-template-timetable" aria-label="하루 템플릿 시간표">
            <div class="daygrid" id="planDaygrid">
                <?php for ($hour = 0; $hour < 24; $hour++): ?>
                    <div class="daygrid-row">
                        <div class="hour-label"><?= sprintf('%02d', $hour) ?></div>
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

        <p class="plan-editor-guide">빈 시간대를 길게 눌러 일정을 등록하세요. 등록한 일정은 눌러 수정하고, 길게 눌러 이동하거나 양 끝 핸들로 시간을 조정할 수 있어요.</p>
    <?php if (!empty($sourcePlan)): ?>
        <div class="msg msg-info">
            이 화면에서는 하루 템플릿을 수정합니다. 이미 캘린더에 복사된 날짜별 계획은 변경되지 않습니다.
        </div>
    <?php endif; ?>

        <button type="submit" class="btn btn-primary plan-floating-save<?= !empty($sourcePlan) ? ' plan-template-update' : '' ?>"><?= !empty($sourcePlan) ? '템플릿 수정' : '템플릿 저장' ?></button>
    </form>
</main>


<dialog class="lf-date-dialog plan-schedule-dialog" id="planScheduleDialog" aria-labelledby="planScheduleTitle">
    <header class="lf-date-header"><strong id="planScheduleTitle">템플릿 일정 등록</strong><button type="button" class="lf-date-close" data-plan-schedule-close aria-label="닫기">×</button></header>
    <form id="planScheduleForm">
        <div class="plan-schedule-body">
            <div class="ui-segmented-tabs" role="tablist" aria-label="등록 방법" data-plan-schedule-tabs>
                <button type="button" role="tab" aria-selected="true" aria-controls="planScheduleManual" data-plan-editor-tab="manual">일정 등록</button>
                <button type="button" role="tab" aria-selected="false" aria-controls="planScheduleRecurring" tabindex="-1" data-plan-editor-tab="template">반복 일정</button>
            </div>
            <div id="planScheduleRecurring" role="tabpanel" data-plan-editor-panel="template" hidden>
                <label class="form-label" for="planScheduleTemplate">반복 할 일 선택</label>
                <select class="input" id="planScheduleTemplate"><option value="">선택해 주세요</option><?php foreach (($blockTemplates ?? []) as $template): if (empty($template['isEnabled'])) continue; ?><option value="<?= e((string) $template['id']) ?>"><?= e((string) $template['title']) ?></option><?php endforeach; ?></select>
                <p class="form-hint">선택한 시간대에 반복 할 일을 복사합니다. 원본은 바뀌지 않아요.</p>
                <a class="link" href="/todo?tab=recurring">반복 할 일 관리</a>
            </div>
            <div id="planScheduleManual" role="tabpanel" data-plan-editor-panel="manual"><p class="form-hint">이 하루 템플릿에 사용할 일정을 직접 입력하세요.</p></div>
            <div class="form-group"><label class="form-label" for="planScheduleName">일정명</label><input class="input" id="planScheduleName" maxlength="80" required></div>
            <fieldset class="importance-choice-group"><legend>중요도</legend><?php foreach (['A','B','C','D'] as $importance): ?><label><input type="radio" name="importance" value="<?= e($importance) ?>" <?= $importance==='D'?'checked':'' ?>><span class="ui-choice-button"><?= e($importance) ?></span></label><?php endforeach; ?></fieldset>
            <div class="form-group"><label class="form-label" for="planScheduleGoal">연결할 목표</label><select class="input" id="planScheduleGoal"><option value="">목표 연결 없음</option><?php foreach (($goalOptions ?? []) as $goal): ?><option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'])) ?></option><?php endforeach; ?></select></div>
            <p class="field-error" data-plan-schedule-error role="alert" hidden></p>
        </div>
        <div class="lf-time-actions"><button type="button" class="btn btn-ghost" data-plan-schedule-delete hidden>일정 삭제</button><button type="submit" class="btn btn-primary" data-plan-schedule-save>일정 등록</button></div>
    </form>
</dialog>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
