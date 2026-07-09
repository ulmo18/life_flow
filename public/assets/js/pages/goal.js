(function () {
  const layer = document.querySelector('[data-goal-layer]');
  const sheets = Array.from(document.querySelectorAll('[data-goal-sheet]'));
  const ui = window.LifeFlowUI;

  if (!layer) {
    return;
  }

  function addMonths(date, months) {
    const next = new Date(date.getTime());
    next.setMonth(next.getMonth() + months);
    return next;
  }

  function addYears(date, years) {
    const next = new Date(date.getTime());
    next.setFullYear(next.getFullYear() + years);
    return next;
  }

  function formatDate(date) {
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0'),
    ].join('-');
  }

  function defaultEndDate(goalType, startDate) {
    const date = new Date(`${startDate}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    switch (goalType) {
      case 'yearly':
        return formatDate(addYears(date, 1));
      case 'half_year':
        return formatDate(addMonths(date, 6));
      case 'quarterly':
        return formatDate(addMonths(date, 3));
      case 'monthly':
        return formatDate(addMonths(date, 1));
      default:
        return '';
    }
  }

  function currentGoalType(form) {
    const checked = form ? form.querySelector('[data-goal-type-radio]:checked') : null;
    return checked ? checked.value : 'monthly';
  }

  function togglePeriodFields(form, shouldRecalculate) {
    const fields = form ? form.querySelector('[data-goal-period-fields]') : null;
    if (!fields) {
      return;
    }

    const goalType = currentGoalType(form);
    const isBucket = goalType === 'bucket';
    const startInput = fields.querySelector('[data-goal-start-date]');
    const endInput = fields.querySelector('[data-goal-end-date]');
    fields.classList.toggle('is-muted', isBucket);
    fields.querySelectorAll('input').forEach(input => {
      input.disabled = isBucket;
    });

    if (!isBucket && shouldRecalculate && startInput && endInput) {
      endInput.value = defaultEndDate(goalType, startInput.value) || endInput.value;
    }
  }

  function openSheet(name) {
    layer.hidden = false;
    sheets.forEach(sheet => {
      sheet.hidden = sheet.dataset.goalSheet !== name;
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

  document.querySelectorAll('[data-goal-open]').forEach(button => {
    button.addEventListener('click', () => openSheet(button.dataset.goalOpen || 'create'));
  });

  document.querySelectorAll('[data-goal-close]').forEach(button => {
    button.addEventListener('click', closeSheets);
  });

  document.querySelectorAll('.goal-form').forEach(form => {
    togglePeriodFields(form, false);

    form.querySelectorAll('[data-goal-type-radio]').forEach(radio => {
      radio.addEventListener('change', () => togglePeriodFields(form, true));
    });

    const startInput = form.querySelector('[data-goal-start-date]');
    if (startInput) {
      startInput.addEventListener('change', () => togglePeriodFields(form, true));
    }
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
