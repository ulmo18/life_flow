(function () {
  const historyMonthInput = document.getElementById('historyMonth');
  const historyMonthOpen = document.querySelector('[data-history-month-open]');
  const historyMonthDialog = document.getElementById('historyMonthDialog');
  if (historyMonthInput && historyMonthOpen && historyMonthDialog && typeof historyMonthDialog.showModal === 'function') {
    const yearLabel = historyMonthDialog.querySelector('[data-history-year]');
    const monthGrid = historyMonthDialog.querySelector('[data-history-month-grid]');
    let year = 0;
    function updateMonthLabel() {
      const [selectedYear, selectedMonth] = historyMonthInput.value.split('-');
      historyMonthOpen.textContent = `${selectedYear}년 ${Number(selectedMonth)}월 ▾`;
      historyMonthOpen.setAttribute('aria-label', `${selectedYear}년 ${Number(selectedMonth)}월 선택`);
    }
    function renderMonths() {
      yearLabel.textContent = `${year}년`;
      historyMonthDialog.querySelector('[data-history-year-prev]').disabled = year <= 1;
      historyMonthDialog.querySelector('[data-history-year-next]').disabled = year >= 9999;
      monthGrid.replaceChildren();
      for (let month = 1; month <= 12; month++) {
        const value = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`;
        const button = document.createElement('button');
        button.type = 'button'; button.textContent = `${month}월`;
        if (value === historyMonthInput.value) { button.classList.add('is-selected'); button.setAttribute('aria-pressed', 'true'); }
        button.addEventListener('click', () => { historyMonthInput.value = value; updateMonthLabel(); historyMonthDialog.close(); });
        monthGrid.append(button);
      }
    }
    historyMonthOpen.addEventListener('click', () => { year = Number(historyMonthInput.value.slice(0, 4)); renderMonths(); historyMonthDialog.showModal(); (monthGrid.querySelector('.is-selected') || monthGrid.querySelector('button'))?.focus(); });
    historyMonthDialog.querySelector('[data-history-year-prev]').addEventListener('click', () => { if (year > 1) { year--; renderMonths(); } });
    historyMonthDialog.querySelector('[data-history-year-next]').addEventListener('click', () => { if (year < 9999) { year++; renderMonths(); } });
    historyMonthDialog.querySelector('[data-history-month-close]').addEventListener('click', () => historyMonthDialog.close());
    historyMonthDialog.addEventListener('click', event => { if (event.target === historyMonthDialog) historyMonthDialog.close(); });
    historyMonthDialog.addEventListener('close', () => historyMonthOpen.focus({ preventScroll:true }));
    updateMonthLabel(); historyMonthInput.hidden = true; historyMonthOpen.hidden = false;
  }
  const dateLayer = document.querySelector('[data-retrospect-date-layer]');
  const detailLayers = {
    routine: document.querySelector('[data-routine-detail-layer]'),
    schedule: document.querySelector('[data-schedule-detail-layer]'),
    memo: document.querySelector('[data-memo-detail-layer]'),
  };
  const retrospectPage = document.querySelector('[data-retrospect-page]');
  const kptSheet = document.querySelector('[data-kpt-sheet]');
  const kptForm = document.querySelector('[data-kpt-form]');
  const routineState = window.LifeFlowRoutineState;
  const compositionInfo = document.querySelector('[data-composition-info]');
  const compositionHelp = document.getElementById('retrospectCompositionHelp');
  function setCompositionHelp(open) {
    if (!compositionInfo || !compositionHelp) return;
    compositionHelp.hidden = !open;
    compositionInfo.setAttribute('aria-expanded', String(open));
  }
  compositionInfo?.addEventListener('click', () => setCompositionHelp(compositionHelp?.hidden !== false));
  document.addEventListener('click', event => {
    if (compositionHelp && !compositionHelp.hidden && !compositionInfo?.contains(event.target) && !compositionHelp.contains(event.target)) setCompositionHelp(false);
  });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && compositionHelp && !compositionHelp.hidden) { setCompositionHelp(false); compositionInfo?.focus(); } });

  function openModal(layer) {
    if (!layer) return;
    layer.hidden = false;
    document.body.classList.add('is-ui-open');
  }

  function closeModal(layer) {
    if (!layer) return;
    layer.hidden = true;
    if ([dateLayer, ...Object.values(detailLayers)].every(item => !item || item.hidden)) {
      document.body.classList.remove('is-ui-open');
    }
  }

  document.querySelector('[data-retrospect-open-date]')?.addEventListener('click', () => openModal(dateLayer));
  document.querySelectorAll('[data-retrospect-close-date]').forEach(button => button.addEventListener('click', () => closeModal(dateLayer)));
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-detail-close]');
    if (button) closeModal(button.closest('.retrospect-local-layer'));
  });

  function selectRecordTab(name) {
    document.querySelectorAll('[data-record-tab]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.recordTab === name ? 'true' : 'false');
    });
    document.querySelectorAll('[data-record-panel]').forEach(panel => {
      panel.hidden = panel.dataset.recordPanel !== name;
    });
  }

  document.querySelectorAll('[data-record-tab]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-composition-target]').forEach(item => { item.classList.remove('is-active'); item.setAttribute('aria-pressed', 'false'); });
      document.querySelectorAll('[data-composition-category]').forEach(row => row.classList.remove('is-composition-match'));
      selectRecordTab(button.dataset.recordTab || 'routine');
    });
  });
  document.querySelectorAll('[data-composition-target]').forEach(button => {
    button.addEventListener('click', () => {
      const kind = button.dataset.compositionTarget;
      document.querySelectorAll('[data-composition-target]').forEach(item => { item.classList.toggle('is-active', item === button); item.setAttribute('aria-pressed', String(item === button)); });
      document.querySelectorAll('[data-composition-category]').forEach(row => row.classList.toggle('is-composition-match', (row.dataset.compositionCategory || '').split(/\s+/).includes(kind)));
      selectRecordTab(kind === 'plan' || kind === 'actual' ? 'schedule' : kind);
      document.querySelector('.retrospect-daily-card')?.scrollIntoView({ behavior:'auto', block:'nearest' });
    });
  });
  if (window.location.hash === '#schedule') selectRecordTab('schedule');
  if (window.location.hash === '#memo') selectRecordTab('memo');

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-detail-open]');
    if (!button) return;
    const target = button.dataset.detailOpen || '';
    const detailKind = target.split('-detail-')[0];
    const detailLayer = detailLayers[detailKind];
    detailLayer?.querySelectorAll('[data-detail-panel]').forEach(panel => {
      panel.hidden = panel.dataset.detailPanel !== target;
    });
    openModal(detailLayer);
  });

  let sortController = null;
  let sortRequestId = 0;
  document.addEventListener('click', async event => {
    const link = event.target.closest('.retrospect-sort-tabs a');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (typeof fetch !== 'function' || typeof AbortController !== 'function' || typeof DOMParser !== 'function') return;
    const panel = link.closest('[data-record-panel="schedule"]');
    if (!panel || !detailLayers.schedule) return;
    event.preventDefault();
    sortController?.abort();
    sortController = new AbortController();
    const requestId = ++sortRequestId;
    const error = panel.querySelector('[data-schedule-sort-error]');
    if (error) { error.hidden = true; error.textContent = ''; }
    panel.setAttribute('aria-busy', 'true');
    try {
      const requested = new URL(link.href, window.location.href);
      const endpoint = new URL('/retrospect/schedule-sort', window.location.origin);
      for (const key of ['date', 'sort', 'edit']) {
        if (requested.searchParams.has(key)) endpoint.searchParams.set(key, requested.searchParams.get(key));
      }
      const response = await fetch(endpoint.toString(), { headers: { Accept: 'application/json' }, signal: sortController.signal });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.message || '일정 정렬에 실패했습니다. 다시 시도해 주세요.');
      if (requestId !== sortRequestId) return;
      const parsedPanel = new DOMParser().parseFromString(payload.panel, 'text/html').querySelector('[data-record-panel="schedule"]');
      const parsedDetails = new DOMParser().parseFromString(payload.details, 'text/html').querySelector('[data-schedule-detail-layer]');
      if (!parsedPanel || !parsedDetails) throw new Error('일정 정렬 결과를 불러오지 못했습니다.');
      parsedPanel.hidden = panel.hidden;
      panel.replaceWith(parsedPanel);
      detailLayers.schedule.replaceWith(parsedDetails);
      detailLayers.schedule = parsedDetails;
      const activeKind = document.querySelector('[data-composition-target].is-active')?.dataset.compositionTarget;
      if (activeKind === 'plan' || activeKind === 'actual') {
        parsedPanel.querySelectorAll('[data-composition-category]').forEach(row => row.classList.toggle('is-composition-match', (row.dataset.compositionCategory || '').split(/\s+/).includes(activeKind)));
      }
      window.history.replaceState(window.history.state, '', requested.href);
      if (!parsedPanel.hidden) parsedPanel.querySelector('.retrospect-sort-tabs [aria-current="page"]')?.focus({ preventScroll: true });
    } catch (exception) {
      if (exception.name === 'AbortError' || requestId !== sortRequestId) return;
      if (error) { error.textContent = '일정 정렬에 실패했습니다. 다시 시도해 주세요.'; error.hidden = false; }
    } finally {
      if (requestId === sortRequestId) panel.removeAttribute('aria-busy');
    }
  });

  document.querySelectorAll('[data-retrospect-routine-toggle-form]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.message || '루틴 상태를 변경할 수 없습니다.');
        const button = form.querySelector('[data-routine-toggle-control]');
        if (routineState) routineState.apply(button, payload.state || '');
        window.LifeFlowToast?.show(payload.message || '루틴 상태를 변경했습니다.');
      } catch (error) {
        window.LifeFlowToast?.show(error.message || '루틴 상태를 변경할 수 없습니다.');
      }
    });
  });

  if (kptSheet && kptForm) {
    const steps = Array.from(kptForm.querySelectorAll('[data-kpt-step]'));
    const progress = kptForm.querySelector('[data-kpt-progress]');
    const previous = kptForm.querySelector('[data-kpt-previous]');
    const next = kptForm.querySelector('[data-kpt-next]');
    const publish = kptForm.querySelector('[data-kpt-publish]');
    const collapse = kptForm.querySelector('[data-kpt-collapse]');
    const close = kptForm.querySelector('[data-kpt-close]');
    const body = kptForm.querySelector('.retrospect-kpt-body');
    const saveStatus = kptForm.querySelector('[data-kpt-save-status]');
    const reviewTimer = kptForm.querySelector('[data-kpt-timer]');
    const reviewTimerMessage = kptForm.querySelector('[data-kpt-timer-message]');
    let currentStep = 0;
    let saveTimer = 0;
    let reviewTimerId = 0;
    let reviewDeadline = 0;

    function activeStepInput() {
      return steps[currentStep]?.querySelector('textarea');
    }

    function updateKeyboardLayout() {
      if (!window.visualViewport || kptSheet.hidden) return;
      const keyboardOpen = document.activeElement?.matches('.retrospect-kpt-body textarea') === true;
      kptSheet.classList.toggle('is-keyboard-open', keyboardOpen && !body.hidden);
      kptSheet.style.setProperty('--kpt-visible-height', `${window.visualViewport.height}px`);
    }

    function renderReviewTimer() {
      if (!reviewTimer || reviewDeadline === 0) return;
      const remainingSeconds = Math.max(0, Math.ceil((reviewDeadline - Date.now()) / 1000));
      const minutes = Math.floor(remainingSeconds / 60);
      const seconds = remainingSeconds % 60;
      reviewTimer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
      reviewTimer.setAttribute('aria-label', `회고 남은 시간 ${minutes}분 ${seconds}초`);

      if (remainingSeconds > 0) return;
      window.clearInterval(reviewTimerId);
      reviewTimerId = 0;
      reviewTimer.classList.add('is-expired');
      if (reviewTimerMessage) reviewTimerMessage.textContent = '10분이 지났어요. 지금까지 적은 내용으로 마무리해도 충분해요.';
    }

    function startReviewTimer() {
      window.clearInterval(reviewTimerId);
      reviewDeadline = Date.now() + (10 * 60 * 1000);
      reviewTimer?.classList.remove('is-expired');
      if (reviewTimerMessage) reviewTimerMessage.textContent = '';
      renderReviewTimer();
      reviewTimerId = window.setInterval(renderReviewTimer, 1000);
    }

    function stopReviewTimer() {
      window.clearInterval(reviewTimerId);
      reviewTimerId = 0;
      reviewDeadline = 0;
      if (reviewTimer) {
        reviewTimer.textContent = '10:00';
        reviewTimer.classList.remove('is-expired');
        reviewTimer.setAttribute('aria-label', '회고 남은 시간 10분');
      }
      if (reviewTimerMessage) reviewTimerMessage.textContent = '';
    }

    function renderStep() {
      const collapsed = body.hidden;
      steps.forEach((step, index) => { step.hidden = index !== currentStep; });
      if (progress) progress.textContent = `${currentStep + 1} / ${steps.length}`;
      if (close) close.hidden = !collapsed;
      if (previous) previous.hidden = collapsed || currentStep === 0;
      if (next) next.hidden = collapsed || currentStep === steps.length - 1;
      if (publish) publish.hidden = collapsed || currentStep !== steps.length - 1;
      if (collapse) collapse.textContent = collapsed ? '돌아가기' : '기록 보기';
    }

    async function saveDraft() {
      if (saveStatus) saveStatus.textContent = '저장 중…';
      try {
        const response = await fetch(kptForm.action, {
          method: 'POST',
          body: new FormData(kptForm),
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) throw new Error(payload.message || '임시 저장에 실패했습니다.');
        if (saveStatus) saveStatus.textContent = '저장됨';
      } catch (error) {
        if (saveStatus) saveStatus.textContent = '저장 실패';
      }
    }

    kptForm.querySelectorAll('textarea').forEach(textarea => {
      textarea.addEventListener('input', () => {
        if (saveStatus) saveStatus.textContent = '수정됨';
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveDraft, 700);
      });
    });
    document.querySelector('[data-kpt-open]')?.addEventListener('click', () => {
      kptSheet.hidden = false;
      body.hidden = false;
      kptSheet.classList.remove('is-collapsed');
      retrospectPage?.classList.add('is-kpt-open');
      renderStep();
      startReviewTimer();
      activeStepInput()?.focus();
      window.setTimeout(updateKeyboardLayout, 80);
    });
    collapse?.addEventListener('click', () => {
      body.hidden = !body.hidden;
      kptSheet.classList.toggle('is-collapsed', body.hidden);
      retrospectPage?.classList.toggle('is-kpt-open', !body.hidden);
      renderStep();
      if (body.hidden) {
        window.clearTimeout(saveTimer);
        saveDraft();
        activeStepInput()?.blur();
        kptSheet.classList.remove('is-keyboard-open');
      } else {
        activeStepInput()?.focus();
        window.setTimeout(updateKeyboardLayout, 80);
      }
    });
    close?.addEventListener('click', () => {
      window.clearTimeout(saveTimer);
      saveDraft();
      kptSheet.hidden = true;
      body.hidden = false;
      kptSheet.classList.remove('is-collapsed');
      retrospectPage?.classList.remove('is-kpt-open');
      renderStep();
      stopReviewTimer();
    });
    function moveStep(step) {
      currentStep = Math.max(0, Math.min(steps.length - 1, step));
      renderStep();
      saveDraft();
      activeStepInput()?.focus();
      window.setTimeout(updateKeyboardLayout, 80);
    }

    previous?.addEventListener('click', () => moveStep(currentStep - 1));
    next?.addEventListener('click', () => moveStep(currentStep + 1));
    kptForm.addEventListener('submit', async event => {
      if (event.submitter !== publish || kptForm.dataset.publishConfirmed === '1') return;
      const hasBlank = Array.from(kptForm.querySelectorAll('textarea')).some(textarea => !textarea.value.trim());
      if (!hasBlank) return;
      event.preventDefault();
      const accepted = window.LifeFlowUI?.confirm
        ? await window.LifeFlowUI.confirm({ title: '회고 발행', message: '비어 있는 KPT 항목이 있습니다. 그대로 발행할까요?', confirmText: '발행', cancelText: '계속 작성' })
        : window.confirm('비어 있는 KPT 항목이 있습니다. 그대로 발행할까요?');
      if (accepted) {
        kptForm.dataset.publishConfirmed = '1';
        kptForm.requestSubmit(publish);
      }
    });
    renderStep();
    window.visualViewport?.addEventListener('resize', updateKeyboardLayout);
    kptForm.querySelectorAll('textarea').forEach(textarea => {
      textarea.addEventListener('focus', () => window.setTimeout(updateKeyboardLayout, 80));
      textarea.addEventListener('blur', () => window.setTimeout(updateKeyboardLayout, 80));
    });
  }

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeModal(dateLayer);
    Object.values(detailLayers).forEach(closeModal);
  });
})();
