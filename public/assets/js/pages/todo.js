(function () {
  const page = document.querySelector('[data-todo-page]');
  const layer = document.querySelector('[data-todo-layer]');
  const timeForm = document.querySelector('[data-todo-time-form]');
  const itemIdInput = timeForm?.querySelector('[data-todo-item-id]');
  const startInput = timeForm?.querySelector('select[name="start_index"]');
  const endInput = timeForm?.querySelector('select[name="end_index"]');
  const message = document.querySelector('[data-todo-time-message]');
  const createLayer = document.querySelector('[data-todo-create-layer]');
  const editLayer = document.querySelector('[data-todo-edit-layer]');
  const editForm = document.querySelector('[data-todo-edit-form]');
  const editId = editForm?.querySelector('[data-todo-edit-id]');
  const editTitle = document.getElementById('todoEditTitleInput');
  const editGoal = document.getElementById('todoEditGoal');
  const editScheduleTypes = Array.from(document.querySelectorAll('[data-todo-edit-schedule-type]'));
  const editTimeFields = document.querySelector('[data-todo-edit-time-fields]');
  const editStart = document.getElementById('todoEditStartIndex');
  const editEnd = document.getElementById('todoEditEndIndex');
  const editLinkAction = document.querySelector('[data-todo-link-action]');
  if (!page || !layer || !timeForm || !itemIdInput || !startInput || !endInput) return;

  function close() {
    layer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }

  function open(itemId, startIndex, endIndex, text) {
    itemIdInput.value = itemId;
    startInput.value = String(startIndex);
    endInput.value = String(endIndex);
    if (message && text) message.textContent = text;
    layer.hidden = false;
    document.body.classList.add('is-menu-open');
    startInput.focus();
  }

  function closeEdit() {
    if (!editLayer) return;
    editLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }

  function setEditScheduleType(type) {
    if (editScheduleTypes.length === 0 || !editTimeFields || !editStart || !editEnd) return;
    const isTimed = type === 'timed';
    editScheduleTypes.forEach(input => { input.checked = input.value === (isTimed ? 'timed' : 'unscheduled'); });
    editTimeFields.hidden = !isTimed;
    editStart.disabled = !isTimed;
    editEnd.disabled = !isTimed;
    editStart.required = isTimed;
    editEnd.required = isTimed;
  }

  document.querySelectorAll('[data-todo-create-open]').forEach(button => button.addEventListener('click', () => {
    if (!createLayer) return;
    createLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => document.getElementById('todoCreateTitleInput')?.focus(), 0);
  }));
  document.querySelectorAll('[data-todo-create-close]').forEach(button => button.addEventListener('click', () => {
    if (!createLayer) return;
    createLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }));

  document.querySelectorAll('[data-todo-edit-open]').forEach(button => button.addEventListener('click', () => {
    if (!editLayer || !editForm || !editId || !editTitle || !editGoal || !editStart || !editEnd || !editLinkAction) return;
    editId.value = button.dataset.itemId || '';
    editTitle.value = button.dataset.title || '';
    editGoal.value = button.dataset.goalId || '';
    editForm.querySelectorAll('input[name="importance"]').forEach(input => {
      input.checked = input.value === (button.dataset.importance || 'D');
    });
    editStart.value = button.dataset.startIndex || '54';
    editEnd.value = button.dataset.endIndex || '60';
    setEditScheduleType(button.dataset.scheduleType || 'unscheduled');
    editLinkAction.hidden = button.dataset.linked !== '1';
    editForm.querySelector('select[name="link_action"]').value = 'keep';
    editLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => editTitle.focus(), 0);
  }));
  document.querySelectorAll('[data-todo-edit-close]').forEach(button => button.addEventListener('click', closeEdit));
  editScheduleTypes.forEach(input => input.addEventListener('change', () => setEditScheduleType(input.value)));
  editForm?.addEventListener('submit', event => {
    if (editScheduleTypes.find(input => input.checked)?.value !== 'timed') return;
    const start = Number.parseInt(editStart?.value || '', 10);
    const end = Number.parseInt(editEnd?.value || '', 10);
    if (!Number.isInteger(start) || !Number.isInteger(end) || start >= end) {
      event.preventDefault();
      window.LifeFlowToast?.show?.('종료시간은 시작시간보다 늦어야 합니다.');
    }
  });

  async function submit(body) {
    const response = await fetch('/todo/complete', {
      method: 'POST',
      body,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();
    if (payload.needsTime) {
      open(body.get('daily_plan_item_id'), payload.startIndex, payload.endIndex, payload.message);
      return;
    }
    if (!response.ok || !payload.ok) throw new Error(payload.message || page.dataset.messageFallback);
    window.LifeFlowToast?.show?.(payload.message);
    window.location.reload();
  }

  document.querySelectorAll('[data-todo-complete]').forEach(button => {
    button.addEventListener('click', async () => {
      const body = new FormData();
      body.append('_csrf_token', timeForm.querySelector('input[name="_csrf_token"]').value);
      body.append('daily_plan_item_id', button.dataset.itemId || '');
      if (button.dataset.startIndex !== '' && button.dataset.endIndex !== '') {
        body.append('start_index', button.dataset.startIndex);
        body.append('end_index', button.dataset.endIndex);
      }
      button.disabled = true;
      try { await submit(body); }
      catch (error) { window.LifeFlowToast?.show?.(error.message || page.dataset.messageFallback); }
      finally { button.disabled = false; }
    });
  });

  timeForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submitButton = timeForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    try { await submit(new FormData(timeForm)); }
    catch (error) { window.LifeFlowToast?.show?.(error.message || page.dataset.messageFallback); }
    finally { if (submitButton) submitButton.disabled = false; }
  });

  document.querySelectorAll('[data-todo-close]').forEach(button => button.addEventListener('click', close));
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (!layer.hidden) close();
    if (createLayer && !createLayer.hidden) {
      createLayer.hidden = true;
      document.body.classList.remove('is-menu-open');
    }
    if (editLayer && !editLayer.hidden) closeEdit();
  });
})();
