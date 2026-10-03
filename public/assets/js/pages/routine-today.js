(function () {
  const state = window.LifeFlowRoutineState;

  document.querySelectorAll('[data-routine-today-toggle]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.message || '루틴 상태 변경에 실패했습니다.');
        if (button && state?.apply) state.apply(button, payload.state || '');
        window.LifeFlowToast?.show?.(payload.message || '루틴 상태를 변경했습니다.');
      } catch (error) {
        form.submit();
      } finally {
        if (button) button.disabled = false;
      }
    });
  });
})();
