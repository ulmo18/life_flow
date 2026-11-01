(function () {
  const page = document.querySelector('[data-todo-page]');
  const layer = document.querySelector('[data-todo-layer]');
  const timeForm = document.querySelector('[data-todo-time-form]');
  const itemIdInput = timeForm?.querySelector('[data-todo-item-id]');
  const startInput = timeForm?.querySelector('select[name="start_index"]');
  const endInput = timeForm?.querySelector('select[name="end_index"]');
  const message = document.querySelector('[data-todo-time-message]');
  const createLayer = document.querySelector('[data-todo-create-layer]');
  const recurringLayer = document.querySelector('[data-todo-recurring-layer]');
  const recurringForm = document.querySelector('[data-todo-recurring-form]');
  let recurringSaving = false;
  const editLayer = document.querySelector('[data-todo-edit-layer]');
  const editForm = document.querySelector('[data-todo-edit-form]');
  const editId = editForm?.querySelector('[data-todo-edit-id]');
  const editTitle = document.getElementById('todoEditTitleInput');
  const editGoalPicker = document.querySelector('[data-todo-goal-picker]');
  const editGoalSummary = document.querySelector('[data-todo-goal-summary]');
  const editGoalInputs = Array.from(document.querySelectorAll('[data-todo-goal-picker] input[name="goal_id"]'));
  let editTrigger = null;
  let todoDeleteHold = null;
  let todoDeletePending = false;
  if (!page || !layer || !timeForm || !itemIdInput || !startInput || !endInput) return;

  const listTabs = Array.from(page.querySelectorAll('[data-todo-list-tab]'));
  const listPanels = Array.from(page.querySelectorAll('[data-todo-list-panel]'));
  function selectListTab(tab, focus = false) {
    listTabs.forEach(item => {
      const selected = item === tab;
      item.setAttribute('aria-selected', String(selected));
      item.tabIndex = selected ? 0 : -1;
    });
    listPanels.forEach(panel => { panel.hidden = panel.dataset.todoListPanel !== tab.dataset.todoListTab; });
    document.querySelectorAll('[data-todo-add-tab]').forEach(button => { button.hidden = button.dataset.todoAddTab !== tab.dataset.todoListTab; });
    if (focus) tab.focus();
  }
  listTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectListTab(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = (index + 1) % listTabs.length;
      else if (event.key === 'ArrowLeft') next = (index + listTabs.length - 1) % listTabs.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = listTabs.length - 1;
      else return;
      event.preventDefault();
      selectListTab(listTabs[next], true);
    });
  });

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
    if (!editLayer || todoDeletePending) return;
    cancelTodoDeleteHold();
    editLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
    editTrigger?.focus();
    editTrigger = null;
  }

  const todoDeleteButton = document.querySelector('[data-todo-delete]');
  const todoDeleteMessage = '삭제하려면 1.5초 이상 눌러서 삭제하세요.';
  let ignoreTodoDeleteClick = false;
  function cancelTodoDeleteHold() {
    if (!todoDeleteHold) return;
    window.clearTimeout(todoDeleteHold.timer);
    todoDeleteButton?.classList.remove('is-holding');
    todoDeleteHold = null;
  }
  function showTodoDeleteMessage(message) {
    if (window.LifeFlowToast?.show) window.LifeFlowToast.show(message);
    else window.alert(message);
  }
  async function deleteTodo() {
    if (!editId?.value || todoDeletePending) return;
    todoDeletePending = true;
    todoDeleteButton.disabled = true;
    const saveButton = editForm.querySelector('button[type="submit"]');
    if (saveButton) saveButton.disabled = true;
    const body = new FormData();
    body.set('_csrf_token', editForm.querySelector('input[name="_csrf_token"]').value);
    body.set('daily_plan_item_id', editId.value);
    try {
      const response = await fetch('/todo/delete', {method: 'POST', body, headers: {'Accept': 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '할 일을 삭제하지 못했습니다.');
      window.location.reload();
    } catch (error) {
      todoDeletePending = false;
      todoDeleteButton.disabled = false;
      if (saveButton) saveButton.disabled = false;
      showTodoDeleteMessage(error.message || '할 일을 삭제하지 못했습니다.');
    }
  }
  function completeTodoDeleteHold(hold) {
    if (todoDeleteHold !== hold) return;
    if (!editLayer || editLayer.hidden) { cancelTodoDeleteHold(); return; }
    cancelTodoDeleteHold();
    deleteTodo();
  }
  function startTodoDeleteHold(kind, key) {
    if (!todoDeleteButton || !editLayer || editLayer.hidden || todoDeletePending || todoDeleteHold) return;
    const hold = {kind, key, startedAt: window.performance.now(), timer: null};
    todoDeleteHold = hold;
    todoDeleteButton.classList.add('is-holding');
    hold.timer = window.setTimeout(() => completeTodoDeleteHold(hold), 1500);
  }
  function finishTodoDeleteHold(kind, key) {
    const hold = todoDeleteHold;
    if (!hold || hold.kind !== kind || hold.key !== key) return;
    if (window.performance.now() - hold.startedAt >= 1500) { completeTodoDeleteHold(hold); return; }
    cancelTodoDeleteHold();
    ignoreTodoDeleteClick = true;
    window.setTimeout(() => { ignoreTodoDeleteClick = false; }, 0);
    showTodoDeleteMessage(todoDeleteMessage);
  }
  todoDeleteButton?.addEventListener('pointerdown', event => {
    if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return;
    startTodoDeleteHold('pointer', event.pointerId);
    if (todoDeleteHold?.kind === 'pointer' && todoDeleteHold.key === event.pointerId) {
      try { todoDeleteButton.setPointerCapture(event.pointerId); } catch (error) { /* Optional. */ }
    }
  });
  todoDeleteButton?.addEventListener('pointermove', event => {
    if (todoDeleteHold?.kind !== 'pointer' || todoDeleteHold.key !== event.pointerId) return;
    const bounds = todoDeleteButton.getBoundingClientRect();
    if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) cancelTodoDeleteHold();
  });
  todoDeleteButton?.addEventListener('pointerup', event => finishTodoDeleteHold('pointer', event.pointerId));
  ['pointercancel', 'lostpointercapture'].forEach(type => todoDeleteButton?.addEventListener(type, event => {
    if (todoDeleteHold?.kind === 'pointer' && todoDeleteHold.key === event.pointerId) cancelTodoDeleteHold();
  }));
  todoDeleteButton?.addEventListener('contextmenu', event => { if (todoDeleteHold) event.preventDefault(); });
  todoDeleteButton?.addEventListener('keydown', event => {
    if (event.key !== ' ' && event.key !== 'Enter') return;
    event.preventDefault();
    if (!event.repeat) startTodoDeleteHold('keyboard', event.key);
  });
  todoDeleteButton?.addEventListener('keyup', event => {
    if (event.key !== ' ' && event.key !== 'Enter') return;
    event.preventDefault();
    finishTodoDeleteHold('keyboard', event.key);
  });
  todoDeleteButton?.addEventListener('blur', cancelTodoDeleteHold);
  todoDeleteButton?.addEventListener('click', event => {
    event.preventDefault();
    if (event.detail === 0 && !ignoreTodoDeleteClick && !todoDeletePending) showTodoDeleteMessage(todoDeleteMessage);
  });
  document.addEventListener('visibilitychange', () => { if (document.hidden) cancelTodoDeleteHold(); });

  function setEditGoal(goalId) {
    const selectedValue = String(goalId || '');
    let selected = editGoalInputs.find(input => input.value === selectedValue);
    if (!selected) selected = editGoalInputs.find(input => input.value === '');
    if (selected) selected.checked = true;
    if (editGoalSummary) {
      const label = selected?.dataset.goalLabel?.trim() || '';
      editGoalSummary.textContent = label === '' ? '연결하기 ›' : `${label} ›`;
    }
  }

  const createTabs = Array.from(document.querySelectorAll('[data-todo-create-tab]'));
  const createPanels = Array.from(document.querySelectorAll('[data-todo-create-panel]'));
  const templateSearch = document.querySelector('[data-todo-template-search]');
  const templateRows = Array.from(document.querySelectorAll('.todo-template-row'));
  const templateNoResults = document.querySelector('[data-todo-template-no-results]');
  function selectCreateTab(name) {
    createTabs.forEach(tab => {
      const selected = tab.dataset.todoCreateTab === name;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
    });
    createPanels.forEach(panel => { panel.hidden = panel.dataset.todoCreatePanel !== name; });
  }
  createTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectCreateTab(tab.dataset.todoCreateTab));
    tab.addEventListener('keydown', event => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? createTabs.length - 1
        : (index + (event.key === 'ArrowRight' ? 1 : -1) + createTabs.length) % createTabs.length;
      selectCreateTab(createTabs[next].dataset.todoCreateTab);
      createTabs[next].focus();
    });
  });
  templateSearch?.addEventListener('input', () => {
    const query = templateSearch.value.trim().toLocaleLowerCase();
    let visibleCount = 0;
    templateRows.forEach(row => {
      const matches = (row.querySelector('strong')?.textContent || '').toLocaleLowerCase().includes(query);
      row.hidden = !matches;
      if (matches) visibleCount++;
    });
    if (templateNoResults) templateNoResults.hidden = visibleCount !== 0;
  });

  document.querySelectorAll('[data-todo-create-open]').forEach(button => button.addEventListener('click', () => {
    if (!createLayer) return;
    selectCreateTab('direct');
    createLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => document.getElementById('todoCreateTitle')?.focus(), 0);
  }));
  document.querySelectorAll('[data-todo-create-close]').forEach(button => button.addEventListener('click', () => {
    if (!createLayer) return;
    createLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }));
  function closeRecurring() {
    if (!recurringLayer || recurringSaving) return;
    recurringLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
    document.querySelector('[data-todo-recurring-open]')?.focus();
  }
  document.querySelector('[data-todo-recurring-open]')?.addEventListener('click', () => {
    if (!recurringLayer || !recurringForm) return;
    recurringForm.reset();
    recurringLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => document.getElementById('todoRecurringName')?.focus(), 0);
  });
  document.querySelectorAll('[data-todo-recurring-close]').forEach(button => button.addEventListener('click', closeRecurring));
  recurringForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (recurringSaving) return;
    recurringSaving = true;
    const submit = recurringForm.querySelector('button[type="submit"]');
    if (submit) submit.disabled = true;
    try {
      const response = await fetch(recurringForm.action, {method: 'POST', body: new FormData(recurringForm), headers: {'Accept': 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '반복 할 일을 추가하지 못했습니다.');
      const url = new URL(window.location.href);
      url.searchParams.set('tab', 'recurring');
      window.location.assign(url.toString());
    } catch (error) {
      recurringSaving = false;
      if (submit) submit.disabled = false;
      if (window.LifeFlowToast?.show) window.LifeFlowToast.show(error.message || '반복 할 일을 추가하지 못했습니다.');
      else window.alert(error.message || '반복 할 일을 추가하지 못했습니다.');
    }
  });
  document.querySelectorAll('[data-todo-template-form]').forEach(form => form.addEventListener('submit', () => {
    form.querySelector('button[type="submit"]').disabled = true;
  }));
  const statusLayer = document.querySelector('[data-todo-status-layer]');
  const statusOptions = Array.from(document.querySelectorAll('[data-todo-status-option]'));
  const statusTitle = document.querySelector('[data-todo-status-title]');
  const statusNote = document.querySelector('[data-todo-status-note]');
  const statusRecord = document.querySelector('[data-todo-status-record]');
  let statusTrigger = null;
  let statusSaving = false;
  function closeStatus() {
    if (!statusLayer || statusSaving) return;
    statusLayer.hidden = true;
    document.body.classList.remove('is-menu-open');
    statusTrigger?.focus();
    statusTrigger = null;
  }
  function updateStatusOptions() {
    statusOptions.forEach(option => {
      option.disabled = statusSaving || statusTrigger?.dataset.statusEditable === '0';
      option.setAttribute('aria-pressed', String(option.dataset.todoStatusOption === statusTrigger?.dataset.savedStatus));
    });
  }
  document.querySelectorAll('[data-todo-block-status]').forEach(button => button.addEventListener('click', () => {
    if (!statusLayer || !button.dataset.itemId) return;
    statusTrigger = button;
    if (statusTitle) statusTitle.textContent = button.dataset.blockTitle || '';
    const template = button.parentElement?.querySelector('[data-todo-record-template]');
    if (statusRecord) {
      if (template) statusRecord.replaceChildren(template.content.cloneNode(true));
      else statusRecord.replaceChildren();
    }
    if (statusNote) statusNote.textContent = button.dataset.statusEditable === '0' ? '상태를 변경할 수 없는 기록입니다.' : '이 할 일의 상태를 변경합니다.';
    updateStatusOptions();
    statusLayer.hidden = false;
    const sheet = statusLayer.querySelector('.todo-sheet');
    if (sheet) sheet.scrollTop = 0;
    document.body.classList.add('is-menu-open');
    (statusOptions.find(option => option.getAttribute('aria-pressed') === 'true' && !option.disabled) || statusOptions.find(option => !option.disabled) || statusLayer.querySelector('[data-todo-status-close].ui-close-button'))?.focus();
  }));
  document.querySelectorAll('[data-todo-status-close]').forEach(button => button.addEventListener('click', closeStatus));
  statusOptions.forEach(option => option.addEventListener('click', async () => {
    if (!statusTrigger || statusSaving || option.disabled) return;
    const status = option.dataset.todoStatusOption;
    if (status === statusTrigger.dataset.savedStatus) { closeStatus(); return; }
    const body = new FormData();
    body.set('_csrf_token', timeForm.querySelector('input[name="_csrf_token"]').value);
    body.set('daily_plan_item_id', statusTrigger.dataset.itemId);
    body.set('status', status);
    statusSaving = true;
    updateStatusOptions();
    try {
      const response = await fetch('/todo/status', {method: 'POST', body, headers: {'Accept': 'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '상태를 변경하지 못했습니다.');
      document.querySelectorAll('[data-todo-block-status]').forEach(button => {
        if (button.dataset.itemId !== statusTrigger.dataset.itemId) return;
        button.dataset.savedStatus = status;
        const symbols = {pending: '—', completed: 'V', cancelled: 'X', postponed: '→', in_progress: '·', external: '○'};
        const symbol = button.querySelector('[data-todo-status-symbol]');
        if (symbol) symbol.textContent = symbols[status] || '—';
        button.setAttribute('aria-label', `${button.dataset.blockTitle} 상태: ${option.textContent.trim()}, 상태와 연결 기록 보기`);
        const itemTime = button.closest('[data-todo-item]')?.querySelector('[data-todo-item-time]');
        if (itemTime) {
          const display = status === 'completed' ? itemTime.dataset.actualTime : ['cancelled', 'postponed', 'external'].includes(status) ? '' : itemTime.dataset.plannedTime;
          itemTime.hidden = !display;
          itemTime.querySelector('.todo-time').textContent = display || '';
        }
      });
      statusSaving = false;
      closeStatus();
    } catch (error) {
      statusSaving = false;
      updateStatusOptions();
      const message = error instanceof SyntaxError || error instanceof TypeError ? '상태 변경 결과를 확인하지 못했습니다. 새로고침 후 확인해 주세요.' : (error.message || '상태를 변경하지 못했습니다.');
      if (window.LifeFlowToast?.show) window.LifeFlowToast.show(message);
      else window.alert(message);
    }
  }));

  document.querySelectorAll('[data-todo-edit-open]').forEach(button => button.addEventListener('click', () => {
    if (!editLayer || !editForm || !editId || !editTitle || editGoalInputs.length === 0) return;
    editTrigger = button;
    editId.value = button.dataset.itemId || '';
    editTitle.value = button.dataset.title || '';
    setEditGoal(button.dataset.goalId || '');
    if (editGoalPicker) editGoalPicker.open = false;
    editForm.querySelectorAll('input[name="importance"]').forEach(input => {
      input.checked = input.value === (button.dataset.importance || 'D');
    });
    editLayer.hidden = false;
    document.body.classList.add('is-menu-open');
    window.setTimeout(() => editTitle.focus(), 0);
  }));
  document.querySelectorAll('[data-todo-edit-close]').forEach(button => button.addEventListener('click', closeEdit));
  editGoalInputs.forEach(input => input.addEventListener('change', () => setEditGoal(input.value)));

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
    if (statusLayer && !statusLayer.hidden) { closeStatus(); return; }
    if (recurringLayer && !recurringLayer.hidden) { closeRecurring(); return; }
    if (!layer.hidden) close();
    if (createLayer && !createLayer.hidden) {
      createLayer.hidden = true;
      document.body.classList.remove('is-menu-open');
    }
    if (editLayer && !editLayer.hidden) closeEdit();
  });
})();
