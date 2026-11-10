(function () {
  const page = document.querySelector('[data-goal-page]');
  const layer = document.querySelector('[data-goal-layer]');
  const panels = Array.from(document.querySelectorAll('[data-goal-sheet]'));
  const csrfToken = document.querySelector('[data-goal-quick-form] input[name="_csrf_token"]')?.value || '';
  const createLayer = document.querySelector('[data-goal-create-layer]');
  const collapsedKey = `lifeflow.goal-map.collapsed.v2.${page?.dataset.mode || 'focus'}`;
  let draggedGoalId = null;
  let goalDeleteHold = null;
  let panelTrigger = null;
  let createTrigger = null;

  if (!page || !layer) return;

  function toast(message, type = 'error') {
    window.LifeFlowToast?.show?.(message, { type });
  }

  function openPanel(name, opener = document.activeElement) {
    if (layer.hidden) panelTrigger = opener;
    layer.hidden = false;
    panels.forEach(panel => { panel.hidden = panel.dataset.goalSheet !== name; });
    document.body.classList.add('is-ui-open');
    const panel = panels.find(item => !item.hidden);
    panel?.querySelector?.(name.startsWith('edit-') ? 'input[name="title"]' : '[data-goal-close]')?.focus();
  }

  function closePanels(event) {
    event?.preventDefault();
    cancelGoalDeleteHold();
    layer.hidden = true;
    panels.forEach(panel => { panel.hidden = true; });
    document.body.classList.remove('is-ui-open');
    panelTrigger?.focus?.({ preventScroll: true });
    panelTrigger = null;
  }

  document.querySelectorAll('[data-goal-open]').forEach(button => {
    button.addEventListener('click', () => openPanel(button.dataset.goalOpen || '', button));
  });
  document.querySelectorAll('[data-goal-close]').forEach(button => button.addEventListener('click', closePanels));

  document.querySelectorAll('[data-goal-date]').forEach(input => input.addEventListener('click', event => {
    if (window.LifeFlowDatePicker?.open(input, input)) event.preventDefault();
  }));

  function cancelGoalDeleteHold() {
    if (!goalDeleteHold) return;
    window.clearTimeout(goalDeleteHold.timer);
    goalDeleteHold.button.classList.remove('is-holding');
    goalDeleteHold = null;
  }
  function goalDeleteHint() {
    const message = '삭제하려면 1.5초 이상 눌러서 삭제하세요.';
    if (window.LifeFlowToast?.show) window.LifeFlowToast.show(message);
    else window.alert(message);
  }
  function completeGoalDeleteHold(hold) {
    if (goalDeleteHold !== hold) return;
    const button = hold.button;
    const form = document.getElementById(button.dataset.goalDelete);
    cancelGoalDeleteHold();
    if (layer.hidden || !form) return;
    button.disabled = true;
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
  }
  document.querySelectorAll('[data-goal-delete]').forEach(button => {
    let ignoreClick = false;
    function start(kind, key) {
      if (button.disabled || layer.hidden || goalDeleteHold) return;
      const hold = {button, kind, key, startedAt:window.performance.now(), timer:null};
      goalDeleteHold = hold;
      button.classList.add('is-holding');
      hold.timer = window.setTimeout(() => completeGoalDeleteHold(hold), 1500);
    }
    function finish(kind, key) {
      const hold = goalDeleteHold;
      if (!hold || hold.button !== button || hold.kind !== kind || hold.key !== key) return;
      if (window.performance.now() - hold.startedAt >= 1500) { completeGoalDeleteHold(hold); return; }
      cancelGoalDeleteHold();
      ignoreClick = true;
      window.setTimeout(() => { ignoreClick = false; }, 0);
      goalDeleteHint();
    }
    button.addEventListener('pointerdown', event => {
      if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return;
      start('pointer', event.pointerId);
      if (goalDeleteHold?.button === button) { try { button.setPointerCapture(event.pointerId); } catch (_) {} }
    });
    button.addEventListener('pointermove', event => {
      if (goalDeleteHold?.button !== button || goalDeleteHold.kind !== 'pointer' || goalDeleteHold.key !== event.pointerId) return;
      const bounds = button.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) cancelGoalDeleteHold();
    });
    button.addEventListener('pointerup', event => finish('pointer', event.pointerId));
    ['pointercancel', 'lostpointercapture'].forEach(type => button.addEventListener(type, event => {
      if (goalDeleteHold?.button === button && goalDeleteHold.kind === 'pointer' && goalDeleteHold.key === event.pointerId) cancelGoalDeleteHold();
    }));
    button.addEventListener('keydown', event => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      if (!event.repeat) start('keyboard', event.key);
    });
    button.addEventListener('keyup', event => {
      if (event.key !== ' ' && event.key !== 'Enter') return;
      event.preventDefault();
      finish('keyboard', event.key);
    });
    button.addEventListener('blur', cancelGoalDeleteHold);
    button.addEventListener('contextmenu', event => { if (goalDeleteHold?.button === button) event.preventDefault(); });
    button.addEventListener('click', event => {
      event.preventDefault();
      if (event.detail === 0 && !ignoreClick && !button.disabled) goalDeleteHint();
    });
  });
  document.addEventListener('visibilitychange', () => { if (document.hidden) cancelGoalDeleteHold(); });

  document.querySelectorAll('[data-goal-linked-records]').forEach(group => {
    const tabs = Array.from(group.querySelectorAll('[data-goal-record-tab]'));
    const records = Array.from(group.querySelectorAll('[data-goal-record-panel]'));
    function selectTab(selected, focus = false) {
      tabs.forEach(tab => {
        const active = tab === selected;
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
      });
      records.forEach(panel => { panel.hidden = panel.dataset.goalRecordPanel !== selected.dataset.goalRecordTab; });
      if (focus) selected.focus();
    }
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => selectTab(tab));
      tab.addEventListener('keydown', event => {
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        selectTab(tabs[next], true);
      });
    });
  });

  function collapsedIds() {
    try {
      const stored = JSON.parse(localStorage.getItem(collapsedKey) || '[]');
      return new Set(Array.isArray(stored) ? stored.map(String) : []);
    } catch (_) {
      return new Set();
    }
  }

  const hasStoredCollapseState = localStorage.getItem(collapsedKey) !== null;
  const collapsed = collapsedIds();
  function setCollapsed(item, shouldCollapse, persist = true) {
    const id = item.dataset.goalId;
    const children = item.querySelector(':scope > [data-goal-children]');
    const button = item.querySelector(':scope > .goal-node-row [data-goal-collapse]');
    if (!children || !button) return;
    children.hidden = shouldCollapse;
    item.classList.toggle('is-collapsed', shouldCollapse);
    button.textContent = shouldCollapse ? '▸' : '▾';
    button.setAttribute('aria-expanded', shouldCollapse ? 'false' : 'true');
    if (shouldCollapse) collapsed.add(id); else collapsed.delete(id);
    if (persist) localStorage.setItem(collapsedKey, JSON.stringify(Array.from(collapsed)));
  }

  const goalNodes = Array.from(document.querySelectorAll('[data-goal-node]'));
  const collapsibleGoalNodes = goalNodes.filter(item => item.querySelector(':scope > [data-goal-children]'));
  goalNodes.forEach(item => {
    if (page.dataset.mode === 'all') {
      const shouldCollapse = hasStoredCollapseState
        ? collapsed.has(item.dataset.goalId)
        : item.dataset.focusPath !== '1';
      setCollapsed(item, shouldCollapse, false);
    } else if (collapsed.has(item.dataset.goalId)) {
      setCollapsed(item, true, false);
    }
    item.querySelector(':scope > .goal-node-row [data-goal-collapse]')?.addEventListener('click', event => {
      event.stopPropagation();
      setCollapsed(item, !item.classList.contains('is-collapsed'));
      updateToggleAllLabel();
    });
  });

  const toggleAll = document.querySelector('[data-goal-toggle-all]');
  function updateToggleAllLabel() {
    if (!toggleAll) return;
    toggleAll.hidden = collapsibleGoalNodes.length === 0;
    const expanded = !collapsibleGoalNodes.some(item => item.classList.contains('is-collapsed'));
    toggleAll.setAttribute('aria-pressed', String(expanded));
  }

  toggleAll?.addEventListener('click', () => {
    const shouldExpand = collapsibleGoalNodes.some(item => item.classList.contains('is-collapsed'));
    collapsibleGoalNodes.forEach(item => setCollapsed(item, !shouldExpand, false));
    localStorage.setItem(collapsedKey, JSON.stringify(Array.from(collapsed)));
    updateToggleAllLabel();
  });
  updateToggleAllLabel();

  document.querySelectorAll('[data-goal-child-add]').forEach(button => {
    button.addEventListener('click', event => {
      event.stopPropagation();
      const item = button.closest('[data-goal-node]');
      const form = item?.querySelector(':scope > [data-goal-quick-form]');
      if (!item || !form) return;
      setCollapsed(item, false);
      form.hidden = false;
      form.querySelector('[data-goal-quick-input]')?.focus();
    });
  });

  document.querySelectorAll('[data-goal-inline-cancel]').forEach(button => {
    button.addEventListener('click', () => {
      const form = button.closest('[data-goal-quick-form]');
      if (!form) return;
      form.reset();
      form.hidden = true;
    });
  });

  document.querySelectorAll('[data-goal-create-open]').forEach(button => button.addEventListener('click', () => {
    if (!createLayer) return;
    createTrigger = button;
    createLayer.hidden = false;
    document.body.classList.add('is-ui-open');
    window.setTimeout(() => document.getElementById('goalQuickTitle')?.focus(), 0);
  }));
  function closeGoalCreate() {
    if (!createLayer) return;
    createLayer.hidden = true;
    document.body.classList.remove('is-ui-open');
    createTrigger?.focus({ preventScroll: true });
    createTrigger = null;
  }
  document.querySelectorAll('[data-goal-create-close]').forEach(button => button.addEventListener('click', closeGoalCreate));

  document.querySelectorAll('[data-goal-quick-form]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const input = form.querySelector('[data-goal-quick-input]');
      if (!input || !input.value.trim()) {
        input?.focus();
        return;
      }
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
          const firstError = payload.errors ? Object.values(payload.errors)[0] : payload.message;
          throw new Error(firstError || '목표를 추가하지 못했습니다.');
        }
        window.location.assign(form.querySelector('input[name="mode"]')?.value === 'all' ? '/goal?mode=all' : '/goal');
      } catch (error) {
        toast(error.message || '목표를 추가하지 못했습니다.');
        if (submit) submit.disabled = false;
      }
    });
  });

  async function moveGoal(parentGoalId, beforeGoalId = '') {
    if (!draggedGoalId || String(parentGoalId || '') === String(draggedGoalId)) return;
    const body = new FormData();
    body.set('_csrf_token', csrfToken);
    body.set('goal_id', draggedGoalId);
    body.set('parent_goal_id', parentGoalId || '');
    body.set('before_goal_id', beforeGoalId || '');
    try {
      const response = await fetch('/goal/move', { method: 'POST', body, headers: { Accept: 'application/json' } });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.message || '목표를 이동하지 못했습니다.');
      window.location.reload();
    } catch (error) {
      toast(error.message || '목표를 이동하지 못했습니다.');
    }
  }

  let touchGoalDrag = null;
  function clearTouchGoalDrag() {
    if (!touchGoalDrag) return;
    touchGoalDrag.source.classList.remove('is-dragging');
    document.querySelectorAll('.is-drop-target').forEach(target => target.classList.remove('is-drop-target'));
    document.body.classList.remove('is-goal-dragging');
    touchGoalDrag = null;
    draggedGoalId = null;
  }
  function touchGoalTarget(element, clientY, source) {
    const end = element?.closest('[data-goal-drop-parent]');
    if (end && !source.contains(end)) return {highlight:end, parent:end.dataset.goalDropParent || '', before:''};
    const row = element?.closest('.goal-node-row');
    const item = row?.closest('[data-goal-node]');
    if (!row || !item || source.contains(item)) return null;
    const bounds = row.getBoundingClientRect();
    if (clientY < bounds.top + Math.min(14, bounds.height * 0.3)) {
      return {highlight:item.querySelector(':scope > [data-goal-drop-before]'), parent:item.dataset.parentId || '', before:item.dataset.goalId || ''};
    }
    return {highlight:row.querySelector('.goal-node-main'), parent:item.dataset.goalId || '', before:''};
  }
  document.querySelectorAll('[data-goal-drag]').forEach(handle => {
    handle.addEventListener('pointerdown', event => {
      if (event.pointerType === 'mouse' || event.isPrimary === false || touchGoalDrag) return;
      const source = handle.closest('[data-goal-node]');
      if (!source) return;
      event.preventDefault();
      draggedGoalId = handle.dataset.goalDrag || null;
      if (!draggedGoalId) return;
      touchGoalDrag = {pointerId:event.pointerId, source, target:null};
      source.classList.add('is-dragging');
      document.body.classList.add('is-goal-dragging');
      try { handle.setPointerCapture(event.pointerId); } catch (_) { /* Optional. */ }
    });
    handle.addEventListener('pointermove', event => {
      if (!touchGoalDrag || touchGoalDrag.pointerId !== event.pointerId) return;
      event.preventDefault();
      document.querySelectorAll('.is-drop-target').forEach(target => target.classList.remove('is-drop-target'));
      touchGoalDrag.target = touchGoalTarget(document.elementFromPoint(event.clientX, event.clientY), event.clientY, touchGoalDrag.source);
      touchGoalDrag.target?.highlight?.classList.add('is-drop-target');
      if (event.clientY < 48) window.scrollBy(0, -12);
      else if (event.clientY > window.innerHeight - 48) window.scrollBy(0, 12);
    });
    handle.addEventListener('pointerup', event => {
      if (!touchGoalDrag || touchGoalDrag.pointerId !== event.pointerId) return;
      const target = touchGoalDrag.target;
      if (target) moveGoal(target.parent, target.before);
      clearTouchGoalDrag();
    });
    ['pointercancel', 'lostpointercapture'].forEach(type => handle.addEventListener(type, event => {
      if (touchGoalDrag?.pointerId === event.pointerId) clearTouchGoalDrag();
    }));
  });
  document.addEventListener('visibilitychange', () => { if (document.hidden) clearTouchGoalDrag(); });

  document.querySelectorAll('[data-goal-drag]').forEach(handle => {
    handle.addEventListener('dragstart', event => {
      draggedGoalId = handle.dataset.goalDrag || null;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', draggedGoalId || '');
      handle.closest('[data-goal-node]')?.classList.add('is-dragging');
      document.body.classList.add('is-goal-dragging');
    });
    handle.addEventListener('dragend', () => {
      handle.closest('[data-goal-node]')?.classList.remove('is-dragging');
      document.querySelectorAll('.is-drop-target').forEach(target => target.classList.remove('is-drop-target'));
      document.body.classList.remove('is-goal-dragging');
      draggedGoalId = null;
    });
  });

  document.querySelectorAll('[data-goal-drop-before]').forEach(target => {
    target.addEventListener('dragover', event => { event.preventDefault(); target.classList.add('is-drop-target'); });
    target.addEventListener('dragleave', () => target.classList.remove('is-drop-target'));
    target.addEventListener('drop', event => {
      event.preventDefault();
      const item = target.closest('[data-goal-node]');
      moveGoal(item?.dataset.parentId || '', target.dataset.goalDropBefore || '');
    });
  });

  document.querySelectorAll('.goal-node-main').forEach(target => {
    target.addEventListener('dragover', event => { event.preventDefault(); target.classList.add('is-drop-target'); });
    target.addEventListener('dragleave', () => target.classList.remove('is-drop-target'));
    target.addEventListener('drop', event => {
      event.preventDefault();
      event.stopPropagation();
      moveGoal(target.closest('[data-goal-node]')?.dataset.goalId || '');
    });
  });

  document.querySelectorAll('[data-goal-drop-parent]').forEach(target => {
    target.addEventListener('dragover', event => { event.preventDefault(); target.classList.add('is-drop-target'); });
    target.addEventListener('dragleave', () => target.classList.remove('is-drop-target'));
    target.addEventListener('drop', event => {
      event.preventDefault();
      moveGoal(target.dataset.goalDropParent || '');
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', async event => {
      if (form.dataset.confirmed === '1') return;
      event.preventDefault();
      const confirmed = window.LifeFlowUI?.confirm
        ? await window.LifeFlowUI.confirm({ title: '목표 삭제', message: form.dataset.confirm, confirmText: '삭제', cancelText: '닫기' })
        : window.confirm(form.dataset.confirm);
      if (confirmed) {
        form.dataset.confirmed = '1';
        form.requestSubmit();
      }
    });
  });

  document.addEventListener('keydown', event => {
    if (event.defaultPrevented) return;
    if (document.querySelector('.lf-date-dialog[open]')) return;
    if (document.getElementById('lifeFlowUiLayer')?.hidden === false) return;
    if (event.key === 'Tab') {
      const panel = createLayer && !createLayer.hidden
        ? createLayer.querySelector('[role="dialog"]')
        : panels.find(item => !item.hidden);
      if (!panel) return;
      const controls = Array.from(panel.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter(control => control.getClientRects().length > 0);
      const first = controls[0], last = controls[controls.length - 1];
      if (!first) return;
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
      return;
    }
    if (event.key !== 'Escape') return;
    if (createLayer && !createLayer.hidden) {
      closeGoalCreate();
      return;
    }
    const openInline = document.querySelector('.goal-inline-form:not([hidden])');
    if (openInline) {
      openInline.hidden = true;
      openInline.reset();
    } else if (!layer.hidden) {
      closePanels(event);
    }
  });
})();
