(function () {
  const page = document.querySelector('[data-goal-page]');
  const layer = document.querySelector('[data-goal-layer]');
  const panels = Array.from(document.querySelectorAll('[data-goal-sheet]'));
  const csrfToken = document.querySelector('[data-goal-quick-form] input[name="_csrf_token"]')?.value || '';
  const collapsedKey = 'lifeflow.goal-map.collapsed';
  let draggedGoalId = null;

  if (!page || !layer) return;

  function toast(message, type = 'error') {
    window.LifeFlowToast?.show?.(message, { type });
  }

  function openPanel(name) {
    layer.hidden = false;
    panels.forEach(panel => { panel.hidden = panel.dataset.goalSheet !== name; });
    document.body.classList.add('is-ui-open');
  }

  function closePanels(event) {
    event?.preventDefault();
    layer.hidden = true;
    panels.forEach(panel => { panel.hidden = true; });
    document.body.classList.remove('is-ui-open');
  }

  document.querySelectorAll('[data-goal-open]').forEach(button => {
    button.addEventListener('click', () => openPanel(button.dataset.goalOpen || ''));
  });
  document.querySelectorAll('[data-goal-close]').forEach(button => button.addEventListener('click', closePanels));

  function collapsedIds() {
    try {
      const stored = JSON.parse(localStorage.getItem(collapsedKey) || '[]');
      return new Set(Array.isArray(stored) ? stored.map(String) : []);
    } catch (_) {
      return new Set();
    }
  }

  const collapsed = collapsedIds();
  function setCollapsed(item, shouldCollapse) {
    const id = item.dataset.goalId;
    const children = item.querySelector(':scope > [data-goal-children]');
    const button = item.querySelector(':scope > .goal-node-row [data-goal-collapse]');
    if (!children || !button) return;
    children.hidden = shouldCollapse;
    item.classList.toggle('is-collapsed', shouldCollapse);
    button.textContent = shouldCollapse ? '▸' : '▾';
    button.setAttribute('aria-expanded', shouldCollapse ? 'false' : 'true');
    if (shouldCollapse) collapsed.add(id); else collapsed.delete(id);
    localStorage.setItem(collapsedKey, JSON.stringify(Array.from(collapsed)));
  }

  document.querySelectorAll('[data-goal-node]').forEach(item => {
    if (collapsed.has(item.dataset.goalId)) setCollapsed(item, true);
    item.querySelector(':scope > .goal-node-row [data-goal-collapse]')?.addEventListener('click', event => {
      event.stopPropagation();
      setCollapsed(item, !item.classList.contains('is-collapsed'));
    });
  });

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

  document.querySelector('[data-goal-focus-root]')?.addEventListener('click', () => {
    document.getElementById('goalQuickTitle')?.focus();
  });

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
        window.location.reload();
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
    if (event.key !== 'Escape') return;
    const openInline = document.querySelector('.goal-inline-form:not([hidden])');
    if (openInline) {
      openInline.hidden = true;
      openInline.reset();
    } else if (!layer.hidden) {
      closePanels(event);
    }
  });
})();
