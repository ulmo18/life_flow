(function () {
  const page = document.querySelector('.calendar-page');
  const daygrid = document.getElementById('daygrid');
  const actualDaygrid = document.getElementById('actualDaygrid');
  const layer = document.querySelector('[data-calendar-layer]');
  const eventSheet = document.querySelector('[data-event-sheet]');
  const eventEditSheet = document.querySelector('[data-event-edit-sheet]');
  const planItemSheet = document.querySelector('[data-plan-item-sheet]');
  const planItemEditSheet = document.querySelector('[data-plan-item-edit-sheet]');
  const planBlockEditSheet = document.querySelector('[data-plan-block-edit-sheet]');
  const goalCreateSheet = document.querySelector('[data-calendar-goal-create-sheet]');
  const planSettingsSheet = document.querySelector('[data-plan-settings-sheet]');
  const quickMemoSheet = document.querySelector('[data-quick-memo-sheet]');
  const conditionSheet = document.querySelector('[data-condition-sheet]');
  const retrospectPreview = document.querySelector('[data-retrospect-preview]');
  const retrospectPreviewTitle = document.querySelector('[data-retrospect-preview-title]');
  const retrospectPreviewBody = document.querySelector('[data-retrospect-preview-body]');
  const retrospectPreviewMoves = Array.from(document.querySelectorAll('[data-retrospect-preview-move]'));
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
  const editStartInput = document.getElementById('calendarEditStartIndex');
  const editEndInput = document.getElementById('calendarEditEndIndex');
  const editTimeFields = document.querySelector('[data-calendar-edit-time-fields]');
  const createWellnessFields = eventForm?.querySelector('[data-wellness-fields]');
  const editWellnessFields = document.getElementById('calendarEventEditForm')?.querySelector('[data-wellness-fields]');
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
  const planDragGuide = document.querySelector('[data-plan-drag-guide]');
  const calendarTodoCreateLayer = document.querySelector('[data-calendar-todo-create-layer]');
  const planItemTitle = document.getElementById('calendarPlanItemTitle');
  const planItemForm = document.getElementById('calendarPlanItemForm');
  const planItemGoal = document.getElementById('calendarPlanItemGoal');
  const planBlockTemplatePicker = document.getElementById('calendarPlanBlockTemplate');
  const planScheduleType = document.getElementById('calendarPlanScheduleType');
  const planEstimatedDuration = document.getElementById('calendarPlanEstimatedDuration');
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
  const planEditTimeControls = document.querySelector('[data-plan-edit-time-controls]');
  const planItemSharedNote = document.querySelector('[data-plan-item-shared-note]');
  const goalCreateForm = document.querySelector('[data-calendar-goal-create-form]');
  const goalCreateTitleInput = document.getElementById('calendarGoalCreateTitleInput');
  const ui = window.LifeFlowUI;
  const routineState = window.LifeFlowRoutineState;
  const userTimeFormatter = new Intl.DateTimeFormat('en-CA', {
    timeZone: page?.dataset.timezone || 'Asia/Seoul',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  });

  if (!page || !daygrid || !layer || !eventSheet || !eventEditSheet || !planItemSheet || !planItemEditSheet || !planSettingsSheet || !quickMemoSheet || !conditionSheet || !retrospectPreview || !eventForm || !titleInput || !startInput || !endInput || !timeFields || !scheduleTypeInput || !sourceEventIdInput || !createMemoInput || !editEventId || !editScheduleTypeInput || !deleteEventId || !editTitleInput || !editMemoInput || !editStartInput || !editEndInput || !editTimeFields || !planLayer || !actualLayer || !planScheduleType || !planTimeToggle || !planCreateTimeFields || !planStartInput || !planEndInput || !planEditScheduleType || !planEditTimeFields || !planEditStart || !planEditEnd) {
    return;
  }

  if (planBlockEditSheet && !layer.contains(planBlockEditSheet)) {
    layer.append(planBlockEditSheet);
  }
  const energyControllers = new WeakMap();
  document.querySelectorAll('[data-actual-form]').forEach(form => {
    const controller = window.LifeFlowCalendarEnergy?.create(form);
    if (controller) energyControllers.set(form, controller);
  });

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

  function getUserCurrentTime() {
    const values = {};
    userTimeFormatter.formatToParts(new Date()).forEach(part => {
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
    const currentTime = getUserCurrentTime();
    document.querySelectorAll('.daygrid .cell.current-cell').forEach(cell => cell.classList.remove('current-cell'));
    document.querySelectorAll('.daygrid .daygrid-row.current-hour-row').forEach(row => row.classList.remove('current-hour-row'));

    if (page.dataset.calendarDate !== currentTime.date) {
      page.dataset.currentIndex = '';
      return;
    }

    document.querySelectorAll(`.daygrid .cell[data-index="${currentTime.index}"]`).forEach(cell => cell.classList.add('current-cell'));
    const currentHour = Math.floor(currentTime.index / 6);
    document.querySelectorAll('.daygrid').forEach(grid => {
      grid.querySelectorAll('.daygrid-row')[currentHour]?.classList.add('current-hour-row');
    });
    page.dataset.currentIndex = String(currentTime.index);
  }

  let currentTimeFocusInFlight = false;

  function cancelCurrentTimeFocus() {
    if (!currentTimeFocusInFlight) return;
    currentTimeFocusInFlight = false;
    // Cancels a pending smooth current-time scroll at the user's current
    // position before a drag, so placement never scrolls underneath it.
    window.scrollTo({ top: window.scrollY, behavior: 'auto' });
  }

  function focusCurrentTimeOnce(force = false) {
    if (page.dataset.currentTimeFocused === '1' || window.location.hash || (!force && window.scrollY > 24)) return;

    const navigation = window.performance?.getEntriesByType?.('navigation')?.[0];
    if (navigation?.type === 'back_forward') return;

    const currentTime = getUserCurrentTime();
    if (page.dataset.calendarDate !== currentTime.date) return;

    const currentCell = daygrid.querySelector('.cell.current-cell');
    if (!currentCell) return;

    page.dataset.currentTimeFocused = '1';
    currentTimeFocusInFlight = true;
    const targetTop = currentCell.getBoundingClientRect().top + window.scrollY - (window.innerHeight * .42);
    window.scrollTo({
      top: Math.max(0, targetTop),
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
    });
    window.setTimeout(() => { currentTimeFocusInFlight = false; }, 700);
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
    syncCalendarViewport();
    layer.hidden = false;
    eventSheet.hidden = panel !== eventSheet;
    eventEditSheet.hidden = panel !== eventEditSheet;
    planItemSheet.hidden = panel !== planItemSheet;
    planItemEditSheet.hidden = panel !== planItemEditSheet;
    if (planBlockEditSheet) planBlockEditSheet.hidden = panel !== planBlockEditSheet;
    if (goalCreateSheet) goalCreateSheet.hidden = panel !== goalCreateSheet;
    planSettingsSheet.hidden = panel !== planSettingsSheet;
    quickMemoSheet.hidden = panel !== quickMemoSheet;
    conditionSheet.hidden = panel !== conditionSheet;
    retrospectPreview.hidden = panel !== retrospectPreview;
    document.body.classList.add('is-ui-open');
    closeFab();
  }

  function syncCalendarViewport() {
    const viewport = window.visualViewport;
    const height = viewport?.height || window.innerHeight;
    const keyboardOffset = viewport ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop) : 0;
    layer.style.setProperty('--calendar-visual-height', `${height}px`);
    layer.style.setProperty('--calendar-keyboard-offset', `${keyboardOffset}px`);
    page.style.setProperty('--calendar-visual-height', `${height}px`);
    page.style.setProperty('--calendar-keyboard-offset', `${keyboardOffset}px`);
  }

  function focusSheetInput(input) {
    if (!input) return;
    const scrollX = window.scrollX;
    const scrollY = window.scrollY;
    try {
      input.focus({ preventScroll: true });
    } catch (error) {
      input.focus();
      window.scrollTo(scrollX, scrollY);
    }

    // `focus({preventScroll:true})` preserves the timetable position.  Calling
    // scrollIntoView here used to scroll the document back to the calendar
    // header whenever a create/edit sheet was opened (including after delete).
    window.requestAnimationFrame(() => window.scrollTo(scrollX, scrollY));
  }

  function closePanels(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    cancelCalendarDelayHold();

    if (document.activeElement instanceof HTMLElement && layer.contains(document.activeElement)) {
      document.activeElement.blur();
    }

    layer.hidden = true;
    eventSheet.hidden = true;
    eventEditSheet.hidden = true;
    planItemSheet.hidden = true;
    planItemEditSheet.hidden = true;
    if (planBlockEditSheet) planBlockEditSheet.hidden = true;
    if (goalCreateSheet) goalCreateSheet.hidden = true;
    planSettingsSheet.hidden = true;
    quickMemoSheet.hidden = true;
    conditionSheet.hidden = true;
    retrospectPreview.hidden = true;
    document.querySelectorAll('[data-actual-form]').forEach(form => showActualFormView(form, 'main'));
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
      sourceTabs.open = false;
      setSourceTab('new');
    }
    if (unscheduledManager) {
      unscheduledManager.hidden = !isUnscheduled;
    }
    if (createPlanGroup) {
      const picker = createPlanGroup.closest('[data-actual-view="plan"]');
      const summaryRow = eventForm.querySelector('[data-actual-view-open="plan"]');
      if (picker) picker.hidden = isUnscheduled;
      if (summaryRow) summaryRow.hidden = isUnscheduled;
      createPlanGroup.hidden = false;
    }
    if (createRoutineGroup) {
      createRoutineGroup.hidden = isUnscheduled;
    }

    const noneOption = eventForm.querySelector('input[name="daily_plan_item_id"][value=""]');
    if (noneOption) {
      noneOption.checked = true;
    }
    syncPlanLinkSummary(createPlanGroup);

    const emptyTagOption = eventForm.querySelector('input[name="calendar_tag_id"][value=""]');
    if (emptyTagOption) {
      emptyTagOption.checked = true;
    }

    eventForm.querySelectorAll('input[name="routine_ids[]"]:not(:disabled)').forEach(input => {
      input.checked = false;
    });
    energyControllers.get(eventForm)?.reset();
    setWellnessFields(createWellnessFields, {}, !isUnscheduled);
    updatePlanRecommendations(eventForm, selectedStart, selectedEnd);
    resetActualDisclosures(eventForm);
    showActualFormView(eventForm, 'main');

    openPanel(eventSheet);
    focusSheetInput(titleInput);
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

  function syncPlanLinkSummary(group) {
    if (!group) return;
    const form = group.closest('form');
    const summary = form?.querySelector('[data-plan-link-summary]');
    const detail = form?.querySelector('[data-plan-link-detail]');
    const action = form?.querySelector('[data-selection-action]');
    const current = form?.querySelector('[data-plan-link-current]');
    const selected = group.querySelector('input[name="daily_plan_item_id"]:checked');
    if (!summary) return;
    const hasPlan = Boolean(selected?.value);
    summary.textContent = hasPlan
      ? (selected.dataset.planSummary || selected.closest('.calendar-plan-link')?.querySelector('span')?.textContent?.trim() || '연결된 계획')
      : '';
    if (current) current.hidden = !hasPlan;
    if (detail) detail.textContent = hasPlan ? (selected.dataset.planDetail || '') : '';
    if (action) action.textContent = hasPlan ? '변경 ›' : '연결하기 ›';
  }

  function syncActualFormSummary(form) {
    if (!(form instanceof HTMLFormElement)) return;

    const selectedRoutines = form.querySelectorAll('input[name="routine_ids[]"]:checked:not(:disabled)').length;
    const routineSummary = form.querySelector('[data-actual-summary="routine"]');
    if (routineSummary) routineSummary.textContent = selectedRoutines > 0 ? `${selectedRoutines}개 선택됨` : '연결 없음';
    form.querySelectorAll('[data-routine-state-label]').forEach(label => {
      const input = label.closest('.calendar-routine-link')?.querySelector('input[name="routine_ids[]"]');
      label.textContent = input?.checked ? '저장 시 완료' : '미완료';
    });
    form.querySelectorAll('[data-routine-done]').forEach(button => {
      button.textContent = `완료${selectedRoutines > 0 ? ` (${selectedRoutines})` : ''}`;
    });

    const wellness = form.querySelector('input[name="wellness_type"]:checked');
    const wellnessSummary = form.querySelector('[data-actual-summary="wellness"]');
    if (wellnessSummary) wellnessSummary.textContent = wellness?.dataset.summaryLabel || '기록 안 함';

    const memo = form.querySelector('textarea[name="memo"]');
    const memoSummary = form.querySelector('[data-actual-summary="memo"]');
    if (memoSummary) {
      const content = memo?.value.trim() || '';
      memoSummary.textContent = content === '' ? '메모 없음' : (content.length > 24 ? `${content.slice(0, 24)}…` : content);
    }

    energyControllers.get(form)?.render();
  }

  function resetActualDisclosures(form, openFilled = false) {
    if (!(form instanceof HTMLFormElement)) return;
    form.querySelectorAll('[data-actual-disclosure]').forEach(disclosure => {
      const type = disclosure.dataset.actualDisclosure;
      const hasValue = type === 'memo'
        ? Boolean(form.querySelector('textarea[name="memo"]')?.value.trim())
        : type === 'wellness' && Boolean(form.querySelector('input[name="wellness_type"]:checked')?.value);
      disclosure.open = openFilled && hasValue;
    });
    syncActualFormSummary(form);
  }

  function updatePlanRecommendations(form, start, end) {
    if (!(form instanceof HTMLFormElement)) return;
    form.querySelectorAll('.calendar-plan-link').forEach(label => {
      label.classList.remove('is-recommended');
      delete label.dataset.recommendationRank;
    });
    if (!Number.isInteger(start) || !Number.isInteger(end)) return;
    const candidates = Array.from(form.querySelectorAll('input[name="daily_plan_item_id"][data-plan-start-index]')).map(input => {
      const planStart = Number.parseInt(input.dataset.planStartIndex || '', 10);
      const planEnd = Number.parseInt(input.dataset.planEndIndex || '', 10);
      if (!Number.isInteger(planStart) || !Number.isInteger(planEnd) || input.disabled) return null;
      const distance = rangeOverlaps(start, end, planStart, planEnd)
        ? 0
        : Math.min(Math.abs(start - planEnd), Math.abs(end - planStart));
      return { input, planStart, distance };
    }).filter(Boolean);
    candidates.sort((left, right) => left.distance - right.distance || left.planStart - right.planStart);
    candidates.slice(0, 3).forEach((candidate, index) => {
      const row = candidate.input.closest('.calendar-plan-link');
      row?.classList.add('is-recommended');
      if (row) row.dataset.recommendationRank = String(index + 1);
    });
  }

  const actualViewSnapshots = new WeakMap();

  function showActualFormView(form, viewName) {
    if (!(form instanceof HTMLFormElement)) return;
    form.querySelectorAll('[data-actual-view]').forEach(view => {
      view.hidden = view.dataset.actualView !== viewName;
    });
    form.dataset.activeActualView = viewName;
    const activeView = form.querySelector(`[data-actual-view="${viewName}"]`);
    const activeTitle = activeView?.querySelector('.ui-form-sheet-header strong[id]');
    if (activeTitle?.id) form.closest('[role="dialog"]')?.setAttribute('aria-labelledby', activeTitle.id);
    const body = activeView?.querySelector('.ui-form-sheet-body');
    if (body instanceof HTMLElement) body.scrollTop = 0;
  }

  function snapshotActualSelection(form, viewName) {
    const selectedPlan = form.querySelector('input[name="daily_plan_item_id"]:checked');
    actualViewSnapshots.set(form, {
      viewName,
      title: form.querySelector('input[name="title"]')?.value || '',
      planId: selectedPlan?.value || '',
      routines: Array.from(form.querySelectorAll('input[name="routine_ids[]"]:checked')).map(input => input.value),
    });
  }

  function restoreActualSelection(form) {
    const snapshot = actualViewSnapshots.get(form);
    if (!snapshot) return;
    const title = form.querySelector('input[name="title"]');
    if (title) title.value = snapshot.title;
    checkRadio(form, 'daily_plan_item_id', snapshot.planId);
    form.querySelectorAll('input[name="routine_ids[]"]').forEach(input => {
      input.checked = snapshot.routines.includes(input.value);
    });
    syncPlanLinkSummary(form.querySelector('[data-create-plan-group], [data-edit-plan-group]'));
    syncActualFormSummary(form);
  }

  function setPlanFilter(view, filter) {
    if (!(view instanceof HTMLElement)) return;
    view.querySelectorAll('[data-plan-filter]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.planFilter === filter ? 'true' : 'false');
    });
    view.querySelectorAll('[data-plan-filter-item]').forEach(row => {
      const input = row.querySelector('input[name="daily_plan_item_id"]');
      row.hidden = filter === 'recommended' && !row.classList.contains('is-recommended') && !input?.checked;
      row.style.order = filter === 'recommended' ? (row.dataset.recommendationRank || '99') : '';
    });
    const list = view.querySelector('.calendar-compact-selection-list');
    const hasVisible = Array.from(view.querySelectorAll('[data-plan-filter-item]')).some(row => !row.hidden);
    list?.classList.toggle('has-no-recommendations', filter === 'recommended' && !hasVisible);
  }

  function setWellnessFields(container, values = {}, enabled = true) {
    if (!container) return;
    const type = enabled ? (values.type || '') : '';
    container.hidden = !enabled;
    container.querySelectorAll('input, textarea').forEach(control => { control.disabled = !enabled; });
    const typeInput = Array.from(container.querySelectorAll('input[name="wellness_type"]'))
      .find(input => input.value === type) || container.querySelector('input[name="wellness_type"][value=""]');
    if (typeInput) typeInput.checked = true;
    const details = container.querySelector('[data-wellness-details]');
    if (details) details.hidden = type === '';
    const detail = container.querySelector('input[name="wellness_detail"]');
    const note = container.querySelector('textarea[name="wellness_note"]');
    if (detail) detail.value = values.detail || '';
    if (note) note.value = values.note || '';
    container.querySelectorAll('input[name="wellness_rating"]').forEach(input => {
      input.checked = input.value === String(values.rating || '');
    });
  }

  [createWellnessFields, editWellnessFields].forEach(container => {
    container?.querySelectorAll('input[name="wellness_type"]').forEach(input => {
      input.addEventListener('change', () => {
        const details = container.querySelector('[data-wellness-details]');
        if (details) details.hidden = input.value === '';
        syncActualFormSummary(container.closest('form'));
      });
    });
  });

  function openEditSheet(button) {
    const form = document.getElementById('calendarEventEditForm');
    if (!form) return;

    const eventId = button.dataset.eventId || '';
    const scheduleType = button.dataset.eventScheduleType === 'unscheduled' ? 'unscheduled' : 'timed';
    editEventId.value = eventId;
    editScheduleTypeInput.value = scheduleType;
    deleteEventId.value = eventId;
    const editStart = Number.parseInt(button.dataset.eventStartIndex || '', 10);
    const editEnd = Number.parseInt(button.dataset.eventEndIndex || '', 10);
    const isTimed = scheduleType === 'timed' && Number.isInteger(editStart) && Number.isInteger(editEnd);
    editTimeFields.hidden = !isTimed;
    editStartInput.disabled = !isTimed;
    editEndInput.disabled = !isTimed;
    editStartInput.required = isTimed;
    editEndInput.required = isTimed;
    if (isTimed) {
      editStartInput.value = String(editStart);
      editEndInput.value = String(editEnd);
    }
    editTitleInput.value = button.dataset.eventTitle || '';
    editMemoInput.value = button.dataset.eventMemoTarget
      ? (document.getElementById(button.dataset.eventMemoTarget)?.value || '')
      : (button.dataset.eventMemo || '');
    checkRadio(form, 'calendar_tag_id', button.dataset.eventTagId || '');
    checkRadio(form, 'daily_plan_item_id', button.dataset.eventDailyPlanItemId || '');
    syncPlanLinkSummary(editPlanGroup);
    form.dataset.originalPlanId = button.dataset.eventDailyPlanItemId || '';
    const savedEnergyType = ['consume', 'recover'].includes(button.dataset.eventEnergyType || '')
      ? button.dataset.eventEnergyType
      : 'none';
    const savedEnergyValue = button.dataset.eventEnergyValue || '';
    energyControllers.get(form)?.reset(savedEnergyType === 'none' ? '' : savedEnergyType, savedEnergyValue, savedEnergyType === 'none');
    setWellnessFields(editWellnessFields, {
      type: button.dataset.eventWellnessType || '',
      detail: button.dataset.eventWellnessDetail || '',
      note: button.dataset.eventWellnessNote || '',
      rating: button.dataset.eventWellnessRating || '',
    }, scheduleType === 'timed');
    form.querySelectorAll('input[name="routine_ids[]"]:not(:disabled)').forEach(input => { input.checked = false; });
    updatePlanRecommendations(form, editStart, editEnd);

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
      const picker = editPlanGroup.closest('[data-actual-view="plan"]');
      const summaryRow = form.querySelector('[data-actual-view-open="plan"]');
      if (picker) picker.hidden = scheduleType === 'unscheduled' || !hasSelectablePlan;
      if (summaryRow) summaryRow.hidden = scheduleType === 'unscheduled' || !hasSelectablePlan;
      editPlanGroup.hidden = false;
      syncPlanLinkSummary(editPlanGroup);
    }

    resetActualDisclosures(form, true);
    showActualFormView(form, 'main');

    openPanel(eventEditSheet);
    focusSheetInput(eventEditSheet.querySelector('[data-calendar-close]'));
  }

  let calendarMode = 'actual';
  let calendarWorkMode = 'normal';
  let calendarView = 'both';
  let selectedPaintTagId = null;
  const tagPaintPalette = document.querySelector('[data-calendar-tag-paint-palette]');
  const activityPanel = document.querySelector('.calendar-activity-panel');
  const workStatus = document.querySelector('[data-calendar-work-status]');

  function updateWorkStatus(interaction = '') {
    const status = interaction || calendarWorkMode;
    const labels = { 'plan-placement':'계획 배치', 'tag-paint':'태그 칠하기', 'actual-selection':'실제 일정 입력', 'plan-selection':'계획 일정 입력', 'resize':'시간 조절', 'move':'일정 이동', 'copy':'계획 → 실제' };
    if (!workStatus) return;
    workStatus.dataset.status = status;
    workStatus.textContent = labels[status] || '';
  }

  function setCalendarView(view) {
    calendarView = ['plan', 'actual'].includes(view) ? view : 'both';
    page.dataset.calendarView = calendarView;
    document.querySelectorAll('[data-calendar-view]').forEach(button => {
      button.setAttribute('aria-pressed', String(button.dataset.calendarView === calendarView));
    });
  }

  document.querySelectorAll('[data-calendar-view]').forEach(button => {
    button.addEventListener('click', () => {
      const nextView = calendarView === button.dataset.calendarView ? 'both' : button.dataset.calendarView;
      if (calendarWorkMode !== 'normal') setCalendarWorkMode('normal', true);
      setCalendarView(nextView);
    });
  });

  function syncMobileWorkBar() {
    const isOpen = calendarWorkMode !== 'normal' && window.matchMedia('(max-width: 767px)').matches;
    page.style.setProperty('--calendar-drawer-height', `${isOpen && activityPanel ? activityPanel.offsetHeight : 0}px`);
  }

  function setCalendarWorkMode(mode, updateHistory = false) {
    const next = ['normal', 'plan-placement', 'tag-paint'].includes(mode) ? mode : 'normal';
    calendarWorkMode = next;
    page.dataset.calendarWorkMode = calendarWorkMode;
    setCalendarView(next === 'plan-placement' ? 'plan' : next === 'tag-paint' ? 'actual' : 'both');
    updateWorkStatus();
    const compactWorkspace = window.matchMedia('(max-width: 1439px)').matches;
    document.querySelectorAll('button[data-calendar-work-mode]').forEach(button => {
      button.setAttribute('aria-pressed', button.dataset.calendarWorkMode === calendarWorkMode ? 'true' : 'false');
    });
    if (activityPanel) {
      const isMobile = compactWorkspace;
      const hasOpenWorkSheet = calendarWorkMode === 'plan-placement' || calendarWorkMode === 'tag-paint';
      activityPanel.setAttribute('aria-hidden', isMobile && !hasOpenWorkSheet ? 'true' : 'false');
      activityPanel.inert = isMobile && !hasOpenWorkSheet;
    }
    if (tagPaintPalette) tagPaintPalette.hidden = compactWorkspace && calendarWorkMode !== 'tag-paint';
    window.requestAnimationFrame(syncMobileWorkBar);
    if (updateHistory && compactWorkspace) {
      const url = new URL(window.location.href);
      if (calendarWorkMode === 'normal') url.searchParams.delete('workspace');
      else url.searchParams.set('workspace', calendarWorkMode);
      window.history.pushState({ calendarWorkMode }, '', `${url.pathname}${url.search}${url.hash}`);
    }
    if (calendarWorkMode !== 'plan-placement' && sourceDrag) {
      sourceDrag.source.classList.remove('is-dragging');
      sourceDrag = null;
      hidePlanDragGuide();
    }
    if (calendarWorkMode !== 'plan-placement' && planPlacement) {
      planPlacement = null;
      clearPlanPlacementPreview();
      hidePlanDragGuide();
    }
    if (calendarWorkMode !== 'plan-placement') {
      selectedPlanBlockSource = null;
      delete page.dataset.planSourceSelected;
      document.querySelectorAll('[data-plan-block-source]').forEach(source => {
        source.classList.remove('is-selected');
        source.querySelector('[data-plan-block-source-select]')?.setAttribute('aria-pressed', 'false');
      });
    }
  }

  async function paintActualEventTag(button) {
    if (selectedPaintTagId === null) {
      showCalendarToast('먼저 적용할 태그 또는 태그 지우기를 선택하세요.');
      return;
    }
    const eventId = button.dataset.eventId || '';
    const tagId = selectedPaintTagId;
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!eventId || !token) return;
    const body = new FormData();
    body.set('_csrf_token', token);
    body.set('event_id', eventId);
    body.set('calendar_tag_id', tagId);
    button.setAttribute('aria-busy', 'true');
    try {
      const response = await fetch('/calendar/event/tag', {
        method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.message || '태그를 적용하지 못했습니다.');
      const eventButtons = Array.from(document.querySelectorAll('[data-event-open]')).filter(
        candidate => candidate.dataset.eventId === eventId
      );
      const option = Array.from(document.querySelectorAll('[data-calendar-tag-paint]')).find(
        candidate => candidate.dataset.calendarTagPaint === tagId
      );
      const color = option?.dataset.tagColor || '';
      eventButtons.forEach(eventButton => {
        eventButton.dataset.eventTagId = tagId;
        if (color) {
        const hex = color.replace('#', '');
        const red = Number.parseInt(hex.slice(0, 2), 16);
        const green = Number.parseInt(hex.slice(2, 4), 16);
        const blue = Number.parseInt(hex.slice(4, 6), 16);
        const luminance = ((red * 299) + (green * 587) + (blue * 114)) / 1000;
          eventButton.style.setProperty('--event-color', color);
          eventButton.style.setProperty('--event-text-color', luminance > 165 ? '#1a1b3a' : '#ffffff');
        } else {
          eventButton.style.removeProperty('--event-color');
          eventButton.style.removeProperty('--event-text-color');
        }
        eventButton.classList.toggle('is-tag-cleared', tagId === '');
        const dot = eventButton.querySelector('.calendar-tag-dot');
        if (tagId === '') dot?.remove();
        else if (!dot) {
          const nextDot = document.createElement('span');
          nextDot.className = 'calendar-tag-dot';
          nextDot.setAttribute('aria-hidden', 'true');
          eventButton.prepend(nextDot);
        }
      });
    } catch (error) {
      showCalendarToast(error.message || '태그를 적용하지 못했습니다.');
    } finally {
      button.removeAttribute('aria-busy');
    }
  }

  function isMobileActualMode() {
    // Calendar mode remains a persistence hint; it no longer disables the
    // Plan grid now that mobile work modes own interaction state.
    return false;
  }

  function setCalendarMode(mode, syncUrl = false, focusCurrentTime = false) {
    calendarMode = mode === 'plan' ? 'plan' : 'actual';
    page.dataset.calendarMode = calendarMode;
    const dateMode = page.querySelector('.daily-date-picker input[name="mode"]');
    if (dateMode) dateMode.value = calendarMode;
    document.querySelectorAll('[data-calendar-mode]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.calendarMode === calendarMode ? 'true' : 'false');
    });
    document.querySelectorAll('input[name="calendar_mode"]').forEach(input => { input.value = calendarMode; });
    document.querySelectorAll('a[href^="/calendar?date="]').forEach(link => {
      const url = new URL(link.href, window.location.origin);
      url.searchParams.set('mode', calendarMode);
      link.href = `${url.pathname}${url.search}${url.hash}`;
    });
    planLayer.hidden = false;
    planLayer.classList.remove('is-background', 'is-copy-source');
    actualLayer.hidden = false;
    document.querySelectorAll('[data-calendar-empty-mode]').forEach(prompt => {
      prompt.hidden = prompt.dataset.calendarEmptyMode !== calendarMode;
    });
    if (syncUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set('mode', calendarMode);
      window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
    }
    if (focusCurrentTime) {
      window.setTimeout(() => focusCurrentTimeOnce(true), 0);
    }
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
    const duration = Number.parseInt(option.dataset.durationIndex || '', 10);
    if (planEstimatedDuration) {
      planEstimatedDuration.value = Number.isInteger(duration) && duration > 0 ? String(duration * 10) : '';
    }
    if (planScheduleType.value !== 'timed') return;

    const start = Number.parseInt(planStartInput.value, 10);
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
    if (planEstimatedDuration) planEstimatedDuration.value = '';
    if (planItemForm) checkRadio(planItemForm, 'importance', 'D');
    if (planItemGoal) planItemGoal.value = '';
    openPanel(planItemSheet);
    focusSheetInput(planItemTitle);
  }

  function openPlanItemEditSheet(button, sharedOnly = false) {
    if (button.dataset.planBlockId && planBlockEditSheet) {
      openPlanBlockEditSheet(button);
      return;
    }
    const isTemplate = Boolean(button.dataset.blockTemplateId);
    if (isTemplate) sharedOnly = true;
    const scheduleType = button.dataset.planItemScheduleType === 'unscheduled' ? 'unscheduled' : 'timed';
    const parsedStart = Number.parseInt(button.dataset.planItemStartIndex || '', 10);
    const parsedEnd = Number.parseInt(button.dataset.planItemEndIndex || '', 10);
    const start = Number.isInteger(parsedStart) ? parsedStart : 54;
    const end = Number.isInteger(parsedEnd) ? parsedEnd : 60;
    planEditItemId.value = button.dataset.planItemId || '';
    planDeleteItemId.value = button.dataset.planItemId || '';
    planEditTitle.value = button.dataset.planItemTitle || button.dataset.planTitle || '';
    checkRadio(document.getElementById('calendarPlanItemEditForm'), 'importance', button.dataset.planItemImportance || 'D');
    planEditGoal.value = button.dataset.planItemGoalId || '';
    planEditScheduleType.value = scheduleType;
    planEditStart.value = String(start);
    planEditEnd.value = String(end);
    setPlanEditScheduleType(scheduleType);
    planLinkAction.value = 'keep';
    planItemEditSheet.dataset.linked = button.dataset.planItemLinked || '0';
    planItemEditSheet.dataset.sharedOnly = sharedOnly ? '1' : '0';
    planItemEditSheet.dataset.templateId = button.dataset.blockTemplateId || '';
    planItemEditSheet.dataset.templateDuration = button.dataset.templateDuration || '';
    const sheetTitle = planItemEditSheet.querySelector('#planItemEditSheetTitle');
    if (sheetTitle) sheetTitle.textContent = isTemplate ? '계획 템플릿 수정' : (sharedOnly ? '할 일 수정' : '계획 일정 수정');
    const titleLabel = planItemEditSheet.querySelector('#calendarPlanEditTitleLabel');
    if (titleLabel) titleLabel.textContent = isTemplate ? '템플릿명' : (sharedOnly ? '할 일명' : '계획 일정명');
    planItemEditSheet.querySelector('form').action = sharedOnly ? '/calendar/plan-item/details' : '/calendar/plan-item/update';
    if (planEditTimeControls) planEditTimeControls.hidden = sharedOnly;
    planEditTimeFields.hidden = sharedOnly || scheduleType !== 'timed';
    if (planItemSharedNote) {
      planItemSharedNote.hidden = !sharedOnly;
      planItemSharedNote.textContent = isTemplate ? '템플릿을 수정해도 이미 배치한 날짜별 계획은 변경되지 않습니다.' : '리스트의 계획을 수정해도 이미 배치한 계획 블록은 변경되지 않습니다.';
    }
    const deleteButton = planItemEditSheet.querySelector('[data-plan-item-delete-button]');
    if (deleteButton) deleteButton.hidden = isTemplate;
    planLinkChoice.hidden = sharedOnly || planItemEditSheet.dataset.linked !== '1';
    openPanel(planItemEditSheet);
    focusSheetInput(planEditTitle);
  }

  let planBlockChangePending = false;
  const calendarDelayDuration = 1500;
  let calendarDelayHold = null;
  let planBlockDeleteAuthorized = false;
  let actualEventDeleteAuthorized = false;
  let actualEventDeleteSubmitting = false;
  const planBlockDeleteButton = planBlockEditSheet?.querySelector('[data-plan-block-delete]');
  const planBlockDetachButton = planBlockEditSheet?.querySelector('[data-plan-block-detach]');
  const planBlockDeleteForm = document.getElementById('calendarPlanBlockDeleteForm');
  const actualEventDeleteButton = eventEditSheet?.querySelector('[data-actual-event-delete]');
  const actualEventDeleteForm = document.getElementById('calendarEventDeleteForm');

  function cancelCalendarDelayHold() {
    if (!calendarDelayHold) return;
    window.clearTimeout(calendarDelayHold.timer);
    calendarDelayHold.button.classList.remove('is-holding');
    calendarDelayHold = null;
  }

  function registerCalendarDelayButton(button, sheet, shortMessage, onComplete) {
    if (!button) return;
    let ignoreClick = false;

    function complete(hold) {
      if (calendarDelayHold !== hold) return;
      if (sheet.hidden) { cancelCalendarDelayHold(); return; }
      cancelCalendarDelayHold();
      onComplete();
    }

    function start(kind, keyOrPointer) {
      if (calendarDelayHold || planBlockChangePending || button.disabled || sheet.hidden) return;
      const hold = { button, kind, keyOrPointer, timer: null, startedAt: window.performance.now() };
      calendarDelayHold = hold;
      button.classList.add('is-holding');
      hold.timer = window.setTimeout(() => complete(hold), calendarDelayDuration);
    }

    function finish(kind, keyOrPointer) {
      const hold = calendarDelayHold;
      if (!hold || hold.button !== button || hold.kind !== kind || hold.keyOrPointer !== keyOrPointer) return;
      if (window.performance.now() - hold.startedAt >= calendarDelayDuration) { complete(hold); return; }
      cancelCalendarDelayHold();
      ignoreClick = true;
      window.setTimeout(() => { ignoreClick = false; }, 0);
      showCalendarToast(shortMessage);
    }

    button.addEventListener('pointerdown', event => {
      if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return;
      start('pointer', event.pointerId);
      if (calendarDelayHold?.button === button && calendarDelayHold.keyOrPointer === event.pointerId) {
        try { button.setPointerCapture(event.pointerId); } catch (error) { /* Pointer capture is optional. */ }
      }
    });
    button.addEventListener('pointermove', event => {
      if (calendarDelayHold?.button !== button || calendarDelayHold.kind !== 'pointer' || calendarDelayHold.keyOrPointer !== event.pointerId) return;
      const bounds = button.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) cancelCalendarDelayHold();
    });
    button.addEventListener('pointerup', event => finish('pointer', event.pointerId));
    ['pointercancel', 'lostpointercapture'].forEach(type => button.addEventListener(type, event => {
      if (calendarDelayHold?.button === button && calendarDelayHold.kind === 'pointer' && calendarDelayHold.keyOrPointer === event.pointerId) cancelCalendarDelayHold();
    }));
    button.addEventListener('contextmenu', event => { if (calendarDelayHold?.button === button) event.preventDefault(); });
    button.addEventListener('keydown', event => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      if (!event.repeat) start('keyboard', event.key);
    });
    button.addEventListener('keyup', event => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      finish('keyboard', event.key);
    });
    button.addEventListener('blur', () => { if (calendarDelayHold?.button === button) cancelCalendarDelayHold(); });
    button.addEventListener('click', event => {
      event.preventDefault();
      if (event.detail === 0 && !ignoreClick && !planBlockChangePending) showCalendarToast(shortMessage);
    });
  }

  registerCalendarDelayButton(planBlockDeleteButton, planBlockEditSheet, '삭제하려면 1.5초 이상 눌러서 삭제하세요.', () => {
    planBlockDeleteAuthorized = true;
    planBlockDeleteForm?.requestSubmit();
  });
  registerCalendarDelayButton(planBlockDetachButton, planBlockEditSheet, '연결을 해제하려면 1.5초 이상 눌러 주세요.', detachPlanBlockActuals);
  registerCalendarDelayButton(actualEventDeleteButton, eventEditSheet, '삭제하려면 1.5초 이상 눌러서 삭제하세요.', () => {
    if (actualEventDeleteSubmitting) return;
    actualEventDeleteAuthorized = true;
    actualEventDeleteForm?.requestSubmit();
  });
  actualEventDeleteForm?.addEventListener('submit', event => {
    if (!actualEventDeleteAuthorized || actualEventDeleteSubmitting) {
      event.preventDefault();
      if (!actualEventDeleteSubmitting) showCalendarToast('삭제하려면 1.5초 이상 눌러서 삭제하세요.');
      return;
    }
    actualEventDeleteAuthorized = false;
    actualEventDeleteSubmitting = true;
    actualEventDeleteButton.disabled = true;
    preserveCalendarScroll();
  });
  window.addEventListener('blur', cancelCalendarDelayHold);
  document.addEventListener('visibilitychange', () => { if (document.hidden) cancelCalendarDelayHold(); });

  function setPlanBlockChangePending(pending) {
    planBlockChangePending = pending;
    if (pending) cancelCalendarDelayHold();
    planBlockEditSheet?.querySelectorAll('#calendarPlanBlockStatus, [data-plan-block-importance], [data-plan-block-detach], [data-plan-block-delete]').forEach(control => { control.disabled = pending; });
  }

  function showPlanBlockChangeError(error, fallback) {
    const message = error instanceof TypeError || error instanceof SyntaxError
      ? '저장 결과를 확인하지 못했습니다. 새로고침해 확인해 주세요.'
      : (error.message || fallback);
    if (window.LifeFlowToast?.show) window.LifeFlowToast.show(message);
    else window.alert(message);
  }

  function openPlanBlockEditSheet(button) {
    if (planBlockChangePending) return;
    const itemId = planBlockEditSheet.querySelector('#calendarPlanBlockItemId');
    const blockId = planBlockEditSheet.querySelector('#calendarPlanBlockId');
    const deleteId = planBlockEditSheet.querySelector('#calendarPlanBlockDeleteId');
    const start = planBlockEditSheet.querySelector('#calendarPlanBlockStart');
    const end = planBlockEditSheet.querySelector('#calendarPlanBlockEnd');
    const title = planBlockEditSheet.querySelector('[data-plan-block-title]');
    const sheetTitle = planBlockEditSheet.querySelector('#planBlockEditSheetTitle');
    if (!itemId || !blockId || !deleteId || !start || !end) return;
    itemId.value = button.dataset.planItemId || '';
    blockId.value = button.dataset.planBlockId || '';
    deleteId.value = button.dataset.planBlockId || '';
    start.value = button.dataset.planItemStartIndex || '0';
    end.value = button.dataset.planItemEndIndex || '1';
    if (title) title.textContent = button.dataset.planItemTitle || '';
    if (sheetTitle) sheetTitle.textContent = '계획 수정';
    const followsTodo = button.dataset.planFollowsTodo === '1';
    const inherited = planBlockEditSheet.querySelector('[data-plan-block-inherited]');
    const statusContainer = planBlockEditSheet.querySelector('[data-plan-block-status-container]');
    if (statusContainer) statusContainer.hidden = followsTodo;
    if (inherited) {
      inherited.hidden = !followsTodo;
      const labels = {pending: '미정', in_progress: '진행 중', completed: '완료', postponed: '연기', cancelled: '취소', external: '위임'};
      const icons = {pending: '—', in_progress: '·', completed: 'V', postponed: '→', cancelled: 'X', external: '○'};
      const importance = button.dataset.planItemImportance || 'D';
      const status = button.dataset.planManualStatus || 'pending';
      const priorityIcon = inherited.querySelector('[data-plan-inherited-importance]');
      const priorityLabel = inherited.querySelector('[data-plan-inherited-importance-label]');
      const statusIcon = inherited.querySelector('[data-plan-inherited-status-icon]');
      const statusLabel = inherited.querySelector('[data-plan-inherited-status-label]');
      if (priorityIcon) priorityIcon.textContent = importance;
      if (priorityLabel) priorityLabel.textContent = `중요도 ${importance}`;
      if (statusIcon) statusIcon.textContent = icons[status] || '—';
      if (statusLabel) statusLabel.textContent = labels[status] || '미정';
    }
    checkRadio(planBlockEditSheet, 'importance', button.dataset.planItemImportance || 'D');
    const importanceGroup = planBlockEditSheet.querySelector('[data-plan-block-importance]');
    if (importanceGroup) { importanceGroup.hidden = followsTodo; importanceGroup.dataset.savedImportance = button.dataset.planItemImportance || 'D'; }
    const linkCount = Number.parseInt(button.dataset.planBlockActualCount || '', 10)
      || (button.dataset.planItemLinked === '1' ? 1 : 0);
    const linkControls = planBlockEditSheet.querySelector('[data-plan-block-actual-link-controls]');
    if (linkControls) linkControls.hidden = linkCount === 0;
    const statusGroup = planBlockEditSheet.querySelector('#calendarPlanBlockStatus');
    const statusNote = planBlockEditSheet.querySelector('[data-plan-block-status-note]');
    if (statusGroup) {
      const status = button.dataset.planManualStatus || 'pending';
      statusGroup.querySelectorAll('input').forEach(input => { input.checked = input.value === status; });
      statusGroup.dataset.savedStatus = status;
      statusGroup.querySelector('input[value="completed"]').disabled = linkCount === 0;
    }
    if (statusNote) statusNote.textContent = linkCount > 0 ? '상태를 선택하면 바로 저장됩니다.' : '실제 일정을 연결한 뒤 완료할 수 있어요.';
    const linkCountLabel = planBlockEditSheet.querySelector('[data-plan-block-link-count]');
    const actualMinutes = Number.parseInt(button.dataset.planBlockActualMinutes || '0', 10) || 0;
    const actualDuration = `${Math.floor(actualMinutes / 60) ? `${Math.floor(actualMinutes / 60)}시간 ` : ''}${actualMinutes % 60 ? `${actualMinutes % 60}분` : (actualMinutes ? '' : '0분')}`.trim();
    if (linkCountLabel) linkCountLabel.textContent = linkCount > 0 ? `실제 기록 ${actualDuration} · ${linkCount}건 연결. 모두 연결 해제되며 실제 기록은 유지됩니다.` : '연결된 실제 일정이 없습니다.';
    const detailsButton = planBlockEditSheet.querySelector('[data-plan-item-details-open]');
    if (detailsButton) {
      detailsButton.dataset.planItemId = button.dataset.planItemId || '';
      detailsButton.dataset.planItemTitle = button.dataset.planItemTitle || '';
      detailsButton.dataset.planItemImportance = button.dataset.planItemImportance || 'D';
      detailsButton.dataset.planItemGoalId = button.dataset.planItemGoalId || '';
      detailsButton.dataset.planItemScheduleType = button.dataset.planItemScheduleType || 'timed';
      detailsButton.dataset.planItemStartIndex = button.dataset.planItemStartIndex || '';
      detailsButton.dataset.planItemEndIndex = button.dataset.planItemEndIndex || '';
      detailsButton.dataset.planItemLinked = button.dataset.planItemLinked || '0';
    }
    openPanel(planBlockEditSheet);
  }

  planBlockEditSheet?.querySelector('#calendarPlanBlockStatus')?.addEventListener('change', async event => {
    const group = event.currentTarget;
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.checked) return;
    if (planBlockChangePending) return;
    const selectedStatus = input.value;
    const form = group.closest('form');
    const body = new FormData();
    body.set('_csrf_token', form.querySelector('[name="_csrf_token"]').value);
    body.set('daily_plan_block_id', form.querySelector('#calendarPlanBlockId').value);
    body.set('manual_status', selectedStatus);
    setPlanBlockChangePending(true);
    try {
      const response = await fetch('/calendar/plan-block/status', {method: 'POST', body, headers: {'Accept': 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '상태를 변경하지 못했습니다.');
      if (form.querySelector('#calendarPlanBlockId').value === body.get('daily_plan_block_id')) {
        group.dataset.savedStatus = selectedStatus;
      }
      const labels = {pending: '미정', in_progress: '진행 중', completed: '완료', postponed: '연기', cancelled: '취소', external: '위임'};
      const icons = {in_progress: '·', completed: 'V', postponed: '→', cancelled: 'X', external: '○'};
      document.querySelectorAll('[data-plan-block-id]').forEach(button => {
        if (button.dataset.planBlockId !== body.get('daily_plan_block_id')) return;
        button.dataset.planManualStatus = selectedStatus;
        button.setAttribute('aria-label', `${button.dataset.planItemTitle || ''} · ${labels[selectedStatus]}`);
        let badge = button.querySelector('.calendar-plan-status');
        if (selectedStatus === 'pending') { badge?.remove(); return; }
        if (!badge) { badge = document.createElement('span'); badge.className = 'calendar-plan-status'; badge.setAttribute('aria-hidden', 'true'); button.appendChild(badge); }
        badge.textContent = icons[selectedStatus];
      });
      if (form.querySelector('#calendarPlanBlockId').value === body.get('daily_plan_block_id') && !planBlockEditSheet.hidden) closePanels();
    } catch (error) {
      if (form.querySelector('#calendarPlanBlockId').value === body.get('daily_plan_block_id')) {
        group.querySelectorAll('input').forEach(option => { option.checked = option.value === (group.dataset.savedStatus || 'pending'); });
      }
      showPlanBlockChangeError(error, '상태를 변경하지 못했습니다.');
    } finally { setPlanBlockChangePending(false); }
  });

  async function savePlanBlockChange(url, body) {
    const response = await fetch(url, {method: 'POST', body, headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || '계획을 변경하지 못했습니다.');
    return result;
  }

  function planBlockChangeBody(blockId) {
    const body = new FormData();
    body.set('_csrf_token', planBlockEditSheet.querySelector('[name="_csrf_token"]').value);
    body.set('daily_plan_block_id', blockId);
    body.set('date', page.dataset.calendarDate || '');
    return body;
  }

  function refreshPlanLinkOptions(itemId) {
    const isLinked = Array.from(actualLayer.querySelectorAll('[data-event-open]')).some(button => button.dataset.eventDailyPlanItemId === itemId);
    const ranges = new Map();
    planLayer.querySelectorAll('[data-plan-item-open]').forEach(button => {
      if (button.dataset.planItemId === itemId && button.dataset.planBlockId) ranges.set(button.dataset.planBlockId, [Number(button.dataset.planItemStartIndex), Number(button.dataset.planItemEndIndex)]);
    });
    const detail = Array.from(ranges.values()).sort((a, b) => a[0] - b[0]).map(range => `${formatGridTime(range[0])}~${formatGridTime(range[1])}`).join(', ') || '시간 미정';
    document.querySelectorAll('input[name="daily_plan_item_id"][data-plan-detail]').forEach(input => {
      if (input.value !== itemId) return;
      const label = input.closest('.calendar-plan-link');
      if (label) { label.dataset.planDisabled = isLinked ? '1' : '0'; label.classList.toggle('is-disabled', isLinked); }
      input.disabled = isLinked;
      input.dataset.planDetail = detail;
      const text = label?.querySelector('small');
      if (text) text.textContent = detail + (isLinked ? ' · 이미 연결됨' : '');
    });
    document.querySelectorAll('[data-create-plan-group], [data-edit-plan-group]').forEach(syncPlanLinkSummary);
  }

  function clearPlanBlockActualLinks(blockId) {
    actualLayer.querySelectorAll('[data-event-open]').forEach(button => {
      if (button.dataset.eventDailyPlanBlockId !== blockId) return;
      button.dataset.eventDailyPlanBlockId = '';
      button.dataset.eventDailyPlanItemId = '';
      button.classList.remove('is-linked');
    });
    planLayer.querySelectorAll('[data-plan-item-open]').forEach(button => {
      if (button.dataset.planBlockId !== blockId) return;
      button.dataset.planItemLinked = '0';
      button.dataset.planBlockActualCount = '0';
      button.dataset.planBlockActualMinutes = '0';
      if (button.dataset.planFollowsTodo !== '1') {
        button.dataset.planManualStatus = 'pending';
        button.setAttribute('aria-label', `${button.dataset.planItemTitle || ''} · 미정`);
        button.querySelector('.calendar-plan-status')?.remove();
      }
    });
  }

  planBlockEditSheet?.querySelector('[data-plan-block-importance]')?.addEventListener('change', async event => {
    const group = event.currentTarget;
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.checked || planBlockChangePending) return;
    const form = group.closest('form');
    const blockId = form.querySelector('#calendarPlanBlockId').value;
    const previous = group.dataset.savedImportance || 'D';
    const body = planBlockChangeBody(blockId);
    body.set('importance', input.value);
    setPlanBlockChangePending(true);
    try {
      await savePlanBlockChange(form.action, body);
      planLayer.querySelectorAll('[data-plan-item-open]').forEach(button => {
        if (button.dataset.planBlockId !== blockId) return;
        button.dataset.planItemImportance = input.value;
        ['a', 'b', 'c', 'd'].forEach(value => button.classList.remove(`importance-${value}`));
        button.classList.add(`importance-${input.value.toLowerCase()}`);
        const badge = button.querySelector('.calendar-importance-badge');
        if (badge) badge.textContent = input.value;
      });
      if (form.querySelector('#calendarPlanBlockId').value === blockId && !planBlockEditSheet.hidden) closePanels();
    } catch (error) {
      if (form.querySelector('#calendarPlanBlockId').value === blockId) checkRadio(group, 'importance', previous);
      showPlanBlockChangeError(error, '중요도를 변경하지 못했습니다.');
    } finally { setPlanBlockChangePending(false); }
  });

  async function detachPlanBlockActuals() {
    if (!planBlockDetachButton || planBlockChangePending || planBlockEditSheet.hidden) return;
    const form = planBlockDetachButton.closest('form');
    const blockId = form.querySelector('#calendarPlanBlockId').value;
    const itemId = form.querySelector('#calendarPlanBlockItemId').value;
    setPlanBlockChangePending(true);
    try {
      const result = await savePlanBlockChange('/calendar/plan-block/detach', planBlockChangeBody(blockId));
      clearPlanBlockActualLinks(blockId);
      refreshPlanLinkOptions(itemId);
      if (form.querySelector('#calendarPlanBlockId').value === blockId && !planBlockEditSheet.hidden) closePanels();
      showCalendarToast(result.message);
    } catch (error) {
      showPlanBlockChangeError(error, '연결을 해제하지 못했습니다.');
    } finally { setPlanBlockChangePending(false); }
  }

  document.getElementById('calendarPlanBlockDeleteForm')?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!planBlockDeleteAuthorized) {
      showCalendarToast('삭제하려면 1.5초 이상 눌러서 삭제하세요.');
      return;
    }
    planBlockDeleteAuthorized = false;
    if (planBlockChangePending) return;
    const form = event.currentTarget;
    const blockId = form.querySelector('#calendarPlanBlockDeleteId').value;
    const itemId = planBlockEditSheet.querySelector('#calendarPlanBlockItemId').value;
    setPlanBlockChangePending(true);
    try {
      const result = await savePlanBlockChange(form.action, new FormData(form));
      clearPlanBlockActualLinks(blockId);
      planLayer.querySelectorAll('[data-plan-item-open]').forEach(button => { if (button.dataset.planBlockId === blockId) button.remove(); });
      refreshPlanLinkOptions(itemId);
      if (planBlockEditSheet.querySelector('#calendarPlanBlockId').value === blockId && !planBlockEditSheet.hidden) closePanels();
      showCalendarToast(result.message);
    } catch (error) {
      showPlanBlockChangeError(error, '계획 블록을 삭제하지 못했습니다.');
    } finally { setPlanBlockChangePending(false); }
  });

  let pendingGoalSelect = null;
  let returnPanelAfterGoalCreate = null;

  function openGoalCreateSheet(select, returnPanel) {
    if (!goalCreateSheet || !goalCreateForm || !goalCreateTitleInput) return;
    pendingGoalSelect = select;
    returnPanelAfterGoalCreate = returnPanel;
    goalCreateForm.reset();
    openPanel(goalCreateSheet);
    focusSheetInput(goalCreateTitleInput);
  }

  function cancelGoalCreate() {
    if (returnPanelAfterGoalCreate) {
      const panel = returnPanelAfterGoalCreate;
      returnPanelAfterGoalCreate = null;
      openPanel(panel);
      if (pendingGoalSelect) focusSheetInput(pendingGoalSelect);
      return;
    }
    closePanels();
  }

  function appendGoalOption(select, goalId, title) {
    if (!select || !goalId) return;
    const option = document.createElement('option');
    option.value = String(goalId);
    option.textContent = title;
    select.append(option);
    select.value = String(goalId);
  }

  let selectedPlanBlockSource = null;
  let sourceDrag = null;
  let planPlacement = null;
  let planPlacementSaving = false;

  function sortPlanSources() {
    const list = document.querySelector('.calendar-plan-source-list');
    if (!list) return;
    const rank = value => ({ A: 0, B: 1, C: 2, D: 3 }[value] ?? 4);
    Array.from(list.querySelectorAll('[data-plan-block-source]')).sort((a, b) =>
      rank(a.dataset.planItemImportance) - rank(b.dataset.planItemImportance)
      || (a.dataset.planCreatedAt || '').localeCompare(b.dataset.planCreatedAt || '')
    ).forEach(source => list.insertBefore(source, list.querySelector('[data-calendar-todo-create-open]')));
  }
  sortPlanSources();

  // Register the mobile workspace controls before optional Calendar features.
  // This keeps the three work modes usable even when an unrelated sheet feature
  // fails to initialize later in this large page script.
  const initialWorkspace = new URL(window.location.href).searchParams.get('workspace');
  setCalendarWorkMode(initialWorkspace === 'plan-placement' || initialWorkspace === 'tag-paint' ? initialWorkspace : 'normal');
  window.requestAnimationFrame(() => window.requestAnimationFrame(() => { page.dataset.calendarViewReady = '1'; }));
  if (activityPanel && window.ResizeObserver) new ResizeObserver(syncMobileWorkBar).observe(activityPanel);
  window.addEventListener('resize', syncMobileWorkBar, { passive: true });
  if (page.dataset.calendarWorkModeControlsBound !== '1') {
    page.dataset.calendarWorkModeControlsBound = '1';
    document.querySelectorAll('button[data-calendar-work-mode]').forEach(button => {
      button.addEventListener('click', () => {
        const requestedMode = button.dataset.calendarWorkMode || 'normal';
        if (requestedMode === 'plan-placement') document.querySelector('[data-calendar-plan-start-guide]')?.remove();
        const isActive = page.dataset.calendarWorkMode === requestedMode;
        setCalendarWorkMode(isActive && requestedMode !== 'normal' ? 'normal' : requestedMode, true);
      });
    });
    document.querySelectorAll('[data-calendar-tag-paint]').forEach(button => {
      button.addEventListener('click', () => {
        selectedPaintTagId = button.dataset.calendarTagPaint || '';
        document.querySelectorAll('[data-calendar-tag-paint]').forEach(option => {
          option.classList.toggle('is-selected', option === button);
          option.setAttribute('aria-pressed', option === button ? 'true' : 'false');
        });
      });
    });
  }
  document.querySelector('[data-calendar-tag-paint-mode]')?.addEventListener('click', () => {
    setCalendarWorkMode(calendarWorkMode === 'tag-paint' ? 'normal' : 'tag-paint');
    const modeButton = document.querySelector('[data-calendar-tag-paint-mode]');
    if (modeButton) {
      const active = calendarWorkMode === 'tag-paint';
      modeButton.setAttribute('aria-pressed', active ? 'true' : 'false');
      modeButton.textContent = active ? '태그 칠하기 완료' : '태그 칠하기 시작';
    }
  });
  window.addEventListener('popstate', () => {
    const requestedMode = new URL(window.location.href).searchParams.get('workspace');
    setCalendarWorkMode(requestedMode === 'plan-placement' || requestedMode === 'tag-paint' ? requestedMode : 'normal');
  });
  actualLayer.addEventListener('click', event => {
    const block = event.target.closest('[data-event-open]');
    if (!block || calendarWorkMode === 'normal') return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (calendarWorkMode === 'tag-paint') paintActualEventTag(block);
  }, true);
  function selectPlanSource(source) {
    selectedPlanBlockSource = selectedPlanBlockSource === source ? null : source;
    if (selectedPlanBlockSource) page.dataset.planSourceSelected = '1';
    else delete page.dataset.planSourceSelected;
    document.querySelectorAll('[data-plan-block-source]').forEach(item => {
      const selected = item === selectedPlanBlockSource;
      item.classList.toggle('is-selected', selected);
      item.querySelector('[data-plan-block-source-select]')?.setAttribute('aria-pressed', String(selected));
    });
    showCalendarToast(selectedPlanBlockSource ? `${source.dataset.planTitle || '할 일'}을(를) 선택했습니다. 계획 시간표에서 시간을 지정하세요.` : '계획 선택을 취소했습니다.');
  }
  document.querySelector('.calendar-plan-source-list')?.addEventListener('click', event => {
    const source = event.target.closest('[data-plan-block-source]');
    if (!source || event.target.closest('[data-plan-block-source-handle]')) return;
    if (event.target.closest('[data-plan-source-edit]')) {
      openPlanItemEditSheet(source, true);
      return;
    }
    if (!event.target.closest('[data-plan-block-source-select]')) return;
    if (calendarWorkMode === 'plan-placement') selectPlanSource(source);
    else if (source.dataset.planItemId) openPlanItemEditSheet(source, true);
  });

  function formatGridTime(index) {
    if (!Number.isInteger(index) || index < 0 || index > 144) return '';
    if (index === 144) return '24:00';
    return `${String(Math.floor(index / 6)).padStart(2, '0')}:${String((index % 6) * 10).padStart(2, '0')}`;
  }

  function hidePlanDragGuide() {
    if (!planDragGuide) return;
    planDragGuide.hidden = true;
    planDragGuide.classList.remove('is-conflict');
  }

  function showPlanDragGuide(source, start, end) {
    if (!planDragGuide || !source) return;
    const rangeStart = Math.min(start, end);
    const rangeEnd = Math.max(start, end) + 1;
    const conflict = hasOccupiedRange(rangeStart, rangeEnd, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex');
    const title = source.dataset.planTitle || '계획 활동';
    planDragGuide.textContent = `${title} · ${formatGridTime(rangeStart)}~${formatGridTime(rangeEnd)} · ${(rangeEnd - rangeStart) * 10}분${conflict ? ' · 다른 계획과 겹침' : ''}`;
    planDragGuide.classList.toggle('is-conflict', conflict);
    planDragGuide.hidden = false;
  }

  function clearPlanPlacementPreview() {
    daygrid.querySelectorAll('.plan-placement-preview').forEach(cell => cell.classList.remove('plan-placement-preview'));
  }

  function clearPlanResizePreview() {
    daygrid.querySelectorAll('.plan-resize-preview').forEach(cell => cell.classList.remove('plan-resize-preview'));
    updateWorkStatus();
  }

  function clearPlanMovePreview() {
    daygrid.querySelectorAll('.plan-move-preview').forEach(cell => cell.classList.remove('plan-move-preview'));
    updateWorkStatus();
  }

  function previewPlanMove(start, end) {
    clearPlanMovePreview();
    updateWorkStatus('move');
    for (let index = start; index < end; index += 1) {
      daygrid.querySelector(`.cell[data-index="${index}"]`)?.classList.add('plan-move-preview');
    }
  }

  function previewPlanResize(start, end) {
    clearPlanResizePreview();
    updateWorkStatus('resize');
    for (let index = start; index < end; index += 1) {
      daygrid.querySelector(`.cell[data-index="${index}"]`)?.classList.add('plan-resize-preview');
    }
  }

  function previewPlanPlacement() {
    clearPlanPlacementPreview();
    if (!planPlacement) return;
    const start = Math.min(planPlacement.startIndex, planPlacement.endIndex);
    const end = Math.max(planPlacement.startIndex, planPlacement.endIndex);
    for (let index = start; index <= end; index += 1) {
      daygrid.querySelector(`.cell[data-index="${index}"]`)?.classList.add('plan-placement-preview');
    }
    showPlanDragGuide(planPlacement.source, start, end);
  }

  function beginPlanPlacement(source, startIndex) {
    planPlacement = { source, startIndex, endIndex: startIndex };
    source.classList.remove('is-dragging');
    previewPlanPlacement();
    showCalendarToast('시간표에서 시작부터 종료까지 드래그하세요.');
  }

  function commitPlanPlacement() {
    if (!planPlacement) return;
    const placement = planPlacement;
    planPlacement = null;
    clearPlanPlacementPreview();
    hidePlanDragGuide();
    const start = Math.min(placement.startIndex, placement.endIndex);
    const end = Math.max(placement.startIndex, placement.endIndex) + 1;
    if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) {
      showCalendarToast('이미 등록된 계획 블록과 시간이 겹칩니다.');
      return;
    }
    submitPlanBlock(placement.source, start, end);
  }

  function appendPlanBlock(itemId, blockId, start, end, source) {
    const title = source?.dataset.planTitle || '계획';
    const importance = source?.dataset.planItemImportance || 'D';
    const followsTodo = !source?.dataset.blockTemplateId;
    const status = followsTodo ? (source?.dataset.planTodoStatus || 'pending') : 'pending';
    const statusLabels = {pending: '미정', in_progress: '진행 중', completed: '완료', postponed: '연기', cancelled: '취소', external: '위임'};
    const statusIcons = {in_progress: '·', completed: 'V', postponed: '→', cancelled: 'X', external: '○'};
    let cursor = start;
    while (cursor < end) {
      const row = Math.floor(cursor / 6);
      const rowEnd = Math.min(end, (row + 1) * 6);
      const eventButton = document.createElement('button');
      eventButton.type = 'button';
      eventButton.className = `event plan-event importance-${importance.toLowerCase()}`;
      eventButton.classList.toggle('is-compact-duration', end - start <= 2);
      eventButton.setAttribute('aria-label', `${title} · ${statusLabels[status] || '미정'}`);
      eventButton.style.setProperty('--row', String(row));
      eventButton.style.setProperty('--col', String(cursor % 6));
      eventButton.style.setProperty('--span', String(rowEnd - cursor));
      eventButton.dataset.planItemOpen = '';
      eventButton.dataset.planItemId = String(itemId);
      eventButton.dataset.planBlockId = String(blockId);
      eventButton.dataset.planItemTitle = title;
      eventButton.dataset.planItemImportance = importance;
      eventButton.dataset.planFollowsTodo = followsTodo ? '1' : '0';
      eventButton.dataset.planManualStatus = status;
      eventButton.dataset.planItemGoalId = source?.dataset.planItemGoalId || '';
      eventButton.dataset.planItemLinked = '0';
      eventButton.dataset.planItemStartIndex = String(start);
      eventButton.dataset.planItemEndIndex = String(end);
      eventButton.dataset.planItemScheduleType = 'timed';
      eventButton.innerHTML = `<span class="calendar-importance-badge is-neutral"></span><span class="event-title"></span>${cursor === start ? '<span class="plan-block-resize-handle is-start" data-plan-block-resize="start" aria-hidden="true"></span>' : ''}${rowEnd === end ? '<span class="plan-block-resize-handle is-end" data-plan-block-resize="end" aria-hidden="true"></span>' : ''}`;
      eventButton.querySelector('.calendar-importance-badge').textContent = importance;
      eventButton.querySelector('.event-title').textContent = title;
      if (statusIcons[status]) { const badge = document.createElement('span'); badge.className = 'calendar-plan-status'; badge.setAttribute('aria-hidden', 'true'); badge.textContent = statusIcons[status]; eventButton.appendChild(badge); }
      planLayer.append(eventButton);
      cursor = rowEnd;
    }
  }

  async function submitPlanBlock(source, start, end) {
    if (planPlacementSaving) return;
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    const itemId = source.dataset.planItemId || '';
    const templateId = source.dataset.blockTemplateId || '';
    if ((!itemId && !templateId) || !token) return;
    const body = new FormData();
    [['_csrf_token', token], ['date', page.dataset.calendarDate || ''], ['daily_plan_item_id', itemId], ['start_index', start], ['end_index', end]].forEach(([name, value]) => body.set(name, String(value)));
    if (templateId) body.set('block_template_id', templateId);
    planPlacementSaving = true;
    try {
      const response = await fetch('/calendar/plan-block', { method: 'POST', body, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.blockId) throw new Error(payload.message || '계획 블록을 추가하지 못했습니다.');
      appendPlanBlock(payload.itemId || itemId, payload.blockId, start, end, source);
      document.querySelector('[data-calendar-day-template]')?.remove();
      if (selectedPlanBlockSource === source) {
        selectedPlanBlockSource = null;
        delete page.dataset.planSourceSelected;
        source.classList.remove('is-selected');
        source.querySelector('[data-plan-block-source-select]')?.setAttribute('aria-pressed', 'false');
      }
      showCalendarToast('계획 블록을 배치했습니다.');
    } catch (error) {
      showCalendarToast(error.message || '계획 블록을 추가하지 못했습니다.');
    } finally {
      planPlacementSaving = false;
    }
  }

  let rangeSavePending = false;

  function replaceRangeSegments(layer, selector, idKey, id, start, end, isActual) {
    const buttons = Array.from(layer.querySelectorAll(selector)).filter(button => button.dataset[idKey] === String(id));
    if (!buttons.length) return;
    const template = (isActual ? buttons[0].closest('.actual-event-form') : buttons[0]).cloneNode(true);
    buttons.forEach(button => (isActual ? button.closest('.actual-event-form') : button).remove());
    for (let cursor = start; cursor < end;) {
      const rowEnd = Math.min(end, (Math.floor(cursor / 6) + 1) * 6);
      const segment = template.cloneNode(true);
      const button = isActual ? segment.querySelector(selector) : segment;
      segment.style.setProperty('--row', String(Math.floor(cursor / 6)));
      segment.style.setProperty('--col', String(cursor % 6));
      segment.style.setProperty('--span', String(rowEnd - cursor));
      button.classList.remove('is-resizing');
      button.classList.toggle('is-compact-duration', end - start <= 2);
      button.dataset[isActual ? 'eventStartIndex' : 'planItemStartIndex'] = String(start);
      button.dataset[isActual ? 'eventEndIndex' : 'planItemEndIndex'] = String(end);
      button.querySelectorAll(isActual ? '[data-actual-block-resize]' : '[data-plan-block-resize]').forEach(handle => handle.remove());
      for (const edge of ['start', 'end']) {
        if ((edge === 'start' && cursor !== start) || (edge === 'end' && rowEnd !== end)) continue;
        const handle = document.createElement('span');
        handle.className = `${isActual ? 'actual' : 'plan'}-block-resize-handle is-${edge}`;
        handle.dataset[isActual ? 'actualBlockResize' : 'planBlockResize'] = edge;
        handle.setAttribute('aria-hidden', 'true');
        button.append(handle);
      }
      layer.append(segment);
      cursor = rowEnd;
    }
  }

  async function saveRange(url, fields, onSuccess) {
    if (rangeSavePending) return;
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!token) { showCalendarToast('요청이 만료되었습니다. 다시 시도해 주세요.'); return; }
    const body = new FormData();
    Object.entries({ _csrf_token:token, date:page.dataset.calendarDate || '', ...fields }).forEach(([key, value]) => body.set(key, String(value)));
    rangeSavePending = true;
    try {
      const response = await fetch(url, { method:'POST', body, headers:{ Accept:'application/json', 'X-Requested-With':'XMLHttpRequest' } });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '시간 변경을 저장하지 못했습니다.');
      onSuccess(result);
    } catch (error) {
      showCalendarToast(error instanceof TypeError || error instanceof SyntaxError ? '저장 결과를 확인하지 못했습니다. 새로고침해 확인해 주세요.' : (error.message || '시간 변경을 저장하지 못했습니다.'));
    } finally { rangeSavePending = false; }
  }

  function submitPlanBlockUpdate(itemId, blockId, start, end) {
    if (!itemId || !blockId) return;
    saveRange('/calendar/plan-block/update', { calendar_mode:calendarMode, daily_plan_item_id:itemId, daily_plan_block_id:blockId, start_index:start, end_index:end }, result => {
      replaceRangeSegments(planLayer, '[data-plan-item-open]', 'planBlockId', blockId, start, end, false);
      const ranges = new Map();
      planLayer.querySelectorAll('[data-plan-item-open]').forEach(button => {
        if (button.dataset.planItemId !== String(itemId) || !button.dataset.planBlockId) return;
        ranges.set(button.dataset.planBlockId, [Number(button.dataset.planItemStartIndex), Number(button.dataset.planItemEndIndex)]);
      });
      const detail = Array.from(ranges.values()).sort((a, b) => a[0] - b[0]).map(range => `${formatGridTime(range[0])}~${formatGridTime(range[1])}`).join(', ');
      document.querySelectorAll('input[name="daily_plan_item_id"][data-plan-detail]').forEach(input => {
        if (input.value !== String(itemId)) return;
        input.dataset.planDetail = detail;
        const label = input.closest('.calendar-plan-link');
        const text = label?.querySelector('small');
        if (text) text.textContent = detail + (label.dataset.planDisabled === '1' ? ' · 이미 연결됨' : '');
      });
      document.querySelectorAll('[data-create-plan-group], [data-edit-plan-group]').forEach(syncPlanLinkSummary);
      if (result.notificationSyncPayload) window.LifeFlowAndroidBridge?.syncNotifications(result.notificationSyncPayload);
    });
  }

  function gridIndexFrom(grid, x, y) {
    const cell = document.elementsFromPoint(x, y).find(element => element instanceof HTMLElement && element.classList.contains('cell') && grid.contains(element));
    const index = Number.parseInt(cell?.dataset.index || '', 10);
    return Number.isInteger(index) ? index : null;
  }

  function gridIndexFromPoint(x, y) {
    return gridIndexFrom(daygrid, x, y);
  }

  function actualGridIndexFromPoint(x, y) {
    return actualDaygrid ? gridIndexFrom(actualDaygrid, x, y) : null;
  }

  let planCreateRange = null;
  let planTouch = null;
  let planTouchTimer = null;
  let planPullSuspended = false;
  function clearPlanTouch() {
    window.clearTimeout(planTouchTimer);
    planTouchTimer = null;
    planTouch = null;
    daygrid.classList.remove('is-range-selecting');
    daygrid.querySelectorAll('.selecting').forEach(cell => cell.classList.remove('selecting'));
    if (planPullSuspended) window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(true);
    planPullSuspended = false;
    updateWorkStatus();
  }
  function previewPlanTouch(start, end) {
    daygrid.querySelectorAll('.selecting').forEach(cell => cell.classList.remove('selecting'));
    for (let index = Math.min(start, end); index <= Math.max(start, end); index++) daygrid.querySelector(`.cell[data-index="${index}"]`)?.classList.add('selecting');
    updateWorkStatus('plan-selection');
  }
  daygrid.addEventListener('touchstart', event => {
    if (event.touches.length !== 1) { if (planTouch) clearPlanTouch(); return; }
    if (planTouch || rangeSavePending || planPlacementSaving || calendarWorkMode === 'tag-paint' || (calendarWorkMode === 'plan-placement' && selectedPlanBlockSource)) return;
    if (event.target.closest('[data-plan-item-open], button, input, select, textarea, a')) return;
    const touch = event.changedTouches[0];
    const index = gridIndexFromPoint(touch.clientX, touch.clientY);
    if (index === null) return;
    const selection = { id:touch.identifier, x:touch.clientX, y:touch.clientY, start:index, current:index, active:false };
    planTouch = selection;
    planTouchTimer = window.setTimeout(() => {
      if (planTouch !== selection) return;
      selection.active = true;
      daygrid.classList.add('is-range-selecting');
      planPullSuspended = Boolean(window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(false));
      previewPlanTouch(index, index);
      navigator.vibrate?.(18);
    }, 360);
  }, { passive:true });
  document.addEventListener('touchmove', event => {
    if (!planTouch) return;
    const touch = Array.from(event.changedTouches).find(item => item.identifier === planTouch.id);
    if (!touch) return;
    if (!planTouch.active) {
      if (Math.max(Math.abs(touch.clientX - planTouch.x), Math.abs(touch.clientY - planTouch.y)) >= 8) clearPlanTouch();
      return;
    }
    if (event.cancelable) event.preventDefault();
    const index = gridIndexFromPoint(touch.clientX, touch.clientY);
    if (index !== null) { planTouch.current = index; previewPlanTouch(planTouch.start, index); }
  }, { passive:false });
  document.addEventListener('touchend', event => {
    if (!planTouch) return;
    const touch = Array.from(event.changedTouches).find(item => item.identifier === planTouch.id);
    if (!touch) return;
    const selection = planTouch;
    const index = gridIndexFromPoint(touch.clientX, touch.clientY) ?? selection.current;
    clearPlanTouch();
    if (!selection.active) return;
    if (event.cancelable) event.preventDefault();
    const endIndex = index;
    const start = Math.min(selection.start, endIndex), end = Math.max(selection.start, endIndex) + 1;
    if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) { showCalendarToast('이미 등록된 계획 블록과 시간이 겹칩니다.'); return; }
    openCalendarPlanCreate({ start, end });
  }, { passive:false });
  document.addEventListener('touchcancel', event => {
    if (planTouch && Array.from(event.changedTouches).some(item => item.identifier === planTouch.id)) clearPlanTouch();
  }, { passive:true });
  window.addEventListener('pagehide', clearPlanTouch);
  document.addEventListener('visibilitychange', () => { if (document.hidden) clearPlanTouch(); });

  daygrid.addEventListener('pointerdown', event => {
    if (!planPlacement && !planPlacementSaving && calendarWorkMode === 'plan-placement' && selectedPlanBlockSource && event.isPrimary !== false) {
      const index = gridIndexFromPoint(event.clientX, event.clientY);
      if (index === null) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      beginPlanPlacement(selectedPlanBlockSource, index);
      planPlacement.pointerId = event.pointerId;
      try { daygrid.setPointerCapture(event.pointerId); } catch (error) { /* Document listeners handle the pointer. */ }
      return;
    }
  }, true);
  document.addEventListener('pointermove', event => {
    if (!planPlacement || planPlacement.pointerId !== event.pointerId) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    planPlacement.endIndex = index;
    previewPlanPlacement();
  });
  document.addEventListener('pointerup', event => {
    if (!planPlacement || planPlacement.pointerId !== event.pointerId) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null || index === planPlacement.startIndex) {
      planPlacement = null;
      clearPlanPlacementPreview();
      hidePlanDragGuide();
      showCalendarToast('시작부터 종료까지 드래그해 주세요.');
      return;
    }
    planPlacement.endIndex = index;
    commitPlanPlacement();
  });
  document.addEventListener('pointercancel', event => {
    if (!planPlacement || planPlacement.pointerId !== event.pointerId) return;
    planPlacement = null;
    clearPlanPlacementPreview();
    hidePlanDragGuide();
  });
  function finishPlanSourceDrag(event) {
    if (!sourceDrag || sourceDrag.pointerId !== event.pointerId) return;
    const drag = sourceDrag;
    sourceDrag = null;
    page.classList.remove('is-plan-source-dragging');
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) {
      drag.source.classList.remove('is-dragging');
      hidePlanDragGuide();
      return;
    }
    selectedPlanBlockSource = drag.source;
    page.dataset.planSourceSelected = '1';
    document.querySelectorAll('[data-plan-block-source]').forEach(item => {
      item.classList.toggle('is-selected', item === drag.source);
      item.querySelector('[data-plan-block-source-select]')?.setAttribute('aria-pressed', String(item === drag.source));
    });
    showCalendarToast('시간표에서 시작부터 종료까지 드래그하세요.');
  }
  document.addEventListener('pointermove', event => {
    if (!sourceDrag || sourceDrag.pointerId !== event.pointerId) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) {
      return;
    }
    showPlanDragGuide(sourceDrag.source, index, index);
  });
  document.addEventListener('pointerup', finishPlanSourceDrag);

  let blockResize = null;
  let suppressPlanBlockClick = false;
  planLayer.addEventListener('pointerdown', event => {
    if (rangeSavePending) return;
    if (calendarWorkMode !== 'normal') return;
    const handle = event.target.closest('[data-plan-block-resize]');
    const block = event.target.closest('[data-plan-block-id]');
    if (!handle || !block || !block.dataset.planBlockId) return;
    event.preventDefault(); event.stopPropagation();
    blockResize = {
      block,
      edge: handle.dataset.planBlockResize,
      itemId: block.dataset.planItemId,
      blockId: block.dataset.planBlockId,
      start: Number.parseInt(block.dataset.planItemStartIndex || '', 10),
      end: Number.parseInt(block.dataset.planItemEndIndex || '', 10),
    };
    if (!Number.isInteger(blockResize.start) || !Number.isInteger(blockResize.end)) { blockResize = null; return; }
    blockResize.originalStart = blockResize.start;
    blockResize.originalEnd = blockResize.end;
    block.classList.add('is-resizing');
    previewPlanResize(blockResize.start, blockResize.end);
    try {
      block.setPointerCapture(event.pointerId);
    } catch (error) {
      blockResize = null;
      block.classList.remove('is-resizing');
      clearPlanResizePreview();
      showCalendarToast('계획 블록 크기 조정을 시작하지 못했습니다. 다시 시도해 주세요.');
    }
  });
  planLayer.addEventListener('pointermove', event => {
    if (!blockResize) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    if (blockResize.edge === 'start' && index < blockResize.end) blockResize.start = index;
    if (blockResize.edge === 'end' && index >= blockResize.start) blockResize.end = Math.min(index + 1, 144);
    blockResize.block.dataset.planItemStartIndex = String(blockResize.start);
    blockResize.block.dataset.planItemEndIndex = String(blockResize.end);
    previewPlanResize(blockResize.start, blockResize.end);
  });
  planLayer.addEventListener('pointerup', event => {
    if (!blockResize) return;
    const resize = blockResize; blockResize = null;
    resize.block.classList.remove('is-resizing');
    resize.block.dataset.planItemStartIndex = String(resize.originalStart);
    resize.block.dataset.planItemEndIndex = String(resize.originalEnd);
    clearPlanResizePreview();
    try { resize.block.releasePointerCapture(event.pointerId); } catch (error) { /* Capture can already be released. */ }
    if (resize.start < resize.end) {
      suppressPlanBlockClick = true;
      window.setTimeout(() => { suppressPlanBlockClick = false; }, 0);
      submitPlanBlockUpdate(resize.itemId, resize.blockId, resize.start, resize.end);
    }
  });
  planLayer.addEventListener('click', event => {
    if (!suppressPlanBlockClick) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }, true);
  planLayer.addEventListener('pointercancel', () => {
    if (blockResize) {
      blockResize.block.classList.remove('is-resizing');
      blockResize.block.dataset.planItemStartIndex = String(blockResize.originalStart);
      blockResize.block.dataset.planItemEndIndex = String(blockResize.originalEnd);
    }
    clearPlanResizePreview();
    blockResize = null;
  });

  function clearActualPreview() {
    actualDaygrid?.querySelectorAll('.actual-selection-preview, .actual-resize-preview, .actual-copy-preview')
      .forEach(cell => cell.classList.remove('actual-selection-preview', 'actual-resize-preview', 'actual-copy-preview'));
    updateWorkStatus();
  }

  function previewActualRange(start, end, className) {
    clearActualPreview();
    if (className === 'actual-resize-preview') updateWorkStatus('resize');
    else if (className === 'actual-copy-preview') updateWorkStatus(planCopyDrag ? 'copy' : 'move');
    else if (actualTouchSelection?.active) updateWorkStatus('actual-selection');
    for (let index = start; index < end; index += 1) {
      actualDaygrid?.querySelector(`.cell[data-index="${index}"]`)?.classList.add(className);
    }
  }

  function actualRangeFromStartAndPointer(startIndex, currentIndex) {
    return {
      start: Math.min(startIndex, currentIndex),
      end: Math.max(startIndex, currentIndex) + 1,
    };
  }

  function hasActualConflict(start, end, excludeEventId = '') {
    return hasOccupiedRange(
      start,
      end,
      '[data-event-open][data-event-start-index]',
      'eventStartIndex',
      'eventEndIndex',
      excludeEventId === '' ? '' : 'eventId',
      excludeEventId
    );
  }

  function submitActualRangeUpdate(eventId, start, end) {
    if (!eventId) return;
    saveRange('/calendar/event/time-range', { event_id:eventId, start_index:start, end_index:end }, () => {
      replaceRangeSegments(actualLayer, '[data-event-open]', 'eventId', eventId, start, end, true);
    });
  }

  function submitPlanBlockCopy(blockId, start, end) {
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!blockId || !token) return;
    const form = document.createElement('form');
    form.method = 'post'; form.action = '/calendar/event/from-plan-block';
    [['_csrf_token', token], ['date', page.dataset.calendarDate || ''], ['daily_plan_block_id', blockId], ['start_index', start], ['end_index', end]].forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = String(value); form.append(input);
    });
    document.body.append(form);
    preserveCalendarScroll();
    form.submit();
  }

  let actualSelection = null;
  let actualResize = null;
  let actualMove = null;
  let planCopyDrag = null;
  let suppressActualEventClick = false;
  let actualTouchSelection = null;
  let actualTouchTimer = null;
  let actualTouchPullToRefreshSuspended = false;

  function clearActualTouchSelection() {
    window.clearTimeout(actualTouchTimer);
    actualTouchTimer = null;
    actualTouchSelection = null;
    actualDaygrid?.classList.remove('is-actual-touch-selecting');
    updateWorkStatus();
    if (actualTouchPullToRefreshSuspended) {
      window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(true);
      actualTouchPullToRefreshSuspended = false;
    }
    clearActualPreview();
  }

  actualDaygrid?.addEventListener('touchstart', event => {
    if (event.touches.length !== 1) {
      if (actualTouchSelection) clearActualTouchSelection();
      return;
    }
    if (calendarWorkMode !== 'normal' || actualTouchSelection) return;
    if (event.target.closest('[data-event-open], [data-actual-block-resize], button, input, select, textarea, a')) return;
    const touch = event.changedTouches[0];
    const index = actualGridIndexFromPoint(touch.clientX, touch.clientY);
    if (index === null) return;
    const selection = { identifier:touch.identifier, startX:touch.clientX, startY:touch.clientY, startIndex:index, currentIndex:index, active:false };
    actualTouchSelection = selection;
    actualTouchTimer = window.setTimeout(() => {
      if (actualTouchSelection !== selection) return;
      selection.active = true;
      updateWorkStatus('actual-selection');
      actualDaygrid.classList.add('is-actual-touch-selecting');
      actualTouchPullToRefreshSuspended = Boolean(window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(false));
      previewActualRange(index, index + 1, 'actual-selection-preview');
      if (navigator.vibrate) navigator.vibrate(18);
    }, 360);
  }, { passive:true });
  document.addEventListener('touchmove', event => {
    const selection = actualTouchSelection;
    if (!selection) return;
    const touch = Array.from(event.changedTouches).find(item => item.identifier === selection.identifier);
    if (!touch) return;
    if (!selection.active) {
      if (Math.max(Math.abs(touch.clientX - selection.startX), Math.abs(touch.clientY - selection.startY)) >= 8) clearActualTouchSelection();
      return;
    }
    if (event.cancelable) event.preventDefault();
    const index = actualGridIndexFromPoint(touch.clientX, touch.clientY);
    if (index === null) return;
    selection.currentIndex = index;
    const range = actualRangeFromStartAndPointer(selection.startIndex, index);
    previewActualRange(range.start, range.end, 'actual-selection-preview');
  }, { passive:false });
  document.addEventListener('touchend', event => {
    const selection = actualTouchSelection;
    if (!selection) return;
    const touch = Array.from(event.changedTouches).find(item => item.identifier === selection.identifier);
    if (!touch) return;
    if (!selection.active) {
      clearActualTouchSelection();
      return;
    }
    const index = actualGridIndexFromPoint(touch.clientX, touch.clientY);
    const range = actualRangeFromStartAndPointer(selection.startIndex, index === null ? selection.currentIndex : index);
    clearActualTouchSelection();
    if (event.cancelable) event.preventDefault();
    if (hasActualConflict(range.start, range.end)) {
      showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
      return;
    }
    openEventSheet(range.start, range.end);
  }, { passive:false });
  document.addEventListener('touchcancel', event => {
    if (actualTouchSelection && Array.from(event.changedTouches).some(item => item.identifier === actualTouchSelection.identifier)) clearActualTouchSelection();
  }, { passive:true });

  actualDaygrid?.addEventListener('pointerdown', event => {
    if (calendarWorkMode !== 'normal') return;
    if (event.pointerType === 'touch') return;
    if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
    if (event.target.closest('[data-event-open], [data-actual-block-resize], button, input, select, textarea, a')) return;
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    event.preventDefault();
    actualSelection = { pointerId: event.pointerId, startIndex: index, currentIndex: index };
    previewActualRange(index, index + 1, 'actual-selection-preview');
    try { actualDaygrid.setPointerCapture(event.pointerId); } catch (error) { /* Native capture may be unavailable. */ }
  });
  actualDaygrid?.addEventListener('pointermove', event => {
    if (!actualSelection || actualSelection.pointerId !== event.pointerId) return;
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    actualSelection.currentIndex = index;
    const range = actualRangeFromStartAndPointer(actualSelection.startIndex, index);
    previewActualRange(range.start, range.end, 'actual-selection-preview');
  });
  actualDaygrid?.addEventListener('pointerup', event => {
    if (!actualSelection || actualSelection.pointerId !== event.pointerId) return;
    const selection = actualSelection;
    actualSelection = null;
    try { actualDaygrid.releasePointerCapture(event.pointerId); } catch (error) { /* Capture may already be released. */ }
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    const range = actualRangeFromStartAndPointer(selection.startIndex, index === null ? selection.currentIndex : index);
    clearActualPreview();
    if (hasActualConflict(range.start, range.end)) {
      showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
      return;
    }
    openEventSheet(range.start, range.end);
  });
  actualDaygrid?.addEventListener('pointercancel', () => {
    actualSelection = null;
    clearActualPreview();
  });

  actualLayer.addEventListener('pointerdown', event => {
    if (rangeSavePending) return;
    if (calendarWorkMode !== 'normal') return;
    const handle = event.target.closest('[data-actual-block-resize]');
    const block = event.target.closest('[data-event-open]');
    if (!handle || !block || !block.dataset.eventId) return;
    event.preventDefault(); event.stopPropagation();
    const start = Number.parseInt(block.dataset.eventStartIndex || '', 10);
    const end = Number.parseInt(block.dataset.eventEndIndex || '', 10);
    if (!Number.isInteger(start) || !Number.isInteger(end)) return;
    actualResize = {
      block,
      edge: handle.dataset.actualBlockResize,
      eventId: block.dataset.eventId,
      start,
      end,
      originalStart: start,
      originalEnd: end,
    };
    block.classList.add('is-resizing');
    previewActualRange(start, end, 'actual-resize-preview');
    try { block.setPointerCapture(event.pointerId); } catch (error) {
      actualResize = null;
      block.classList.remove('is-resizing');
      clearActualPreview();
      showCalendarToast('실제 일정 크기 조정을 시작하지 못했습니다. 다시 시도해 주세요.');
    }
  });
  actualLayer.addEventListener('pointermove', event => {
    if (!actualResize) return;
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    if (actualResize.edge === 'start' && index < actualResize.end) actualResize.start = index;
    if (actualResize.edge === 'end' && index >= actualResize.start) actualResize.end = Math.min(index + 1, 144);
    previewActualRange(actualResize.start, actualResize.end, 'actual-resize-preview');
  });
  actualLayer.addEventListener('pointerup', event => {
    if (!actualResize) return;
    const resize = actualResize;
    actualResize = null;
    resize.block.classList.remove('is-resizing');
    clearActualPreview();
    try { resize.block.releasePointerCapture(event.pointerId); } catch (error) { /* Capture may already be released. */ }
    suppressActualEventClick = true;
    window.setTimeout(() => { suppressActualEventClick = false; }, 0);
    if (resize.start < resize.end && !hasActualConflict(resize.start, resize.end, resize.eventId)) {
      submitActualRangeUpdate(resize.eventId, resize.start, resize.end);
      return;
    }
    showCalendarToast('다른 실제 일정과 시간이 겹쳐 변경하지 않았습니다.');
  });
  actualLayer.addEventListener('pointercancel', () => {
    if (actualResize) actualResize.block.classList.remove('is-resizing');
    actualResize = null;
    clearActualPreview();
  });
  actualLayer.addEventListener('click', event => {
    if (!suppressActualEventClick) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }, true);

  actualLayer.addEventListener('pointerdown', event => {
    if (rangeSavePending) return;
    if (calendarWorkMode !== 'normal') return;
    if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
    if (event.target.closest('[data-actual-block-resize]')) return;
    const block = event.target.closest('[data-event-open]');
    const start = Number.parseInt(block?.dataset.eventStartIndex || '', 10);
    const end = Number.parseInt(block?.dataset.eventEndIndex || '', 10);
    if (!block || !block.dataset.eventId || !Number.isInteger(start) || !Number.isInteger(end) || end <= start) return;
    const grabbedIndex = actualGridIndexFromPoint(event.clientX, event.clientY);
    actualMove = {
      pointerId: event.pointerId,
      block,
      eventId: block.dataset.eventId,
      start,
      duration: end - start,
      offset: Number.isInteger(grabbedIndex) ? Math.max(0, grabbedIndex - start) : 0,
      targetStart: null,
    };
    try { block.setPointerCapture(event.pointerId); } catch (error) { /* Document listeners remain the fallback. */ }
  });
  document.addEventListener('pointermove', event => {
    if (!actualMove || actualMove.pointerId !== event.pointerId) return;
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    const targetStart = index === null ? null : index - actualMove.offset;
    if (targetStart === null || targetStart < 0 || targetStart + actualMove.duration > 144) {
      actualMove.targetStart = null;
      clearActualPreview();
      return;
    }
    actualMove.targetStart = targetStart;
    previewActualRange(targetStart, targetStart + actualMove.duration, 'actual-copy-preview');
  });
  document.addEventListener('pointerup', event => {
    if (!actualMove || actualMove.pointerId !== event.pointerId) return;
    const move = actualMove;
    actualMove = null;
    clearActualPreview();
    try { move.block.releasePointerCapture(event.pointerId); } catch (error) { /* Capture may already be released. */ }
    if (move.targetStart === null || move.targetStart === move.start) return;
    suppressActualEventClick = true;
    window.setTimeout(() => { suppressActualEventClick = false; }, 0);
    const end = move.targetStart + move.duration;
    if (hasActualConflict(move.targetStart, end, move.eventId)) {
      showCalendarToast('다른 실제 일정과 시간이 겹쳐 이동하지 않았습니다.');
      return;
    }
    submitActualRangeUpdate(move.eventId, move.targetStart, end);
  });
  document.addEventListener('pointercancel', event => {
    if (actualMove?.pointerId !== event.pointerId) return;
    actualMove = null;
    clearActualPreview();
  });

  planLayer.addEventListener('pointerdown', event => {
    if (rangeSavePending) return;
    if (calendarWorkMode !== 'normal') return;
    if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
    if (event.target.closest('[data-plan-block-resize]')) return;
    const block = event.target.closest('[data-plan-block-id]');
    const blockId = block?.dataset.planBlockId || '';
    const start = Number.parseInt(block?.dataset.planItemStartIndex || '', 10);
    const end = Number.parseInt(block?.dataset.planItemEndIndex || '', 10);
    if (!block || !blockId || !Number.isInteger(start) || !Number.isInteger(end) || end <= start) return;
    const grabbedIndex = gridIndexFromPoint(event.clientX, event.clientY);
    planCopyDrag = {
      pointerId: event.pointerId,
      block,
      blockId,
      itemId: block.dataset.planItemId || '',
      start,
      duration: end - start,
      offset: Number.isInteger(grabbedIndex) ? Math.max(0, grabbedIndex - start) : 0,
      mode: '',
      lastStart: null,
    };
    page.classList.add('is-plan-copy-dragging');
    try { block.setPointerCapture(event.pointerId); } catch (error) { /* Document listeners remain the fallback. */ }
  });
  document.addEventListener('pointermove', event => {
    if (!planCopyDrag || planCopyDrag.pointerId !== event.pointerId) return;
    const actualIndex = actualGridIndexFromPoint(event.clientX, event.clientY);
    if (actualIndex !== null) {
      if (actualIndex + planCopyDrag.duration > 144) {
        planCopyDrag.lastStart = null;
        planCopyDrag.mode = '';
        clearActualPreview();
        return;
      }
      planCopyDrag.mode = 'copy';
      planCopyDrag.lastStart = actualIndex;
      clearPlanMovePreview();
      previewActualRange(actualIndex, actualIndex + planCopyDrag.duration, 'actual-copy-preview');
      return;
    }
    if (isMobileActualMode()) {
      // In Actual mode a Plan block is a copy source only. It cannot open,
      // move, or resize from the left timetable.
      planCopyDrag.lastStart = null;
      planCopyDrag.mode = '';
      clearActualPreview();
      clearPlanMovePreview();
      return;
    }
    const planIndex = gridIndexFromPoint(event.clientX, event.clientY);
    const targetStart = planIndex === null ? null : planIndex - planCopyDrag.offset;
    if (targetStart === null || targetStart < 0 || targetStart + planCopyDrag.duration > 144) {
      planCopyDrag.lastStart = null;
      planCopyDrag.mode = '';
      clearActualPreview();
      clearPlanMovePreview();
      return;
    }
    planCopyDrag.mode = 'move';
    planCopyDrag.lastStart = targetStart;
    clearActualPreview();
    previewPlanMove(targetStart, targetStart + planCopyDrag.duration);
  });
  document.addEventListener('pointerup', event => {
    if (!planCopyDrag || planCopyDrag.pointerId !== event.pointerId) return;
    const copy = planCopyDrag;
    planCopyDrag = null;
    page.classList.remove('is-plan-copy-dragging');
    clearActualPreview();
    clearPlanMovePreview();
    try { copy.block.releasePointerCapture(event.pointerId); } catch (error) { /* Capture may already be released. */ }
    if (copy.lastStart === null) return;
    const end = copy.lastStart + copy.duration;
    suppressPlanBlockClick = true;
    window.setTimeout(() => { suppressPlanBlockClick = false; }, 0);
    if (copy.mode === 'move') {
      if (copy.lastStart === copy.start) return;
      if (hasOccupiedRange(copy.lastStart, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex', 'planBlockId', copy.blockId)) {
        showCalendarToast('다른 계획 블록과 시간이 겹쳐 이동하지 않았습니다.');
        return;
      }
      submitPlanBlockUpdate(copy.itemId, copy.blockId, copy.lastStart, end);
      return;
    }
    if (copy.mode !== 'copy' || hasActualConflict(copy.lastStart, end)) {
      showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
      return;
    }
    submitPlanBlockCopy(copy.blockId, copy.lastStart, end);
  });
  document.addEventListener('pointercancel', event => {
    if (planCopyDrag?.pointerId === event.pointerId) {
      planCopyDrag = null;
      page.classList.remove('is-plan-copy-dragging');
      clearActualPreview();
      clearPlanMovePreview();
    }
  });

  function setPlanEditScheduleType(scheduleType) {
    const isTimed = scheduleType === 'timed';
    planEditScheduleType.value = isTimed ? 'timed' : 'unscheduled';
    planEditTimeFields.hidden = !isTimed;
    planEditStart.disabled = !isTimed;
    planEditEnd.disabled = !isTimed;
    planEditStart.required = isTimed;
    planEditEnd.required = isTimed;
  }

  // Range selection belongs to the prototype-style source drag flow below.
  // Keep the legacy controller disabled so an empty Plan cell cannot open a form.
  if (false) timeGrid.create({
    grid: daygrid,
    ignoreSelector: '[data-event-open], [data-plan-item-open], button, input, select, textarea, a',
    onSelect({ start, end }) {
      if (calendarMode === 'plan') {
        if (page.dataset.planBlocksEnabled === '1' && selectedPlanBlockSource) {
          if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) {
            showCalendarToast('이미 등록된 계획 블록과 시간이 겹칩니다.');
            return;
          }
          submitPlanBlock(selectedPlanBlockSource.dataset.planItemId || '', start, end);
          return;
        }
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
    button.addEventListener('click', () => setCalendarMode(
      button.dataset.calendarMode,
      true,
      false
    ));
  });
  planLayer.addEventListener('click', event => {
      const button = event.target.closest('[data-plan-item-open]');
      if (!button || rangeSavePending) return;
      if (calendarWorkMode !== 'normal') return;
      if (isMobileActualMode()) {
        showCalendarToast('시간 블록을 실제 시간표 위로 드래그하세요.');
        return;
      }
      openPlanItemEditSheet(button);
  });
  document.querySelectorAll('[data-plan-item-details-open]').forEach(button => {
    button.addEventListener('click', () => openPlanItemEditSheet(button, true));
  });
  document.querySelectorAll('[data-plan-block-source]').forEach(button => {
    const handle = button.querySelector('[data-plan-block-source-handle]');
    handle?.addEventListener('pointerdown', event => {
      if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
      if (calendarWorkMode !== 'plan-placement') return;
      sourceDrag = { pointerId: event.pointerId, source: button };
      button.classList.add('is-dragging');
      page.classList.add('is-plan-source-dragging');
      if (planDragGuide) {
        planDragGuide.textContent = `${button.dataset.planTitle || '계획 활동'} · 계획 시간표로 끌어 놓으세요`;
        planDragGuide.classList.remove('is-conflict');
        planDragGuide.hidden = false;
      }
      try { handle.setPointerCapture(event.pointerId); } catch (error) { /* Pointer capture is optional. */ }
    });
    handle?.addEventListener('pointerup', finishPlanSourceDrag);
    handle?.addEventListener('click', event => {
      event.preventDefault();
      event.stopPropagation();
    });
    handle?.addEventListener('pointercancel', event => {
      if (sourceDrag?.pointerId === event.pointerId) {
        sourceDrag = null;
        button.classList.remove('is-dragging');
        page.classList.remove('is-plan-source-dragging');
        hidePlanDragGuide();
      }
    });
  });
  function openCalendarPlanCreate(range = null) {
    if (!calendarTodoCreateLayer) return;
    planCreateRange = range;
    const form = calendarTodoCreateLayer.querySelector('[data-calendar-plan-create-form]');
    const rangeLabel = form?.querySelector('[data-calendar-create-range]');
    if (rangeLabel) { rangeLabel.hidden = !range; rangeLabel.textContent = range ? `${formatGridTime(range.start)} ~ ${formatGridTime(range.end)}` : ''; }
    const hint = form?.querySelector('input[name="schedule_type"] + .muted');
    if (hint) hint.hidden = Boolean(range);
    calendarTodoCreateLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => document.getElementById('calendarTodoCreateInputTitle')?.focus(), 0);
  }
  document.querySelectorAll('[data-calendar-todo-create-open]').forEach(button => button.addEventListener('click', () => openCalendarPlanCreate()));
  document.querySelector('[data-calendar-day-template]')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    const body = new FormData(form);
    body.set('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
    body.set('date', page.dataset.calendarDate || '');
    const submit = form.querySelector('button[type="submit"]');
    if (submit) submit.disabled = true;
    try {
      const response = await fetch('/calendar/day-plan', { method:'POST', body, headers:{ Accept:'application/json', 'X-Requested-With':'XMLHttpRequest' } });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || '템플릿을 적용하지 못했습니다.');
      const list = document.querySelector('.calendar-plan-source-list');
      const sources = new Map();
      (data.items || []).forEach(item => {
        const source = document.createElement('div');
        source.className = 'calendar-plan-source';
        source.dataset.planBlockSource = '';
        source.dataset.planItemId = String(item.itemId);
        source.dataset.planTitle = item.title;
        source.dataset.planItemImportance = item.importance;
        source.dataset.planCreatedAt = item.createdAt || '';
        source.dataset.planItemGoalId = item.goalId == null ? '' : String(item.goalId);
        source.innerHTML = '<span class="calendar-importance-badge"></span><button type="button" class="calendar-plan-source-title" data-plan-block-source-select aria-pressed="false"></button><button type="button" class="calendar-plan-source-edit" data-plan-source-edit aria-label="할 일 옵션 수정">⋯</button>';
        source.firstElementChild.textContent = item.importance;
        source.querySelector('[data-plan-block-source-select]').textContent = item.title;
        source.querySelector('[data-plan-block-source-select]').title = item.title;
        sources.set(String(item.itemId), source);
        list?.prepend(source);
      });
      const rendered = new Set();
      (data.segments || []).forEach(segment => {
        const id = String(segment.blockId || '');
        if (!id || rendered.has(id)) return;
        rendered.add(id);
        appendPlanBlock(segment.itemId, segment.blockId, Number(segment.startIndex), Number(segment.endIndex), sources.get(String(segment.itemId)));
      });
      list?.querySelector('.calendar-plan-source-empty')?.remove();
      sortPlanSources();
      form.remove();
      showCalendarToast('하루 템플릿을 적용했습니다.');
    } catch (error) {
      showCalendarToast(error.message || '템플릿을 적용하지 못했습니다.');
      if (submit) submit.disabled = false;
    }
  });
  document.querySelectorAll('[data-calendar-todo-create-close]').forEach(button => button.addEventListener('click', () => {
    if (!calendarTodoCreateLayer) return;
    calendarTodoCreateLayer.hidden = true;
    planCreateRange = null;
    document.body.classList.remove('is-menu-open');
  }));
  document.querySelector('[data-calendar-plan-create-form]')?.addEventListener('submit', async event => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!(form instanceof HTMLFormElement)) return;
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    try {
      const body = new FormData(form);
      const range = planCreateRange;
      if (range) { body.set('start_index', String(range.start)); body.set('end_index', String(range.end)); }
      const response = await fetch(range ? '/calendar/plan-item/with-block' : form.action, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.item) throw new Error(payload.message || '계획을 추가하지 못했습니다.');
      const list = document.querySelector('.calendar-plan-source-list');
      if (!list) return;
      list.querySelector('.calendar-plan-source-empty')?.remove();
      const item = payload.item;
      const source = document.createElement('div');
      source.className = 'calendar-plan-source';
      source.dataset.planBlockSource = '';
      source.dataset.planItemId = String(item.id);
      source.dataset.planTitle = item.title || '';
      source.dataset.planItemImportance = item.importance || 'D';
      source.dataset.planCreatedAt = new Date().toISOString().slice(0, 19).replace('T', ' ');
      source.dataset.planItemGoalId = item.goalId === null ? '' : String(item.goalId);
      source.dataset.planItemScheduleType = item.scheduleType || 'unscheduled';
      source.innerHTML = `<span class="calendar-importance-badge importance-${String(item.importance || 'D').toLowerCase()}"></span><button type="button" class="calendar-plan-source-title" data-plan-block-source-select aria-pressed="false"></button><button type="button" class="calendar-plan-source-edit" data-plan-source-edit aria-label="할 일 옵션 수정">⋯</button><button type="button" class="calendar-plan-source-handle" data-plan-block-source-handle aria-label="계획 시간표에 배치"><span aria-hidden="true">⠿</span></button>`;
      source.querySelector('.calendar-importance-badge').textContent = item.importance || 'D';
      source.querySelector('.calendar-plan-source-title').textContent = item.title || '';
      source.querySelector('.calendar-plan-source-title').title = item.title || '';
      const handle = source.querySelector('[data-plan-block-source-handle]');
      handle?.addEventListener('pointerdown', pointerEvent => {
        if (calendarWorkMode !== 'plan-placement' || pointerEvent.isPrimary === false) return;
        sourceDrag = { pointerId: pointerEvent.pointerId, source };
        source.classList.add('is-dragging');
        page.classList.add('is-plan-source-dragging');
        try { handle.setPointerCapture(pointerEvent.pointerId); } catch (error) { /* Optional on older browsers. */ }
      });
      handle?.addEventListener('pointerup', finishPlanSourceDrag);
      handle?.addEventListener('click', clickEvent => { clickEvent.preventDefault(); clickEvent.stopPropagation(); });
      const rank = value => ({ A: 0, B: 1, C: 2, D: 3 }[value] ?? 4);
      const nextSource = Array.from(list.querySelectorAll('[data-plan-block-source]')).find(candidate =>
        rank(candidate.dataset.planItemImportance) > rank(source.dataset.planItemImportance)
      );
      list.insertBefore(source, nextSource || null);
      sortPlanSources();
      if (range && payload.segments?.length) {
        appendPlanBlock(item.id, payload.segments[0].blockId, range.start, range.end, source);
        if (payload.notificationSyncPayload) window.LifeFlowAndroidBridge?.syncNotifications(payload.notificationSyncPayload);
      }
      document.querySelector('[data-calendar-day-template]')?.remove();
      form.reset();
      calendarTodoCreateLayer.hidden = true;
      document.body.classList.remove('is-menu-open');
      planCreateRange = null;
      if (range) setCalendarWorkMode('plan-placement', true);
      source.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
      source.querySelector('.calendar-plan-source-title')?.click();
      showCalendarToast(payload.message || '계획을 추가했습니다.');
    } catch (error) {
      showCalendarToast(error.message || '계획을 추가하지 못했습니다.');
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  });
  document.querySelectorAll('[data-plan-add-open]').forEach(button => {
    button.addEventListener('click', () => openPlanItemSheet());
  });
  planTimeToggle.addEventListener('change', async () => {
    setPlanCreateScheduleType(planTimeToggle.checked ? 'timed' : 'unscheduled');
    if (planTimeToggle.checked) await applySelectedPlanBlockTemplate();
  });
  planBlockTemplatePicker?.addEventListener('change', () => {
    if (planBlockTemplatePicker.value === '' && planEstimatedDuration) planEstimatedDuration.value = '';
    applySelectedPlanBlockTemplate();
  });
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
  document.getElementById('calendarPlanItemEditForm')?.addEventListener('submit', async event => {
    if (planItemEditSheet.dataset.templateId) {
      event.preventDefault();
      const form = event.currentTarget;
      const source = document.querySelector(`[data-plan-block-source][data-block-template-id="${CSS.escape(planItemEditSheet.dataset.templateId)}"]`);
      const body = new FormData(form);
      body.set('block_template_id', planItemEditSheet.dataset.templateId);
      body.set('duration_index', planItemEditSheet.dataset.templateDuration);
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
      try {
        const response = await fetch('/plan/block-template/update', { method:'POST', body, headers:{ Accept:'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || '템플릿을 수정하지 못했습니다.');
        if (source) {
          source.dataset.planTitle = planEditTitle.value.trim();
          source.dataset.planItemImportance = String(body.get('importance') || 'D');
          source.dataset.planItemGoalId = planEditGoal.value;
          const title = source.querySelector('[data-plan-block-source-select]');
          if (title) { title.textContent = source.dataset.planTitle; title.title = source.dataset.planTitle; title.setAttribute('aria-label', `템플릿: ${source.dataset.planTitle}`); }
          const badge = source.querySelector('.calendar-importance-badge');
          if (badge) { badge.className = `calendar-importance-badge importance-${source.dataset.planItemImportance.toLowerCase()}`; badge.textContent = source.dataset.planItemImportance; }
          source.querySelector('[data-plan-source-edit]')?.setAttribute('aria-label', `${source.dataset.planTitle} 템플릿 옵션 수정`);
          const option = planBlockTemplatePicker?.querySelector(`option[value="${CSS.escape(planItemEditSheet.dataset.templateId)}"]`);
          if (option) {
            const durationLabel = option.textContent.split(' · ').slice(1).join(' · ');
            option.dataset.title = source.dataset.planTitle;
            option.dataset.importance = source.dataset.planItemImportance;
            option.dataset.goalId = source.dataset.planItemGoalId;
            option.textContent = durationLabel ? `${source.dataset.planTitle} · ${durationLabel}` : source.dataset.planTitle;
          }
          sortPlanSources();
        }
        closePanels();
        showCalendarToast(result.message);
      } catch (error) {
        showCalendarToast(error.message || '템플릿을 수정하지 못했습니다.');
      } finally {
        if (submit) submit.disabled = false;
      }
      return;
    }
    if (planItemEditSheet.dataset.sharedOnly === '1') return;
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

  document.querySelectorAll('[data-actual-form]').forEach(form => {
    form.querySelectorAll('[data-actual-view-open]').forEach(button => {
      button.addEventListener('click', () => {
        const viewName = button.dataset.actualViewOpen;
        snapshotActualSelection(form, viewName);
        const view = form.querySelector(`[data-actual-view="${viewName}"]`);
        if (viewName === 'plan') {
          const hasRecommended = Boolean(view?.querySelector('[data-plan-filter-item].is-recommended:not(.is-disabled)'));
          setPlanFilter(view, hasRecommended ? 'recommended' : 'all');
        } else if (viewName === 'routine') {
          const search = view?.querySelector('[data-routine-search]');
          if (search) search.value = '';
          view?.querySelectorAll('[data-routine-filter-item]').forEach(row => { row.hidden = false; });
        }
        showActualFormView(form, viewName);
        focusSheetInput(view?.querySelector('[data-routine-search], [data-plan-filter-item]:not([hidden]) input:not([disabled]), [data-routine-filter-item]:not([hidden]) input:not([disabled]), button') || button);
      });
    });
    form.querySelectorAll('[data-actual-view-cancel]').forEach(button => {
      button.addEventListener('click', () => {
        restoreActualSelection(form);
        showActualFormView(form, 'main');
        focusSheetInput(form.querySelector(`[data-actual-view-open="${actualViewSnapshots.get(form)?.viewName || ''}"]`) || form.querySelector('[data-calendar-close]'));
      });
    });
    form.querySelectorAll('[data-actual-view-done]').forEach(button => {
      button.addEventListener('click', () => {
        syncPlanLinkSummary(form.querySelector('[data-create-plan-group], [data-edit-plan-group]'));
        syncActualFormSummary(form);
        const viewName = form.dataset.activeActualView || '';
        showActualFormView(form, 'main');
        focusSheetInput(form.querySelector(`[data-actual-view-open="${viewName}"]`) || form.querySelector('[data-calendar-close]'));
      });
    });
    form.querySelectorAll('[data-plan-filter]').forEach(button => {
      button.addEventListener('click', () => setPlanFilter(button.closest('[data-actual-view="plan"]'), button.dataset.planFilter || 'all'));
    });
    form.querySelectorAll('[data-plan-clear]').forEach(button => {
      button.addEventListener('click', () => {
        checkRadio(form, 'daily_plan_item_id', '');
        syncPlanLinkSummary(form.querySelector('[data-create-plan-group], [data-edit-plan-group]'));
      });
    });
    form.querySelectorAll('[data-routine-clear]').forEach(button => {
      button.addEventListener('click', () => {
        form.querySelectorAll('input[name="routine_ids[]"]:not(:disabled)').forEach(input => { input.checked = false; });
        syncActualFormSummary(form);
      });
    });
    form.querySelectorAll('[data-routine-search]').forEach(input => {
      input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase();
        form.querySelectorAll('[data-routine-filter-item]').forEach(row => {
          row.hidden = query !== '' && !row.textContent.toLocaleLowerCase().includes(query);
        });
      });
    });
  });

  eventForm.querySelectorAll('input[name="daily_plan_item_id"][data-plan-title]').forEach(input => {
    input.addEventListener('change', () => {
      if (input.checked) {
        titleInput.value = input.dataset.planTitle || '';
        syncPlanLinkSummary(createPlanGroup);
      }
    });
  });
  eventForm.querySelector('input[name="daily_plan_item_id"][value=""]')?.addEventListener('change', () => {
    syncPlanLinkSummary(createPlanGroup);
  });

  document.getElementById('calendarEventEditForm')?.querySelectorAll('input[name="daily_plan_item_id"][data-plan-title]').forEach(input => {
    input.addEventListener('change', () => {
      const form = input.form;
      if (input.checked && input.value !== (form?.dataset.originalPlanId || '')) {
        editTitleInput.value = input.dataset.planTitle || '';
      }
      syncPlanLinkSummary(editPlanGroup);
    });
  });
  document.getElementById('calendarEventEditForm')?.querySelector('input[name="daily_plan_item_id"][value=""]')?.addEventListener('change', () => {
    syncPlanLinkSummary(editPlanGroup);
  });
  document.querySelectorAll('[data-actual-form]').forEach(form => {
    const refresh = () => {
      syncActualFormSummary(form);
      const startControl = form.querySelector('select[name="start_index"]');
      const endControl = form.querySelector('select[name="end_index"]');
      updatePlanRecommendations(
        form,
        Number.parseInt(startControl?.value || '', 10),
        Number.parseInt(endControl?.value || '', 10)
      );
    };
    form.addEventListener('input', refresh);
    form.addEventListener('change', refresh);
    refresh();
  });
  // Both grids now belong to one document scroll context. Preserve the server
  // selected tab so mobile layout and URL mode do not jump on page load.
  setCalendarMode(page.dataset.calendarMode || 'actual');

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
        preserveCalendarScroll();
        window.location.assign(payload.redirect || `/calendar?date=${encodeURIComponent(page.dataset.calendarDate)}&mode=${encodeURIComponent(calendarMode)}`);
      } catch (error) {
        window.LifeFlowToast?.show?.(error.message || '계획 일정을 삭제하지 못했습니다.');
      }
    }
  });


  startCurrentTimeCellUpdates();

  const calendarScrollKey = `life-flow.calendar.scroll.${page.dataset.calendarDate || ''}`;
  const currentUrl = new URL(window.location.href);
  const shouldFocusOnEntry = currentUrl.searchParams.get('focus') === 'current';
  if ('scrollRestoration' in window.history) window.history.scrollRestoration = 'manual';

  function preserveCalendarScroll() {
    try {
      window.sessionStorage.setItem(calendarScrollKey, String(window.scrollY));
    } catch (error) { /* Session storage can be unavailable in private contexts. */ }
  }

  function restoreCalendarScroll() {
    if (shouldFocusOnEntry) return;
    try {
      const stored = window.sessionStorage.getItem(calendarScrollKey);
      if (stored === null) return;
      window.sessionStorage.removeItem(calendarScrollKey);
      const top = Number.parseInt(stored, 10);
      if (!Number.isInteger(top) || top < 0) return;
      const restore = () => window.scrollTo({ top, behavior: 'auto' });
      restore();
      window.requestAnimationFrame(() => {
        restore();
        window.requestAnimationFrame(restore);
      });
      window.setTimeout(restore, 80);
    } catch (error) { /* Session storage can be unavailable in private contexts. */ }
  }

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const action = new URL(form.action || window.location.href, window.location.href);
    if (action.pathname.startsWith('/calendar/')) {
      cancelCurrentTimeFocus();
      preserveCalendarScroll();
    }
  }, true);

  document.addEventListener('pointerdown', event => {
    if (page.contains(event.target)) cancelCurrentTimeFocus();
  }, { passive: true });

  const scheduleEntryFocus = () => {
    restoreCalendarScroll();
    if (!shouldFocusOnEntry) return;
    window.setTimeout(() => focusCurrentTimeOnce(true), 80);
    currentUrl.searchParams.delete('focus');
    window.history.replaceState(null, '', `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`);
  };
  if (document.readyState === 'complete') scheduleEntryFocus();
  else window.addEventListener('load', scheduleEntryFocus, { once: true });

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      startCurrentTimeCellUpdates();
    }
  });

  window.addEventListener('pagehide', stopCurrentTimeCellUpdates);

  document.querySelectorAll('[data-calendar-close]').forEach(button => {
    button.addEventListener('click', closePanels);
  });

  document.querySelectorAll('[data-calendar-goal-create-open]').forEach(button => {
    button.addEventListener('click', () => openGoalCreateSheet(
      button.closest('form')?.querySelector('select[name="goal_id"]'),
      button.closest('[data-plan-item-edit-sheet]') || planItemSheet
    ));
  });
  document.querySelectorAll('[data-calendar-goal-cancel]').forEach(button => {
    button.addEventListener('click', cancelGoalCreate);
  });
  goalCreateForm?.addEventListener('submit', async event => {
    event.preventDefault();
    const submitButton = goalCreateForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    try {
      const response = await fetch(goalCreateForm.action, {
        method: 'POST',
        body: new FormData(goalCreateForm),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.goalId) {
        throw new Error(payload.message || Object.values(payload.errors || {})[0] || '목표를 추가하지 못했습니다.');
      }
      const title = goalCreateTitleInput?.value.trim() || '새 목표';
      [planItemGoal, planEditGoal].forEach(select => appendGoalOption(select, payload.goalId, title));
      if (pendingGoalSelect) pendingGoalSelect.value = String(payload.goalId);
      showCalendarToast('새 목표를 연결했습니다.');
      cancelGoalCreate();
    } catch (error) {
      showCalendarToast(error.message || '목표를 추가하지 못했습니다.');
    } finally {
      if (submitButton) submitButton.disabled = false;
    }
  });

  document.querySelectorAll('[data-retrospect-preview-open]').forEach(button => {
    button.addEventListener('click', () => {
      closeFab();
      openPanel(retrospectPreview);
    });
  });
  if (page.dataset.retrospectAutoPrompt === '1') {
    const body = new FormData();
    body.set('_csrf_token', document.querySelector('input[name="_csrf_token"]')?.value || '');
    body.set('date', page.dataset.calendarDate || '');
    fetch('/calendar/retrospect-prompt', { method:'POST', body, headers:{ Accept:'application/json' } })
      .then(response => response.ok ? response.json() : null)
      .then(async result => {
        if (!result?.show) return;
        const message = '최근 발행한 회고를 확인하고 오늘 계획을 세워볼까요?';
        const confirmed = window.LifeFlowUI?.confirm
          ? await window.LifeFlowUI.confirm({ title:'발행 회고 보기', message, confirmText:'회고 보기', cancelText:'다음에' })
          : window.confirm(message);
        if (confirmed) openPanel(retrospectPreview);
      })
      .catch(() => { /* The manual preview remains available. */ });
  }

  async function loadRetrospectPreview(date) {
    if (!retrospectPreviewBody || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return;
    retrospectPreview.setAttribute('aria-busy', 'true');
    retrospectPreviewMoves.forEach(button => { button.disabled = true; });
    try {
      const response = await fetch(`/retrospect/report/preview?date=${encodeURIComponent(date)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.message || '발행 회고를 불러오지 못했습니다.');
      retrospectPreviewBody.innerHTML = payload.html || '';
      retrospectPreviewBody.scrollTop = 0;
      if (retrospectPreviewTitle) retrospectPreviewTitle.textContent = `${payload.dateTitle || payload.date} 발행 회고`;
      retrospectPreviewMoves.forEach(button => {
        const targetDate = button.dataset.retrospectPreviewMove === 'previous' ? payload.previous : payload.next;
        button.dataset.reportDate = targetDate || '';
        button.disabled = !targetDate;
      });
    } catch (error) {
      showCalendarToast(error.message || '발행 회고를 불러오지 못했습니다.');
      retrospectPreviewMoves.forEach(button => { button.disabled = !button.dataset.reportDate; });
    } finally {
      retrospectPreview.removeAttribute('aria-busy');
    }
  }

  retrospectPreviewMoves.forEach(button => {
    button.addEventListener('click', () => loadRetrospectPreview(button.dataset.reportDate || ''));
  });

  document.querySelectorAll('[data-plan-settings-open]').forEach(button => {
    button.addEventListener('click', () => openPanel(planSettingsSheet));
  });

  document.querySelectorAll('[data-actual-event-open]').forEach(button => {
    button.addEventListener('click', () => {
      openEventSheet(null, null, 'timed', true);
    });
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
        syncPlanLinkSummary(createPlanGroup);
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

  document.querySelectorAll('[data-condition-open]').forEach(button => {
    button.addEventListener('click', () => {
      openPanel(conditionSheet);
      focusSheetInput(conditionSheet.querySelector('input[type="radio"]'));
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
      syncActualFormSummary(eventForm);
      sourceTabs.open = false;
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
    document.querySelectorAll('[data-paint-tag-color]').forEach(input => {
      if (input.value === String(paletteId)) input.closest('label')?.remove();
    });
    const remainingPaintColors = document.querySelectorAll('[data-paint-tag-color]');
    if (remainingPaintColors.length) remainingPaintColors[0].checked = true;
    else {
      const editor = document.querySelector('[data-paint-tag-create]');
      const toggle = document.querySelector('[data-paint-tag-toggle]');
      if (editor) editor.hidden = true;
      if (toggle) toggle.hidden = true;
    }
  }

  function appendPaintTag(tag) {
    const list = document.querySelector('.calendar-tag-paint-list');
    if (!list || !tag?.id || list.querySelector(`[data-calendar-tag-paint="${tag.id}"]`)) return null;
    const button = document.createElement('button');
    button.type = 'button';
    button.dataset.calendarTagPaint = String(tag.id);
    button.dataset.tagColor = tag.colorHex || '';
    button.style.setProperty('--tag-color', tag.colorHex || '');
    button.setAttribute('aria-pressed', 'false');
    const dot = document.createElement('span');
    dot.setAttribute('aria-hidden', 'true');
    button.append(dot, document.createTextNode(tag.name || ''));
    button.addEventListener('click', () => {
      selectedPaintTagId = button.dataset.calendarTagPaint;
      document.querySelectorAll('[data-calendar-tag-paint]').forEach(option => {
        option.classList.toggle('is-selected', option === button);
        option.setAttribute('aria-pressed', option === button ? 'true' : 'false');
      });
    });
    list.insertBefore(button, list.querySelector('[data-paint-tag-toggle]'));
    return button;
  }

  const paintTagEditor = document.querySelector('[data-paint-tag-create]');
  const paintTagToggle = document.querySelector('[data-paint-tag-toggle]');
  paintTagToggle?.addEventListener('click', () => {
    if (!paintTagEditor) return;
    selectedPaintTagId = null;
    document.querySelectorAll('[data-calendar-tag-paint]').forEach(option => { option.classList.remove('is-selected'); option.setAttribute('aria-pressed', 'false'); });
    syncCalendarViewport();
    paintTagEditor.hidden = false;
    paintTagToggle.hidden = true;
    window.requestAnimationFrame(syncMobileWorkBar);
    paintTagEditor.querySelector('[data-paint-tag-name]')?.focus({ preventScroll: true });
  });
  document.querySelector('[data-paint-tag-cancel]')?.addEventListener('click', () => {
    if (paintTagEditor) paintTagEditor.hidden = true;
    if (paintTagToggle) paintTagToggle.hidden = false;
    window.requestAnimationFrame(syncMobileWorkBar);
  });
  document.querySelector('[data-paint-tag-submit]')?.addEventListener('click', async event => {
    const nameInput = paintTagEditor?.querySelector('[data-paint-tag-name]');
    const colorInput = paintTagEditor?.querySelector('[data-paint-tag-color]:checked');
    const error = paintTagEditor?.querySelector('[data-paint-tag-error]');
    const token = document.querySelector('input[name="_csrf_token"]')?.value;
    if (!nameInput || !colorInput || !token) return;
    const body = new FormData();
    body.set('_csrf_token', token);
    body.set('name', nameInput.value.trim());
    body.set('palette_id', colorInput.value);
    event.currentTarget.disabled = true;
    try {
      const response = await fetch('/tags', { method:'POST', body, headers:{ Accept:'application/json', 'X-Requested-With':'XMLHttpRequest' } });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.tag) throw new Error(payload.message || '태그를 추가하지 못했습니다.');
      document.querySelectorAll('[data-calendar-tag-group]').forEach(group => appendTagOption(group, payload.tag, false));
      appendPaintTag(payload.tag)?.click();
      removeUsedQuickTagPalette(colorInput.value);
      nameInput.value = '';
      if (error) error.hidden = true;
      paintTagEditor.hidden = true;
      window.requestAnimationFrame(syncMobileWorkBar);
      if (paintTagToggle && document.querySelector('[data-paint-tag-color]')) paintTagToggle.hidden = false;
      showCalendarToast(payload.message || '태그가 추가되었습니다.');
    } catch (requestError) {
      if (error) { error.textContent = requestError.message || '태그를 추가하지 못했습니다.'; error.hidden = false; }
    } finally { event.currentTarget.disabled = false; }
  });

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
        appendPaintTag(payload.tag);
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

  editStartInput.addEventListener('change', () => {
    const start = Number.parseInt(editStartInput.value, 10);
    const end = Number.parseInt(editEndInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      editEndInput.value = String(Math.min(start + 6, 144));
    }
  });

  editEndInput.addEventListener('change', () => {
    const start = Number.parseInt(editStartInput.value, 10);
    const end = Number.parseInt(editEndInput.value, 10);
    if (Number.isInteger(start) && (!Number.isInteger(end) || end <= start)) {
      editEndInput.value = String(Math.min(start + 1, 144));
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

  document.getElementById('calendarEventEditForm')?.addEventListener('submit', event => {
    if (editScheduleTypeInput.value !== 'timed') return;
    const start = Number.parseInt(editStartInput.value, 10);
    const end = Number.parseInt(editEndInput.value, 10);
    if (!Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end > 144 || end <= start) {
      event.preventDefault();
      showCalendarToast('시작시간보다 늦은 종료시간을 선택해주세요.');
      return;
    }
    if (hasOccupiedRange(start, end, '[data-event-open][data-event-start-index]', 'eventStartIndex', 'eventEndIndex', 'eventId', editEventId.value)) {
      event.preventDefault();
      showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
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

  actualLayer.addEventListener('click', event => {
      const button = event.target.closest('[data-event-open]');
      if (!button || rangeSavePending) return;
      if (calendarWorkMode === 'tag-paint') {
        paintActualEventTag(button);
        return;
      }
      if (calendarWorkMode === 'plan-placement') return;
      openEditSheet(button);
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
    if (event.key === 'Escape' && sourceDrag) {
      sourceDrag.source.classList.remove('is-dragging');
      sourceDrag = null;
      page.classList.remove('is-plan-source-dragging');
      hidePlanDragGuide();
      showCalendarToast('할 일 배치를 취소했습니다.');
      return;
    }
    if (event.key === 'Escape' && (actualSelection || actualResize || planCopyDrag)) {
      if (actualResize) actualResize.block.classList.remove('is-resizing');
      actualSelection = null;
      actualResize = null;
      planCopyDrag = null;
      page.classList.remove('is-plan-copy-dragging');
      clearActualPreview();
      clearPlanMovePreview();
      showCalendarToast('실제 일정 조작을 취소했습니다.');
      return;
    }
    if (event.key === 'Escape' && actualTouchSelection) {
      clearActualTouchSelection();
      showCalendarToast('실제 일정 조작을 취소했습니다.');
      return;
    }
    if (event.key === 'Escape' && planPlacement) {
      planPlacement = null;
      clearPlanPlacementPreview();
      hidePlanDragGuide();
      showCalendarToast('계획 블록 배치를 취소했습니다.');
      return;
    }
    if (event.key === 'Escape' && !layer.hidden) {
      const activeForm = [eventForm, document.getElementById('calendarEventEditForm')]
        .find(form => form && form.dataset.activeActualView && form.dataset.activeActualView !== 'main');
      if (activeForm) {
        restoreActualSelection(activeForm);
        showActualFormView(activeForm, 'main');
        return;
      }
      closePanels();
    }
  });

  if (window.visualViewport) {
    let viewportResizeTimer = null;

    window.visualViewport.addEventListener('resize', () => {
      syncCalendarViewport();
      window.clearTimeout(viewportResizeTimer);
      viewportResizeTimer = window.setTimeout(() => {
        const activeElement = document.activeElement;
        if (!(activeElement instanceof HTMLElement) || !layer.contains(activeElement)) {
          return;
        }

        // Fixed sheets own their layout through the visual-viewport CSS
        // variables. Do not scroll the document here: a visual-viewport
        // resize after selecting an event must not move the timetable.
      }, 80);
    });
  }
})();
