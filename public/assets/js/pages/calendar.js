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
  const createEnergyCustomValue = document.getElementById('calendarCreateEnergyCustomValue');
  const editEnergyCustomValue = document.getElementById('calendarEditEnergyCustomValue');
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

    if (page.dataset.calendarDate !== currentTime.date) {
      page.dataset.currentIndex = '';
      return;
    }

    document.querySelectorAll(`.daygrid .cell[data-index="${currentTime.index}"]`).forEach(cell => cell.classList.add('current-cell'));
    page.dataset.currentIndex = String(currentTime.index);
  }

  function focusCurrentTimeOnce() {
    if (page.dataset.currentTimeFocused === '1' || window.location.hash || window.scrollY > 24) return;

    const navigation = window.performance?.getEntriesByType?.('navigation')?.[0];
    if (navigation?.type === 'back_forward') return;

    const currentTime = getUserCurrentTime();
    if (page.dataset.calendarDate !== currentTime.date) return;

    const currentCell = daygrid.querySelector('.cell.current-cell');
    if (!currentCell) return;

    page.dataset.currentTimeFocused = '1';
    const targetTop = currentCell.getBoundingClientRect().top + window.scrollY - (window.innerHeight * .42);
    window.scrollTo({
      top: Math.max(0, targetTop),
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
    });
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
    if (planBlockEditSheet) planBlockEditSheet.hidden = true;
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
    checkRadio(eventForm, 'execution_energy_type', 'consume');
    checkRadio(eventForm, 'execution_energy_amount', '25');
    if (createEnergyCustomValue) createEnergyCustomValue.value = '';
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

    const energyType = form.querySelector('input[name="execution_energy_type"]:checked')?.value || 'none';
    const energyAmount = form.querySelector('input[name="execution_energy_amount"]:checked')?.value || '25';
    const customValue = form.querySelector('input[name="execution_energy_custom_value"]');
    const amountPanel = form.querySelector('[data-energy-amount-panel]');
    const customField = form.querySelector('[data-energy-custom]');
    const energyMain = form.querySelector('[data-energy-summary-main]');
    const energyFields = form.querySelector('[data-energy-fields]');
    const energyHint = form.querySelector('[data-energy-hint]');
    const customPrefix = form.querySelector('[data-energy-custom-prefix]');
    const hasChange = energyType === 'consume' || energyType === 'recover';
    const isCustom = energyAmount === 'custom';
    const sign = energyType === 'recover' ? '+' : '−';
    if (energyFields) energyFields.dataset.energyDirection = energyType;
    if (amountPanel) amountPanel.hidden = !hasChange;
    if (customField) customField.hidden = !hasChange || !isCustom;
    form.querySelectorAll('[data-energy-amount-option]').forEach(option => {
      const value = option.dataset.energyAmountValue || '';
      option.textContent = `${sign}${value}`;
      const input = option.previousElementSibling;
      if (input instanceof HTMLInputElement) {
        input.setAttribute('aria-label', `실행력 ${value} ${energyType === 'recover' ? '증가' : '감소'}`);
      }
    });
    if (customPrefix) customPrefix.textContent = sign;
    if (customValue) {
      customValue.disabled = !hasChange || !isCustom;
      customValue.required = hasChange && isCustom;
      customValue.setAttribute('aria-label', `실행력 ${energyType === 'recover' ? '증가' : '감소'} 변화량 직접 입력`);
    }
    if (energyMain) {
      const value = isCustom ? customValue?.value : energyAmount;
      energyMain.textContent = hasChange ? `${sign}${value || '…'}` : '0';
      energyMain.classList.toggle('is-consume', energyType === 'consume');
      energyMain.classList.toggle('is-recover', energyType === 'recover');
      energyMain.classList.toggle('is-none', !hasChange);
      energyMain.setAttribute('aria-label', hasChange && value
        ? `실행력 ${value} ${energyType === 'recover' ? '회복' : '사용'}`
        : (hasChange ? '실행력 변화량 입력 필요' : '실행력 변화 없음'));
    }
    if (energyHint) {
      const value = isCustom ? customValue?.value : energyAmount;
      energyHint.textContent = !hasChange
        ? '이 일정은 실행력에 영향을 주지 않습니다.'
        : value
          ? `이 일정은 실행력을 ${sign}${value} ${energyType === 'recover' ? '회복합니다.' : '사용합니다.'}`
          : '변화량을 입력하면 실행력 변화를 확인할 수 있습니다.';
    }
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
    const presetValues = ['10', '25', '50', '75', '100'];
    checkRadio(form, 'execution_energy_type', savedEnergyType);
    checkRadio(
      form,
      'execution_energy_amount',
      savedEnergyType === 'none' ? '25' : (presetValues.includes(savedEnergyValue) ? savedEnergyValue : 'custom')
    );
    if (editEnergyCustomValue) {
      editEnergyCustomValue.value = savedEnergyType !== 'none' && !presetValues.includes(savedEnergyValue)
        ? savedEnergyValue
        : '';
    }
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

  function setCalendarMode(mode, syncUrl = false) {
    calendarMode = mode === 'plan' ? 'plan' : 'actual';
    page.dataset.calendarMode = calendarMode;
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
    planLayer.classList.remove('is-background');
    actualLayer.hidden = false;
    document.querySelectorAll('[data-calendar-empty-mode]').forEach(prompt => {
      prompt.hidden = prompt.dataset.calendarEmptyMode !== calendarMode;
    });
    if (syncUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set('mode', calendarMode);
      window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
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

  function openPlanItemEditSheet(button) {
    if (button.dataset.planBlockId && planBlockEditSheet) {
      openPlanBlockEditSheet(button);
      return;
    }
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

  function openPlanBlockEditSheet(button) {
    const itemId = planBlockEditSheet.querySelector('#calendarPlanBlockItemId');
    const blockId = planBlockEditSheet.querySelector('#calendarPlanBlockId');
    const deleteId = planBlockEditSheet.querySelector('#calendarPlanBlockDeleteId');
    const start = planBlockEditSheet.querySelector('#calendarPlanBlockStart');
    const end = planBlockEditSheet.querySelector('#calendarPlanBlockEnd');
    const title = planBlockEditSheet.querySelector('[data-plan-block-title]');
    if (!itemId || !blockId || !deleteId || !start || !end) return;
    itemId.value = button.dataset.planItemId || '';
    blockId.value = button.dataset.planBlockId || '';
    deleteId.value = button.dataset.planBlockId || '';
    start.value = button.dataset.planItemStartIndex || '0';
    end.value = button.dataset.planItemEndIndex || '1';
    if (title) title.textContent = button.dataset.planItemTitle || '';
    openPanel(planBlockEditSheet);
  }

  let selectedPlanBlockSource = null;
  let sourceDrag = null;
  let planPlacement = null;

  function clearPlanPlacementPreview() {
    daygrid.querySelectorAll('.plan-placement-preview').forEach(cell => cell.classList.remove('plan-placement-preview'));
  }

  function clearPlanResizePreview() {
    daygrid.querySelectorAll('.plan-resize-preview').forEach(cell => cell.classList.remove('plan-resize-preview'));
  }

  function previewPlanResize(start, end) {
    clearPlanResizePreview();
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
  }

  function beginPlanPlacement(source, startIndex) {
    planPlacement = { source, startIndex, endIndex: startIndex };
    previewPlanPlacement();
    showCalendarToast('포인터를 움직여 길이를 정한 뒤 다시 클릭하면 계획 블록이 저장됩니다.');
  }

  function commitPlanPlacement() {
    if (!planPlacement) return;
    const placement = planPlacement;
    planPlacement = null;
    clearPlanPlacementPreview();
    const start = Math.min(placement.startIndex, placement.endIndex);
    const end = Math.max(placement.startIndex, placement.endIndex) + 1;
    if (hasOccupiedRange(start, end, '[data-plan-item-open]', 'planItemStartIndex', 'planItemEndIndex')) {
      showCalendarToast('이미 등록된 계획 블록과 시간이 겹칩니다.');
      return;
    }
    submitPlanBlock(placement.source.dataset.planItemId || '', start, end);
  }

  function submitPlanBlock(itemId, start, end) {
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!itemId || !token) return;
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/calendar/plan-block';
    [['_csrf_token', token], ['date', page.dataset.calendarDate || ''], ['daily_plan_item_id', itemId], ['start_index', start], ['end_index', end]].forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = String(value); form.append(input);
    });
    document.body.append(form);
    form.submit();
  }

  function submitPlanBlockUpdate(itemId, blockId, start, end) {
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!itemId || !blockId || !token) return;
    const form = document.createElement('form');
    form.method = 'post'; form.action = '/calendar/plan-block/update';
    [['_csrf_token', token], ['date', page.dataset.calendarDate || ''], ['daily_plan_item_id', itemId], ['daily_plan_block_id', blockId], ['start_index', start], ['end_index', end]].forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = String(value); form.append(input);
    });
    document.body.append(form);
    form.submit();
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

  daygrid.addEventListener('pointerdown', event => {
    if (!planPlacement) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    planPlacement.endIndex = index;
    commitPlanPlacement();
  }, true);
  document.addEventListener('pointermove', event => {
    if (!planPlacement) return;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    planPlacement.endIndex = index;
    previewPlanPlacement();
  });
  function finishPlanSourceDrag(event) {
    if (!sourceDrag || sourceDrag.pointerId !== event.pointerId) return;
    const drag = sourceDrag;
    sourceDrag = null;
    const index = gridIndexFromPoint(event.clientX, event.clientY);
    if (index === null) return;
    beginPlanPlacement(drag.source, index);
  }
  document.addEventListener('pointerup', finishPlanSourceDrag);

  let blockResize = null;
  let suppressPlanBlockClick = false;
  planLayer.addEventListener('pointerdown', event => {
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
  }

  function previewActualRange(start, end, className) {
    clearActualPreview();
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
    const token = document.querySelector('input[name="_csrf_token"]')?.value || '';
    if (!eventId || !token) return;
    const form = document.createElement('form');
    form.method = 'post'; form.action = '/calendar/event/time-range';
    [['_csrf_token', token], ['date', page.dataset.calendarDate || ''], ['event_id', eventId], ['start_index', start], ['end_index', end]].forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = String(value); form.append(input);
    });
    document.body.append(form);
    form.submit();
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
    form.submit();
  }

  let actualSelection = null;
  let actualResize = null;
  let planCopyDrag = null;
  let suppressActualEventClick = false;

  actualDaygrid?.addEventListener('pointerdown', event => {
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

  planLayer.addEventListener('pointerdown', event => {
    if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
    if (event.target.closest('[data-plan-block-resize]')) return;
    const block = event.target.closest('[data-plan-block-id]');
    const blockId = block?.dataset.planBlockId || '';
    const start = Number.parseInt(block?.dataset.planItemStartIndex || '', 10);
    const end = Number.parseInt(block?.dataset.planItemEndIndex || '', 10);
    if (!block || !blockId || !Number.isInteger(start) || !Number.isInteger(end) || end <= start) return;
    planCopyDrag = { pointerId: event.pointerId, block, blockId, duration: end - start, lastStart: null };
    try { block.setPointerCapture(event.pointerId); } catch (error) { /* Document listeners remain the fallback. */ }
  });
  document.addEventListener('pointermove', event => {
    if (!planCopyDrag || planCopyDrag.pointerId !== event.pointerId) return;
    const index = actualGridIndexFromPoint(event.clientX, event.clientY);
    if (index === null || index + planCopyDrag.duration > 144) {
      planCopyDrag.lastStart = null;
      clearActualPreview();
      return;
    }
    planCopyDrag.lastStart = index;
    previewActualRange(index, index + planCopyDrag.duration, 'actual-copy-preview');
  });
  document.addEventListener('pointerup', event => {
    if (!planCopyDrag || planCopyDrag.pointerId !== event.pointerId) return;
    const copy = planCopyDrag;
    planCopyDrag = null;
    clearActualPreview();
    try { copy.block.releasePointerCapture(event.pointerId); } catch (error) { /* Capture may already be released. */ }
    if (copy.lastStart === null) return;
    const end = copy.lastStart + copy.duration;
    suppressPlanBlockClick = true;
    window.setTimeout(() => { suppressPlanBlockClick = false; }, 0);
    if (hasActualConflict(copy.lastStart, end)) {
      showCalendarToast('이미 등록된 실제 일정과 시간이 겹칩니다.');
      return;
    }
    submitPlanBlockCopy(copy.blockId, copy.lastStart, end);
  });
  document.addEventListener('pointercancel', event => {
    if (planCopyDrag?.pointerId === event.pointerId) {
      planCopyDrag = null;
      clearActualPreview();
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
    button.addEventListener('click', () => setCalendarMode(button.dataset.calendarMode, true));
  });
  document.querySelectorAll('[data-plan-item-open]').forEach(button => {
    button.addEventListener('click', () => openPlanItemEditSheet(button));
  });
  document.querySelectorAll('[data-plan-block-source]').forEach(button => {
    button.addEventListener('pointerdown', event => {
      if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
      sourceDrag = { pointerId: event.pointerId, source: button };
      try { button.setPointerCapture(event.pointerId); } catch (error) { /* Pointer capture is optional. */ }
    });
    button.addEventListener('pointerup', finishPlanSourceDrag);
    button.addEventListener('pointercancel', event => {
      if (sourceDrag?.pointerId === event.pointerId) sourceDrag = null;
    });
    button.addEventListener('click', () => {
      selectedPlanBlockSource = button;
      document.querySelectorAll('[data-plan-block-source]').forEach(source => source.classList.toggle('is-selected', source === button));
      showCalendarToast(`${button.dataset.planTitle || '할 일'}을(를) 선택했습니다. 시간표에서 범위를 드래그하세요.`);
    });
  });
  document.querySelector('[data-untimed-plan-toggle]')?.addEventListener('click', event => {
    const button = event.currentTarget;
    const expanded = button.getAttribute('aria-expanded') === 'true';
    document.querySelectorAll('[data-untimed-plan-extra]').forEach(item => { item.hidden = expanded; });
    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    button.setAttribute('aria-label', expanded ? '시간 미정 계획 펼치기' : '시간 미정 계획 접기');
    button.textContent = expanded ? '펼치기' : '접기';
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
  // The desktop timetable always exposes Plan and Actual together. Plan remains
  // the interactive grid in STEP 2; Actual-range creation stays in STEP 3.
  setCalendarMode('plan');

  if (actualDaygrid) {
    const planScroller = daygrid.closest('.calendar-timetable-panel');
    const actualScroller = actualDaygrid.closest('.calendar-timetable-panel');
    let syncingTimetableScroll = false;
    const syncScroll = (from, to) => {
      if (syncingTimetableScroll || !from || !to) return;
      syncingTimetableScroll = true;
      to.scrollTop = from.scrollTop;
      window.requestAnimationFrame(() => { syncingTimetableScroll = false; });
    };
    planScroller?.addEventListener('scroll', () => syncScroll(planScroller, actualScroller), { passive: true });
    actualScroller?.addEventListener('scroll', () => syncScroll(actualScroller, planScroller), { passive: true });
  }

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
        window.location.assign(payload.redirect || `/calendar?date=${encodeURIComponent(page.dataset.calendarDate)}&mode=${encodeURIComponent(calendarMode)}`);
      } catch (error) {
        window.LifeFlowToast?.show?.(error.message || '계획 일정을 삭제하지 못했습니다.');
      }
    }
  });


  startCurrentTimeCellUpdates();

  const scheduleCurrentTimeFocus = () => window.setTimeout(focusCurrentTimeOnce, 80);
  if (document.readyState === 'complete') scheduleCurrentTimeFocus();
  else window.addEventListener('load', scheduleCurrentTimeFocus, { once: true });

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
      openPanel(retrospectPreview);
    });
  });

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

  document.querySelectorAll('[data-event-open]').forEach(button => {
    button.addEventListener('click', () => {
      openEditSheet(button);
    });
  });

  document.querySelectorAll('[data-calendar-date-picker]').forEach(input => {
    input.addEventListener('change', () => {
      if (/^\d{4}-\d{2}-\d{2}$/.test(input.value)) {
        window.location.href = `/calendar?date=${encodeURIComponent(input.value)}&mode=${encodeURIComponent(calendarMode)}`;
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
      window.location.href = `/calendar?date=${encodeURIComponent(targetDate)}&mode=${encodeURIComponent(calendarMode)}`;
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
    if (event.key === 'Escape' && (actualSelection || actualResize || planCopyDrag)) {
      if (actualResize) actualResize.block.classList.remove('is-resizing');
      actualSelection = null;
      actualResize = null;
      planCopyDrag = null;
      clearActualPreview();
      showCalendarToast('실제 일정 조작을 취소했습니다.');
      return;
    }
    if (event.key === 'Escape' && planPlacement) {
      planPlacement = null;
      clearPlanPlacementPreview();
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

        const viewportBottom = window.visualViewport.offsetTop + window.visualViewport.height;
        const inputBottom = activeElement.getBoundingClientRect().bottom;
        if (inputBottom > viewportBottom - 16) {
          activeElement.scrollIntoView({ block: 'center', inline: 'nearest' });
        }
      }, 80);
    });
  }
})();
