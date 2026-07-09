(function () {
  const page = document.querySelector('.calendar-page');
  const daygrid = document.getElementById('daygrid');
  const layer = document.querySelector('[data-calendar-layer]');
  const eventSheet = document.querySelector('[data-event-sheet]');
  const eventEditSheet = document.querySelector('[data-event-edit-sheet]');
  const planSettingsSheet = document.querySelector('[data-plan-settings-sheet]');
  const routinePopup = document.querySelector('[data-routine-popup]');
  const retrospectPreview = document.querySelector('[data-retrospect-preview]');
  const eventForm = document.getElementById('calendarEventForm');
  const titleInput = document.getElementById('calendarEventTitle');
  const startInput = document.getElementById('calendarStartIndex');
  const endInput = document.getElementById('calendarEndIndex');
  const selectedTime = document.getElementById('calendarSelectedTime');
  const editEventId = document.getElementById('calendarEditEventId');
  const deleteEventId = document.getElementById('calendarDeleteEventId');
  const editTitleInput = document.getElementById('calendarEditEventTitle');
  const editMemoInput = document.getElementById('calendarEditMemo');
  const planPicker = document.querySelector('.calendar-plan-picker');
  const ui = window.LifeFlowUI;
  const SCROLL_LANE_WIDTH = 26;

  if (!page || !daygrid || !layer || !eventSheet || !eventEditSheet || !planSettingsSheet || !routinePopup || !retrospectPreview || !eventForm || !titleInput || !startInput || !endInput || !selectedTime || !editEventId || !deleteEventId || !editTitleInput || !editMemoInput) {
    return;
  }

  let isDragging = false;
  let startIndex = null;
  let endIndex = null;
  let selectedCells = [];
  let pendingPointerId = null;

  function clearSelection() {
    selectedCells.forEach(cell => cell.classList.remove('selecting'));
    selectedCells = [];
  }

  function isInScrollLane(clientX) {
    const rect = (daygrid.closest('.daygrid-wrap') || daygrid).getBoundingClientRect();
    return clientX >= rect.right - SCROLL_LANE_WIDTH;
  }

  function cancelPendingDrag() {
    pendingPointerId = null;
  }

  function beginDragSelection(pointerId) {
    if (pendingPointerId !== pointerId || startIndex === null) {
      return;
    }

    cancelPendingDrag();
    isDragging = true;
    selectRange(startIndex, endIndex);

    try {
      daygrid.setPointerCapture(pointerId);
    } catch (error) {
      // Pointer capture can fail if WebView has already handed the gesture to scrolling.
    }
  }

  function cellFromPoint(x, y) {
    let el = document.elementFromPoint(x, y);
    if (el && el.classList.contains('cell')) {
      return el;
    }

    const hiddenElements = [];

    while (el instanceof HTMLElement && hiddenElements.length < 8) {
      const previousPointerEvents = el.style.pointerEvents;
      hiddenElements.push([el, previousPointerEvents]);
      el.style.pointerEvents = 'none';

      el = document.elementFromPoint(x, y);
      if (el && el.classList.contains('cell')) {
        break;
      }
    }

    hiddenElements.forEach(([element, previousPointerEvents]) => {
      element.style.pointerEvents = previousPointerEvents;
    });

    return el && el.classList.contains('cell') ? el : null;
  }

  function selectRange(start, end) {
    clearSelection();

    const min = Math.min(start, end);
    const max = Math.max(start, end);

    for (let i = min; i <= max; i++) {
      const cell = daygrid.querySelector(`.cell[data-index="${i}"]`);
      if (cell) {
        cell.classList.add('selecting');
        selectedCells.push(cell);
      }
    }
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
    routinePopup.hidden = panel !== routinePopup;
    retrospectPreview.hidden = panel !== retrospectPreview;
    document.body.classList.add('is-ui-open');
  }

  function closePanels(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    layer.hidden = true;
    eventSheet.hidden = true;
    eventEditSheet.hidden = true;
    planSettingsSheet.hidden = true;
    routinePopup.hidden = true;
    retrospectPreview.hidden = true;
    document.body.classList.remove('is-ui-open');
  }

  function openEventSheet(start, end) {
    startInput.value = String(start);
    endInput.value = String(end);
    selectedTime.textContent = `${indexToTime(start)} ~ ${indexToTime(end)}`;
    titleInput.value = '';

    const noneOption = eventForm.querySelector('input[name="plan_template_id"][value=""]');
    if (noneOption) {
      noneOption.checked = true;
    }

    const emptyTagOption = eventForm.querySelector('input[name="calendar_tag_id"][value=""]');
    if (emptyTagOption) {
      emptyTagOption.checked = true;
    }

    openPanel(eventSheet);
    setTimeout(() => titleInput.focus(), 0);
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
    editEventId.value = eventId;
    deleteEventId.value = eventId;
    editTitleInput.value = button.dataset.eventTitle || '';
    editMemoInput.value = button.dataset.eventMemo || '';

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
    setTimeout(() => editTitleInput.focus(), 0);
  }

  daygrid.addEventListener('pointerdown', event => {
    if (isInScrollLane(event.clientX)) {
      return;
    }

    const cell = cellFromPoint(event.clientX, event.clientY);
    if (!cell) return;

    const startedOnActualEvent = Boolean(event.target.closest('[data-event-open]'));
    if (startedOnActualEvent) {
      return;
    }

    cancelPendingDrag();
    isDragging = false;

    startIndex = Number(cell.dataset.index);
    endIndex = startIndex;
    pendingPointerId = event.pointerId;

    beginDragSelection(event.pointerId);
  });

  daygrid.addEventListener('pointermove', event => {
    if (!isDragging) return;

    const cell = cellFromPoint(event.clientX, event.clientY);
    if (!cell) return;

    endIndex = Number(cell.dataset.index);
    selectRange(startIndex, endIndex);
  });

  daygrid.addEventListener('pointerup', () => {
    if (!isDragging) {
      cancelPendingDrag();
      clearSelection();
      return;
    }

    isDragging = false;
    cancelPendingDrag();

    const start = Math.min(startIndex, endIndex);
    const end = Math.max(startIndex, endIndex) + 1;
    clearSelection();
    openEventSheet(start, end);
  });

  daygrid.addEventListener('pointercancel', () => {
    cancelPendingDrag();
    isDragging = false;
    clearSelection();
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

  document.querySelectorAll('[data-event-open]').forEach(button => {
    button.addEventListener('click', () => {
      openEditSheet(button);
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
})();
