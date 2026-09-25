(function () {
  const page = document.querySelector('.calendar-page');
  const daygrid = document.getElementById('daygrid');
  const layer = document.querySelector('[data-calendar-layer]');
  const eventSheet = document.querySelector('[data-event-sheet]');
  const eventEditSheet = document.querySelector('[data-event-edit-sheet]');
  const planItemSheet = document.querySelector('[data-plan-item-sheet]');
  const planItemEditSheet = document.querySelector('[data-plan-item-edit-sheet]');
  const planSettingsSheet = document.querySelector('[data-plan-settings-sheet]');
  const quickMemoSheet = document.querySelector('[data-quick-memo-sheet]');
  const retrospectPreview = document.querySelector('[data-retrospect-preview]');
  const eventForm = document.getElementById('calendarEventForm');
  const titleInput = document.getElementById('calendarEventTitle');
  const startInput = document.getElementById('calendarStartIndex');
  const endInput = document.getElementById('calendarEndIndex');
  const timeFields = document.querySelector('[data-calendar-time-fields]');
  const scheduleTypeInput = document.getElementById('calendarScheduleType');
  const sourceEventIdInput = document.getElementById('calendarSourceEventId');
  const createMemoInput = document.getElementById('calendarEventMemo');
  const editEventId = document.getElementById('calendarEditEventId');
  const editScheduleTypeInput = document.getElementById('calendarEditScheduleType');
  const deleteEventId = document.getElementById('calendarDeleteEventId');
  const editTitleInput = document.getElementById('calendarEditEventTitle');
  const editMemoInput = document.getElementById('calendarEditMemo');
  const planPicker = document.querySelector('.calendar-plan-picker');
  const createPlanGroup = document.querySelector('[data-create-plan-group]');
  const createRoutineGroup = document.querySelector('[data-create-routine-group]');
  const editPlanGroup = document.querySelector('[data-edit-plan-group]');
  const sourceTabs = document.querySelector('[data-event-source-tabs]');
  const unscheduledManager = document.querySelector('[data-unscheduled-manager]');
  const quickMemoInput = document.getElementById('calendarQuickMemo');
  const fab = document.querySelector('[data-calendar-fab]');
  const fabToggle = document.querySelector('[data-calendar-fab-toggle]');
  const fabActions = document.querySelector('[data-calendar-fab-actions]');
  const planLayer = document.getElementById('planLayer');
  const actualLayer = document.getElementById('actualLayer');
  const planItemTitle = document.getElementById('calendarPlanItemTitle');
  const planItemForm = document.getElementById('calendarPlanItemForm');
  const planItemGoal = document.getElementById('calendarPlanItemGoal');
  const planBlockTemplatePicker = document.getElementById('calendarPlanBlockTemplate');
  const planScheduleType = document.getElementById('calendarPlanScheduleType');
  const planTimeToggle = document.getElementById('calendarPlanTimeToggle');
  const planCreateTimeFields = document.querySelector('[data-plan-create-time-fields]');
  const planStartInput = document.getElementById('calendarPlanStartIndex');
  const planEndInput = document.getElementById('calendarPlanEndIndex');
  const planSelectedTime = document.getElementById('calendarPlanSelectedTime');
  const planEditItemId = document.getElementById('calendarPlanEditItemId');
  const planDeleteItemId = document.getElementById('calendarPlanDeleteItemId');
  const planEditTitle = document.getElementById('calendarPlanEditTitle');
  const planEditGoal = document.getElementById('calendarPlanEditGoal');
  const planEditScheduleType = document.getElementById('calendarPlanEditScheduleType');
  const planEditTimeFields = document.querySelector('[data-plan-edit-time-fields]');
  const planEditStart = document.getElementById('calendarPlanEditStartIndex');
  const planEditEnd = document.getElementById('calendarPlanEditEndIndex');
  const planLinkAction = document.getElementById('calendarPlanLinkAction');
  const planLinkChoice = document.querySelector('[data-plan-link-choice]');
  const timeGrid = window.LifeFlowTimeGrid;
  const ui = window.LifeFlowUI;
  const routineState = window.LifeFlowRoutineState;
  const seoulTimeFormatter = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Seoul',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  });

  if (!page || !daygrid || !layer || !eventSheet || !eventEditSheet || !planItemSheet || !planItemEditSheet || !planSettingsSheet || !quickMemoSheet || !retrospectPreview || !eventForm || !titleInput || !startInput || !endInput || !timeFields || !scheduleTypeInput || !sourceEventIdInput || !createMemoInput || !editEventId || !editScheduleTypeInput || !deleteEventId || !editTitleInput || !editMemoInput || !planLayer || !actualLayer || !planScheduleType || !planTimeToggle || !planCreateTimeFields || !planStartInput || !planEndInput || !planEditScheduleType || !planEditTimeFields || !planEditStart || !planEditEnd || !timeGrid) {
    return;
  }

  function getDefaultEventRange() {
    const currentIndex = Number.parseInt(page.dataset.currentIndex || '', 10);
    const start = Number.isInteger(currentIndex) && currentIndex >= 0 && currentIndex < 144
      ? Math.min(currentIndex, 138)
      : 54;

    return { start, end: Math.min(start + 6, 144) };
  }

  function rangeOverlaps(start, end, occupiedStart, occupiedEnd) {
    return start < occupiedEnd && end > occupiedStart;
  }

  function hasOccupiedRange(start, end, selector, startKey, endKey, excludeKey = '', excludeValue = '') {
    return Array.from(document.querySelectorAll(selector)).some(element => {
      if (excludeKey && element.dataset[excludeKey] === String(excludeValue)) return false;
      const occupiedStart = Number.parseInt(element.dataset[startKey] || '', 10);
      const occupiedEnd = Number.parseInt(element.dataset[endKey] || '', 10);
      return Number.isInteger(occupiedStart)
        && Number.isInteger(occupiedEnd)
        && rangeOverlaps(start, end, occupiedStart, occupiedEnd);
    });
  }

  function showCalendarToast(message) {
    window.LifeFlowToast?.show?.(message);
  }

  function getSeoulCurrentTime() {
    const values = {};
    seoulTimeFormatter.formatToParts(new Date()).forEach(part => {
      if (part.type !== 'literal') {
        values[part.type] = part.value;
      }
    });

    return {
      date: `${values.year}-${values.month}-${values.day}`,
      index: (Number(values.hour) * 6) + Math.floor(Number(values.minute) / 10),
    };
  }

  function updateCurrentTimeCell() {
    const currentTime = getSeoulCurrentTime();
    const currentCell = daygrid.querySelector('.cell.current-cell');
    if (currentCell) {
      currentCell.classList.remove('current-cell');
    }

    if (page.dataset.calendarDate !== currentTime.date) {
      page.dataset.currentIndex = '';
      return;
    }

    const nextCurrentCell = daygrid.querySelector(`.cell[data-index="${currentTime.index}"]`);
    if (nextCurrentCell) {
      nextCurrentCell.classList.add('current-cell');
      page.dataset.currentIndex = String(currentTime.index);
    }
  }

  let currentTimeCellTimer = null;
  let currentTimeCellInterval = null;

  function stopCurrentTimeCellUpdates() {
    window.clearTimeout(currentTimeCellTimer);
    window.clearInterval(currentTimeCellInterval);
    currentTimeCellTimer = null;
    currentTimeCellInterval = null;
  }

  function startCurrentTimeCellUpdates() {
    stopCurrentTimeCellUpdates();
    updateCurrentTimeCell();

    const delayUntilNextTenMinutes = 600000 - (Date.now() % 600000) + 30;
    currentTimeCellTimer = window.setTimeout(() => {
      updateCurrentTimeCell();
      currentTimeCellInterval = window.setInterval(updateCurrentTimeCell, 600000);
    }, delayUntilNextTenMinutes);
  }

  function openPanel(panel) {
    layer.hidden = false;
    eventSheet.hidden = panel !== eventSheet;
    eventEditSheet.hidden = panel !== eventEditSheet;
    planItemSheet.hidden = panel !== planItemSheet;
    planItemEditSheet.hidden = panel !== planItemEditSheet;
    planSettingsSheet.hidden = panel !== planSettingsSheet;
    quickMemoSheet.hidden = panel !== quickMemoSheet;
    retrospectPreview.hidden = panel !== retrospectPreview;
    document.body.classList.add('is-ui-open');
    closeFab();
  }

  function focusSheetInput(input) {
    try {
      input.focus({ preventScroll: true });
    } catch (error) {
      input.focus();
    }

    window.requestAnimationFrame(() => {
      input.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
  }

  function closePanels(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    if (document.activeElement instanceof HTMLElement && layer.contains(document.activeElement)) {
      document.activeElement.blur();
    }

    layer.hidden = true;
    eventSheet.hidden = true;
    eventEditSheet.hidden = true;
    planItemSheet.hidden = true;
    planItemEditSheet.hidden = true;
    planSettingsSheet.hidden = true;
    quickMemoSheet.hidden = true;
    retrospectPreview.hidden = true;
    document.querySelectorAll('[data-quick-tag-create]').forEach(editor => closeQuickTagEditor(editor));
    document.body.classList.remove('is-ui-open');
  }

  function setSourceTab(tabName) {
    if (!sourceTabs) return;

    sourceTabs.querySelectorAll('[data-event-source-tab]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.eventSourceTab === tabName ? 'true' : 'false');
    });
    const listPanel = sourceTabs.querySelector('[data-event-source-panel="unscheduled"]');
    if (listPanel) listPanel.hidden = tabName !== 'unscheduled';

    sourceEventIdInput.value = '';
    sourceTabs.querySelectorAll('[data-source-event]').forEach(input => { input.checked = false; });
    titleInput.value = '';
    createMemoInput.value = '';
    checkRadio(eventForm, 'calendar_tag_id', '');
  }

  function openEventSheet(start, end, scheduleType = 'timed', focusTime = false) {
    const isUnscheduled = scheduleType === 'unscheduled';
    const defaultRange = getDefaultEventRange();
    const selectedStart = Number.isInteger(start) ? start : defaultRange.start;
    const selectedEnd = Number.isInteger(end) ? end : defaultRange.end;
    scheduleTypeInput.value = isUnscheduled ? 'unscheduled' : 'timed';
    startInput.disabled = isUnscheduled;
    endInput.disabled = isUnscheduled;
    startInput.required = !isUnscheduled;
    endInput.required = !isUnscheduled;
    timeFields.hidden = isUnscheduled;
    startInput.value = String(selectedStart);
    endInput.value = String(selectedEnd);
    sourceEventIdInput.value = '';
    titleInput.value = '';
    createMemoInput.value = '';
    if (sourceTabs) {
      sourceTabs.hidden = isUnscheduled;
      setSourceTab('new');
    }
    if (unscheduledManager) {
      unscheduledManager.hidden = !isUnscheduled;
    }
    if (createPlanGroup) {
      createPlanGroup.hidden = isUnscheduled;
    }
    if (createRoutineGroup) {
      createRoutineGroup.hidden = isUnscheduled;
    }

    const noneOption = eventForm.querySelector('input[name="daily_plan_item_id"][value=""]');
    if (noneOption) {
      noneOption.checked = true;
    }

    const emptyTagOption = eventForm.querySelector('input[name="calendar_tag_id"][value=""]');
    if (emptyTagOption) {
      emptyTagOption.checked = true;
    }

    eventForm.querySelectorAll('input[name="routine_ids[]"]:not(:disabled)').forEach(input => {
      input.checked = false;
    });

    openPanel(eventSheet);
    focusSheetInput(focusTime && !isUnscheduled ? startInput : titleInput);
  }

  function checkRadio(form, name, value) {
    const radio = Array.from(form.querySelectorAll(`input[name="${name}"]`)).find(input => input.value === value);
    const fallback = form.querySelector(`input[name="${name}"][value=""]`);

    if (radio && !radio.disabled) {
      radio.checked = true;
      return;
    }

    if (fallback) {
      fallback.checked = true;
    }
  }

  function openEditSheet(button) {
    const form = document.getElementById('calendarEventEditForm');
    if (!form) return;

    const eventId = button.dataset.eventId || '';
    const scheduleType = button.dataset.eventScheduleType === 'unscheduled' ? 'unscheduled' : 'timed';
    editEventId.value = eventId;
    editScheduleTypeInput.value = scheduleType;
    deleteEventId.value = eventId;
    editTitleInput.value = button.dataset.eventTitle || '';
    editMemoInput.value = button.dataset.eventMemoTarget
      ? (document.getElementById(button.dataset.eventMemoTarget)?.value || '')
      : (button.dataset.eventMemo || '');
    checkRadio(form, 'calendar_tag_id', button.dataset.eventTagId || '');
    checkRadio(form, 'daily_plan_item_id', button.dataset.eventDailyPlanItemId || '');
    form.dataset.originalPlanId = button.dataset.eventDailyPlanItemId || '';

    const currentTagId = button.dataset.eventTagId || '';
    form.querySelectorAll('[data-tag-option]').forEach(label => {
      const input = label.querySelector('input[name="calendar_tag_id"]');
      if (!input) return;
      const shouldDisable = label.dataset.tagDisabled === '1' && input.value !== currentTagId;
      input.disabled = shouldDisable;
      label.classList.toggle('is-disabled', shouldDisable);
    });

    const currentPlanId = button.dataset.eventDailyPlanItemId || '';
    form.querySelectorAll('[data-plan-option]').forEach(label => {
      const input = label.querySelector('input[name="daily_plan_item_id"]');
      if (!input) return;

      const shouldDisable = label.dataset.planDisabled === '1' && input.value !== currentPlanId;
      input.disabled = shouldDisable;
      label.classList.toggle('is-disabled', shouldDisable);

      if (input.value === currentPlanId) {
        input.disabled = false;
        input.checked = true;
        label.classList.remove('is-disabled');
      }
    });

    if (editPlanGroup) {
      const hasSelectablePlan = currentPlanId !== '' || Array.from(
        editPlanGroup.querySelectorAll('input[name="daily_plan_item_id"]:not([value=""])')
      ).some(input => !input.disabled);
      editPlanGroup.hidden = scheduleType === 'unscheduled' || !hasSelectablePlan;
    }

    openPanel(eventEditSheet);
    focusSheetInput(editTitleInput);
  }

  let calendarMode = 'actual';

  function setCalendarMode(mode) {
    calendarMode = mode === 'plan' ? 'plan' : 'actual';
    document.querySelectorAll('[data-calendar-mode]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.calendarMode === calendarMode ? 'true' : 'false');
    });
    planLayer.hidden = false;
    planLayer.classList.toggle('is-background', calendarMode === 'actual');
    actualLayer.hidden = calendarMode !== 'actual';
    document.querySelectorAll('[data-calendar-empty-mode]').forEach(prompt => {
      prompt.hidden = prompt.dataset.calendarEmptyMode !== calendarMode;
    });
  }

  function setPlanCreateScheduleType(scheduleType) {
    const isTimed = scheduleType === 'timed';
    planScheduleType.value = isTimed ? 'timed' : 'unscheduled';
    planTimeToggle.checked = isTimed;
    planCreateTimeFields.hidden = !isTimed;
    planStartInput.disabled = !isTimed;
    planEndInput.disabled = !isTimed;
    planStartInput.required = isTimed;
    planEndInput.required = isTimed;
    planSelectedTime.textContent = isTimed
      ? '선택한 시간에 계획 블록으로 표시됩니다.'
      : '시간을 정하지 않은 계획은 + 메뉴의 오늘의 계획에서 확인할 수 있습니다.';
  }

  async function applySelectedPlanBlockTemplate() {
    if (!planBlockTemplatePicker || !planItemForm) return;
    const option = planBlockTemplatePicker.selectedOptions[0];
    if (!option || option.value === '') return;

    planItemTitle.value = option.dataset.title || '';
    checkRadio(planItemForm, 'importance', option.dataset.importance || 'D');
    if (planItemGoal) planItemGoal.value = option.dataset.goalId || '';
    if (planScheduleType.value !== 'timed') return;

    const start = Number.parseInt(planStartInput.value, 10);
    const duration = Number.parseInt(option.dataset.durationIndex || '', 10);
    if (!Number.isInteger(start) || !Number.isInteger(duration) || duration < 1) return;

    const desiredEnd = start + duration;
    if (desiredEnd <= 143) {
      planEndInput.value = String(desiredEnd);
      return;
    }

    if (start >= 143) {
      showCalendarToast('23:50 이후에는 오늘 일정으로 배치할 수 없습니다.');
      planBlockTemplatePicker.value = '';
      return;
    }

    const confirmed = ui && typeof ui.confirm === 'function'
      ? await ui.confirm({
          title: '오늘 범위를 넘는 계획',
          message: '이 계획은 오늘 범위를 넘어갑니다. 이후 구간을 제외하고 23:50까지만 등록할까요?',
          confirmText: '오늘까지만 등록',
          cancelText: '취소',
        })
      : confirm('오늘 이후 구간을 제외하고 23:50까지만 등록할까요?');
    if (confirmed) {
      planEndInput.value = '143';
    } else {
      planBlockTemplatePicker.value = '';
    }
  }

  function openPlanItemSheet(start, end, scheduleType = '') {
    const isTimed = scheduleType === 'timed'
      || (scheduleType !== 'unscheduled' && Number.isInteger(start) && Number.isInteger(end));
    const selectedStart = Number.isInteger(start) ? start : 54;
    const selectedEnd = Number.isInteger(end) ? end : 60;
    planStartInput.value = String(selectedStart);
    planEndInput.value = String(selectedEnd);
    setPlanCreateScheduleType(isTimed ? 'timed' : 'unscheduled');
    planItemTitle.value = '';
    if (planBlockTemplatePicker) planBlockTemplatePicker.value = '';
    if (planItemForm) checkRadio(planItemForm, 'importance', 'D');
    if (planItemGoal) planItemGoal.value = '';
    openPanel(planItemSheet);
    focusSheetInput(planItemTitle);
  }

  function openPlanItemEditSheet(button) {
    const scheduleType = button.dataset.planItemScheduleType === 'unscheduled' ? 'unscheduled' : 'timed';
    const parsedStart = Number.parseInt(button.dataset.planItemStartIndex || '', 10);
    const parsedEnd = Number.parseInt(button.dataset.planItemEndIndex || '', 10);
    const start = Number.isInteger(parsedStart) ? parsedStart : 54;
    const end = Number.isInteger(parsedEnd) ? parsedEnd : 60;
    planEditItemId.value = button.dataset.planItemId || '';
    planDeleteItemId.value = button.dataset.planItemId || '';
    planEditTitle.value = button.dataset.planItemTitle || '';
    checkRadio(document.getElementById('calendarPlanItemEditForm'), 'importance', button.dataset.planItemImportance || 'D');
    planEditGoal.value = button.dataset.planItemGoalId || '';
    planEditScheduleType.value = scheduleType;
    planEditStart.value = String(start);
    planEditEnd.value = String(end);
    setPlanEditScheduleType(scheduleType);
    planLinkAction.value = 'keep';
    planItemEditSheet.dataset.linked = button.dataset.planItemLinked || '0';
    planLinkChoice.hidden = planItemEditSheet.dataset.linked !== '1';
    openPanel(planItemEditSheet);
    focusSheetInput(planEditTitle);
  }

  function setPlanEditScheduleType(scheduleType) {
    const isTimed = scheduleType === 'timed';
    planEditScheduleType.value = isTimed ? 'timed' : 'unscheduled';
    planEditTimeFields.hidden = !isTimed;
    planEditStart.disabled = !isTimed;
    planEditEnd.disabled = !isTimed;
    planEditStart.required = isTimed;
    planEditEnd.required = isTimed;
  }

  timeGrid.create({
    grid: daygrid,
    ignoreSelector: '[data-event-open], [data-plan-item-open], button, input, select, textarea, a',
    onSelect({ start, end }) {
      if (calendarMode === 'plan') {
        if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) {
          showCalendarToast('이미 등록된 계획 일정과 시간이 겹칩니다.');
          return;
        }
        openPlanItemSheet(start, end);
      } else {
        if (hasOccupiedRange(start, end, '[data-event-open][data-event-start-index]', 'eventStartIndex', 'eventEndIndex')) {
          showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
          return;
        }
        openEventSheet(start, end);
      }
    },
  });

  document.querySelectorAll('[data-calendar-mode]').forEach(button => {
    button.addEventListener('click', () => setCalendarMode(button.dataset.calendarMode));
  });
  document.querySelectorAll('[data-plan-item-open]').forEach(button => {
    button.addEventListener('click', () => openPlanItemEditSheet(button));
  });
  document.querySelectorAll('[data-plan-add-open]').forEach(button => {
    button.addEventListener('click', () => openPlanItemSheet());
  });
  planTimeToggle.addEventListener('change', async () => {
    setPlanCreateScheduleType(planTimeToggle.checked ? 'timed' : 'unscheduled');
    if (planTimeToggle.checked) await applySelectedPlanBlockTemplate();
  });
  planBlockTemplatePicker?.addEventListener('change', applySelectedPlanBlockTemplate);
  planStartInput.addEventListener('change', async () => {
    if (planBlockTemplatePicker?.value) {
      await applySelectedPlanBlockTemplate();
      return;
    }
    const start = Number.parseInt(planStartInput.value, 10);
    const end = Number.parseInt(planEndInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      planEndInput.value = String(Math.min(start + 6, 144));
    }
  });
  planEndInput.addEventListener('change', () => {
    const start = Number.parseInt(planStartInput.value, 10);
    const end = Number.parseInt(planEndInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      planEndInput.value = String(Math.min(start + 1, 144));
      showCalendarToast('종료시간은 시작시간보다 늦어야 합니다.');
    }
  });
  document.getElementById('calendarPlanItemForm')?.addEventListener('submit', event => {
    if (planScheduleType.value !== 'timed') return;
    const start = Number.parseInt(planStartInput.value, 10);
    const end = Number.parseInt(planEndInput.value, 10);
    if (!Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end > 144 || end <= start) {
      event.preventDefault();
      showCalendarToast('시작시간보다 늦은 종료시간을 선택해주세요.');
      return;
    }
    if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) {
      event.preventDefault();
      showCalendarToast('이미 등록된 계획 일정과 시간이 겹칩니다.');
    }
  });
  planEditScheduleType.addEventListener('change', () => {
    setPlanEditScheduleType(planEditScheduleType.value);
  });
  planEditStart.addEventListener('change', () => {
    const start = Number.parseInt(planEditStart.value, 10);
    const end = Number.parseInt(planEditEnd.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      planEditEnd.value = String(Math.min(start + 6, 144));
    }
  });
  planEditEnd.addEventListener('change', () => {
    const start = Number.parseInt(planEditStart.value, 10);
    const end = Number.parseInt(planEditEnd.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      planEditEnd.value = String(Math.min(start + 1, 144));
      showCalendarToast('종료시간은 시작시간보다 늦어야 합니다.');
    }
  });
  document.getElementById('calendarPlanItemEditForm')?.addEventListener('submit', event => {
    if (planEditScheduleType.value !== 'timed') return;
    const start = Number.parseInt(planEditStart.value, 10);
    const end = Number.parseInt(planEditEnd.value, 10);
    if (!Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end > 144 || end <= start) {
      event.preventDefault();
      showCalendarToast('시작시간보다 늦은 종료시간을 선택해주세요.');
      return;
    }
    if (hasOccupiedRange(
      start,
      end,
      '[data-plan-item-open]',
      'planItemStartIndex',
      'planItemEndIndex',
      'planItemId',
      planEditItemId.value
    )) {
      event.preventDefault();
      showCalendarToast('이미 등록된 계획 일정과 시간이 겹칩니다.');
    }
  });

  eventForm.querySelectorAll('input[name="daily_plan_item_id"][data-plan-title]').forEach(input => {
    input.addEventListener('change', () => {
      if (input.checked) titleInput.value = input.dataset.planTitle || '';
    });
  });

  document.getElementById('calendarEventEditForm')?.querySelectorAll('input[name="daily_plan_item_id"][data-plan-title]').forEach(input => {
    input.addEventListener('change', () => {
      const form = input.form;
      if (input.checked && input.value !== (form?.dataset.originalPlanId || '')) {
        editTitleInput.value = input.dataset.planTitle || '';
      }
    });
  });
  setCalendarMode('actual');

  document.getElementById('calendarPlanItemDeleteForm')?.addEventListener('submit', async event => {
    const deleteForm = event.currentTarget;
    event.preventDefault();
    const linked = planItemEditSheet.dataset.linked === '1';
    const confirmed = ui && typeof ui.confirm === 'function'
      ? await ui.confirm({
          title: '계획 일정 삭제',
          message: linked
            ? '계획 일정을 삭제하면 연결은 끊어지지만 실제 일정은 유지됩니다.'
            : '이 계획 일정을 삭제할까요?',
          confirmText: '삭제',
          cancelText: '취소',
        })
      : confirm(linked ? '연결을 끊고 계획 일정만 삭제할까요?' : '계획 일정을 삭제할까요?');
    if (confirmed) {
      if (typeof window.fetch !== 'function') {
        deleteForm.submit();
        return;
      }

      try {
        const response = await fetch(deleteForm.action, {
          method: 'POST',
          body: new FormData(deleteForm),
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || '계획 일정을 삭제하지 못했습니다.');
        }

        closePanels();
        window.location.assign(payload.redirect || `/calendar?date=${encodeURIComponent(page.dataset.calendarDate)}`);
      } catch (error) {
        window.LifeFlowToast?.show?.(error.message || '계획 일정을 삭제하지 못했습니다.');
      }
    }
  });


  startCurrentTimeCellUpdates();

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      startCurrentTimeCellUpdates();
    }
  });

  window.addEventListener('pagehide', stopCurrentTimeCellUpdates);

  document.querySelectorAll('[data-calendar-close]').forEach(button => {
    button.addEventListener('click', closePanels);
  });

  document.querySelectorAll('[data-retrospect-preview-open]').forEach(button => {
    button.addEventListener('click', () => {
      if (!button.disabled) {
        openPanel(retrospectPreview);
      }
    });
  });

  document.querySelectorAll('[data-plan-settings-open]').forEach(button => {
    button.addEventListener('click', () => openPanel(planSettingsSheet));
  });

  document.querySelectorAll('[data-actual-event-open]').forEach(button => {
    button.addEventListener('click', () => openEventSheet(null, null, 'timed', true));
  });

  document.querySelectorAll('[data-calendar-header-action]').forEach(button => {
    button.addEventListener('click', () => {
      if (button.dataset.calendarHeaderAction === 'plan') {
        openPlanItemSheet();
        return;
      }

      if (button.dataset.calendarHeaderAction !== 'actual') return;
      const start = Number.parseInt(button.dataset.startIndex || '', 10);
      const end = Number.parseInt(button.dataset.endIndex || '', 10);
      const hasRange = Number.isInteger(start) && Number.isInteger(end);
      openEventSheet(hasRange ? start : null, hasRange ? end : null, 'timed', !hasRange);

      const planItemId = button.dataset.planItemId || '';
      if (planItemId !== '') {
        checkRadio(eventForm, 'daily_plan_item_id', planItemId);
        titleInput.value = button.dataset.planTitle || '';
        focusSheetInput(titleInput);
      }
    });
  });

  document.querySelectorAll('[data-quick-memo-open]').forEach(button => {
    button.addEventListener('click', () => {
      if (quickMemoInput) quickMemoInput.value = '';
      openPanel(quickMemoSheet);
      if (quickMemoInput) focusSheetInput(quickMemoInput);
    });
  });

  sourceTabs?.querySelectorAll('[data-event-source-tab]').forEach(button => {
    button.addEventListener('click', () => setSourceTab(button.dataset.eventSourceTab || 'new'));
  });

  sourceTabs?.querySelectorAll('[data-source-event]').forEach(input => {
    input.addEventListener('change', () => {
      if (!input.checked) return;
      sourceEventIdInput.value = input.value;
      titleInput.value = input.dataset.eventTitle || '';
      createMemoInput.value = input.closest('.calendar-source-option')?.querySelector('[data-source-event-memo]')?.value || '';
      checkRadio(eventForm, 'calendar_tag_id', input.dataset.eventTagId || '');
      focusSheetInput(titleInput);
    });
  });

  function closeQuickTagEditor(editor) {
    const group = editor.closest('[data-calendar-tag-group]');
    const toggle = group?.querySelector('[data-quick-tag-toggle]');
    const error = editor.querySelector('[data-quick-tag-error]');
    editor.hidden = true;
    if (toggle) toggle.hidden = editor.querySelector('[data-quick-tag-palette]') === null;
    if (error) {
      error.hidden = true;
      error.textContent = '';
    }
  }

  function appendTagOption(group, tag, selected) {
    if (!(group instanceof HTMLElement) || !tag?.id) return;

    const label = document.createElement('label');
    label.className = 'calendar-tag-link';
    label.dataset.tagOption = String(tag.id);
    label.dataset.tagDisabled = '0';
    label.style.setProperty('--tag-color', tag.colorHex || 'var(--color-primary)');

    const input = document.createElement('input');
    input.type = 'radio';
    input.name = 'calendar_tag_id';
    input.value = String(tag.id);
    input.checked = selected;

    const swatch = document.createElement('span');
    swatch.className = 'calendar-tag-swatch';
    swatch.setAttribute('aria-hidden', 'true');

    const name = document.createElement('span');
    name.textContent = tag.name || '';

    label.append(input, swatch, name);
    group.insertBefore(label, group.querySelector('[data-quick-tag-toggle]'));
  }

  function removeUsedQuickTagPalette(paletteId) {
    document.querySelectorAll('[data-quick-tag-create]').forEach(editor => {
      editor.querySelectorAll('[data-quick-tag-palette]').forEach(input => {
        if (input.value === String(paletteId)) {
          input.closest('label')?.remove();
        }
      });

      const remaining = editor.querySelectorAll('[data-quick-tag-palette]');
      if (remaining.length > 0) {
        remaining[0].checked = true;
        return;
      }

      editor.hidden = true;
      const group = editor.closest('[data-calendar-tag-group]');
      const toggle = group?.querySelector('[data-quick-tag-toggle]');
      if (toggle) toggle.hidden = true;
    });
  }

  document.querySelectorAll('[data-quick-tag-toggle]').forEach(toggle => {
    toggle.addEventListener('click', () => {
      const editor = toggle.parentElement?.querySelector('[data-quick-tag-create]');
      if (!(editor instanceof HTMLElement)) return;
      toggle.hidden = true;
      editor.hidden = false;
      const nameInput = editor.querySelector('[data-quick-tag-name]');
      if (nameInput instanceof HTMLInputElement) focusSheetInput(nameInput);
    });
  });

  document.querySelectorAll('[data-quick-tag-cancel]').forEach(button => {
    button.addEventListener('click', () => {
      const editor = button.closest('[data-quick-tag-create]');
      if (editor instanceof HTMLElement) closeQuickTagEditor(editor);
    });
  });

  document.querySelectorAll('[data-quick-tag-submit]').forEach(button => {
    button.addEventListener('click', async () => {
      const editor = button.closest('[data-quick-tag-create]');
      const group = editor?.closest('[data-calendar-tag-group]');
      const form = editor?.closest('form');
      const nameInput = editor?.querySelector('[data-quick-tag-name]');
      const paletteInput = editor?.querySelector('[data-quick-tag-palette]:checked');
      const error = editor?.querySelector('[data-quick-tag-error]');
      const csrfInput = form?.querySelector('input[name="_csrf_token"]');
      if (!(editor instanceof HTMLElement) || !(group instanceof HTMLElement)
        || !(nameInput instanceof HTMLInputElement) || !(paletteInput instanceof HTMLInputElement)
        || !(csrfInput instanceof HTMLInputElement)) return;

      const name = nameInput.value.trim();
      if (name === '') {
        if (error) {
          error.textContent = '태그명을 입력해주세요.';
          error.hidden = false;
        }
        focusSheetInput(nameInput);
        return;
      }

      const body = new FormData();
      body.append('_csrf_token', csrfInput.value);
      body.append('name', name);
      body.append('palette_id', paletteInput.value);
      button.disabled = true;

      try {
        const response = await fetch('/tags', {
          method: 'POST',
          body,
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok || !payload.tag) {
          throw new Error(payload.message || '태그를 추가하지 못했습니다.');
        }

        document.querySelectorAll('[data-calendar-tag-group]').forEach(tagGroup => {
          appendTagOption(tagGroup, payload.tag, tagGroup === group);
        });
        removeUsedQuickTagPalette(paletteInput.value);
        nameInput.value = '';
        closeQuickTagEditor(editor);
        showCalendarToast(payload.message || '태그가 추가되었습니다.');
      } catch (requestError) {
        if (error) {
          error.textContent = requestError.message || '태그를 추가하지 못했습니다.';
          error.hidden = false;
        }
      } finally {
        button.disabled = false;
      }
    });
  });

  startInput.addEventListener('change', () => {
    const start = Number.parseInt(startInput.value, 10);
    const end = Number.parseInt(endInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      endInput.value = String(Math.min(start + 6, 144));
    }
  });

  endInput.addEventListener('change', () => {
    const start = Number.parseInt(startInput.value, 10);
    const end = Number.parseInt(endInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      endInput.value = String(Math.min(start + 1, 144));
      showCalendarToast('종료시간은 시작시간보다 늦어야 합니다.');
    }
  });

  eventForm.addEventListener('submit', event => {
    if (scheduleTypeInput.value === 'timed') {
      const start = Number.parseInt(startInput.value, 10);
      const end = Number.parseInt(endInput.value, 10);
      if (!Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end > 144 || end <= start) {
        event.preventDefault();
        showCalendarToast('시작시간보다 늦은 종료시간을 선택해주세요.');
        return;
      }
      if (hasOccupiedRange(start, end, '[data-event-open][data-event-start-index]', 'eventStartIndex', 'eventEndIndex')) {
        event.preventDefault();
        showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
        return;
      }
    }

    const sourceTab = sourceTabs?.querySelector('[data-event-source-tab][aria-selected="true"]')?.dataset.eventSourceTab;
    if (scheduleTypeInput.value === 'timed' && sourceTab === 'unscheduled' && !sourceEventIdInput.value) {
      event.preventDefault();
      showCalendarToast('시간을 배치할 일정을 선택해주세요.');
    }
  });

  function closeFab() {
    if (!fab || !fabToggle || !fabActions) return;
    fab.classList.remove('is-open');
    fabToggle.textContent = '+';
    fabToggle.setAttribute('aria-expanded', 'false');
    fabToggle.setAttribute('aria-label', '빠른 메뉴 열기');
    fabActions.hidden = true;
  }

  fabToggle?.addEventListener('click', event => {
    event.stopPropagation();
    const willOpen = !fab?.classList.contains('is-open');
    if (!willOpen) {
      closeFab();
      return;
    }
    fab?.classList.add('is-open');
    fabActions.hidden = false;
    fabToggle.textContent = '×';
    fabToggle.setAttribute('aria-expanded', 'true');
    fabToggle.setAttribute('aria-label', '빠른 메뉴 닫기');
  });

  document.addEventListener('click', event => {
    if (fab?.classList.contains('is-open') && !fab.contains(event.target)) {
      closeFab();
    }
  });

  document.querySelectorAll('[data-event-open]').forEach(button => {
    button.addEventListener('click', () => {
      openEditSheet(button);
    });
  });

  document.querySelectorAll('[data-calendar-date-picker]').forEach(input => {
    input.addEventListener('change', () => {
      if (/^\d{4}-\d{2}-\d{2}$/.test(input.value)) {
        window.location.href = `/calendar?date=${encodeURIComponent(input.value)}`;
      }
    });
  });

  document.querySelectorAll('[data-calendar-date-picker-open]').forEach(button => {
    button.addEventListener('click', () => {
      const input = button.closest('.calendar-date-picker-control')?.querySelector('[data-calendar-date-picker]');
      if (!input) return;
      try {
        if (typeof input.showPicker === 'function') {
          input.showPicker();
          return;
        }
      } catch (error) {
        // Fall through to the click-based picker.
      }
      if (typeof input.click === 'function') {
        input.click();
      }
    });
  });

  document.querySelectorAll('[data-calendar-date-swipe]').forEach(header => {
    let startX = null;
    let startY = null;
    let activePointerId = null;
    let suppressClick = false;

    function navigateFromGesture(endX, endY) {
      if (startX === null || startY === null) {
        startX = null;
        startY = null;
        return;
      }

      const deltaX = endX - startX;
      const deltaY = endY - startY;
      startX = null;
      startY = null;

      if (Math.abs(deltaX) < 48 || Math.abs(deltaX) <= Math.abs(deltaY)) return;

      const targetDate = deltaX > 0 ? header.dataset.prevDate : header.dataset.nextDate;
      if (!/^\d{4}-\d{2}-\d{2}$/.test(targetDate || '')) return;

      suppressClick = true;
      window.setTimeout(() => { suppressClick = false; }, 500);
      window.location.href = `/calendar?date=${encodeURIComponent(targetDate)}`;
    }

    if (window.PointerEvent) {
      header.addEventListener('pointerdown', event => {
        if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return;
        activePointerId = event.pointerId;
        startX = event.clientX;
        startY = event.clientY;
      });

      header.addEventListener('pointerup', event => {
        if (event.pointerId !== activePointerId) return;
        activePointerId = null;
        navigateFromGesture(event.clientX, event.clientY);
      });

      header.addEventListener('pointercancel', event => {
        if (event.pointerId !== activePointerId) return;
        activePointerId = null;
        startX = null;
        startY = null;
      });
    } else {
      header.addEventListener('touchstart', event => {
        if (event.touches.length !== 1) return;
        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
      }, { passive: true });

      header.addEventListener('touchend', event => {
        if (event.changedTouches.length !== 1) {
          startX = null;
          startY = null;
          return;
        }
        navigateFromGesture(event.changedTouches[0].clientX, event.changedTouches[0].clientY);
      }, { passive: true });

      header.addEventListener('touchcancel', () => {
        startX = null;
        startY = null;
      }, { passive: true });
    }

    header.addEventListener('dragstart', event => event.preventDefault());

    header.addEventListener('click', event => {
      if (!suppressClick) return;
      event.preventDefault();
      event.stopPropagation();
      suppressClick = false;
    }, true);
  });

  if (planPicker) {
    const select = planPicker.querySelector('select[name="plan_group_id"]');
    let allowSubmit = false;

    select?.addEventListener('change', async () => {
      if (allowSubmit) {
        return;
      }

      if (planPicker.dataset.hasDailyPlan === '1') {
        const hasLinks = planPicker.dataset.hasLinkedEvents === '1';
        const confirmed = ui && typeof ui.confirm === 'function'
          ? await ui.confirm({
              title: '계획 일정 변경',
              message: hasLinks
                ? '새 템플릿을 연결하면 현재 오늘의 계획이 교체되고 기존 실제 일정의 연결도 해제됩니다. 계속할까요?'
                : '새 템플릿을 연결하면 현재 오늘의 계획이 교체됩니다. 계속할까요?',
              confirmText: '변경',
              cancelText: '취소',
            })
          : confirm(hasLinks
              ? '현재 오늘의 계획을 교체하고 실제 일정 연결을 해제할까요?'
              : '현재 오늘의 계획을 새 템플릿으로 교체할까요?');

        if (!confirmed) {
          window.location.reload();
          return;
        }
      }

      allowSubmit = true;
      planPicker.requestSubmit();
    });
  }

  function applyRoutineState(button, state) {
    if (routineState) {
      routineState.apply(button, state);
    }
  }

  async function submitRoutineToggle(form) {
    const response = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || '루틴 상태 변경에 실패했습니다.');
    }

    document.querySelectorAll(
      `[data-calendar-routine-state-button][data-routine-id="${payload.routineId}"][data-routine-date="${payload.date}"]`
    ).forEach(button => {
      applyRoutineState(button, payload.state || '');
    });

    if (window.LifeFlowToast && typeof window.LifeFlowToast.show === 'function') {
      window.LifeFlowToast.show(payload.message || '루틴 상태를 변경했습니다.');
    }
  }

  document.querySelectorAll('[data-calendar-routine-toggle-form]').forEach(form => {
    form.addEventListener('submit', event => {
      event.preventDefault();

      submitRoutineToggle(form).catch(() => {
        form.submit();
      });
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !layer.hidden) {
      closePanels();
    }
  });

  if (window.visualViewport) {
    let viewportResizeTimer = null;

    window.visualViewport.addEventListener('resize', () => {
      window.clearTimeout(viewportResizeTimer);
      viewportResizeTimer = window.setTimeout(() => {
        const activeElement = document.activeElement;
        if (!(activeElement instanceof HTMLElement) || !layer.contains(activeElement)) {
          return;
        }

        const viewportBottom = window.visualViewport.offsetTop + window.visualViewport.height;
        const inputBottom = activeElement.getBoundingClientRect().bottom;
        if (inputBottom > viewportBottom - 16) {
          activeElement.scrollIntoView({ block: 'center', inline: 'nearest' });
        }
      }, 80);
    });
  }
})();
