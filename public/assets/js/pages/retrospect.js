(function () {
  const settingsLayer = document.querySelector('[data-retrospect-settings-layer]');
  const dateLayer = document.querySelector('[data-retrospect-date-layer]');
  const openSettingsButton = document.querySelector('[data-retrospect-open-settings]');
  const openDateButton = document.querySelector('[data-retrospect-open-date]');
  const routineState = window.LifeFlowRoutineState;

  function openLayer(layer) {
    if (!layer) return;

    layer.hidden = false;
    document.body.classList.add('is-ui-open');
  }

  function closeLayer(layer, event) {
    if (event) {
      event.preventDefault();
    }

    if (!layer) return;

    layer.hidden = true;
    if ((!settingsLayer || settingsLayer.hidden) && (!dateLayer || dateLayer.hidden)) {
      document.body.classList.remove('is-ui-open');
    }
  }

  openSettingsButton?.addEventListener('click', () => openLayer(settingsLayer));
  openDateButton?.addEventListener('click', () => openLayer(dateLayer));

  document.querySelectorAll('[data-retrospect-close-settings]').forEach(button => {
    button.addEventListener('click', event => closeLayer(settingsLayer, event));
  });

  document.querySelectorAll('[data-retrospect-close-date]').forEach(button => {
    button.addEventListener('click', event => closeLayer(dateLayer, event));
  });

  function updateRoutineScore() {
    const buttons = Array.from(document.querySelectorAll('[data-retrospect-routine-state]'));
    const done = buttons.filter(button => button.dataset.state === 'O').length;
    const score = document.querySelector('[data-routine-score]');
    const rate = document.querySelector('[data-routine-rate]');
    if (score) {
      score.textContent = `${done}/${buttons.length}`;
    }
    if (rate) {
      rate.textContent = `${buttons.length > 0 ? Math.round((done / buttons.length) * 100) : 0}%`;
    }
  }

  document.querySelectorAll('[data-retrospect-autosize]').forEach(textarea => {
    const resize = () => {
      textarea.style.height = 'auto';
      textarea.style.height = `${Math.min(textarea.scrollHeight, 280)}px`;
    };

    textarea.addEventListener('input', resize);
    resize();
  });

  document.querySelectorAll('[data-retrospect-more]').forEach(button => {
    button.addEventListener('click', () => {
      const list = button.previousElementSibling;
      if (!list?.matches('[data-retrospect-record-list]')) {
        return;
      }

      const isCollapsed = list.classList.toggle('is-collapsed');
      button.textContent = isCollapsed ? '전체 보기' : '접기';
      button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    });
    button.previousElementSibling?.classList.add('is-collapsed');
    button.classList.add('is-ready');
    button.setAttribute('aria-expanded', 'false');
  });

  document.querySelectorAll('[data-retrospect-routine-toggle-form]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();

      try {
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
          throw new Error(payload.message || '루틴 상태를 변경할 수 없습니다.');
        }

        const button = form.querySelector('[data-routine-toggle-control]');
        const state = payload.state || '';
        if (routineState) {
          routineState.apply(button, state);
        }
        if (button?.matches('[data-retrospect-routine-state]')) {
          updateRoutineScore();
        }
        if (payload.routine?.id) {
          document.querySelectorAll(`[data-retrospect-history-done="${payload.routine.id}"]`).forEach(element => {
            element.textContent = String(payload.routine.doneCount ?? 0);
          });
          document.querySelectorAll(`[data-retrospect-history-rate="${payload.routine.id}"]`).forEach(element => {
            element.textContent = String(payload.routine.progressPercent ?? 0);
          });
        }

        window.LifeFlowToast?.show(payload.message || '루틴 상태를 변경했습니다.');
      } catch (error) {
        form.submit();
      }
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', async event => {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      event.preventDefault();

      const accepted = window.LifeFlowUI?.confirm
        ? await window.LifeFlowUI.confirm({
            title: '회고 재발행',
            message: form.dataset.confirm || '계속 진행할까요?',
            confirmText: '재발행',
            cancelText: '취소',
          })
        : confirm(form.dataset.confirm || '계속 진행할까요?');

      if (!accepted) {
        return;
      }

      form.dataset.confirmed = '1';
      form.requestSubmit();
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') {
      return;
    }

    closeLayer(settingsLayer, event);
    closeLayer(dateLayer, event);
  });
})();
