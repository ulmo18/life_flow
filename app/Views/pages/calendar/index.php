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
$calendarMode = ($calendarMode ?? 'actual') === 'plan' ? 'plan' : 'actual';
$planActivityItems = $calendar['planActivityItems'] ?? [];
$enabledBlockTemplates = array_values(array_filter($calendar['blockTemplates'] ?? [], static fn(array $template): bool => !empty($template['isEnabled'])));
?>

<main
    class="page calendar-page <?= e((string) ($calendar['dateClass'] ?? '')) ?>"
    data-calendar-date="<?= e((string) $calendar['date']) ?>"
    data-timezone="<?= e(date_default_timezone_get()) ?>"
    data-current-index="<?= $calendar['currentIndex'] === null ? '' : e((string) $calendar['currentIndex']) ?>"
    data-calendar-mode="<?= e($calendarMode) ?>"
    data-calendar-work-mode="normal"
    data-retrospect-auto-prompt="<?= !empty($calendar['isToday']) ? '1' : '0' ?>"
    data-plan-blocks-enabled="<?= !empty($calendar['planBlocksEnabled']) ? '1' : '0' ?>"
>
    <div class="calendar-context-sticky">
    <?php $dailyDateContext = ['title' => '일정', 'path' => '/calendar', 'date' => $calendar['date'], 'prevDate' => $calendar['prevDate'], 'nextDate' => $calendar['nextDate'], 'todayDate' => $calendar['todayDate'], 'isToday' => $calendar['isToday'], 'inputId' => 'calendarContextDate', 'mode' => $calendarMode]; require __DIR__ . '/../../components/daily-date-navigation.php'; ?>
    <div class="calendar-sticky-timetable-titles"><button type="button" data-calendar-view="plan" aria-pressed="false" aria-label="계획 시간표 보기 전환">계획</button><span class="calendar-work-status" data-calendar-work-status role="status" aria-live="polite"></span><button type="button" data-calendar-view="actual" aria-pressed="false" aria-label="실제 시간표 보기 전환">실제</button></div>
    </div>

    <div class="calendar-work-mode-controls" aria-label="일정 작업 모드">
        <button type="button" data-calendar-work-mode="plan-placement" aria-pressed="false"><svg class="calendar-work-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg><span>계획 배치</span><?php if ($planActivityItems === [] && empty($calendar['planSegments'])): ?><span class="calendar-plan-start-guide" data-calendar-plan-start-guide>여기서 계획을 추가해 보세요</span><?php endif; ?></button>
        <button type="button" data-calendar-work-mode="tag-paint" aria-pressed="false"><svg class="calendar-work-icon is-tag" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h9l9 9-8 8-9-9L3 4Z"></path><circle cx="8" cy="8" r="1"></circle></svg><span>태그 칠하기</span></button>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <span data-toast-message="<?= e($errorMessage) ?>" hidden></span>
    <?php elseif (!empty($flashSuccess)): ?>
        <span data-toast-message="<?= e((string) $flashSuccess) ?>" hidden></span>
    <?php endif; ?>

    <?php if (false && empty($calendar['planReminderItems'])): ?>
        <section class="calendar-empty-prompt" data-calendar-empty-mode="plan" <?= $calendarMode !== 'plan' ? 'hidden' : '' ?>>
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

    <?php if (false && empty($calendar['actualSegments'])): ?>
        <section class="calendar-empty-prompt" data-calendar-empty-mode="actual" <?= $calendarMode !== 'actual' ? 'hidden' : '' ?>>
            <div>
                <strong>오늘의 흐름을 기록해보세요</strong>
                <p>실행한 시간을 남기면 계획과 실제 흐름을 회고할 수 있어요.</p>
            </div>
            <button type="button" class="btn btn-primary" data-actual-event-open>실제 일정 추가</button>
        </section>
    <?php endif; ?>

    <div class="calendar-workspace-shell">
    <aside class="calendar-activity-panel" aria-label="계획 배치와 태그 칠하기">
        <button type="button" class="calendar-work-mode-close" data-calendar-work-mode="normal" aria-label="작업 모드 닫기">×</button>
        <section class="calendar-plan-sources" data-calendar-plan-sources>
            <div class="calendar-plan-sources-heading"><div><strong class="calendar-plan-sources-title">계획 배치</strong><small>계획을 선택한 뒤 시간표에서 시간을 지정하세요.</small></div><a class="calendar-plan-template-link" href="/plan?view=blocks">계획 템플릿 관리 <span aria-hidden="true">↗</span></a></div>
            <div class="calendar-plan-source-list">
                <?php foreach ($planActivityItems as $item): ?>
                    <div class="calendar-plan-source" data-plan-block-source
                        data-plan-item-id="<?= e((string) $item['itemId']) ?>"
                        data-plan-created-at="<?= e((string) ($item['createdAt'] ?? '')) ?>"
                        data-plan-title="<?= e((string) $item['title']) ?>"
                        data-plan-item-importance="<?= e((string) $item['importance']) ?>"
                        data-plan-todo-status="<?= e((string) ($item['todoStatus'] ?? 'pending')) ?>"
                        data-plan-item-goal-id="<?= $item['goalId'] === null ? '' : e((string) $item['goalId']) ?>"
                        data-plan-item-schedule-type="<?= e((string) $item['scheduleType']) ?>"
                        <?= empty($calendar['planBlocksEnabled']) ? 'aria-disabled="true"' : '' ?>>
                        <span class="calendar-importance-badge importance-<?= e(strtolower((string) $item['importance'])) ?>"><?= e((string) $item['importanceBadge']) ?></span>
                        <button type="button" class="calendar-plan-source-title" data-plan-block-source-select aria-pressed="false" title="<?= e((string) $item['title']) ?>"><?= e((string) $item['title']) ?></button>
                        <button type="button" class="calendar-plan-source-edit" data-plan-source-edit aria-label="<?= e((string) $item['title']) ?> 옵션 수정">⋯</button>
                        <button type="button" class="calendar-plan-source-handle" data-plan-block-source-handle aria-label="<?= e((string) $item['title']) ?> 드래그하여 계획 시간표에 배치" <?= empty($calendar['planBlocksEnabled']) ? 'disabled' : '' ?>><span aria-hidden="true">⠿</span></button>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($enabledBlockTemplates as $template): ?>
                    <div class="calendar-plan-source" data-plan-block-source data-block-template-id="<?= e((string) $template['id']) ?>" data-template-duration="<?= e((string) $template['durationIndex']) ?>" data-plan-created-at="<?= e((string) $template['createdAt']) ?>" data-plan-title="<?= e((string) $template['title']) ?>" data-plan-item-importance="<?= e((string) $template['importance']) ?>" data-plan-item-goal-id="<?= $template['goalId'] === null ? '' : e((string) $template['goalId']) ?>">
                        <span class="calendar-importance-badge importance-<?= e(strtolower((string) $template['importance'])) ?>"><?= e((string) $template['importance']) ?></span>
                        <button type="button" class="calendar-plan-source-title" data-plan-block-source-select aria-pressed="false" aria-label="템플릿: <?= e((string) $template['title']) ?>" title="<?= e((string) $template['title']) ?>"><?= e((string) $template['title']) ?></button>
                        <button type="button" class="calendar-plan-source-edit" data-plan-source-edit aria-label="<?= e((string) $template['title']) ?> 템플릿 옵션 수정">⋯</button>
                    </div>
                <?php endforeach; ?>
                <?php if ($planActivityItems === [] && $enabledBlockTemplates === []): ?>
                    <p class="calendar-plan-source-empty">아직 계획이 없습니다. 새 계획을 추가해 바로 배치해 보세요.</p>
                <?php endif; ?>
                <button type="button" class="calendar-plan-source-add" data-calendar-todo-create-open>+ 계획 추가</button>
            </div>
            <?php if ($planActivityItems === [] && empty($calendar['planSegments']) && !empty($calendar['planGroups'])): ?>
                <form class="calendar-day-template-apply" data-calendar-day-template>
                    <label for="calendarPlacementTemplate">하루 템플릿</label>
                    <select class="input" id="calendarPlacementTemplate" name="plan_group_id" required>
                        <option value="">템플릿 선택</option>
                        <?php foreach ($calendar['planGroups'] as $planGroup): ?>
                            <option value="<?= e((string) $planGroup['id']) ?>"><?= e((string) $planGroup['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary">템플릿 적용</button>
                </form>
            <?php endif; ?>
        </section>
        <section class="calendar-tag-paint-palette" data-calendar-tag-paint-palette hidden aria-label="적용할 태그">
            <div class="calendar-tag-paint-heading"><strong>태그 칠하기</strong><small>태그를 고른 뒤 실제 일정 블록을 누르면 바로 적용됩니다.</small><a class="calendar-plan-template-link calendar-tag-management-link" href="/tags">일정 태그 관리 <span aria-hidden="true">↗</span></a></div>
            <div class="calendar-tag-paint-actions"><button type="button" data-calendar-tag-paint-mode aria-pressed="false">태그 칠하기 시작</button></div>
            <div class="calendar-tag-paint-list">
                <button type="button" data-calendar-tag-paint="" aria-pressed="false">태그 지우기</button>
                <?php foreach (($calendar['calendarTags'] ?? []) as $tag): ?>
                    <?php if ((int) ($tag['is_enabled'] ?? 1) !== 1) continue; ?>
                    <button type="button" data-calendar-tag-paint="<?= e((string) $tag['id']) ?>" data-tag-color="<?= e((string) $tag['color_hex']) ?>" style="--tag-color: <?= e((string) $tag['color_hex']) ?>;" aria-pressed="false"><span aria-hidden="true"></span><?= e((string) $tag['name']) ?></button>
                <?php endforeach; ?>
                <?php if ($availableTagPalettes !== []): ?>
                <button type="button" class="calendar-tag-add-toggle" data-paint-tag-toggle aria-label="태그 추가">+<span class="calendar-paint-add-desktop-label" aria-hidden="true"> 태그 추가</span></button>
                <?php endif; ?>
            </div>
            <?php if ($availableTagPalettes !== []): ?>
            <div class="calendar-quick-tag" data-paint-tag-create hidden>
                <label class="form-label" for="paintTagName">태그명</label>
                <input class="input" id="paintTagName" maxlength="24" data-paint-tag-name>
                <fieldset class="calendar-quick-tag-palettes"><legend>색상</legend>
                <?php foreach ($availableTagPalettes as $index => $palette): ?>
                    <label style="--tag-color: <?= e((string) $palette['color_hex']) ?>"><input type="radio" name="paint_tag_palette" value="<?= e((string) $palette['id']) ?>" data-paint-tag-color <?= $index === 0 ? 'checked' : '' ?>><span aria-label="<?= e((string) $palette['color_hex']) ?>"></span></label>
                <?php endforeach; ?>
                </fieldset>
                <p class="field-error" data-paint-tag-error hidden></p>
                <div class="calendar-quick-tag-actions">
                    <button type="button" class="btn btn-secondary" data-paint-tag-cancel>취소</button>
                    <button type="button" class="btn btn-primary" data-paint-tag-submit>추가</button>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </aside>

    <section class="calendar-workspace" aria-label="Daily plan and actual schedule">
        <div class="calendar-timetable-pair" data-calendar-timetable-pair>
    <section class="daygrid-wrap calendar-timetable-panel is-plan" aria-label="계획 시간표" data-grid-label="계획 시간표">
        <div class="calendar-mobile-split-title calendar-timetable-title">계획</div>
        <div class="daygrid" id="daygrid">
            <div class="calendar-minute-labels" aria-hidden="true"><span></span><span>00</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row <?= $hour < 12 ? 'is-am' : 'is-pm' ?><?= $calendar['currentIndex'] !== null && intdiv((int) $calendar['currentIndex'], 6) === $hour ? ' current-hour-row' : '' ?>">
                    <div class="hour-label" data-hour="<?= sprintf('%02d', $hour) ?>"><?= sprintf('%02d', $hour) ?></div>
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
                    <?php
                    $isFirstPlanSegment = (int) $segment['row'] === intdiv((int) $segment['startIndex'], 6);
                    $isLastPlanSegment = (int) $segment['row'] === intdiv((int) $segment['endIndex'] - 1, 6);
                    $planStatusLabels = ['pending' => '미정', 'in_progress' => '진행 중', 'completed' => '완료', 'postponed' => '연기', 'cancelled' => '취소', 'external' => '위임'];
                    $planStatusIcons = ['in_progress' => '·', 'completed' => 'V', 'postponed' => '→', 'cancelled' => 'X', 'external' => '○'];
                    $planStatusLabel = $planStatusLabels[$segment['manualStatus'] ?? 'pending'] ?? '미정';
                    ?>
                    <button
                        type="button"
                        class="event plan-event importance-<?= e(strtolower((string) $segment['importance'])) ?><?= (int) $segment['endIndex'] - (int) $segment['startIndex'] <= 2 ? ' is-compact-duration' : '' ?>"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                        data-ui-tooltip="<?= e((string) $segment['title']) ?>"
                        data-plan-item-open
                        data-plan-item-id="<?= e((string) $segment['itemId']) ?>"
                        data-plan-block-id="<?= $segment['blockId'] === null ? '' : e((string) $segment['blockId']) ?>"
                        data-plan-item-title="<?= e((string) $segment['title']) ?>"
                        aria-label="<?= e((string) $segment['title'] . ' · ' . $planStatusLabel) ?>"
                        data-plan-item-importance="<?= e((string) $segment['importance']) ?>"
                        data-plan-item-goal-id="<?= $segment['goalId'] === null ? '' : e((string) $segment['goalId']) ?>"
                        data-plan-item-schedule-type="timed"
                        data-plan-item-start-index="<?= e((string) $segment['startIndex']) ?>"
                        data-plan-item-end-index="<?= e((string) $segment['endIndex']) ?>"
                        data-plan-item-linked="<?= !empty($segment['isLinked']) ? '1' : '0' ?>"
                        data-plan-block-actual-count="<?= e((string) ($segment['actualCount'] ?? 0)) ?>"
                        data-plan-block-actual-minutes="<?= e((string) ($segment['actualMinutes'] ?? 0)) ?>"
                        data-plan-manual-status="<?= e((string) ($segment['manualStatus'] ?? 'pending')) ?>"
                        data-plan-follows-todo="<?= !empty($segment['followsTodo']) ? '1' : '0' ?>"
                    >
                        <span class="calendar-importance-badge is-neutral"><?= e((string) ($segment['importanceBadge'] ?? 'D')) ?></span>
                        <span class="event-title"><?= e((string) $segment['title']) ?></span>
                        <?php if ($planStatusLabel !== '미정'): ?><span class="calendar-plan-status" aria-hidden="true"><?= e($planStatusIcons[$segment['manualStatus']] ?? '') ?></span><?php endif; ?>
                        <?php if ($segment['blockId'] !== null && $isFirstPlanSegment): ?>
                            <span class="plan-block-resize-handle is-start" data-plan-block-resize="start" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php if ($segment['blockId'] !== null && $isLastPlanSegment): ?>
                            <span class="plan-block-resize-handle is-end" data-plan-block-resize="end" aria-hidden="true"></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

        </div>
    </section>
    <aside class="calendar-time-axis" aria-label="공통 시간축" aria-hidden="true">
        <div class="calendar-time-axis-title"></div>
        <div class="calendar-time-axis-grid">
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="calendar-time-axis-row <?= $hour < 12 ? 'is-am' : 'is-pm' ?><?= $calendar['currentIndex'] !== null && intdiv((int) $calendar['currentIndex'], 6) === $hour ? ' current-hour-row' : '' ?>"><?= sprintf('%02d', $hour) ?></div>
            <?php endfor; ?>
        </div>
    </aside>

    <section class="daygrid-wrap calendar-timetable-panel is-actual" aria-label="실제 시간표" data-grid-label="실제 시간표">
        <div class="calendar-mobile-split-title calendar-timetable-title">실제</div>
        <div class="daygrid actual-daygrid" id="actualDaygrid">
            <div class="calendar-minute-labels" aria-hidden="true"><span></span><span>00</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span></div>
            <?php for ($hour = 0; $hour < 24; $hour++): ?>
                <div class="daygrid-row <?= $hour < 12 ? 'is-am' : 'is-pm' ?><?= $calendar['currentIndex'] !== null && intdiv((int) $calendar['currentIndex'], 6) === $hour ? ' current-hour-row' : '' ?>">
                    <div class="hour-label" data-hour="<?= sprintf('%02d', $hour) ?>"><?= sprintf('%02d', $hour) ?></div>
                    <div class="cells">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <?php $index = ($hour * 6) + $i; ?>
                            <div class="cell <?= $calendar['currentIndex'] === $index ? 'current-cell' : '' ?>" data-index="<?= e((string) $index) ?>" data-time="<?= sprintf('%02d:%02d', $hour, $i * 10) ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endfor; ?>
            <div class="event-layer actual-layer" id="actualLayer">
                <?php foreach (($calendar['actualSegments'] ?? []) as $segment): ?>
                    <?php
                    $tooltipMemo = trim((string) ($segment['memo'] ?? ''));
                    $tooltipMemo = mb_strlen($tooltipMemo) > 80
                        ? mb_substr($tooltipMemo, 0, 80) . '…'
                        : $tooltipMemo;
                    $isFirstActualSegment = (int) $segment['row'] === intdiv((int) $segment['startIndex'], 6);
                    $isLastActualSegment = (int) $segment['row'] === intdiv((int) $segment['endIndex'] - 1, 6);
                    ?>
                    <div
                        class="event actual-event-form"
                        style="--row: <?= e((string) $segment['row']) ?>; --col: <?= e((string) $segment['col']) ?>; --span: <?= e((string) $segment['span']) ?>;"
                    >
                        <button
                            type="button"
                            class="actual-event <?= $segment['dailyPlanItemId'] === null ? '' : 'is-linked' ?><?= (int) $segment['endIndex'] - (int) $segment['startIndex'] <= 2 ? ' is-compact-duration' : '' ?>"
                            style="--event-color: <?= e((string) $segment['tagColor']) ?>; --event-text-color: <?= e((string) $segment['tagTextColor']) ?>;"
                            data-ui-tooltip="<?= e((string) $segment['title'] . ($tooltipMemo !== '' ? "\n메모 · " . $tooltipMemo : '')) ?>"
                            data-event-open
                            data-event-id="<?= e((string) $segment['id']) ?>"
                            data-event-title="<?= e((string) $segment['title']) ?>"
                            aria-label="<?= e((string) $segment['title']) ?>"
                            data-event-daily-plan-item-id="<?= $segment['dailyPlanItemId'] === null ? '' : e((string) $segment['dailyPlanItemId']) ?>"
                            data-event-daily-plan-block-id="<?= $segment['blockId'] === null ? '' : e((string) $segment['blockId']) ?>"
                            data-event-tag-id="<?= $segment['tagId'] === null ? '' : e((string) $segment['tagId']) ?>"
                            data-event-memo="<?= e((string) $segment['memo']) ?>"
                            data-event-start-index="<?= e((string) $segment['startIndex']) ?>"
                            data-event-end-index="<?= e((string) $segment['endIndex']) ?>"
                            data-event-schedule-type="timed"
                            data-event-energy-group-id="<?= $segment['energyGroupId'] === null ? '' : e((string) $segment['energyGroupId']) ?>"
                            data-event-energy-type="<?= e((string) ($segment['energyType'] ?? '')) ?>"
                            data-event-energy-value="<?= $segment['energyValue'] === null ? '' : e((string) $segment['energyValue']) ?>"
                            data-event-energy-override="<?= !empty($segment['energyIsOverride']) ? '1' : '0' ?>"
                            data-event-wellness-type="<?= e((string) ($segment['wellnessType'] ?? '')) ?>"
                            data-event-wellness-detail="<?= e((string) ($segment['wellnessDetail'] ?? '')) ?>"
                            data-event-wellness-note="<?= e((string) ($segment['wellnessNote'] ?? '')) ?>"
                            data-event-wellness-rating="<?= $segment['wellnessRating'] === null ? '' : e((string) $segment['wellnessRating']) ?>"
                        >
                            <?php if (!empty($segment['tagName'])): ?>
                                <span class="calendar-tag-dot" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span class="event-title"><?= e((string) $segment['title']) ?></span>
                            <?php if ($isFirstActualSegment): ?>
                                <span class="actual-block-resize-handle is-start" data-actual-block-resize="start" aria-hidden="true"></span>
                            <?php endif; ?>
                            <?php if ($isLastActualSegment): ?>
                                <span class="actual-block-resize-handle is-end" data-actual-block-resize="end" aria-hidden="true"></span>
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

        </div>
    </section>
    </div>
    <div class="calendar-plan-drag-guide" data-plan-drag-guide hidden aria-live="polite"></div>

    <div class="todo-layer calendar-todo-create-layer" data-calendar-todo-create-layer hidden>
        <button type="button" class="todo-overlay" data-calendar-todo-create-close aria-label="닫기"></button>
        <section class="todo-sheet ui-form-sheet" role="dialog" aria-modal="true" aria-labelledby="calendarTodoCreateTitle">
            <div class="todo-sheet-header ui-form-sheet-header"><strong id="calendarTodoCreateTitle">계획 추가</strong><button type="button" class="ui-close-button" data-calendar-todo-create-close aria-label="닫기">×</button></div>
            <form class="todo-create-form" method="post" action="/todo" data-calendar-plan-create-form><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="return_to" value="calendar"><input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>"><p class="muted" data-calendar-create-range hidden></p><?php $activityFieldPrefix = 'calendarTodoCreateInput'; $activityGoalOptions = $calendar['goalOptions'] ?? []; require __DIR__ . '/../../components/activity-create-fields.php'; ?><div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">계획 추가</button></div></form>
        </section>
    </div>

    <section class="calendar-sheet" data-plan-block-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planBlockEditSheetTitle">
        <form method="post" action="/calendar/plan-block/details" id="calendarPlanBlockEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="daily_plan_item_id" id="calendarPlanBlockItemId">
            <input type="hidden" name="daily_plan_block_id" id="calendarPlanBlockId">
            <div class="calendar-sheet-header"><strong id="planBlockEditSheetTitle">계획 수정</strong><button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button></div>
            <p class="calendar-sheet-time" data-plan-block-title></p>
            <div class="calendar-plan-inherited" data-plan-block-inherited hidden>
                <span class="calendar-plan-inherited-item"><span class="calendar-importance-badge is-neutral" data-plan-inherited-importance aria-hidden="true"></span><span data-plan-inherited-importance-label></span></span>
                <span class="calendar-plan-inherited-item"><span class="calendar-plan-inherited-status-icon" data-plan-inherited-status-icon aria-hidden="true"></span><span data-plan-inherited-status-label></span></span>
            </div>
            <div class="calendar-block-status-container" data-plan-block-status-container>
                <fieldset class="importance-choice-group calendar-block-status-choice" id="calendarPlanBlockStatus">
                    <legend>상태</legend>
                    <?php foreach (['pending' => ['—', '미정'], 'completed' => ['V', '완료'], 'cancelled' => ['X', '취소'], 'postponed' => ['→', '연기'], 'in_progress' => ['·', '진행 중'], 'external' => ['○', '위임']] as $status => [$symbol, $label]): ?>
                        <label><input type="radio" name="plan_block_status_ui" value="<?= e($status) ?>"><span><strong aria-hidden="true"><?= e($symbol) ?></strong><small><?= e($label) ?></small></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <p class="calendar-sheet-time" data-plan-block-status-note aria-live="polite"></p>
            </div>
            <fieldset class="importance-choice-group" data-plan-block-importance><legend>중요도</legend><?php foreach (['A', 'B', 'C', 'D'] as $importance): ?><label><input type="radio" name="importance" value="<?= e($importance) ?>"><span><strong><?= e($importance) ?></strong></span></label><?php endforeach; ?></fieldset>
            <div data-plan-block-actual-link-controls hidden>
                <p class="calendar-sheet-time" data-plan-block-link-count></p>
                <button type="button" class="btn btn-ghost calendar-delay-button calendar-block-detach-button" data-plan-block-detach aria-describedby="calendarPlanDetachHoldHelp"><span>실제 일정 연결 해제</span></button>
                <span class="sr-only" id="calendarPlanDetachHoldHelp">연결을 해제하려면 버튼을 1.5초 이상 누르세요. 키보드는 스페이스 또는 엔터를 길게 누르세요.</span>
            </div>
            <div class="calendar-time-fields" hidden>
                <label for="calendarPlanBlockStart"><span>시작 시간</span><select class="input" name="start_index" id="calendarPlanBlockStart"><?php for ($index = 0; $index < 144; $index++): ?><option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?></select></label>
                <label for="calendarPlanBlockEnd"><span>종료 시간</span><select class="input" name="end_index" id="calendarPlanBlockEnd"><?php for ($index = 1; $index <= 144; $index++): ?><option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?></select></label>
            </div>
            <div class="calendar-sheet-actions"><button type="button" class="btn btn-ghost calendar-delay-button" data-plan-block-delete aria-describedby="calendarPlanDeleteHoldHelp"><span>계획 삭제</span></button><span class="sr-only" id="calendarPlanDeleteHoldHelp">삭제하려면 버튼을 1.5초 이상 누르세요. 키보드는 스페이스 또는 엔터를 길게 누르세요.</span></div>
        </form>
        <form method="post" action="/calendar/plan-block/delete" id="calendarPlanBlockDeleteForm" hidden><input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>"><input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>"><input type="hidden" name="daily_plan_block_id" id="calendarPlanBlockDeleteId"></form>
    </section>

    <div class="calendar-fab <?= !empty($calendar['isToday']) && empty($calendar['actualSegments']) && empty($calendar['unscheduledEvents']) ? 'is-waiting' : '' ?>" data-calendar-fab>
        <div class="calendar-fab-actions" data-calendar-fab-actions hidden>
            <div class="calendar-fab-quick-actions">
                <?php if (!empty($calendar['isToday'])): ?><button type="button" data-condition-open>오늘의 컨디션</button><?php endif; ?>
                <?php if (!empty($calendar['planGroups'])): ?>
                    <button type="button" data-plan-settings-open>계획 템플릿 연결</button>
                <?php endif; ?>
                <button type="button" data-quick-memo-open>메모 작성</button>
                <button type="button" data-retrospect-preview-open>발행 회고 보기</button>
            </div>
        </div>
        <button type="button" class="calendar-fab-toggle" data-calendar-fab-toggle aria-label="빠른 메뉴 열기" aria-expanded="false">+</button>
    </div>
</main>

<div class="calendar-local-layer" data-calendar-layer hidden>
    <div class="calendar-local-overlay" data-calendar-close></div>

    <section class="calendar-sheet ui-form-sheet is-structured" data-event-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventSheetTitle">
        <form class="calendar-actual-form ui-form-sheet-layout" method="post" action="/calendar/event" id="calendarEventForm" data-actual-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="schedule_type" id="calendarScheduleType" value="timed">
            <input type="hidden" name="source_event_id" id="calendarSourceEventId">
            <div class="ui-form-sheet-view" data-actual-view="main">
                <div class="calendar-sheet-header ui-form-sheet-header">
                    <strong id="eventSheetTitle">실제 일정</strong>
                    <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
                </div>
                <div class="ui-form-sheet-body calendar-actual-body">
                    <div class="calendar-actual-basic-group">
                    <div class="calendar-actual-title-field">
                    <label class="form-label calendar-actual-title-label" for="calendarEventTitle">일정명</label>
                    <input class="input calendar-actual-title-input" id="calendarEventTitle" name="title" type="text" maxlength="80" autocomplete="off" placeholder="무엇을 했나요?" required>
                    </div>

                    <fieldset class="calendar-time-fields calendar-actual-time-range" data-calendar-time-fields>
                <legend>시간</legend>
                <label for="calendarStartIndex">
                    <span class="sr-only">시작시간</span>
                    <select class="input" name="start_index" id="calendarStartIndex" required>
                        <?php for ($index = 0; $index < 144; $index++): ?>
                            <option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <span class="calendar-time-arrow" aria-hidden="true">→</span>
                <label for="calendarEndIndex">
                    <span class="sr-only">종료시간</span>
                    <select class="input" name="end_index" id="calendarEndIndex" required>
                        <?php for ($index = 1; $index <= 144; $index++): ?>
                            <option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                    </fieldset>
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

            <fieldset class="calendar-tag-links" data-calendar-tag-group>
                <legend>태그</legend>
                <label class="calendar-tag-link is-empty">
                    <input type="radio" name="calendar_tag_id" value="" checked>
                    <span>없음</span>
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

            <?php if (!empty($calendar['unscheduledEvents'])): ?>
                <details class="calendar-event-source calendar-actual-disclosure ui-progressive-disclosure" data-event-source-tabs>
                    <summary><span>시간 미정 일정 불러오기</span><strong><?= e((string) count($calendar['unscheduledEvents'])) ?>개</strong></summary>
                    <div class="calendar-event-source-tabs" role="tablist" aria-label="일정 입력 방식">
                        <button type="button" role="tab" aria-selected="true" data-event-source-tab="new">새 일정 입력</button>
                        <button type="button" role="tab" aria-selected="false" data-event-source-tab="unscheduled">시간 미정 일정 (<?= e((string) count($calendar['unscheduledEvents'])) ?>)</button>
                    </div>
                    <div class="calendar-source-list" data-event-source-panel="unscheduled" hidden>
                        <?php foreach ($calendar['unscheduledEvents'] as $event): ?>
                            <label class="calendar-source-option" style="--event-color: <?= e((string) $event['tagColor']) ?>;"><input type="radio" name="source_event_choice" value="<?= e((string) $event['id']) ?>" data-source-event data-event-title="<?= e((string) $event['title']) ?>" data-event-tag-id="<?= $event['tagId'] === null ? '' : e((string) $event['tagId']) ?>"><span><?= e((string) $event['title']) ?></span><small><?= !empty($event['tagName']) ? e((string) $event['tagName']) : '태그 없음' ?></small><textarea hidden data-source-event-memo><?= e((string) $event['memo']) ?></textarea></label>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if (!empty($availablePlanOptions)): ?>
                <button type="button" class="calendar-selection-row" data-actual-view-open="plan">
                    <span>계획과 연결</span>
                    <span class="calendar-selection-summary" data-plan-link-current hidden><strong data-plan-link-summary></strong><small data-plan-link-detail></small></span>
                    <b data-selection-action>연결하기 ›</b>
                </button>
            <?php endif; ?>

            <section class="calendar-additional-records" aria-labelledby="calendarCreateAdditionalTitle">
                <h3 id="calendarCreateAdditionalTitle">함께 기록하기</h3>
                <?php if (!empty($calendar['routines']) && !empty($calendar['canEditRoutines'])): ?>
                    <button type="button" class="calendar-selection-row" data-actual-view-open="routine"><span>루틴</span><strong data-actual-summary="routine">연결 없음</strong><b>선택하기 ›</b></button>
                <?php endif; ?>
                <details class="calendar-actual-disclosure ui-progressive-disclosure" data-actual-disclosure="wellness">
                    <summary><span>생활 기록</span><strong data-actual-summary="wellness">기록 안 함</strong></summary>
                    <?php $wellnessFieldPrefix = 'calendarCreateWellness'; require __DIR__ . '/_wellness-fields.php'; ?>
                </details>
                <details class="calendar-actual-disclosure ui-progressive-disclosure" data-actual-disclosure="memo">
                    <summary><span>메모</span><strong data-actual-summary="memo">메모 없음</strong></summary>
                    <label class="sr-only" for="calendarEventMemo">메모</label>
                    <textarea class="input calendar-memo-input" id="calendarEventMemo" name="memo" maxlength="500" rows="3" placeholder="메모를 입력하세요..."></textarea>
                </details>
            <details class="calendar-actual-disclosure calendar-energy-disclosure" data-actual-disclosure="energy">
                <summary><span>실행력</span><span class="calendar-energy-summary"><strong data-energy-summary-main>−25</strong></span></summary>
                <?php $energyFieldPrefix = 'calendarCreateEnergy'; $energyDefaultType = 'consume'; require __DIR__ . '/_execution-energy-fields.php'; ?>
            </details>
            </section>

                </div>
                <div class="calendar-actual-actions ui-form-sheet-actions"><button type="submit" class="btn btn-primary">일정 저장</button></div>
            </div>
            <?php if (!empty($availablePlanOptions)): ?>
                <div class="ui-form-sheet-view calendar-selection-view" data-actual-view="plan" hidden>
                    <div class="calendar-sheet-header ui-form-sheet-header"><strong id="calendarCreatePlanSelectionTitle">계획 일정 선택</strong><button type="button" class="ui-close-button" data-actual-view-cancel aria-label="계획 선택 닫기">×</button></div>
                    <div class="ui-form-sheet-body calendar-selection-body">
                        <div class="calendar-selection-tabs" role="tablist" aria-label="계획 일정 필터"><button type="button" role="tab" aria-selected="true" data-plan-filter="recommended">추천</button><button type="button" role="tab" aria-selected="false" data-plan-filter="all">전체 계획</button></div>
                        <fieldset class="calendar-plan-links calendar-compact-selection-list" data-create-plan-group>
                            <legend class="sr-only">연결할 계획 일정 선택</legend>
                            <label class="calendar-plan-link is-clear-option" hidden><input type="radio" name="daily_plan_item_id" value="" checked data-plan-summary="연결하기"><span>연결하지 않음</span><small>계획 연결 없음</small></label>
                            <?php foreach ($availablePlanOptions as $option): ?>
                                <label class="calendar-plan-link" data-plan-filter-item>
                                    <input type="radio" name="daily_plan_item_id" value="<?= e((string) $option['itemId']) ?>" data-plan-title="<?= e((string) $option['title']) ?>" data-plan-summary="<?= e((string) $option['title']) ?>" data-plan-detail="<?= e((string) $option['timeRange']) ?>" data-plan-start-index="<?= $option['startIndex'] === null ? '' : e((string) $option['startIndex']) ?>" data-plan-end-index="<?= $option['endIndex'] === null ? '' : e((string) $option['endIndex']) ?>">
                                    <span><?= e((string) $option['title']) ?></span><small><?= e((string) $option['timeRange']) ?></small>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    </div>
                    <div class="ui-form-sheet-actions calendar-selection-actions"><button type="button" class="btn btn-ghost" data-plan-clear>연결 해제</button><button type="button" class="btn btn-primary" data-actual-view-done>완료</button></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($calendar['routines']) && !empty($calendar['canEditRoutines'])): ?>
                <div class="ui-form-sheet-view calendar-selection-view" data-actual-view="routine" hidden>
                    <div class="calendar-sheet-header ui-form-sheet-header"><strong id="calendarCreateRoutineSelectionTitle">루틴 선택</strong><button type="button" class="ui-close-button" data-actual-view-cancel aria-label="루틴 선택 닫기">×</button></div>
                    <div class="ui-form-sheet-body calendar-selection-body">
                        <?php if (count($calendar['routines']) >= 8): ?><label class="sr-only" for="calendarCreateRoutineSearch">루틴 검색</label><input class="input calendar-selection-search" id="calendarCreateRoutineSearch" type="search" placeholder="루틴 검색..." data-routine-search><?php endif; ?>
                        <fieldset class="calendar-routine-links calendar-compact-selection-list" data-create-routine-group><legend class="sr-only">일정 저장과 함께 완료할 미완료 루틴</legend><?php foreach ($calendar['routines'] as $routine): ?><label class="calendar-routine-link" data-routine-filter-item><input type="checkbox" name="routine_ids[]" value="<?= e((string) $routine['id']) ?>"><span><?= e((string) $routine['name']) ?></span><small data-routine-state-label>미완료</small></label><?php endforeach; ?></fieldset>
                    </div>
                    <div class="ui-form-sheet-actions calendar-selection-actions"><button type="button" class="btn btn-ghost" data-routine-clear>초기화</button><button type="button" class="btn btn-primary" data-actual-view-done data-routine-done>완료</button></div>
                </div>
            <?php endif; ?>
        </form>
    </section>

    <section class="calendar-sheet calendar-condition-sheet ui-form-sheet" data-condition-sheet hidden aria-modal="true" role="dialog" aria-labelledby="conditionSheetTitle">
        <form method="post" action="/execution-energy/morning" id="calendarConditionForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <div class="calendar-sheet-header ui-form-sheet-header"><strong id="conditionSheetTitle">오늘의 컨디션</strong><button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button></div>
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
            <div class="ui-form-sheet-actions"><button class="btn btn-primary" type="submit">컨디션 반영</button></div>
        </form>
    </section>

    <section class="calendar-sheet ui-form-sheet is-structured" data-event-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="eventEditSheetTitle">
        <form class="calendar-actual-form ui-form-sheet-layout" method="post" action="/calendar/event/update" id="calendarEventEditForm" data-actual-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarEditEventId">
            <input type="hidden" name="schedule_type" id="calendarEditScheduleType" value="timed">
            <div class="ui-form-sheet-view" data-actual-view="main">
                <div class="calendar-sheet-header ui-form-sheet-header">
                    <strong id="eventEditSheetTitle">일정 수정</strong>
                    <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
                </div>
                <div class="ui-form-sheet-body calendar-actual-body">
                    <div class="calendar-actual-basic-group">
                    <div class="calendar-actual-title-field">
                    <label class="form-label calendar-actual-title-label" for="calendarEditEventTitle">일정명</label>
                    <input class="input calendar-actual-title-input" id="calendarEditEventTitle" name="title" type="text" maxlength="80" autocomplete="off" placeholder="무엇을 했나요?" required>
                    </div>
                    <fieldset class="calendar-time-fields calendar-actual-time-range" data-calendar-edit-time-fields>
                        <legend>시간</legend>
                        <label for="calendarEditStartIndex"><span class="sr-only">시작시간</span><select class="input" name="start_index" id="calendarEditStartIndex" required><?php for ($index = 0; $index < 144; $index++): ?><option value="<?= $index ?>"><?= sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?></select></label>
                        <span class="calendar-time-arrow" aria-hidden="true">→</span>
                        <label for="calendarEditEndIndex"><span class="sr-only">종료시간</span><select class="input" name="end_index" id="calendarEditEndIndex" required><?php for ($index = 1; $index <= 144; $index++): ?><option value="<?= $index ?>"><?= $index === 144 ? '24:00' : sprintf('%02d:%02d', intdiv($index, 6), ($index % 6) * 10) ?></option><?php endfor; ?></select></label>
                    </fieldset>
                    </div>

            <fieldset class="calendar-tag-links" data-edit-tag-group data-calendar-tag-group>
                <legend>태그</legend>
                <label class="calendar-tag-link is-empty">
                    <input type="radio" name="calendar_tag_id" value="">
                    <span>없음</span>
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
                <button type="button" class="calendar-selection-row" data-actual-view-open="plan"><span>계획과 연결</span><span class="calendar-selection-summary" data-plan-link-current hidden><strong data-plan-link-summary></strong><small data-plan-link-detail></small></span><b data-selection-action>연결하기 ›</b></button>
            <?php endif; ?>

            <section class="calendar-additional-records" aria-labelledby="calendarEditAdditionalTitle">
                <h3 id="calendarEditAdditionalTitle">함께 기록하기</h3>
                <?php if (!empty($calendar['routines']) && !empty($calendar['canEditRoutines'])): ?>
                    <button type="button" class="calendar-selection-row" data-actual-view-open="routine"><span>루틴</span><strong data-actual-summary="routine">연결 없음</strong><b>선택하기 ›</b></button>
                <?php endif; ?>
                <details class="calendar-actual-disclosure" data-actual-disclosure="wellness"><summary><span>생활 기록</span><strong data-actual-summary="wellness">기록 안 함</strong></summary><?php $wellnessFieldPrefix = 'calendarEditWellness'; require __DIR__ . '/_wellness-fields.php'; ?></details>
                <details class="calendar-actual-disclosure" data-actual-disclosure="memo"><summary><span>메모</span><strong data-actual-summary="memo">메모 없음</strong></summary><label class="sr-only" for="calendarEditMemo">메모</label><textarea class="input calendar-memo-input" id="calendarEditMemo" name="memo" maxlength="500" rows="4" placeholder="메모를 입력하세요..."></textarea></details>
            <details class="calendar-actual-disclosure calendar-energy-disclosure" data-actual-disclosure="energy">
                <summary><span>실행력</span><span class="calendar-energy-summary"><strong data-energy-summary-main>0</strong></span></summary>
                <?php $energyFieldPrefix = 'calendarEditEnergy'; $energyDefaultType = 'none'; require __DIR__ . '/_execution-energy-fields.php'; ?>
            </details>
            </section>

                </div>
                <div class="calendar-sheet-actions calendar-actual-actions ui-form-sheet-actions">
                    <button type="submit" class="btn btn-primary">수정 저장</button>
                    <button type="button" class="btn btn-ghost calendar-delay-button" data-actual-event-delete aria-describedby="calendarActualDeleteHoldHelp"><span>일정 삭제</span></button>
                    <span class="sr-only" id="calendarActualDeleteHoldHelp">삭제하려면 버튼을 1.5초 이상 누르세요. 키보드는 스페이스 또는 엔터를 길게 누르세요.</span>
                </div>
            </div>
            <?php if (!empty($calendar['planOptions'])): ?>
                <div class="ui-form-sheet-view calendar-selection-view" data-actual-view="plan" hidden>
                    <div class="calendar-sheet-header ui-form-sheet-header"><strong id="calendarEditPlanSelectionTitle">계획 일정 선택</strong><button type="button" class="ui-close-button" data-actual-view-cancel aria-label="계획 선택 닫기">×</button></div>
                    <div class="ui-form-sheet-body calendar-selection-body">
                        <div class="calendar-selection-tabs" role="tablist" aria-label="계획 일정 필터"><button type="button" role="tab" aria-selected="true" data-plan-filter="recommended">추천</button><button type="button" role="tab" aria-selected="false" data-plan-filter="all">전체 계획</button></div>
                        <fieldset class="calendar-plan-links calendar-compact-selection-list" data-edit-plan-group><legend class="sr-only">연결할 계획 일정 선택</legend><label class="calendar-plan-link is-clear-option" hidden><input type="radio" name="daily_plan_item_id" value="" data-plan-summary="연결하기"><span>연결하지 않음</span><small>계획 연결 없음</small></label><?php foreach (($calendar['planOptions'] ?? []) as $option): ?><label class="calendar-plan-link <?= !empty($option['disabled']) ? 'is-disabled' : '' ?>" data-plan-filter-item data-plan-option="<?= e((string) $option['itemId']) ?>" data-plan-disabled="<?= !empty($option['disabled']) ? '1' : '0' ?>"><input type="radio" name="daily_plan_item_id" value="<?= e((string) $option['itemId']) ?>" data-plan-title="<?= e((string) $option['title']) ?>" data-plan-summary="<?= e((string) $option['title']) ?>" data-plan-detail="<?= e((string) $option['timeRange']) ?>" data-plan-start-index="<?= $option['startIndex'] === null ? '' : e((string) $option['startIndex']) ?>" data-plan-end-index="<?= $option['endIndex'] === null ? '' : e((string) $option['endIndex']) ?>" <?= !empty($option['disabled']) ? 'disabled' : '' ?>><span><?= e((string) $option['title']) ?></span><small><?= e((string) $option['timeRange']) ?><?= !empty($option['disabled']) ? ' · 이미 연결됨' : '' ?></small></label><?php endforeach; ?></fieldset>
                    </div>
                    <div class="ui-form-sheet-actions calendar-selection-actions"><button type="button" class="btn btn-ghost" data-plan-clear>연결 해제</button><button type="button" class="btn btn-primary" data-actual-view-done>완료</button></div>
                </div>
            <?php endif; ?>
            <?php if (!empty($calendar['routines']) && !empty($calendar['canEditRoutines'])): ?>
                <div class="ui-form-sheet-view calendar-selection-view" data-actual-view="routine" hidden>
                    <div class="calendar-sheet-header ui-form-sheet-header"><strong id="calendarEditRoutineSelectionTitle">루틴 선택</strong><button type="button" class="ui-close-button" data-actual-view-cancel aria-label="루틴 선택 닫기">×</button></div>
                    <div class="ui-form-sheet-body calendar-selection-body"><?php if (count($calendar['routines']) >= 8): ?><label class="sr-only" for="calendarEditRoutineSearch">루틴 검색</label><input class="input calendar-selection-search" id="calendarEditRoutineSearch" type="search" placeholder="루틴 검색..." data-routine-search><?php endif; ?><fieldset class="calendar-routine-links calendar-compact-selection-list" data-edit-routine-group><legend class="sr-only">수정 저장과 함께 완료할 미완료 루틴</legend><?php foreach ($calendar['routines'] as $routine): ?><label class="calendar-routine-link" data-routine-filter-item><input type="checkbox" name="routine_ids[]" value="<?= e((string) $routine['id']) ?>"><span><?= e((string) $routine['name']) ?></span><small data-routine-state-label>미완료</small></label><?php endforeach; ?></fieldset></div>
                    <div class="ui-form-sheet-actions calendar-selection-actions"><button type="button" class="btn btn-ghost" data-routine-clear>초기화</button><button type="button" class="btn btn-primary" data-actual-view-done data-routine-done>완료</button></div>
                </div>
            <?php endif; ?>
        </form>
        <form method="post" action="/calendar/event/delete" id="calendarEventDeleteForm" hidden>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="event_id" id="calendarDeleteEventId">
        </form>
    </section>

    <section class="calendar-sheet" data-plan-item-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planItemSheetTitle">
        <form method="post" action="/calendar/plan-item" id="calendarPlanItemForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="schedule_type" id="calendarPlanScheduleType" value="unscheduled">
            <input type="hidden" name="estimated_duration_minutes" id="calendarPlanEstimatedDuration">
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
            <button type="button" class="calendar-goal-create-link" data-calendar-goal-create-open>+ 새 목표 추가</button>
            <button type="submit" class="btn btn-primary">계획 일정 추가</button>
        </form>
    </section>

    <section class="calendar-sheet" data-plan-item-edit-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planItemEditSheetTitle">
        <form method="post" action="/calendar/plan-item/update" id="calendarPlanItemEditForm">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="daily_plan_item_id" id="calendarPlanEditItemId">
            <div class="calendar-sheet-header">
                <strong id="planItemEditSheetTitle">계획 일정 수정</strong>
                <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
            </div>
            <p class="calendar-sheet-time" data-plan-item-shared-note hidden>리스트의 계획을 수정해도 이미 배치한 계획 블록은 변경되지 않습니다.</p>
            <div data-plan-edit-time-controls>
                <label class="form-label" for="calendarPlanEditScheduleType">시간 설정</label>
                <select class="input" name="schedule_type" id="calendarPlanEditScheduleType">
                    <option value="unscheduled">시간 미정</option>
                    <option value="timed">시간 지정</option>
                </select>
            </div>
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
            <label class="form-label" id="calendarPlanEditTitleLabel" for="calendarPlanEditTitle">할 일명</label>
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
            <button type="button" class="calendar-goal-create-link" data-calendar-goal-create-open>+ 새 목표 추가</button>
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
                <button type="submit" class="btn btn-ghost" form="calendarPlanItemDeleteForm" data-plan-item-delete-button>계획 일정 삭제</button>
            </div>
        </form>
        <form method="post" action="/calendar/plan-item/delete" id="calendarPlanItemDeleteForm" hidden>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
            <input type="hidden" name="date" value="<?= e((string) $calendar['date']) ?>">
            <input type="hidden" name="daily_plan_item_id" id="calendarPlanDeleteItemId">
        </form>
    </section>

    <section class="calendar-sheet" data-calendar-goal-create-sheet hidden aria-modal="true" role="dialog" aria-labelledby="calendarGoalCreateTitle">
        <form method="post" action="/goal" data-calendar-goal-create-form>
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="goal_type" value="bucket">
            <input type="hidden" name="status" value="active">
            <div class="calendar-sheet-header"><strong id="calendarGoalCreateTitle">새 목표 추가</strong><button type="button" class="ui-close-button" data-calendar-goal-cancel aria-label="닫기">×</button></div>
            <label class="form-label" for="calendarGoalCreateTitleInput">목표명</label>
            <input class="input" id="calendarGoalCreateTitleInput" name="title" type="text" maxlength="80" autocomplete="off" required placeholder="연결할 목표를 입력하세요">
            <p class="calendar-sheet-time">목표를 만든 뒤 상세 화면에서 기간과 유형을 정할 수 있습니다.</p>
            <div class="calendar-sheet-actions"><button type="submit" class="btn btn-primary">목표 추가</button><button type="button" class="btn btn-ghost" data-calendar-goal-cancel>취소</button></div>
        </form>
    </section>

    <section class="calendar-sheet" data-plan-settings-sheet hidden aria-modal="true" role="dialog" aria-labelledby="planSettingsTitle">
        <form class="calendar-plan-picker" method="post" action="/calendar/day-plan"
              data-has-daily-plan="<?= !empty($calendar['dailyPlan']) ? '1' : '0' ?>"
              data-has-linked-events="<?= !empty($calendar['hasLinkedActualEvents']) ? '1' : '0' ?>">
            <input type="hidden" name="_csrf_token" value="<?= e((string) $csrfToken) ?>">
            <input type="hidden" name="calendar_mode" value="<?= e($calendarMode) ?>">
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

    <section class="calendar-popup calendar-retrospect-popup" data-retrospect-preview hidden aria-modal="true" role="dialog" aria-labelledby="retrospectPreviewTitle">
        <div class="calendar-sheet-header">
            <strong id="retrospectPreviewTitle" data-retrospect-preview-title><?= is_array($retrospectPreview) ? e((string) $retrospectPreview['dateTitle']) . ' 발행 회고' : '최근 발행 회고' ?></strong>
            <button type="button" class="ui-close-button" data-calendar-close aria-label="닫기">×</button>
        </div>
        <?php if (is_array($retrospectPreview)): ?>
            <nav class="calendar-retrospect-navigation" aria-label="발행 회고 탐색">
                <button type="button" class="btn btn-secondary" data-retrospect-preview-move="previous" data-report-date="<?= e((string) ($retrospectPreview['adjacent']['previous'] ?? '')) ?>" <?= empty($retrospectPreview['adjacent']['previous']) ? 'disabled' : '' ?>>이전 발행일</button>
                <button type="button" class="btn btn-secondary" data-retrospect-preview-move="next" data-report-date="<?= e((string) ($retrospectPreview['adjacent']['next'] ?? '')) ?>" <?= empty($retrospectPreview['adjacent']['next']) ? 'disabled' : '' ?>>다음 발행일</button>
            </nav>
            <div class="calendar-retrospect-report-scroll" data-retrospect-preview-body><?php $report = $retrospectPreview; $embeddedReport = true; require __DIR__ . '/../retrospect/report-content.php'; ?></div>
        <?php else: ?>
            <p class="muted">아직 참고할 발행 회고가 없어요. 첫 회고를 발행하면 이곳에서 바로 볼 수 있습니다.</p>
        <?php endif; ?>
    </section>
</div>

<?php if (!empty($notificationSyncPayload)): ?>
    <script type="application/json" data-notification-sync>
        <?= json_encode($notificationSyncPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
