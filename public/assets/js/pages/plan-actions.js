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

  function setBlockImportance(value) {
    if (!(blockForm instanceof HTMLFormElement)) return;
    const normalized = ['A', 'B', 'C', 'D'].includes(value) ? value : 'D';
    blockForm.querySelectorAll('input[name="importance"]').forEach(input => {
      input.checked = input.value === normalized;
    });
  }

  function openBlockSheet(mode, data = {}) {
    if (!(blockLayer instanceof HTMLElement) || !(blockForm instanceof HTMLFormElement)) return;

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
    if (blockSheetTitle) blockSheetTitle.textContent = isEdit ? '계획 블록 수정' : '계획 블록 추가';
    if (blockSubmit) blockSubmit.textContent = isEdit ? '수정 저장' : '계획 블록 저장';

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
  }

  document.querySelector('[data-plan-block-create]')?.addEventListener('click', () => openBlockSheet('create'));
  document.querySelectorAll('[data-plan-block-edit]').forEach(button => {
    button.addEventListener('click', () => openBlockSheet('edit', {
      id: button.dataset.templateId,
      title: button.dataset.templateTitle,
      duration: button.dataset.templateDuration,
      importance: button.dataset.templateImportance,
      goal: button.dataset.templateGoal,
    }));
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
    if (event.key === 'Escape' && blockLayer instanceof HTMLElement && !blockLayer.hidden) {
      closeBlockSheet(event);
    }
  });
})();
