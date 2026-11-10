(() => {
  'use strict';

  const blockLayer = document.querySelector('[data-plan-block-layer]');
  const blockForm = document.querySelector('[data-plan-block-form]');
  const blockId = document.querySelector('[data-plan-block-id]');
  const blockTitle = document.querySelector('[data-plan-block-title]');
  const blockDuration = document.querySelector('[data-plan-block-duration]');
  const blockGoal = document.querySelector('[data-plan-block-goal]');
  const blockSheetTitle = document.querySelector('[data-plan-block-sheet-title]');
  const blockSubmit = document.querySelector('[data-plan-block-submit]');
  let blockTrigger = null;

  function setBlockImportance(value) {
    if (!(blockForm instanceof HTMLFormElement)) return;
    const normalized = ['A', 'B', 'C', 'D'].includes(value) ? value : 'D';
    blockForm.querySelectorAll('input[name="importance"]').forEach(input => {
      input.checked = input.value === normalized;
    });
  }

  function openBlockSheet(mode, data = {}, opener = document.activeElement) {
    if (!(blockLayer instanceof HTMLElement) || !(blockForm instanceof HTMLFormElement)) return;
    if (blockLayer.hidden) blockTrigger = opener;

    const isEdit = mode === 'edit';
    blockForm.reset();
    blockForm.action = isEdit ? '/plan/block-template/update' : '/plan/block-template';
    if (blockId instanceof HTMLInputElement) {
      blockId.disabled = !isEdit;
      blockId.value = isEdit ? String(data.id || '') : '';
    }
    if (blockTitle instanceof HTMLInputElement) blockTitle.value = isEdit ? String(data.title || '') : '';
    if (blockDuration instanceof HTMLSelectElement) blockDuration.value = isEdit ? String(data.duration || '6') : '6';
    if (blockGoal instanceof HTMLSelectElement) blockGoal.value = isEdit ? String(data.goal || '') : '';
    setBlockImportance(isEdit ? String(data.importance || 'D') : 'D');
    if (blockSheetTitle) blockSheetTitle.textContent = isEdit ? '할 일 블록 수정' : '할 일 블록 추가';
    if (blockSubmit) blockSubmit.textContent = isEdit ? '수정 저장' : '할 일 블록 저장';

    blockLayer.hidden = false;
    document.body.classList.add('is-ui-open');
    window.setTimeout(() => blockTitle?.focus(), 0);
  }

  function closeBlockSheet(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    if (!(blockLayer instanceof HTMLElement)) return;
    blockLayer.hidden = true;
    document.body.classList.remove('is-ui-open');
    blockTrigger?.focus({ preventScroll: true });
    blockTrigger = null;
  }

  document.querySelector('[data-plan-block-create]')?.addEventListener('click', event => openBlockSheet('create', {}, event.currentTarget));
  document.querySelectorAll('[data-plan-block-edit]').forEach(button => {
    button.addEventListener('click', () => openBlockSheet('edit', {
      id: button.dataset.templateId,
      title: button.dataset.templateTitle,
      duration: button.dataset.templateDuration,
      importance: button.dataset.templateImportance,
      goal: button.dataset.templateGoal,
    }, button));
  });
  document.querySelectorAll('[data-plan-block-close]').forEach(button => {
    button.addEventListener('click', closeBlockSheet);
  });
  if (blockLayer instanceof HTMLElement && ['create', 'edit'].includes(blockLayer.dataset.reopenMode || '')) {
    openBlockSheet(blockLayer.dataset.reopenMode, {
      id: blockLayer.dataset.reopenId,
      title: blockLayer.dataset.reopenTitle,
      duration: blockLayer.dataset.reopenDuration,
      importance: blockLayer.dataset.reopenImportance,
      goal: blockLayer.dataset.reopenGoal,
    });
  }

  document.querySelectorAll('[data-plan-delay-delete]').forEach(button => {
    let hold = null;
    function cancel() { if (!hold) return; clearTimeout(hold.timer); hold = null; button.classList.remove('is-holding'); }
    function complete() { if (!hold) return; const form = button.closest('form'); cancel(); button.disabled = true; if (form) form.requestSubmit(); }
    function start(kind, id) { if (hold || button.disabled) return; hold = {kind, id, started:performance.now(), timer:setTimeout(complete, 1500)}; button.classList.add('is-holding'); }
    function finish(kind, id) { if (!hold || hold.kind !== kind || hold.id !== id) return; if (performance.now() - hold.started >= 1500) { complete(); return; } cancel(); window.LifeFlowToast?.show('삭제하려면 1.5초 이상 눌러서 삭제하세요.'); }
    button.addEventListener('pointerdown', event => { if (!event.isPrimary || (event.pointerType === 'mouse' && event.button !== 0)) return; start('pointer', event.pointerId); try { button.setPointerCapture(event.pointerId); } catch (_) {} });
    button.addEventListener('pointermove', event => { if (!hold || hold.kind !== 'pointer' || hold.id !== event.pointerId) return; const r=button.getBoundingClientRect(); if (event.clientX<r.left || event.clientX>r.right || event.clientY<r.top || event.clientY>r.bottom) cancel(); });
    button.addEventListener('pointerup', event => finish('pointer',event.pointerId));
    ['pointercancel','lostpointercapture','blur'].forEach(type => button.addEventListener(type,cancel));
    button.addEventListener('keydown', event => { if (event.key !== ' ' && event.key !== 'Enter') return; event.preventDefault(); if (!event.repeat) start('key',event.key); });
    button.addEventListener('keyup', event => { if (event.key !== ' ' && event.key !== 'Enter') return; event.preventDefault(); finish('key',event.key); });
    button.addEventListener('contextmenu', event => { if (hold) event.preventDefault(); });
    button.addEventListener('click', event => { event.preventDefault(); if (event.detail === 0 && !button.disabled) window.LifeFlowToast?.show('삭제하려면 1.5초 이상 눌러서 삭제하세요.'); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) cancel(); });
  });

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    let allowSubmit = false;

    form.addEventListener('submit', async event => {
      if (allowSubmit) {
        allowSubmit = false;
        return;
      }

      event.preventDefault();
      const message = form.dataset.confirm || '계속 진행할까요?';
      const title = form.dataset.confirmTitle || '확인';
      const confirmText = form.dataset.confirmText || '진행';
      const confirmed = window.LifeFlowUI && typeof window.LifeFlowUI.confirm === 'function'
        ? await window.LifeFlowUI.confirm({ title, message, confirmText, cancelText: '취소' })
        : window.confirm(message);

      if (confirmed) {
        allowSubmit = true;
        form.requestSubmit();
      }
    });
  });

  document.addEventListener('keydown', event => {
    if (event.defaultPrevented || document.querySelector('.lf-date-dialog[open]') || document.getElementById('lifeFlowUiLayer')?.hidden === false) return;
    if (event.key === 'Escape' && blockLayer instanceof HTMLElement && !blockLayer.hidden) {
      closeBlockSheet(event);
    }
    if (event.key !== 'Tab' || !(blockLayer instanceof HTMLElement) || blockLayer.hidden) return;
    const panel = blockLayer.querySelector('[role="dialog"]');
    if (!panel) return;
    const controls = Array.from(panel.querySelectorAll('button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), a[href]'))
      .filter(control => control.getClientRects().length > 0);
    const first = controls[0], last = controls[controls.length - 1];
    if (!first) return;
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
})();
