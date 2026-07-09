(function () {
  const layer = document.getElementById('lifeFlowUiLayer');
  const modal = document.getElementById('lifeFlowModal');
  const sheet = document.getElementById('lifeFlowSheet');
  const modalTitle = document.getElementById('lifeFlowModalTitle');
  const modalBody = document.getElementById('lifeFlowModalBody');
  const modalConfirm = modal ? modal.querySelector('[data-ui-confirm]') : null;
  const modalCancel = modal ? modal.querySelector('[data-ui-cancel]') : null;
  const sheetTitle = document.getElementById('lifeFlowSheetTitle');
  const sheetLabel = document.getElementById('lifeFlowSheetLabel');
  const sheetInput = document.getElementById('lifeFlowSheetInput');
  const sheetImportanceGroup = document.getElementById('lifeFlowSheetImportanceGroup');
  const sheetImportance = document.getElementById('lifeFlowSheetImportance');
  const sheetGoalGroup = document.getElementById('lifeFlowSheetGoalGroup');
  const sheetGoal = document.getElementById('lifeFlowSheetGoal');
  const sheetError = document.getElementById('lifeFlowSheetError');
  const sheetForm = document.getElementById('lifeFlowSheetForm');
  const sheetSubmit = sheet ? sheet.querySelector('[data-ui-sheet-submit]') : null;
  const sheetCancel = sheet ? sheet.querySelector('[data-ui-cancel]') : null;
  const closers = layer ? layer.querySelectorAll('[data-ui-close]') : [];

  if (!layer || !modal || !sheet || !modalTitle || !modalBody || !modalConfirm || !modalCancel || !sheetTitle || !sheetLabel || !sheetInput || !sheetImportanceGroup || !sheetImportance || !sheetGoalGroup || !sheetGoal || !sheetError || !sheetForm || !sheetSubmit || !sheetCancel) {
    return;
  }

  let activeMode = null;
  let activeResolve = null;
  let lastFocused = null;
  let sheetOptions = null;
  let tooltip = null;
  let activeTooltipTarget = null;
  const supportsHoverTooltip = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  function ensureTooltip() {
    if (!tooltip) {
      tooltip = document.createElement('div');
      tooltip.className = 'ui-tooltip';
      tooltip.hidden = true;
      document.body.appendChild(tooltip);
    }

    return tooltip;
  }

  function positionTooltip(event) {
    if (!tooltip || tooltip.hidden) {
      return;
    }

    const offset = 14;
    let left = event.clientX + offset;
    let top = event.clientY + offset;

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;

    const rect = tooltip.getBoundingClientRect();
    if (rect.right > window.innerWidth - offset) {
      left = Math.max(offset, event.clientX - rect.width - offset);
    }

    if (rect.bottom > window.innerHeight - offset) {
      top = Math.max(offset, event.clientY - rect.height - offset);
    }

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
  }

  function showTooltip(target, event) {
    if (!supportsHoverTooltip) {
      return;
    }

    const message = target.dataset.uiTooltip;
    if (!message) {
      return;
    }

    activeTooltipTarget = target;
    const tooltipEl = ensureTooltip();
    tooltipEl.textContent = message;
    tooltipEl.hidden = false;
    positionTooltip(event);
  }

  function hideTooltip() {
    activeTooltipTarget = null;
    if (tooltip) {
      tooltip.hidden = true;
    }
  }

  function openLayer(mode) {
    lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    layer.hidden = false;
    modal.hidden = mode !== 'modal';
    sheet.hidden = mode !== 'sheet';
    document.body.classList.add('is-ui-open');
    activeMode = mode;

    requestAnimationFrame(() => {
      layer.classList.toggle('ui-modal-open', mode === 'modal');
      layer.classList.toggle('ui-sheet-open', mode === 'sheet');
    });
  }

  function closeLayer(result) {
    if (activeResolve) {
      activeResolve(result);
    }

    activeResolve = null;
    activeMode = null;
    sheetOptions = null;
    layer.classList.remove('ui-modal-open', 'ui-sheet-open');
    layer.hidden = true;
    modal.hidden = true;
    sheet.hidden = true;
    document.body.classList.remove('is-ui-open');

    if (lastFocused) {
      lastFocused.focus();
    }
  }

  function closeIfOpen(result) {
    if (!layer.hidden) {
      closeLayer(result);
    }
  }

  function confirm(options = {}) {
    return new Promise(resolve => {
      modalTitle.textContent = options.title || '확인';
      modalBody.textContent = options.message || '';
      modalConfirm.textContent = options.confirmText || '확인';
      modalCancel.textContent = options.cancelText || '취소';
      activeResolve = resolve;
      openLayer('modal');
      modalConfirm.focus();
    });
  }

  function promptSheet(options = {}) {
    return new Promise(resolve => {
      sheetOptions = options;
      sheetTitle.textContent = options.title || '입력';
      sheetLabel.textContent = options.label || '내용';
      sheetInput.value = options.value || '';
      sheetInput.placeholder = options.placeholder || '';
      sheetSubmit.textContent = options.confirmText || '확인';
      sheetCancel.textContent = options.cancelText || '취소';
      sheetImportanceGroup.hidden = !options.showImportance;
      sheetImportance.value = options.importance || 'D';
      sheetGoalGroup.hidden = !options.showGoal;
      sheetGoal.innerHTML = '<option value="">연결하지 않음</option>';
      (options.goalOptions || []).forEach(goal => {
        const option = document.createElement('option');
        option.value = String(goal.id || '');
        option.textContent = String(goal.label || goal.title || '');
        option.selected = Number(goal.id) === Number(options.goalId || 0);
        sheetGoal.appendChild(option);
      });
      sheetError.hidden = true;
      sheetError.textContent = '';
      activeResolve = resolve;
      openLayer('sheet');

      setTimeout(() => {
        sheetInput.focus();
        sheetInput.setSelectionRange(sheetInput.value.length, sheetInput.value.length);
      }, 0);
    });
  }

  function handleConfirm() {
    if (activeMode === 'modal') {
      closeLayer(true);
    }
  }

  function handleCancel(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }

    if (activeMode === 'modal' || activeMode === 'sheet') {
      closeLayer(null);
    }
  }

  function handleSheetSubmit(event) {
    event.preventDefault();
    const value = sheetInput.value.trim();
    if (!value) {
      sheetError.textContent = sheetOptions?.requiredMessage || '값을 입력해주세요.';
      sheetError.hidden = false;
      sheetInput.focus();
      return;
    }

    if (sheetOptions?.showImportance) {
      closeLayer({
        title: value,
        importance: String(sheetImportance.value || 'D').toUpperCase(),
        goalId: sheetGoalGroup.hidden ? null : (sheetGoal.value || null),
      });
      return;
    }

    closeLayer(value);
  }

  modalConfirm.addEventListener('click', handleConfirm);
  modalCancel.addEventListener('click', handleCancel);
  sheetCancel.addEventListener('click', handleCancel);
  sheetForm.addEventListener('submit', handleSheetSubmit);
  closers.forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault();
      event.stopPropagation();
      closeIfOpen(null);
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeIfOpen(null);
      hideTooltip();
    }
  });

  if (supportsHoverTooltip) {
    document.addEventListener('mouseover', event => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-ui-tooltip]')
        : null;

      if (target instanceof HTMLElement) {
        showTooltip(target, event);
      }
    });

    document.addEventListener('mousemove', event => {
      if (activeTooltipTarget) {
        positionTooltip(event);
      }
    });

    document.addEventListener('mouseout', event => {
      if (!activeTooltipTarget) {
        return;
      }

      const relatedTarget = event.relatedTarget instanceof Node ? event.relatedTarget : null;
      if (!relatedTarget || !activeTooltipTarget.contains(relatedTarget)) {
        hideTooltip();
      }
    });
  }

  document.addEventListener('touchstart', hideTooltip, { passive: true });

  window.addEventListener('scroll', hideTooltip, true);

  window.LifeFlowUI = {
    confirm,
    promptSheet,
    close: closeIfOpen,
    show(message) {
      if (window.LifeFlowToast && typeof window.LifeFlowToast.show === 'function') {
        window.LifeFlowToast.show(message);
        return;
      }

      window.alert(message);
    },
  };
})();
