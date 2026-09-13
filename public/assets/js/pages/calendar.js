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
  const scheduleTypeInput = document.getElementById('calendarScheduleType');
  const sourceEventIdInput = document.getElementById('calendarSourceEventId');
  const createMemoInput = document.getElementById('calendarEventMemo');
  const selectedTime = document.getElementById('calendarSelectedTime');
  const editEventId = document.getElementById('calendarEditEventId');
  const editScheduleTypeInput = document.getElementById('calendarEditScheduleType');
  const deleteEventId = document.getElementById('calendarDeleteEventId');
  const editTitleInput = document.getElementById('calendarEditEventTitle');
  const editMemoInput = document.getElementById('calendarEditMemo');
  const actualLinkAction = document.getElementById('calendarActualLinkAction');
  const actualLinkChoice = document.querySelector('[data-actual-link-choice]');
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
  const planStartInput = document.getElementById('calendarPlanStartIndex');
  const planEndInput = document.getElementById('calendarPlanEndIndex');
  const planSelectedTime = document.getElementById('calendarPlanSelectedTime');
  const planEditItemId = document.getElementById('calendarPlanEditItemId');
  const planDeleteItemId = document.getElementById('calendarPlanDeleteItemId');
  const planEditTitle = document.getElementById('calendarPlanEditTitle');
  const planEditImportance = document.getElementById('calendarPlanEditImportance');
  const planEditGoal = document.getElementById('calendarPlanEditGoal');
  const planEditStart = document.getElementById('calendarPlanEditStartIndex');
  const planEditEnd = document.getElementById('calendarPlanEditEndIndex');
  const planEditTime = document.getElementById('calendarPlanEditSelectedTime');
  const planLinkAction = document.getElementById('calendarPlanLinkAction');
  const planLinkChoice = document.querySelector('[data-plan-link-choice]');
  const untimedAction = document.querySelector('.calendar-untimed-action');
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

  if (!page || !daygrid || !layer || !eventSheet || !eventEditSheet || !planItemSheet || !planItemEditSheet || !planSettingsSheet || !quickMemoSheet || !retrospectPreview || !eventForm || !titleInput || !startInput || !endInput || !scheduleTypeInput || !sourceEventIdInput || !createMemoInput || !selectedTime || !editEventId || !editScheduleTypeInput || !deleteEventId || !editTitleInput || !editMemoInput || !planLayer || !actualLayer || !timeGrid) {
    return;
  }

  function indexToTime(index) {
    const minutes = index * 10;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
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

  function openEventSheet(start, end, scheduleType = 'timed') {
    const isUnscheduled = scheduleType === 'unscheduled';
    scheduleTypeInput.value = isUnscheduled ? 'unscheduled' : 'timed';
    startInput.value = isUnscheduled ? '' : String(start);
    endInput.value = isUnscheduled ? '' : String(end);
    sourceEventIdInput.value = '';
    selectedTime.textContent = isUnscheduled ? '시간 미정' : `${indexToTime(start)} ~ ${indexToTime(end)}`;
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
    if (editPlanGroup) {
      editPlanGroup.hidden = scheduleType === 'unscheduled';
    }

    checkRadio(form, 'calendar_tag_id', button.dataset.eventTagId || '');
    checkRadio(form, 'daily_plan_item_id', button.dataset.eventDailyPlanItemId || '');
    actualLinkAction.value = 'keep';
    eventEditSheet.dataset.linked = button.dataset.eventDailyPlanItemId ? '1' : '0';
    actualLinkChoice.hidden = eventEditSheet.dataset.linked !== '1';

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
        label.classList.remove('is-disabled');
      }
    });

    openPanel(eventEditSheet);
    focusSheetInput(editTitleInput);
  }

  let calendarMode = 'actual';

  function setCalendarMode(mode) {
    calendarMode = mode === 'plan' ? 'plan' : 'actual';
    window.sessionStorage?.setItem('lifeflow.calendarMode', calendarMode);
    document.querySelectorAll('[data-calendar-mode]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.calendarMode === calendarMode ? 'true' : 'false');
    });
    planLayer.hidden = calendarMode !== 'plan';
    actualLayer.hidden = calendarMode !== 'actual';
    if (untimedAction) untimedAction.hidden = calendarMode !== 'actual';
    document.querySelector('.time-grid-toolbar span').textContent = calendarMode === 'plan'
      ? '빈 시간 칸을 길게 누른 뒤 드래그하면 오늘의 계획 일정을 추가할 수 있습니다.'
      : '빈 시간 칸을 길게 누른 뒤 드래그하면 실제 일정 범위를 선택할 수 있습니다.';
  }

  function openPlanItemSheet(start, end) {
    planStartInput.value = String(start);
    planEndInput.value = String(end);
    planSelectedTime.textContent = `${indexToTime(start)} ~ ${indexToTime(end)}`;
    planItemTitle.value = '';
    openPanel(planItemSheet);
    focusSheetInput(planItemTitle);
  }

  function openPlanItemEditSheet(button) {
    const start = Number(button.dataset.planItemStartIndex);
    const end = Number(button.dataset.planItemEndIndex);
    planEditItemId.value = button.dataset.planItemId || '';
    planDeleteItemId.value = button.dataset.planItemId || '';
    planEditTitle.value = button.dataset.planItemTitle || '';
    planEditImportance.value = button.dataset.planItemImportance || 'D';
    planEditGoal.value = button.dataset.planItemGoalId || '';
    planEditStart.value = String(start);
    planEditEnd.value = String(end);
    planEditTime.textContent = `${indexToTime(start)} ~ ${indexToTime(end)}`;
    planLinkAction.value = 'keep';
    planItemEditSheet.dataset.linked = button.dataset.planItemLinked || '0';
    planLinkChoice.hidden = planItemEditSheet.dataset.linked !== '1';
    openPanel(planItemEditSheet);
    focusSheetInput(planEditTitle);
  }

  timeGrid.create({
    grid: daygrid,
    ignoreSelector: '[data-event-open], [data-plan-item-open], button, input, select, textarea, a',
    onSelect({ start, end }) {
      if (calendarMode === 'plan') {
        openPlanItemSheet(start, end);
      } else {
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
  setCalendarMode(window.sessionStorage?.getItem('lifeflow.calendarMode') || 'actual');

  document.getElementById('calendarPlanItemDeleteForm')?.addEventListener('submit', async event => {
    if (event.currentTarget.dataset.confirmed === '1') return;
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
      event.currentTarget.dataset.confirmed = '1';
      event.currentTarget.requestSubmit();
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

  document.querySelectorAll('[data-unscheduled-open]').forEach(button => {
    button.addEventListener('click', () => openEventSheet(null, null, 'unscheduled'));
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

  eventForm.addEventListener('submit', event => {
    const sourceTab = sourceTabs?.querySelector('[data-event-source-tab][aria-selected="true"]')?.dataset.eventSourceTab;
    if (scheduleTypeInput.value === 'timed' && sourceTab === 'unscheduled' && !sourceEventIdInput.value) {
      event.preventDefault();
      window.LifeFlowToast?.show?.('시간을 배치할 일정을 선택해주세요.');
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
