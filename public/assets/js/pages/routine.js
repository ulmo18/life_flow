(function () {
  const layer = document.querySelector('[data-routine-layer]');
  const sheets = Array.from(document.querySelectorAll('[data-routine-sheet]'));
  const toast = window.LifeFlowToast;
  const ui = window.LifeFlowUI;
  const routineState = window.LifeFlowRoutineState;

  if (!layer) {
    return;
  }

  function openSheet(name) {
    layer.hidden = false;
    sheets.forEach(sheet => {
      sheet.hidden = sheet.dataset.routineSheet !== name;
    });
    document.body.classList.add('is-ui-open');
  }

  function closeSheets(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    layer.hidden = true;
    sheets.forEach(sheet => {
      sheet.hidden = true;
    });
    document.body.classList.remove('is-ui-open');
  }

  function applyButtonState(button, state) {
    if (button.matches('.routine-state-control') && routineState) {
      routineState.apply(button, state);
      return;
    }

    const display = button.dataset.routineStateDisplay || 'symbol';
    if (display === 'label') {
      button.textContent = state === 'O' ? '완료' : (state === 'X' ? '미완료' : '미기록');
    } else if (display === 'period') {
      const marker = button.querySelector('i');
      if (marker) {
        marker.textContent = state === 'O' ? '●' : (state === 'X' ? '×' : '');
      }
    } else if (display === 'tracker') {
      const marker = button.querySelector('span');
      if (marker) {
        marker.textContent = state === 'O' ? '✓' : (state === 'X' ? '×' : '');
      }
    } else {
      button.textContent = state === '' ? ' ' : state;
    }
    button.classList.toggle('is-done', state === 'O');
    button.classList.toggle('is-failed', state === 'X');
    button.title = state === '' ? '미기록' : (state === 'O' ? '완료' : '미완료');
  }

  function updateRoutineSummary(routine) {
    if (!routine || !routine.id) {
      return;
    }

    document.querySelectorAll(`[data-routine-done-count="${routine.id}"]`).forEach(element => {
      element.textContent = String(routine.doneCount);
    });

    document.querySelectorAll(`[data-routine-progress-percent="${routine.id}"]`).forEach(element => {
      element.textContent = String(routine.progressPercent);
    });

    document.querySelectorAll(`[data-routine-progress-bar="${routine.id}"]`).forEach(element => {
      element.style.width = `${routine.progressPercent}%`;
    });

    document.querySelectorAll(`[data-routine-streak="${routine.id}"]`).forEach(element => {
      element.textContent = routine.streakLabel || '';
      element.hidden = !routine.streakLabel;
    });

  }

  function updatePageSummary(summary) {
    if (!summary) {
      return;
    }

    const values = [
      ['[data-routine-page-active]', summary.activeCount],
      ['[data-routine-page-rate]', summary.weekAchievementRate],
      ['[data-routine-page-done]', summary.weekDoneCount],
      ['[data-routine-page-total]', summary.weekTotalCount],
      ['[data-routine-page-streak]', summary.streakCount],
    ];
    values.forEach(([selector, value]) => {
      document.querySelectorAll(selector).forEach(element => {
        element.textContent = String(value ?? 0);
      });
    });
  }

  async function submitToggle(form) {
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
      `[data-routine-state-button][data-routine-id="${payload.routineId}"][data-routine-date="${payload.date}"]`
    ).forEach(button => {
      applyButtonState(button, payload.state || '');
    });

    document.querySelectorAll(
      `[data-routine-state-cell][data-routine-id="${payload.routineId}"][data-routine-date="${payload.date}"]`
    ).forEach(cell => {
      cell.classList.toggle('is-done', payload.state === 'O');
      cell.classList.toggle('is-failed', payload.state === 'X');
      const stateLabel = payload.state === 'O' ? '완료' : (payload.state === 'X' ? '미완료' : '미기록');
      cell.title = `${payload.date} ${stateLabel}`;
      cell.setAttribute('aria-label', cell.matches('button') ? `오늘 루틴 상태 변경, ${payload.date} ${stateLabel}` : `${payload.date} ${stateLabel}`);
    });

    updateRoutineSummary(payload.routine);
    updatePageSummary(payload.pageSummary);

    if (toast && typeof toast.show === 'function') {
      toast.show(payload.message || '루틴 상태를 변경했습니다.');
    }
  }

  document.querySelectorAll('[data-routine-open]').forEach(button => {
    button.addEventListener('click', () => openSheet(button.dataset.routineOpen || 'create'));
  });

  document.querySelectorAll('[data-routine-close]').forEach(button => {
    button.addEventListener('click', closeSheets);
  });

  document.querySelectorAll('[data-routine-toggle-form]').forEach(form => {
    form.addEventListener('submit', event => {
      event.preventDefault();

      submitToggle(form).catch(() => {
        form.submit();
      });
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    let allowSubmit = false;

    form.addEventListener('submit', async event => {
      if (allowSubmit) {
        return;
      }

      event.preventDefault();
      const confirmed = ui && typeof ui.confirm === 'function'
        ? await ui.confirm({
            title: '확인',
            message: form.dataset.confirm || '계속 진행할까요?',
            confirmText: '진행',
            cancelText: '취소',
          })
        : confirm(form.dataset.confirm || '계속 진행할까요?');

      if (confirmed) {
        allowSubmit = true;
        form.requestSubmit();
      }
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !layer.hidden) {
      closeSheets(event);
    }
  });
})();
