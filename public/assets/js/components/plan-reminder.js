(function () {
  const pending = new Set();
  function emphasizeClock(target, prefix, time) {
    target.textContent = prefix;
    const strong = document.createElement('strong');
    strong.textContent = time;
    target.append(strong);
  }
  function refresh(container) {
    const supported = container.dataset.supported === '1' && Number(container.dataset.blockId) > 0;
    const saving = pending.has(container.dataset.blockId);
    const allowed = container.dataset.masterEnabled === '1' && container.dataset.calendarEnabled === '1';
    const toggle = container.querySelector('[data-reminder-enabled]');
    toggle.disabled = !supported || saving;
    container.querySelector('[data-reminder-minutes]').disabled = !supported || !toggle.checked || saving;
    container.querySelector('[data-plan-reminder-save]').disabled = !supported || !allowed || saving;
    container.querySelector('[data-reminder-options]').hidden = !allowed;
    container.querySelector('[data-reminder-gates]').hidden = allowed;
    container.querySelectorAll('[data-reminder-choice]').forEach(choice => {
      choice.disabled = !supported || !allowed || saving;
      choice.setAttribute('aria-pressed', String(choice.dataset.reminderChoice === 'off' ? !toggle.checked : toggle.checked && choice.dataset.reminderChoice === container.querySelector('[data-reminder-minutes]').value));
    });
    const minutes = container.querySelector('[data-reminder-minutes]').value;
    container.querySelector('[data-reminder-summary]').textContent = !allowed ? '전역 알림 꺼짐' : !toggle.checked ? '꺼짐' : minutes === '0' ? '시작할 때' : `${minutes}분 전`;
    let timing = {};
    try { timing = JSON.parse(container.dataset.reminderTiming || '{}'); } catch (_) { /* Missing clock data can be reloaded. */ }
    const fireLabel = allowed && toggle.checked ? timing.choices?.[minutes] : null;
    const clock = container.querySelector('[data-reminder-clock]');
    if (container.dataset.clockDisplay === 'time') {
      const time = fireLabel?.match(/\d{2}:\d{2}$/)?.[0];
      const clockText = time ? `${timing.startLabel && fireLabel.slice(0, 5) !== timing.startLabel.slice(0, 5) ? '전날 ' : ''}${time}` : '';
      if (clockText) emphasizeClock(container.querySelector('[data-reminder-summary]'), container.querySelector('[data-reminder-summary]').textContent + ' · ', clockText);
      if (clock) {
        if (clockText) emphasizeClock(clock, '알림 ', clockText);
        else clock.textContent = '';
      }
    } else {
      if (fireLabel) container.querySelector('[data-reminder-summary]').textContent += ` · ${fireLabel}`;
      if (clock) clock.textContent = timing.startLabel ? `계획 시작 ${timing.startLabel}${fireLabel ? ` · 알림 ${fireLabel}` : ''}` : '';
    }
  }
  function set(container, reminder) {
    if (!container) return;
    container.dataset.clockTicket = String(Number(container.dataset.clockTicket || 0) + 1);
    container.open = false;
    container.dataset.blockId = String(reminder.blockId);
    container.dataset.supported = reminder.supported ? '1' : '0';
    if (reminder.timing !== undefined) container.dataset.reminderTiming = JSON.stringify(reminder.timing || {});
    container.querySelector('[data-reminder-enabled]').checked = Boolean(reminder.enabled);
    container.querySelector('[data-reminder-minutes]').value = String(reminder.minutesBefore);
    const message = container.querySelector('[data-reminder-message]');
    message.textContent = reminder.supported ? '' : '현재 이 계획의 알림을 설정할 수 없습니다.';
    message.classList.remove('is-error');
    refresh(container);
  }
  function updateCopies(reminder) {
    const apply = root => root.querySelectorAll('[data-plan-reminder]').forEach(container => {
      if (container.dataset.blockId === String(reminder.blockId)) set(container, reminder);
    });
    apply(document);
    document.querySelectorAll('template[data-todo-record-template]').forEach(template => apply(template.content));
    document.querySelectorAll('[data-plan-block-id]').forEach(button => {
      if (button.dataset.planBlockId !== String(reminder.blockId)) return;
      button.dataset.planReminderEnabled = reminder.enabled ? '1' : '0';
      button.dataset.planReminderMinutes = String(reminder.minutesBefore);
      button.dataset.planRemindersSupported = reminder.supported ? '1' : '0';
    });
  }
  document.addEventListener('change', event => {
    if (!event.target.matches('[data-reminder-enabled]')) return;
    const container = event.target.closest('[data-plan-reminder]');
    if (container) refresh(container);
  });
  document.addEventListener('toggle', event => {
    if (event.target.matches('[data-plan-reminder]')) refresh(event.target);
  }, true);
  document.addEventListener('click', async event => {
    const choice = event.target.closest('[data-reminder-choice]');
    if (choice) {
      if (choice.disabled) return;
      const widget = choice.closest('[data-plan-reminder]');
      widget.querySelector('[data-reminder-enabled]').checked = choice.dataset.reminderChoice !== 'off';
      if (choice.dataset.reminderChoice !== 'off') widget.querySelector('[data-reminder-minutes]').value = choice.dataset.reminderChoice;
      widget.querySelector('[data-reminder-message]').textContent = '';
      refresh(widget);
      return;
    }
    const button = event.target.closest('[data-plan-reminder-save]');
    if (!button || button.disabled) return;
    const container = button.closest('[data-plan-reminder]');
    if (!container || pending.has(container.dataset.blockId) || container.dataset.supported !== '1' || container.dataset.masterEnabled !== '1' || container.dataset.calendarEnabled !== '1') return;
    const blockId = container.dataset.blockId;
    const message = container.querySelector('[data-reminder-message]');
    const body = new URLSearchParams({
      _csrf_token: container.querySelector('[data-reminder-csrf]').value,
      daily_plan_block_id: blockId,
      reminder_enabled: container.querySelector('[data-reminder-enabled]').checked ? '1' : '0',
      reminder_minutes_before: container.querySelector('[data-reminder-minutes]').value,
    });
    pending.add(blockId);
    refresh(container);
    message.classList.remove('is-error');
    message.textContent = '저장 중…';
    try {
      const response = await fetch('/calendar/plan-block/reminder', {method:'POST', body, credentials:'same-origin', headers:{Accept:'application/json'}});
      let result;
      try { result = await response.json(); }
      catch (error) { throw new Error('저장 응답을 확인하지 못했습니다. 다시 시도해 주세요.'); }
      if (!response.ok || !result.ok) throw new Error(result.message || '알림 설정을 저장하지 못했습니다.');
      pending.delete(blockId);
      const expanded = container.open;
      updateCopies(result.reminder);
      container.open = expanded;
      if (result.notificationSyncPayload) window.LifeFlowAndroidBridge?.syncNotifications(result.notificationSyncPayload);
      message.textContent = '알림 설정을 저장했습니다.';
    } catch (error) {
      message.classList.add('is-error');
      message.textContent = error.message || '알림 설정을 저장하지 못했습니다. 다시 시도해 주세요.';
    } finally {
      pending.delete(blockId);
      refresh(container);
    }
  });
  async function load(container) {
    if (!container || container.dataset.supported !== '1' || !['null', '{}', '[]', undefined, ''].includes(container.dataset.reminderTiming)) return;
    const blockId = container.dataset.blockId;
    const ticket = container.dataset.clockTicket;
    const clock = container.querySelector('[data-reminder-clock]');
    if (clock) clock.textContent = '알림 시각 확인 중…';
    try {
      const response = await fetch('/calendar/plan-block/reminder?daily_plan_block_id=' + encodeURIComponent(blockId), {headers:{Accept:'application/json'}});
      const result = await response.json();
      if (container.dataset.blockId !== blockId || container.dataset.clockTicket !== ticket) return;
      if (!response.ok || !result.ok) throw new Error('Clock lookup failed');
      container.dataset.reminderTiming = JSON.stringify(result.reminder.timing || {});
      refresh(container);
    } catch (_) {
      if (clock && container.dataset.blockId === blockId && container.dataset.clockTicket === ticket) clock.textContent = '알림 시각을 확인하지 못했습니다. 다시 열어 주세요.';
    }
  }
  window.LifeFlowPlanReminder = {set, load};
  document.querySelectorAll('[data-plan-reminder]').forEach(refresh);
})();
