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
            <span class="calendar-date-picker-control">
                <button type="button" class="calendar-date-picker-button" data-calendar-date-picker-open aria-label="특정 날짜로 이동">
                    <span aria-hidden="true">▦</span>
                </button>
                <input type="date" value="<?= e((string) $calendar['date']) ?>" data-calendar-date-picker aria-label="특정 날짜로 이동">
            </span>
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

    <div class="time-grid-toolbar">
        <span>빈 시간 칸을 길게 누른 뒤 드래그하면 일정 범위를 선택할 수 있습니다.</span>
    </div>

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
                            style="--event-color: <?= e((string) $segment['tagColor']) ?>; --event-text-color: <?= e((string) $segment['tagTextColor']) ?>;"
                            data-ui-tooltip="<?= e((string) $segment['title']) ?>"
                            data-event-open
                            data-event-id="<?= e((string) $segment['id']) ?>"
                            data-event-title="<?= e((string) $segment['title']) ?>"
                            data-event-plan-template-id="<?= $segment['planTemplateId'] === null ? '' : e((string) $segment['planTemplateId']) ?>"
                            data-event-tag-id="<?= $segment['tagId'] === null ? '' : e((string) $segment['tagId']) ?>"
                            data-event-memo="<?= e((string) $segment['memo']) ?>"
                            data-event-schedule-type="timed"
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

    <div class="calendar-fab" data-calendar-fab>
        <div class="calendar-fab-actions" data-calendar-fab-actions hidden>
            <button type="button" data-quick-memo-open>메모 작성</button>
            <button type="button" data-unscheduled-open>일정 추가</button>
            <button type="button" data-routine-open>루틴 확인</button>
            <span class="calendar-fab-divider" aria-hidden="true"></span>
            <button type="button" data-plan-settings-open>기준 계획 설정</button>
        </div>
        <button type="button" class="calendar-fab-toggle" data-calendar-fab-toggle aria-label="빠른 메뉴 열기" aria-expanded="false">+</button>
    </div>
</main>

<div class="calendar-local-layer" data-calendar-layer hidden>
    <div class="calendar-local-overlay" data-calendar-close></div>

    <section class="calendar-sheet" data-event-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventSheetTitle">
        <form method="post" action="/calendar/event" id="calendarEventForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="schedule_type" id="calendarScheduleType" value="timed">
            <input type="hidden" name="start_index" id="calendarStartIndex">
            <input type="hidden" name="end_index" id="calendarEndIndex">
            <input type="hidden" name="source_event_id" id="calendarSourceEventId">
            <div class="calendar-sheet-header">
                <strong id="eventSheetTitle">실제 일정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <p class="calendar-sheet-time" id="calendarSelectedTime"></p>

            <?php if (!empty($calendar['unscheduledEvents'])): ?>
                <div class="calendar-unscheduled-manager" data-unscheduled-manager hidden>
                    <strong>시간 미정 일정</strong>
                    <div class="calendar-unscheduled-list">
                        <?php foreach ($calendar['unscheduledEvents'] as $event): ?>
                            <?php $memoTargetId = 'calendarUnscheduledMemo' . (int) $event['id']; ?>
                            <button
                                type="button"
                                class="calendar-unscheduled-item"
                                style="--event-color: <?= e((string) $event['tagColor']) ?>;"
                                data-event-open
                                data-event-id="<?= e((string) $event['id']) ?>"
                                data-event-title="<?= e((string) $event['title']) ?>"
                                data-event-plan-template-id=""
                                data-event-tag-id="<?= $event['tagId'] === null ? '' : e((string) $event['tagId']) ?>"
                                data-event-memo-target="<?= e($memoTargetId) ?>"
                                data-event-schedule-type="unscheduled"
                            >
                                <span><?= e((string) $event['title']) ?></span>
                                <small><?= !empty($event['tagName']) ? e((string) $event['tagName']) : '태그 없음' ?></small>
                            </button>
                            <textarea hidden id="<?= e($memoTargetId) ?>"><?= e((string) $event['memo']) ?></textarea>
                        <?php endforeach; ?>
                    </div>
                    <strong>새 시간 미정 일정</strong>
                </div>
            <?php endif; ?>

            <?php if (!empty($calendar['unscheduledEvents'])): ?>
                <div class="calendar-event-source" data-event-source-tabs>
                    <div class="calendar-event-source-tabs" role="tablist" aria-label="일정 입력 방식">
                        <button type="button" role="tab" aria-selected="true" data-event-source-tab="new">새 일정 입력</button>
                        <button type="button" role="tab" aria-selected="false" data-event-source-tab="unscheduled">
                            시간 미정 일정 (<?= e((string) count($calendar['unscheduledEvents'])) ?>)
                        </button>
                    </div>
                    <div class="calendar-source-list" data-event-source-panel="unscheduled" hidden>
                        <?php foreach ($calendar['unscheduledEvents'] as $event): ?>
                            <label class="calendar-source-option" style="--event-color: <?= e((string) $event['tagColor']) ?>;">
                                <input
                                    type="radio"
                                    name="source_event_choice"
                                    value="<?= e((string) $event['id']) ?>"
                                    data-source-event
                                    data-event-title="<?= e((string) $event['title']) ?>"
                                    data-event-tag-id="<?= $event['tagId'] === null ? '' : e((string) $event['tagId']) ?>"
                                >
                                <span><?= e((string) $event['title']) ?></span>
                                <small><?= !empty($event['tagName']) ? e((string) $event['tagName']) : '태그 없음' ?></small>
                                <textarea hidden data-source-event-memo><?= e((string) $event['memo']) ?></textarea>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <label class="form-label" for="calendarEventTitle">일정명</label>
            <input class="input" id="calendarEventTitle" name="title" type="text" maxlength="80" autocomplete="off" required>

            <fieldset class="calendar-tag-links">
                <legend>태그</legend>
                <label class="calendar-tag-link is-empty">
                    <input type="radio" name="calendar_tag_id" value="" checked>
                    <span>태그 없음</span>
                </label>
                <?php foreach (($calendar['calendarTags'] ?? []) as $tag): ?>
                    <?php if ((int) ($tag['is_enabled'] ?? 1) !== 1): ?>
                        <input type="radio" name="calendar_tag_id" value="<?= e((string) $tag['id']) ?>" hidden>
                        <?php continue; ?>
                    <?php endif; ?>
                    <label class="calendar-tag-link" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;">
                        <input type="radio" name="calendar_tag_id" value="<?= e((string) $tag['id']) ?>">
                        <span class="calendar-tag-swatch" aria-hidden="true"></span>
                        <span><?= e((string) $tag['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="calendar-plan-links" data-create-plan-group>
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

            <?php if (!empty($calendar['routines']) && !empty($calendar['canEditRoutines'])): ?>
                <fieldset class="calendar-routine-links" data-create-routine-group>
                    <legend>함께 완료할 루틴</legend>
                    <?php foreach ($calendar['routines'] as $routine): ?>
                        <label class="calendar-routine-link <?= $routine['state'] === 'O' ? 'is-done' : '' ?>">
                            <input
                                type="checkbox"
                                name="routine_ids[]"
                                value="<?= e((string) $routine['id']) ?>"
                                <?= $routine['state'] === 'O' ? 'checked disabled' : '' ?>
                            >
                            <span><?= e((string) $routine['name']) ?></span>
                            <small><?= $routine['state'] === 'O' ? '이미 완료' : '일정 저장과 함께 완료' ?></small>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>

            <label class="form-label" for="calendarEventMemo">메모</label>
            <textarea class="input calendar-memo-input" id="calendarEventMemo" name="memo" maxlength="500" rows="3"></textarea>

            <button type="submit" class="btn btn-primary">일정 저장</button>
        </form>
    </section>

    <section class="calendar-sheet" data-event-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventEditSheetTitle">
        <form method="post" action="/calendar/event/update" id="calendarEventEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarEditEventId">
            <input type="hidden" name="schedule_type" id="calendarEditScheduleType" value="timed">
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
                    <?php $tagDisabled = (int) ($tag['is_enabled'] ?? 1) !== 1; ?>
                    <label
                        class="calendar-tag-link <?= $tagDisabled ? 'is-disabled' : '' ?>"
                        style="--tag-color: <?= e((string) $tag['color_hex']) ?>;"
                        data-tag-option
                        data-tag-disabled="<?= $tagDisabled ? '1' : '0' ?>"
                    >
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

    <section class="calendar-sheet" data-quick-memo-sheet hidden aria-modal="true" role="dialog" aria-labelledby="quickMemoTitle">
        <form method="post" action="/memo">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="return_to" value="calendar">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <div class="calendar-sheet-header">
                <strong id="quickMemoTitle">빠른 메모</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="calendarQuickMemo">바로 기록해보세요</label>
            <textarea class="input calendar-quick-memo-input" id="calendarQuickMemo" name="content" maxlength="10000" rows="7" required></textarea>
            <button type="submit" class="btn btn-primary">메모 저장</button>
        </form>
    </section>

    <section class="calendar-popup" data-routine-popup hidden aria-modal="true" role="dialog" aria-labelledby="routinePopupTitle">
        <div class="calendar-sheet-header">
            <strong id="routinePopupTitle">루틴 확인</strong>
            <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
        </div>
        <?php if (empty($calendar['routines']) || empty($calendar['canEditRoutines'])): ?>
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
                                class="routine-state-control <?= $routineState === 'O' ? 'is-done' : ($routineState === 'X' ? 'is-failed' : '') ?>"
                                data-calendar-routine-state-button
                                data-routine-id="<?= e((string) $routine['id']) ?>"
                                data-routine-date="<?= e((string) $calendar['date']) ?>"
                                data-routine-control-label="<?= e((string) $routine['name']) ?> 상태 변경"
                                data-state="<?= e($routineState) ?>"
                                title="<?= $routineState === '' ? '미기록' : ($routineState === 'O' ? '완료' : '미완료') ?>"
                                aria-label="<?= e((string) $routine['name']) ?> 상태 변경, <?= $routineState === 'O' ? '완료' : ($routineState === 'X' ? '미완료' : '미기록') ?>"
                            >
                                <span data-routine-state-marker aria-hidden="true"><?= $routineState === 'O' ? '✓' : ($routineState === 'X' ? '×' : '') ?></span>
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

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
