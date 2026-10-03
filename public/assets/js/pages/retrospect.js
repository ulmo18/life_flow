(function () {
  const settingsLayer = document.querySelector('[data-retrospect-settings-layer]');
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

  function openModal(layer) {
    if (!layer) return;
    layer.hidden = false;
    document.body.classList.add('is-ui-open');
  }

  function closeModal(layer) {
    if (!layer) return;
    layer.hidden = true;
    if ([settingsLayer, dateLayer, ...Object.values(detailLayers)].every(item => !item || item.hidden)) {
      document.body.classList.remove('is-ui-open');
    }
  }

  document.querySelector('[data-retrospect-open-settings]')?.addEventListener('click', () => openModal(settingsLayer));
  document.querySelector('[data-retrospect-open-date]')?.addEventListener('click', () => openModal(dateLayer));
  document.querySelectorAll('[data-retrospect-close-settings]').forEach(button => button.addEventListener('click', () => closeModal(settingsLayer)));
  document.querySelectorAll('[data-retrospect-close-date]').forEach(button => button.addEventListener('click', () => closeModal(dateLayer)));
  document.querySelectorAll('[data-detail-close]').forEach(button => button.addEventListener('click', () => {
    closeModal(button.closest('.retrospect-local-layer'));
  }));

  function selectRecordTab(name) {
    document.querySelectorAll('[data-record-tab]').forEach(button => {
      button.setAttribute('aria-selected', button.dataset.recordTab === name ? 'true' : 'false');
    });
    document.querySelectorAll('[data-record-panel]').forEach(panel => {
      panel.hidden = panel.dataset.recordPanel !== name;
    });
  }

  document.querySelectorAll('[data-record-tab]').forEach(button => {
    button.addEventListener('click', () => selectRecordTab(button.dataset.recordTab || 'routine'));
  });
  if (window.location.hash === '#schedule') selectRecordTab('schedule');
  if (window.location.hash === '#memo') selectRecordTab('memo');

  document.querySelectorAll('[data-detail-open]').forEach(button => {
    button.addEventListener('click', () => {
      const target = button.dataset.detailOpen || '';
      const detailKind = target.split('-detail-')[0];
      const detailLayer = detailLayers[detailKind];
      detailLayer?.querySelectorAll('[data-detail-panel]').forEach(panel => {
        panel.hidden = panel.dataset.detailPanel !== target;
      });
      openModal(detailLayer);
    });
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
    let currentStep = 0;
    let saveTimer = 0;

    function renderStep() {
      steps.forEach((step, index) => { step.hidden = index !== currentStep; });
      if (progress) progress.textContent = `${currentStep + 1} / ${steps.length}`;
      if (previous) previous.hidden = currentStep === 0;
      if (next) next.hidden = currentStep === steps.length - 1;
      if (publish) publish.hidden = currentStep !== steps.length - 1;
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
      collapse.textContent = '접기';
      steps[currentStep]?.querySelector('textarea')?.focus();
    });
    collapse?.addEventListener('click', () => {
      body.hidden = !body.hidden;
      kptSheet.classList.toggle('is-collapsed', body.hidden);
      retrospectPage?.classList.toggle('is-kpt-open', !body.hidden);
      collapse.textContent = body.hidden ? '펼치기' : '접기';
    });
    close?.addEventListener('click', () => {
      window.clearTimeout(saveTimer);
      saveDraft();
      kptSheet.hidden = true;
      body.hidden = false;
      kptSheet.classList.remove('is-collapsed');
      retrospectPage?.classList.remove('is-kpt-open');
      collapse.textContent = '접기';
    });
    previous?.addEventListener('click', () => { currentStep = Math.max(0, currentStep - 1); renderStep(); saveDraft(); });
    next?.addEventListener('click', () => { currentStep = Math.min(steps.length - 1, currentStep + 1); renderStep(); saveDraft(); });
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
  }

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeModal(settingsLayer);
    closeModal(dateLayer);
    Object.values(detailLayers).forEach(closeModal);
  });
})();
