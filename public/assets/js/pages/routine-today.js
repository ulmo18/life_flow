(function () {
  const state = window.LifeFlowRoutineState;
  const quickLayer = document.querySelector('[data-routine-quick-layer]');
  const quickInput = document.getElementById('routineQuickName');

  function closeQuickCreate() {
    if (!quickLayer) return;
    quickLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }

  document.querySelectorAll('[data-routine-quick-open]').forEach(button => button.addEventListener('click', () => {
    if (!quickLayer) return;
    quickLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => quickInput?.focus(), 0);
  }));
  document.querySelectorAll('[data-routine-quick-close]').forEach(button => button.addEventListener('click', closeQuickCreate));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && quickLayer && !quickLayer.hidden) closeQuickCreate();
  });

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
        const streak = form.closest('li')?.querySelector('[data-routine-today-streak]');
        const streakCount = Number(payload.routine?.streakCount || 0);
        if (streak) {
          streak.hidden = streakCount < 3;
          streak.textContent = streakCount >= 3 ? `🔥 ${streakCount}일 연속 완료` : '';
        }
        window.LifeFlowToast?.show?.(payload.message || '루틴 상태를 변경했습니다.');
      } catch (error) {
        form.submit();
      } finally {
        if (button) button.disabled = false;
      }
    });
  });
})();
