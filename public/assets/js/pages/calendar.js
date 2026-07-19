(function () {
  const page = document.querySelector('.calendar-page');
  const daygrid = document.getElementById('daygrid');
  const layer = document.querySelector('[data-calendar-layer]');
  const eventSheet = document.querySelector('[data-event-sheet]');
  const eventEditSheet = document.querySelector('[data-event-edit-sheet]');
  const planSettingsSheet = document.querySelector('[data-plan-settings-sheet]');
  const quickMemoSheet = document.querySelector('[data-quick-memo-sheet]');
  const routinePopup = document.querySelector('[data-routine-popup]');
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
  const timeGrid = window.LifeFlowTimeGrid;
  const ui = window.LifeFlowUI;

  if (!page || !daygrid || !layer || !eventSheet || !eventEditSheet || !planSettingsSheet || !quickMemoSheet || !routinePopup || !retrospectPreview || !eventForm || !titleInput || !startInput || !endInput || !scheduleTypeInput || !sourceEventIdInput || !createMemoInput || !selectedTime || !editEventId || !editScheduleTypeInput || !deleteEventId || !editTitleInput || !editMemoInput || !timeGrid) {
    return;
  }

  function indexToTime(index) {
    const minutes = index * 10;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }

  function openPanel(panel) {
    layer.hidden = false;
    eventSheet.hidden = panel !== eventSheet;
    eventEditSheet.hidden = panel !== eventEditSheet;
    planSettingsSheet.hidden = panel !== planSettingsSheet;
    quickMemoSheet.hidden = panel !== quickMemoSheet;
    routinePopup.hidden = panel !== routinePopup;
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
    planSettingsSheet.hidden = true;
    quickMemoSheet.hidden = true;
    routinePopup.hidden = true;
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

    const noneOption = eventForm.querySelector('input[name="plan_template_id"][value=""]');
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
    checkRadio(form, 'plan_template_id', button.dataset.eventPlanTemplateId || '');

    const currentPlanId = button.dataset.eventPlanTemplateId || '';
    form.querySelectorAll('[data-plan-option]').forEach(label => {
      const input = label.querySelector('input[name="plan_template_id"]');
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

  timeGrid.create({
    grid: daygrid,
    ignoreSelector: '[data-event-open], button, input, select, textarea, a',
    onSelect({ start, end }) {
      openEventSheet(start, end);
    },
  });

  document.querySelectorAll('[data-calendar-close]').forEach(button => {
    button.addEventListener('click', closePanels);
  });

  document.querySelectorAll('[data-routine-open]').forEach(button => {
    button.addEventListener('click', () => openPanel(routinePopup));
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

  if (planPicker) {
    const select = planPicker.querySelector('select[name="plan_group_id"]');
    let allowSubmit = false;

    select?.addEventListener('change', async () => {
      if (allowSubmit) {
        return;
      }

      if (planPicker.dataset.hasLinkedEvents === '1') {
        const confirmed = ui && typeof ui.confirm === 'function'
          ? await ui.confirm({
              title: '계획 일정 변경',
              message: '계획 일정을 변경하면 기존 실제 일정의 계획 연결이 모두 해제됩니다. 계속할까요?',
              confirmText: '변경',
              cancelText: '취소',
            })
          : confirm('계획 일정을 변경하면 기존 실제 일정의 계획 연결이 모두 해제됩니다. 계속할까요?');

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
    button.textContent = state === '' ? ' ' : state;
    button.classList.toggle('is-done', state === 'O');
    button.classList.toggle('is-failed', state === 'X');
    button.title = state === '' ? '미기록' : (state === 'O' ? '완료' : '미완료');
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
