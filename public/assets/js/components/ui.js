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
  const sheetImportanceInputs = sheetImportanceGroup
    ? sheetImportanceGroup.querySelectorAll('input[name="life_flow_sheet_importance"]')
    : [];
  const sheetGoalGroup = document.getElementById('lifeFlowSheetGoalGroup');
  const sheetGoal = document.getElementById('lifeFlowSheetGoal');
  const sheetError = document.getElementById('lifeFlowSheetError');
  const sheetForm = document.getElementById('lifeFlowSheetForm');
  const sheetSubmit = sheet ? sheet.querySelector('[data-ui-sheet-submit]') : null;
  const sheetCancel = sheet ? sheet.querySelector('[data-ui-cancel]') : null;
  const closers = layer ? layer.querySelectorAll('[data-ui-close]') : [];

  if (!layer || !modal || !sheet || !modalTitle || !modalBody || !modalConfirm || !modalCancel || !sheetTitle || !sheetLabel || !sheetInput || !sheetImportanceGroup || sheetImportanceInputs.length === 0 || !sheetGoalGroup || !sheetGoal || !sheetError || !sheetForm || !sheetSubmit || !sheetCancel) {
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

  function focusSheetInput() {
    try {
      sheetInput.focus({ preventScroll: true });
    } catch (error) {
      sheetInput.focus();
    }
    sheetInput.setSelectionRange(sheetInput.value.length, sheetInput.value.length);

    requestAnimationFrame(() => {
      sheetInput.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
  }

  function closeLayer(result) {
    if (activeResolve) {
      activeResolve(result);
    }

    activeResolve = null;
    activeMode = null;
    sheetOptions = null;

    if (document.activeElement instanceof HTMLElement && layer.contains(document.activeElement)) {
      document.activeElement.blur();
    }

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
      const notices = Array.isArray(options.noticeItems) ? options.noticeItems.filter(item => typeof item === 'string' && item.trim()) : [];
      const hasNotices = typeof options.question === 'string' && options.question.trim() && notices.length > 0;
      modal.classList.toggle('ui-confirm-with-notices', Boolean(hasNotices));
      modalBody.replaceChildren();
      if (hasNotices) {
        const question = document.createElement('div');
        question.className = 'ui-confirm-question';
        const text = document.createElement('strong');
        text.textContent = options.question;
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'ui-confirm-notice-toggle';
        toggle.setAttribute('aria-label', '전환 시 주의사항 보기');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', 'lifeFlowModalNotice');
        const icon = document.createElement('span');
        icon.textContent = '!';
        icon.setAttribute('aria-hidden', 'true');
        toggle.append(icon);
        const bubble = document.createElement('div');
        bubble.id = 'lifeFlowModalNotice';
        bubble.className = 'ui-confirm-notice';
        bubble.hidden = true;
        bubble.setAttribute('role', 'note');
        const list = document.createElement('ul');
        notices.forEach(notice => {
          const item = document.createElement('li');
          item.textContent = notice;
          list.append(item);
        });
        bubble.append(list);
        let pinned = false;
        const show = open => { bubble.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); };
        toggle.addEventListener('click', () => { pinned = !pinned; show(pinned); });
        toggle.addEventListener('pointerenter', event => { if (event.pointerType === 'mouse') show(true); });
        toggle.addEventListener('pointerleave', () => { if (!pinned) show(false); });
        toggle.addEventListener('keydown', event => {
          if (event.key === 'Escape' && !bubble.hidden) {
            event.preventDefault(); event.stopPropagation(); pinned = false; show(false);
          }
        });
        question.append(text, toggle);
        modalBody.append(question);
        if (typeof options.warning === 'string' && options.warning.trim()) {
          const warning = document.createElement('p');
          warning.className = 'ui-confirm-warning';
          warning.textContent = options.warning;
          modalBody.append(warning);
        }
        modalBody.append(bubble);
      } else {
        modalBody.textContent = options.message || '';
      }
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
      const selectedImportance = String(options.importance || 'D').toUpperCase();
      sheetImportanceInputs.forEach(input => {
        input.checked = input.value === selectedImportance;
      });
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
      focusSheetInput();
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
      const selectedImportance = Array.from(sheetImportanceInputs).find(input => input.checked);
      closeLayer({
        title: value,
        importance: String(selectedImportance?.value || 'D').toUpperCase(),
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
      if (!layer.hidden) {
        event.preventDefault();
        event.stopImmediatePropagation();
      }
      closeIfOpen(null);
      hideTooltip();
    }
    if (event.key !== 'Tab' || layer.hidden) return;
    const panel = activeMode === 'modal' ? modal : sheet;
    const controls = Array.from(panel.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
      .filter(control => control.getClientRects().length > 0);
    const first = controls[0], last = controls[controls.length - 1];
    if (!first) return;
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
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

  if (window.visualViewport) {
    let viewportResizeTimer = null;
    window.visualViewport.addEventListener('resize', () => {
      window.clearTimeout(viewportResizeTimer);
      viewportResizeTimer = window.setTimeout(() => {
        const activeElement = document.activeElement;
        if (!(activeElement instanceof HTMLElement) || !sheet.contains(activeElement)) return;

        const viewportBottom = window.visualViewport.offsetTop + window.visualViewport.height;
        if (activeElement.getBoundingClientRect().bottom > viewportBottom - 16) {
          activeElement.scrollIntoView({ block: 'center', inline: 'nearest' });
        }
      }, 80);
    });
  }

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
