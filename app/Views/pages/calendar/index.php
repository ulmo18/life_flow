<?php require __DIR__ . '/../../layouts/header.php'; ?>

<?php
$retrospectPreview = $calendar['retrospectPreview'] ?? null;
$errorMessage = '';
foreach (($errors ?? []) as $error) {
    if (is_string($error) && $error !== '') {
        $errorMessage = $error;
        break;
    }
}
$availablePlanOptions = array_values(array_filter(
    $calendar['planOptions'] ?? [],
    static fn(array $option): bool => empty($option['disabled'])
));
$availableTagPalettes = array_values(array_filter(
    $calendar['tagPalettes'] ?? [],
    static fn(array $palette): bool => empty($palette['isUsed'])
));
$executionEnergy = $calendar['executionEnergy'] ?? [];
$executionEnergyGroups = $executionEnergy['groups'] ?? [];
$untimedPlanItems = array_values(array_filter(
    $calendar['planReminderItems'] ?? [],
    static fn(array $item): bool => ($item['scheduleType'] ?? '') === 'unscheduled'
));
?>

<main
    class="page calendar-page <?= e((string) ($calendar['dateClass'] ?? '')) ?>"
    data-calendar-date="<?= e((string) $calendar['date']) ?>"
    data-timezone="<?= e(date_default_timezone_get()) ?>"
    data-current-index="<?= $calendar['currentIndex'] === null ? '' : e((string) $calendar['currentIndex']) ?>"
>
    <section class="calendar-header">
        <div
            class="calendar-date-nav"
            aria-label="날짜 이동"
            data-calendar-date-swipe
            data-prev-date="<?= e((string) $calendar['prevDate']) ?>"
            data-next-date="<?= e((string) $calendar['nextDate']) ?>"
        >
            <h1 class="calendar-date-picker-control">
                <button type="button" class="calendar-date-picker-button" data-calendar-date-picker-open aria-label="날짜 선택">
                    <span class="calendar-date"><?= e((string) $calendar['dateTitle']) ?></span>
                    <span class="calendar-subtitle"><?= e((string) $calendar['dateSubTitle']) ?></span>
                </button>
                <input type="date" value="<?= e((string) $calendar['date']) ?>" data-calendar-date-picker aria-label="특정 날짜로 이동">
            </h1>
            <a class="calendar-header-action" href="/calendar?date=<?= e((string) $calendar['prevDate']) ?>" aria-label="이전 날짜">
                <span class="calendar-header-action-visual is-icon" aria-hidden="true">&lsaquo;</span>
            </a>
            <a class="calendar-header-action" href="/calendar?date=<?= e((string) $calendar['nextDate']) ?>" aria-label="다음 날짜">
                <span class="calendar-header-action-visual is-icon" aria-hidden="true">&rsaquo;</span>
            </a>
            <?php if (empty($calendar['isToday'])): ?>
                <a class="calendar-header-action is-text" href="/calendar?date=<?= e((string) $calendar['todayDate']) ?>" aria-label="오늘 날짜로 이동">
                    <span class="calendar-header-action-visual">오늘</span>
                </a>
            <?php endif; ?>
            <button
                type="button"
                class="calendar-header-action is-text"
                data-retrospect-preview-open
                title="<?= is_array($retrospectPreview) ? '선택한 날짜의 발행 회고 보기' : '선택한 날짜의 회고 작성으로 이동' ?>"
                data-retrospect-empty-url="/retrospect?date=<?= e((string) $calendar['date']) ?>"
            >
                <span class="calendar-header-action-visual">회고</span>
            </button>
        </div>
    </section>

    <?php if ($errorMessage !== ''): ?>
        <span data-toast-message="<?= e($errorMessage) ?>" hidden></span>
    <?php elseif (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <div class="calendar-mode-tabs" role="tablist" aria-label="캘린더 일정 종류">
        <button type="button" role="tab" aria-selected="false" data-calendar-mode="plan">계획 일정</button>
        <button type="button" role="tab" aria-selected="true" data-calendar-mode="actual">실제 일정</button>
    </div>

    <?php if (empty($calendar['planReminderItems'])): ?>
        <section class="calendar-empty-prompt" data-calendar-empty-mode="plan" hidden>
            <div>
                <strong>오늘의 계획을 하나만 적어보세요</strong>
                <p>완벽한 시간표보다 가장 중요한 한 가지면 충분해요.</p>
            </div>
            <div class="calendar-empty-prompt-actions">
                <button type="button" class="btn btn-primary" data-plan-add-open>계획 추가</button>
                <?php if (!empty($calendar['planGroups'])): ?>
                    <button type="button" class="btn btn-secondary" data-plan-settings-open>템플릿 연결</button>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (empty($calendar['actualSegments'])): ?>
        <section class="calendar-empty-prompt" data-calendar-empty-mode="actual">
            <div>
                <strong>오늘의 흐름을 기록해보세요</strong>
                <p>실행한 시간을 남기면 계획과 실제 흐름을 회고할 수 있어요.</p>
            </div>
            <button type="button" class="btn btn-primary" data-actual-event-open>실제 일정 추가</button>
        </section>
    <?php endif; ?>

    <?php if ($untimedPlanItems !== []): ?>
        <section class="calendar-untimed-plans" data-calendar-empty-mode="plan" hidden aria-labelledby="calendarUntimedPlanTitle">
            <div class="calendar-untimed-plans-heading">
                <strong id="calendarUntimedPlanTitle">시간 미정 계획</strong>
                <span><?= e((string) count($untimedPlanItems)) ?></span>
            </div>
            <ul id="calendarUntimedPlanList">
                <?php foreach ($untimedPlanItems as $index => $item): ?>
                    <li class="<?= !empty($item['isLinked']) ? 'is-linked' : '' ?>" <?= $index > 0 ? 'hidden data-untimed-plan-extra' : '' ?>>
                        <span class="calendar-importance-badge importance-<?= e(strtolower((string) $item['importance'])) ?>"><?= e((string) $item['importanceBadge']) ?></span>
                        <strong><?= e((string) $item['title']) ?></strong>
                        <button type="button" class="btn btn-secondary" data-plan-item-open
                            data-plan-item-id="<?= e((string) $item['itemId']) ?>"
                            data-plan-item-title="<?= e((string) $item['title']) ?>"
                            data-plan-item-importance="<?= e((string) $item['importance']) ?>"
                            data-plan-item-goal-id="<?= $item['goalId'] === null ? '' : e((string) $item['goalId']) ?>"
                            data-plan-item-schedule-type="unscheduled"
                            data-plan-item-start-index=""
                            data-plan-item-end-index=""
                            data-plan-item-linked="<?= !empty($item['isLinked']) ? '1' : '0' ?>"
                        >시간 설정</button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($untimedPlanItems) > 1): ?>
                <button type="button" class="calendar-untimed-toggle" data-untimed-plan-toggle aria-expanded="false" aria-controls="calendarUntimedPlanList" data-hidden-count="<?= e((string) (count($untimedPlanItems) - 1)) ?>">
                    나머지 <?= e((string) (count($untimedPlanItems) - 1)) ?>개 보기
                </button>
            <?php endif; ?>
        </section>
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

            <div class="event-layer is-background" id="planLayer">
                <?php foreach (($calendar['planSegments'] ?? []) as $segment): ?>
                    <button
                        type="button"
                        class="event plan-event"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                        data-ui-tooltip="<?= e((string) $segment['title']) ?>"
                        data-plan-item-open
                        data-plan-item-id="<?= e((string) $segment['itemId']) ?>"
                        data-plan-item-title="<?= e((string) $segment['title']) ?>"
                        data-plan-item-importance="<?= e((string) $segment['importance']) ?>"
                        data-plan-item-goal-id="<?= $segment['goalId'] === null ? '' : e((string) $segment['goalId']) ?>"
                        data-plan-item-schedule-type="timed"
                        data-plan-item-start-index="<?= e((string) $segment['startIndex']) ?>"
                        data-plan-item-end-index="<?= e((string) $segment['endIndex']) ?>"
                        data-plan-item-linked="<?= !empty($segment['isLinked']) ? '1' : '0' ?>"
                    >
                        <span class="calendar-importance-badge is-neutral"><?= e((string) ($segment['importanceBadge'] ?? 'D')) ?></span>
                        <span class="event-title"><?= e((string) $segment['title']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="event-layer actual-layer" id="actualLayer">
                <?php foreach (($calendar['actualSegments'] ?? []) as $segment): ?>
                    <?php
                    $tooltipMemo = trim((string) ($segment['memo'] ?? ''));
                    $tooltipMemo = mb_strlen($tooltipMemo) > 80
                        ? mb_substr($tooltipMemo, 0, 80) . '…'
                        : $tooltipMemo;
                    ?>
                    <div
                        class="event actual-event-form"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                    >
                        <button
                            type="button"
                            class="actual-event <?= $segment['dailyPlanItemId'] === null ? '' : 'is-linked' ?>"
                            style="--event-color: <?= e((string) $segment['tagColor']) ?>; --event-text-color: <?= e((string) $segment['tagTextColor']) ?>;"
                            data-ui-tooltip="<?= e((string) $segment['title'] . ($tooltipMemo !== '' ? "\n메모 · " . $tooltipMemo : '')) ?>"
                            data-event-open
                            data-event-id="<?= e((string) $segment['id']) ?>"
                            data-event-title="<?= e((string) $segment['title']) ?>"
                            data-event-daily-plan-item-id="<?= $segment['dailyPlanItemId'] === null ? '' : e((string) $segment['dailyPlanItemId']) ?>"
                            data-event-tag-id="<?= $segment['tagId'] === null ? '' : e((string) $segment['tagId']) ?>"
                            data-event-memo="<?= e((string) $segment['memo']) ?>"
                            data-event-start-index="<?= e((string) $segment['startIndex']) ?>"
                            data-event-end-index="<?= e((string) $segment['endIndex']) ?>"
                            data-event-schedule-type="timed"
                            data-event-energy-group-id="<?= $segment['energyGroupId'] === null ? '' : e((string) $segment['energyGroupId']) ?>"
                            data-event-energy-type="<?= e((string) ($segment['energyType'] ?? '')) ?>"
                            data-event-energy-value="<?= $segment['energyValue'] === null ? '' : e((string) $segment['energyValue']) ?>"
                            data-event-energy-override="<?= !empty($segment['energyIsOverride']) ? '1' : '0' ?>"
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

    <div class="calendar-fab <?= !empty($calendar['isToday']) && empty($calendar['actualSegments']) && empty($calendar['unscheduledEvents']) ? 'is-waiting' : '' ?>" data-calendar-fab>
        <div class="calendar-fab-actions" data-calendar-fab-actions hidden>
            <div class="calendar-fab-header">
                <strong>오늘의 흐름</strong>
                <small><?= e((string) $calendar['date']) ?></small>
            </div>
            <div class="calendar-fab-quick-actions">
                <?php if (!empty($calendar['isToday'])): ?><button type="button" data-condition-open>오늘의 컨디션</button><?php endif; ?>
                <?php if (!empty($calendar['planGroups'])): ?>
                    <button type="button" data-plan-settings-open>계획 템플릿 연결</button>
                <?php endif; ?>
                <button type="button" data-quick-memo-open>메모 작성</button>
                <button type="button" data-plan-add-open>계획 일정 추가</button>
                <button type="button" class="is-primary" data-actual-event-open>실제 일정 추가</button>
            </div>
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
            <input type="hidden" name="source_event_id" id="calendarSourceEventId">
            <div class="calendar-sheet-header">
                <strong id="eventSheetTitle">실제 일정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <div class="calendar-time-fields" data-calendar-time-fields>
                <label for="calendarStartIndex">
                    <span>시작시간</span>
                    <select class="input" name="start_index" id="calendarStartIndex" required>
                        <?php for ($index = 0; $index < 144; $index++): ?>
                            <option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label for="calendarEndIndex">
                    <span>종료시간</span>
                    <select class="input" name="end_index" id="calendarEndIndex" required>
                        <?php for ($index = 1; $index <= 144; $index++): ?>
                            <option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>

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
                                data-event-daily-plan-item-id=""
                                data-event-tag-id="<?= $event['tagId'] === null ? '' : e((string) $event['tagId']) ?>"
                                data-event-memo-target="<?= e($memoTargetId) ?>"
                                data-event-schedule-type="unscheduled"
                                data-event-energy-group-id="<?= $event['energyGroupId'] === null ? '' : e((string) $event['energyGroupId']) ?>"
                                data-event-energy-type="<?= e((string) ($event['energyType'] ?? '')) ?>"
                                data-event-energy-value="<?= $event['energyValue'] === null ? '' : e((string) $event['energyValue']) ?>"
                                data-event-energy-override="<?= !empty($event['energyIsOverride']) ? '1' : '0' ?>"
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

            <fieldset class="calendar-tag-links" data-calendar-tag-group>
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
                <?php $quickTagPrefix = 'create'; ?>
                <?php require __DIR__ . '/_quick-tag.php'; ?>
            </fieldset>

            <?php if (!empty($availablePlanOptions)): ?>
                <fieldset class="calendar-plan-links" data-create-plan-group>
                    <legend>연결할 계획 일정</legend>
                    <label class="calendar-plan-link">
                        <input type="radio" name="daily_plan_item_id" value="" checked>
                        <span>연결하지 않음</span>
                    </label>
                    <?php foreach ($availablePlanOptions as $option): ?>
                        <label class="calendar-plan-link">
                            <input type="radio" name="daily_plan_item_id" value="<?= e((string) $option['itemId']) ?>" data-plan-title="<?= e((string) $option['title']) ?>">
                            <span><?= e((string) $option['title']) ?></span>
                            <small><?= e((string) $option['timeRange']) ?></small>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <p class="calendar-form-hint">계획을 연결하면 실제 일정명이 계획 일정명으로 설정됩니다.</p>
            <?php endif; ?>

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

            <?php if (!empty($executionEnergyGroups)): ?><fieldset class="calendar-energy-links"><legend>실행력 적용</legend><label class="form-label" for="calendarEnergyGroup">실행력 그룹</label><select class="input" id="calendarEnergyGroup" name="execution_energy_group_id"><?php foreach ($executionEnergyGroups as $group): ?><option value="<?= e((string) $group['id']) ?>" <?= !empty($group['is_default']) ? 'selected' : '' ?>><?= e((string) $group['name']) ?> · <?= $group['effect_type'] === 'recover' ? '+' : '-' ?><?= e((string) $group['energy_value']) ?></option><?php endforeach; ?></select><div class="calendar-energy-override"><label><input type="radio" name="execution_energy_type" value="" checked> 그룹 기준</label><label><input type="radio" name="execution_energy_type" value="consume"> 소모</label><label><input type="radio" name="execution_energy_type" value="recover"> 회복</label><input class="input" name="execution_energy_value" type="number" min="5" max="1000" step="5" placeholder="개별 값 (선택)"></div><p class="calendar-form-hint">실제 일정 저장 시에만 현재 실행력에 반영됩니다.</p></fieldset><?php endif; ?>

            <button type="submit" class="btn btn-primary">일정 저장</button>
        </form>
    </section>

    <section class="calendar-sheet calendar-condition-sheet" data-condition-sheet hidden aria-modal="true" role="dialog" aria-labelledby="conditionSheetTitle">
        <form method="post" action="/execution-energy/morning" id="calendarConditionForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <div class="calendar-sheet-header"><strong id="conditionSheetTitle">오늘의 컨디션</strong><button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button></div>
            <p class="calendar-form-hint">오늘의 시작 실행력은 컨디션 선택을 저장할 때 한 번만 다시 계산됩니다.</p>
            <?php foreach ([
                'sleep_state' => ['수면', [1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음']],
                'fatigue_state' => ['피로', [1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음']],
                'mood_state' => ['기분', [1 => '매우 나쁨', 2 => '나쁨', 3 => '평소', 4 => '좋음', 5 => '매우 좋음']],
                'stress_state' => ['스트레스', [1 => '매우 높음', 2 => '높음', 3 => '보통', 4 => '낮음', 5 => '매우 낮음']],
            ] as $field => [$label, $options]): ?>
                <fieldset class="calendar-condition-choice"><legend><?= e($label) ?></legend><div><label><input type="radio" name="<?= e($field) ?>" value="" <?= ($executionEnergy['day'][$field] ?? null) === null ? 'checked' : '' ?>><span>선택 안 함</span></label><?php foreach ($options as $value => $option): ?><label><input type="radio" name="<?= e($field) ?>" value="<?= e((string) $value) ?>" <?= (int) ($executionEnergy['day'][$field] ?? 0) === $value ? 'checked' : '' ?>><span><?= e($option) ?></span></label><?php endforeach; ?></div></fieldset>
            <?php endforeach; ?>
            <fieldset class="calendar-condition-choice"><legend>오늘 체감 부담</legend><div><label><input type="radio" name="burden_feedback" value="" <?= empty($executionEnergy['day']['burden_feedback']) ? 'checked' : '' ?>><span>선택 안 함</span></label><?php foreach (['lower' => '예상보다 낮음', 'similar' => '예상과 비슷함', 'higher' => '예상보다 높음'] as $value => $label): ?><label><input type="radio" name="burden_feedback" value="<?= e($value) ?>" <?= ($executionEnergy['day']['burden_feedback'] ?? '') === $value ? 'checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></fieldset>
            <button class="btn btn-primary" type="submit">컨디션 반영</button>
        </form>
    </section>

    <section class="calendar-sheet" data-event-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventEditSheetTitle">
        <form method="post" action="/calendar/event/update" id="calendarEventEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarEditEventId">
            <input type="hidden" name="schedule_type" id="calendarEditScheduleType" value="timed">
            <input type="hidden" name="execution_energy_preserve" id="calendarEditEnergyPreserve" value="0">
            <div class="calendar-sheet-header">
                <strong id="eventEditSheetTitle">일정 수정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="calendarEditEventTitle">일정명</label>
            <input class="input" id="calendarEditEventTitle" name="title" type="text" maxlength="80" autocomplete="off" required>

            <fieldset class="calendar-tag-links" data-edit-tag-group data-calendar-tag-group>
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
                <?php $quickTagPrefix = 'edit'; ?>
                <?php require __DIR__ . '/_quick-tag.php'; ?>
            </fieldset>

            <?php if (!empty($calendar['planOptions'])): ?>
                <fieldset class="calendar-plan-links" data-edit-plan-group>
                    <legend>연결할 계획 일정</legend>
                    <label class="calendar-plan-link">
                        <input type="radio" name="daily_plan_item_id" value="">
                        <span>연결하지 않음</span>
                    </label>
                    <?php foreach (($calendar['planOptions'] ?? []) as $option): ?>
                        <label
                            class="calendar-plan-link <?= !empty($option['disabled']) ? 'is-disabled' : '' ?>"
                            data-plan-option="<?= e((string) $option['itemId']) ?>"
                            data-plan-disabled="<?= !empty($option['disabled']) ? '1' : '0' ?>"
                        >
                            <input
                                type="radio"
                                name="daily_plan_item_id"
                                value="<?= e((string) $option['itemId']) ?>"
                                data-plan-title="<?= e((string) $option['title']) ?>"
                                <?= !empty($option['disabled']) ? 'disabled' : '' ?>
                            >
                            <span><?= e((string) $option['title']) ?></span>
                            <small><?= e((string) $option['timeRange']) ?><?= !empty($option['disabled']) ? ' · 이미 연결됨' : '' ?></small>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <p class="calendar-form-hint">다른 계획을 연결하면 실제 일정명이 해당 계획명으로 변경됩니다.</p>
            <?php endif; ?>

            <label class="form-label" for="calendarEditMemo">메모</label>
            <textarea class="input calendar-memo-input" id="calendarEditMemo" name="memo" maxlength="500" rows="4"></textarea>

            <?php if (!empty($executionEnergyGroups)): ?><fieldset class="calendar-energy-links"><legend>실행력 적용</legend><label class="form-label" for="calendarEditEnergyGroup">실행력 그룹</label><select class="input" id="calendarEditEnergyGroup" name="execution_energy_group_id"><?php foreach ($executionEnergyGroups as $group): ?><option value="<?= e((string) $group['id']) ?>" <?= !empty($group['is_default']) ? 'selected' : '' ?>><?= e((string) $group['name']) ?> · <?= $group['effect_type'] === 'recover' ? '+' : '-' ?><?= e((string) $group['energy_value']) ?></option><?php endforeach; ?></select><div class="calendar-energy-override"><label><input type="radio" name="execution_energy_type" value="" checked> 그룹 기준</label><label><input type="radio" name="execution_energy_type" value="consume"> 소모</label><label><input type="radio" name="execution_energy_type" value="recover"> 회복</label><input class="input" id="calendarEditEnergyValue" name="execution_energy_value" type="number" min="5" max="1000" step="5" placeholder="개별 값 (선택)"></div><p class="calendar-form-hint">수정한 값으로 해당 날짜의 실행력을 다시 계산합니다.</p></fieldset><?php endif; ?>

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

    <section class="calendar-sheet" data-plan-item-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planItemSheetTitle">
        <form method="post" action="/calendar/plan-item" id="calendarPlanItemForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="schedule_type" id="calendarPlanScheduleType" value="unscheduled">
            <div class="calendar-sheet-header">
                <strong id="planItemSheetTitle">계획 일정 추가</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <?php if (!empty($calendar['blockTemplates'])): ?>
                <label class="form-label" for="calendarPlanBlockTemplate">계획 블록 템플릿</label>
                <select class="input" id="calendarPlanBlockTemplate">
                    <option value="">직접 입력</option>
                    <?php foreach ($calendar['blockTemplates'] as $template): ?>
                        <option
                            value="<?= e((string) $template['id']) ?>"
                            data-title="<?= e((string) $template['title']) ?>"
                            data-duration-index="<?= e((string) $template['durationIndex']) ?>"
                            data-importance="<?= e((string) $template['importance']) ?>"
                            data-goal-id="<?= $template['goalId'] === null ? '' : e((string) $template['goalId']) ?>"
                        ><?= e((string) $template['title']) ?> · <?= e((string) $template['durationLabel']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <label class="calendar-plan-time-toggle" for="calendarPlanTimeToggle">
                <input type="checkbox" id="calendarPlanTimeToggle">
                <span>시간 정하기</span>
            </label>
            <div class="calendar-time-fields" data-plan-create-time-fields hidden>
                <label for="calendarPlanStartIndex">
                    <span>시작시간</span>
                    <select class="input" name="start_index" id="calendarPlanStartIndex" disabled>
                        <?php for ($index = 0; $index < 144; $index++): ?>
                            <option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label for="calendarPlanEndIndex">
                    <span>종료시간</span>
                    <select class="input" name="end_index" id="calendarPlanEndIndex" disabled>
                        <?php for ($index = 1; $index <= 144; $index++): ?>
                            <option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <p class="calendar-sheet-time" id="calendarPlanSelectedTime">시간을 정하지 않은 계획은 하단의 할 일 메뉴에서 확인할 수 있습니다.</p>
            <label class="form-label" for="calendarPlanItemTitle">계획 일정명</label>
            <input class="input" id="calendarPlanItemTitle" name="title" type="text" maxlength="80" required>
            <fieldset class="importance-choice-group">
                <legend>중요도</legend>
                <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                    <label><input type="radio" name="importance" value="<?= $importance ?>" <?= $importance === 'D' ? 'checked' : '' ?>><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                <?php endforeach; ?>
            </fieldset>
            <label class="form-label" for="calendarPlanItemGoal">연결할 목표</label>
            <select class="input" id="calendarPlanItemGoal" name="goal_id">
                <option value="">목표 연결 없음</option>
                <?php foreach (($calendar['goalOptions'] ?? []) as $goal): ?>
                    <option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">계획 일정 추가</button>
        </form>
    </section>

    <section class="calendar-sheet" data-plan-item-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planItemEditSheetTitle">
        <form method="post" action="/calendar/plan-item/update" id="calendarPlanItemEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="daily_plan_item_id" id="calendarPlanEditItemId">
            <div class="calendar-sheet-header">
                <strong id="planItemEditSheetTitle">계획 일정 수정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <label class="form-label" for="calendarPlanEditScheduleType">시간 설정</label>
            <select class="input" name="schedule_type" id="calendarPlanEditScheduleType">
                <option value="unscheduled">시간 미정</option>
                <option value="timed">시간 지정</option>
            </select>
            <div class="calendar-time-fields" data-plan-edit-time-fields>
                <label for="calendarPlanEditStartIndex">
                    <span>시작시간</span>
                    <select class="input" name="start_index" id="calendarPlanEditStartIndex">
                        <?php for ($index = 0; $index < 144; $index++): ?>
                            <option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label for="calendarPlanEditEndIndex">
                    <span>종료시간</span>
                    <select class="input" name="end_index" id="calendarPlanEditEndIndex">
                        <?php for ($index = 1; $index <= 144; $index++): ?>
                            <option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <label class="form-label" for="calendarPlanEditTitle">계획 일정명</label>
            <input class="input" id="calendarPlanEditTitle" name="title" type="text" maxlength="80" required>
            <fieldset class="importance-choice-group">
                <legend>중요도</legend>
                <?php foreach (['A' => '중요·긴급', 'B' => '중요·비긴급', 'C' => '긴급·비중요', 'D' => '일반'] as $importance => $label): ?>
                    <label><input type="radio" name="importance" value="<?= $importance ?>"><span><strong><?= $importance ?></strong><small><?= e($label) ?></small></span></label>
                <?php endforeach; ?>
            </fieldset>
            <label class="form-label" for="calendarPlanEditGoal">연결할 목표</label>
            <select class="input" id="calendarPlanEditGoal" name="goal_id">
                <option value="">목표 연결 없음</option>
                <?php foreach (($calendar['goalOptions'] ?? []) as $goal): ?>
                    <option value="<?= e((string) $goal['id']) ?>"><?= e((string) ($goal['label'] ?? $goal['title'] ?? '목표')) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-group" data-plan-link-choice hidden>
                <label class="form-label" for="calendarPlanLinkAction">연결된 실제 일정 처리</label>
                <select class="input" name="link_action" id="calendarPlanLinkAction">
                    <option value="keep">계획 일정만 변경</option>
                    <option value="sync">실제 일정도 함께 변경</option>
                    <option value="detach">연결을 끊고 계획 일정만 변경</option>
                </select>
            </div>
            <div class="calendar-sheet-actions">
                <button type="submit" class="btn btn-primary">수정 저장</button>
                <button type="submit" class="btn btn-ghost" form="calendarPlanItemDeleteForm">계획 일정 삭제</button>
            </div>
        </form>
        <form method="post" action="/calendar/plan-item/delete" id="calendarPlanItemDeleteForm" hidden>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="daily_plan_item_id" id="calendarPlanDeleteItemId">
        </form>
    </section>

    <section class="calendar-sheet" data-plan-settings-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planSettingsTitle">
        <form class="calendar-plan-picker" method="post" action="/calendar/day-plan"
              data-has-daily-plan="<?= !empty($calendar['dailyPlan']) ? '1' : '0' ?>"
              data-has-linked-events="<?= !empty($calendar['hasLinkedActualEvents']) ? '1' : '0' ?>">
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

    <section class="calendar-popup" data-retrospect-preview hidden aria-modal="true" role="dialog" aria-labelledby="retrospectPreviewTitle">
        <div class="calendar-sheet-header">
            <strong id="retrospectPreviewTitle">이 날짜의 회고</strong>
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
            <a class="btn btn-secondary" href="/retrospect?date=<?= e((string) ($retrospectPreview['date'] ?? $calendar['date'])) ?>">회고 전체 보기</a>
        <?php else: ?>
            <p class="muted">이 날짜에 발행된 회고가 없습니다.</p>
            <a class="btn btn-primary" href="/retrospect?date=<?= e((string) $calendar['date']) ?>">회고 페이지로 이동</a>
        <?php endif; ?>
    </section>
</div>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
