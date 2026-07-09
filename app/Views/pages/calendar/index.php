<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php $retrospectPreview = $calendar['retrospectPreview'] ?? null; ?>

<main
    class="calendar-page <?= e((string) ($calendar['dateClass'] ?? '')) ?>"
    data-calendar-date="<?= e((string) $calendar['date']) ?>"
    data-current-index="<?= $calendar['currentIndex'] === null ? '' : e((string) $calendar['currentIndex']) ?>"
>
    <section class="calendar-header">
        <div class="calendar-date-nav" aria-label="날짜 이동">
            <a class="calendar-icon-button" href="/calendar?date=<?= e((string) $calendar['prevDate']) ?>" aria-label="이전 날짜">&lsaquo;</a>
            <div>
                <h1 class="calendar-date"><?= e((string) $calendar['dateTitle']) ?></h1>
                <p class="calendar-subtitle"><?= e((string) $calendar['dateSubTitle']) ?></p>
            </div>
            <a class="calendar-icon-button" href="/calendar?date=<?= e((string) $calendar['nextDate']) ?>" aria-label="다음 날짜">&rsaquo;</a>
        </div>

        <button
            type="button"
            class="calendar-retrospect-button"
            data-retrospect-preview-open
            <?= is_array($retrospectPreview) ? '' : 'disabled' ?>
            title="<?= is_array($retrospectPreview) ? '최근 회고 보기' : '발행된 회고 없음' ?>"
        >
            회고
        </button>
    </section>

    <?php if (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php elseif (empty($calendar['selectedPlanGroupId'])): ?>
        <span data-toast-message="계획 일정이 아직 등록되지 않았습니다. 기준 계획을 선택해 오늘의 계획을 먼저 세워보세요." hidden></span>
    <?php endif; ?>

    <?php if (!empty($errors['general'])): ?>
        <div class="msg msg-error"><?= e((string) $errors['general']) ?></div>
    <?php endif; ?>

    <?php if (!empty($calendar['selectedPlanGroupId'])): ?>
        <details class="calendar-plan-summary">
            <summary>
                <span>오늘의 계획</span>
                <small><?= e((string) count($calendar['planReminderItems'] ?? [])) ?>개</small>
            </summary>
            <?php if (empty($calendar['planReminderItems'])): ?>
                <p class="calendar-plan-summary-empty">선택된 계획 일정이 없습니다.</p>
            <?php else: ?>
                <ol class="calendar-plan-summary-list">
                    <?php foreach (($calendar['planReminderItems'] ?? []) as $item): ?>
                        <?php
                        $durationClass = '';
                        if ((int) ($item['durationMinutes'] ?? 0) <= 30) {
                            $durationClass = 'is-short';
                        } elseif ((int) ($item['durationMinutes'] ?? 0) >= 60) {
                            $durationClass = 'is-focus';
                        }
                        ?>
                        <li class="<?= !empty($item['isLinked']) ? 'is-linked' : '' ?>">
                            <span class="calendar-importance-badge importance-<?= e(strtolower((string) $item['importance'])) ?>">
                                <?= e((string) $item['importanceBadge']) ?>
                            </span>
                            <span class="calendar-plan-summary-title <?= e($durationClass) ?>"><?= e((string) $item['title']) ?></span>
                            <time><?= e((string) $item['timeRange']) ?></time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </details>
    <?php endif; ?>

    <section class="daygrid-wrap" aria-label="일간 캘린더">
        <div class="daygrid" id="daygrid">
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row">
                    <div class="hour-label"><?= sprintf('%02d:00', $hour) ?></div>
                    <div class="cells">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <?php $index = ($hour * 6) + $i; ?>
                            <div
                                class="cell <?= $calendar['currentIndex'] === $index ? 'current-cell' : '' ?>"
                                data-index="<?= e((string) $index) ?>"
                                data-time="<?= sprintf('%02d:%02d', $hour, $i * 10) ?>"
                            ></div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>

            <div class="event-layer" id="planLayer">
                <?php foreach (($calendar['planSegments'] ?? []) as $segment): ?>
                    <div
                        class="event plan-event"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                        data-ui-tooltip="<?= e((string) $segment['title']) ?>"
                    >
                        <span class="calendar-importance-badge is-neutral"><?= e((string) ($segment['importanceBadge'] ?? 'D')) ?></span>
                        <span class="event-title"><?= e((string) $segment['title']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="event-layer actual-layer" id="actualLayer">
                <?php foreach (($calendar['actualSegments'] ?? []) as $segment): ?>
                    <div
                        class="event actual-event-form"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                    >
                        <button
                            type="button"
                            class="actual-event <?= $segment['planTemplateId'] === null ? '' : 'is-linked' ?>"
                            style="--event-color: <?= e((string) $segment['tagColor']) ?>;"
                            data-ui-tooltip="<?= e((string) $segment['title']) ?>"
                            data-event-open
                            data-event-id="<?= e((string) $segment['id']) ?>"
                            data-event-title="<?= e((string) $segment['title']) ?>"
                            data-event-plan-template-id="<?= $segment['planTemplateId'] === null ? '' : e((string) $segment['planTemplateId']) ?>"
                            data-event-tag-id="<?= $segment['tagId'] === null ? '' : e((string) $segment['tagId']) ?>"
                            data-event-memo="<?= e((string) $segment['memo']) ?>"
                        >
                            <?php if (!empty($segment['tagName'])): ?>
                                <span class="calendar-tag-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span class="event-title"><?= e((string) $segment['title']) ?></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <button type="button" class="calendar-plan-settings-fab" data-plan-settings-open aria-label="기준 계획 변경">
        <span aria-hidden="true">+</span>
        기준 계획
    </button>
    <button type="button" class="calendar-routine-fab" data-routine-open>루틴</button>
</main>

<div class="calendar-local-layer" data-calendar-layer hidden>
    <div class="calendar-local-overlay" data-calendar-close></div>

    <section class="calendar-sheet" data-event-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventSheetTitle">
        <form method="post" action="/calendar/event" id="calendarEventForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="start_index" id="calendarStartIndex">
            <input type="hidden" name="end_index" id="calendarEndIndex">
            <div class="calendar-sheet-header">
                <strong id="eventSheetTitle">실제 일정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <p class="calendar-sheet-time" id="calendarSelectedTime"></p>
            <label class="form-label" for="calendarEventTitle">일정명</label>
            <input class="input" id="calendarEventTitle" name="title" type="text" maxlength="80" autocomplete="off" required>

            <fieldset class="calendar-tag-links">
                <legend>태그</legend>
                <label class="calendar-tag-link is-empty">
                    <input type="radio" name="calendar_tag_id" value="" checked>
                    <span>태그 없음</span>
                </label>
                <?php foreach (($calendar['calendarTags'] ?? []) as $tag): ?>
                    <label class="calendar-tag-link" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;">
                        <input type="radio" name="calendar_tag_id" value="<?= e((string) $tag['id']) ?>">
                        <span class="calendar-tag-swatch" aria-hidden="true"></span>
                        <span><?= e((string) $tag['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="calendar-plan-links">
                <legend>연결할 계획 일정</legend>
                <label class="calendar-plan-link">
                    <input type="radio" name="plan_template_id" value="" checked>
                    <span>연결하지 않음</span>
                </label>
                <?php foreach (($calendar['planOptions'] ?? []) as $option): ?>
                    <label class="calendar-plan-link <?= !empty($option['disabled']) ? 'is-disabled' : '' ?>">
                        <input
                            type="radio"
                            name="plan_template_id"
                            value="<?= e((string) $option['templateId']) ?>"
                            <?= !empty($option['disabled']) ? 'disabled' : '' ?>
                        >
                        <span><?= e((string) $option['title']) ?></span>
                        <small><?= e((string) $option['timeRange']) ?><?= !empty($option['disabled']) ? ' · 이미 연결됨' : '' ?></small>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <button type="submit" class="btn btn-primary">일정 저장</button>
        </form>
    </section>

    <section class="calendar-sheet" data-event-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventEditSheetTitle">
        <form method="post" action="/calendar/event/update" id="calendarEventEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarEditEventId">
            <div class="calendar-sheet-header">
                <strong id="eventEditSheetTitle">일정 수정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="calendarEditEventTitle">일정명</label>
            <input class="input" id="calendarEditEventTitle" name="title" type="text" maxlength="80" autocomplete="off" required>

            <fieldset class="calendar-tag-links" data-edit-tag-group>
                <legend>태그</legend>
                <label class="calendar-tag-link is-empty">
                    <input type="radio" name="calendar_tag_id" value="">
                    <span>태그 없음</span>
                </label>
                <?php foreach (($calendar['calendarTags'] ?? []) as $tag): ?>
                    <label class="calendar-tag-link" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;">
                        <input type="radio" name="calendar_tag_id" value="<?= e((string) $tag['id']) ?>">
                        <span class="calendar-tag-swatch" aria-hidden="true"></span>
                        <span><?= e((string) $tag['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="calendar-plan-links" data-edit-plan-group>
                <legend>연결할 계획 일정</legend>
                <label class="calendar-plan-link">
                    <input type="radio" name="plan_template_id" value="">
                    <span>연결하지 않음</span>
                </label>
                <?php foreach (($calendar['planOptions'] ?? []) as $option): ?>
                    <label
                        class="calendar-plan-link <?= !empty($option['disabled']) ? 'is-disabled' : '' ?>"
                        data-plan-option="<?= e((string) $option['templateId']) ?>"
                        data-plan-disabled="<?= !empty($option['disabled']) ? '1' : '0' ?>"
                    >
                        <input
                            type="radio"
                            name="plan_template_id"
                            value="<?= e((string) $option['templateId']) ?>"
                            <?= !empty($option['disabled']) ? 'disabled' : '' ?>
                        >
                        <span><?= e((string) $option['title']) ?></span>
                        <small><?= e((string) $option['timeRange']) ?><?= !empty($option['disabled']) ? ' · 이미 연결됨' : '' ?></small>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <label class="form-label" for="calendarEditMemo">메모</label>
            <textarea class="input calendar-memo-input" id="calendarEditMemo" name="memo" maxlength="500" rows="4"></textarea>

            <div class="calendar-sheet-actions">
                <button type="submit" class="btn btn-primary">수정 저장</button>
                <button type="submit" class="btn btn-ghost" form="calendarEventDeleteForm">일정 삭제</button>
            </div>
        </form>
        <form method="post" action="/calendar/event/delete" id="calendarEventDeleteForm" hidden>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarDeleteEventId">
        </form>
    </section>

    <section class="calendar-sheet" data-plan-settings-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planSettingsTitle">
        <form class="calendar-plan-picker" method="post" action="/calendar/day-plan" data-has-linked-events="<?= !empty($calendar['hasLinkedActualEvents']) ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <div class="calendar-sheet-header">
                <strong id="planSettingsTitle">기준 계획</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="calendarPlanGroup">선택한 날짜에 연결할 계획</label>
            <select class="input" id="calendarPlanGroup" name="plan_group_id">
                <option value="">계획 없음</option>
                <?php foreach (($calendar['planGroups'] ?? []) as $planGroup): ?>
                    <option
                        value="<?= e((string) $planGroup['id']) ?>"
                        <?= (int) ($calendar['selectedPlanGroupId'] ?? 0) === (int) $planGroup['id'] ? 'selected' : '' ?>
                    >
                        <?= e((string) $planGroup['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </section>

    <section class="calendar-popup" data-routine-popup hidden aria-modal="true" role="dialog" aria-labelledby="routinePopupTitle">
        <div class="calendar-sheet-header">
            <strong id="routinePopupTitle">루틴 체크</strong>
            <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
        </div>
        <?php if (empty($calendar['routines'])): ?>
            <p class="calendar-retrospect-body">선택한 날짜에 체크할 루틴이 없습니다.</p>
        <?php else: ?>
            <ul class="routine-preview-list">
                <?php foreach (($calendar['routines'] ?? []) as $routine): ?>
                    <?php $routineState = (string) ($routine['state'] ?? ''); ?>
                    <li>
                        <span><?= e((string) $routine['name']) ?></span>
                        <form method="post" action="/routine/toggle" data-calendar-routine-toggle-form>
                            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
                            <input type="hidden" name="return_to" value="calendar">
                            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
                            <input type="hidden" name="routine_id" value="<?= e((string) $routine['id']) ?>">
                            <button
                                type="submit"
                                class="<?= $routineState === 'O' ? 'is-done' : ($routineState === 'X' ? 'is-failed' : '') ?>"
                                data-calendar-routine-state-button
                                data-routine-id="<?= e((string) $routine['id']) ?>"
                                data-routine-date="<?= e((string) $calendar['date']) ?>"
                                title="<?= $routineState === '' ? '미기록' : ($routineState === 'O' ? '완료' : '미완료') ?>"
                            >
                                <?= $routineState === '' ? ' ' : e($routineState) ?>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="calendar-popup" data-retrospect-preview hidden aria-modal="true" role="dialog" aria-labelledby="retrospectPreviewTitle">
        <div class="calendar-sheet-header">
            <strong id="retrospectPreviewTitle">최근 회고</strong>
            <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
        </div>
        <?php if (is_array($retrospectPreview)): ?>
            <?php $previewTexts = $retrospectPreview['texts'] ?? []; ?>
            <p class="calendar-retrospect-date">
                <?= e((string) ($retrospectPreview['dateTitle'] ?? '')) ?> <?= e((string) ($retrospectPreview['dateSubTitle'] ?? '')) ?>
            </p>
            <div class="calendar-retrospect-summary">
                <span>계획 <?= e((string) $retrospectPreview['planAchievementRate']) ?>%</span>
                <span>루틴 <?= e((string) $retrospectPreview['routineAchievementRate']) ?>%</span>
                <span>실제 <?= e((string) $retrospectPreview['linkedActualTimeLabel']) ?></span>
            </div>
            <article class="calendar-retrospect-preview-text">
                <strong>오늘 하루</strong>
                <p><?= nl2br(e((string) ($previewTexts['today_review'] ?? ''))) ?: '작성된 내용이 없습니다.' ?></p>
            </article>
            <article class="calendar-retrospect-preview-text">
                <strong>오늘의 생각</strong>
                <p><?= nl2br(e((string) ($previewTexts['today_thoughts'] ?? ''))) ?: '작성된 내용이 없습니다.' ?></p>
            </article>
            <article class="calendar-retrospect-preview-text">
                <strong>내일의 설계</strong>
                <p><?= nl2br(e((string) ($previewTexts['tomorrow_plan'] ?? ''))) ?: '작성된 내용이 없습니다.' ?></p>
            </article>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
