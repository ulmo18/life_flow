(function () {
  const settingsLayer = document.querySelector('[data-retrospect-settings-layer]');
  const dateLayer = document.querySelector('[data-retrospect-date-layer]');
  const openSettingsButton = document.querySelector('[data-retrospect-open-settings]');
  const openDateButton = document.querySelector('[data-retrospect-open-date]');

  function openLayer(layer) {
    if (!layer) return;

    layer.hidden = false;
    document.body.classList.add('is-ui-open');
  }

  function closeLayer(layer, event) {
    if (event) {
      event.preventDefault();
    }

    if (!layer) return;

    layer.hidden = true;
    if ((!settingsLayer || settingsLayer.hidden) && (!dateLayer || dateLayer.hidden)) {
      document.body.classList.remove('is-ui-open');
    }
  }

  openSettingsButton?.addEventListener('click', () => openLayer(settingsLayer));
  openDateButton?.addEventListener('click', () => openLayer(dateLayer));

  document.querySelectorAll('[data-retrospect-close-settings]').forEach(button => {
    button.addEventListener('click', event => closeLayer(settingsLayer, event));
  });

  document.querySelectorAll('[data-retrospect-close-date]').forEach(button => {
    button.addEventListener('click', event => closeLayer(dateLayer, event));
  });

  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', async event => {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      event.preventDefault();

      const accepted = window.LifeFlowUI?.confirm
        ? await window.LifeFlowUI.confirm({
            title: '회고 재발행',
            message: form.dataset.confirm || '계속 진행할까요?',
            confirmText: '재발행',
            cancelText: '취소',
          })
        : confirm(form.dataset.confirm || '계속 진행할까요?');

      if (!accepted) {
        return;
      }

      form.dataset.confirmed = '1';
      form.requestSubmit();
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') {
      return;
    }

    closeLayer(settingsLayer, event);
    closeLayer(dateLayer, event);
  });
})();
