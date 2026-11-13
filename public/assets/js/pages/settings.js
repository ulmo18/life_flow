(function () {
  const bridge = window.LifeFlowAndroidBridge;
  const toast = window.LifeFlowToast;
  const page = document.querySelector('[data-settings-page]');
  if (location.hash) {
    const target = document.getElementById(location.hash.slice(1));
    target?.closest('details.settings-section')?.setAttribute('open', '');
  }
  const themeStatus = document.querySelector('[data-theme-status]');
  const available = Boolean(bridge && bridge.isAvailable());
  const permissionStatus = document.querySelector('[data-permission-status]');
  if (permissionStatus && page) permissionStatus.textContent = available ? page.dataset.permissionUnknown : page.dataset.appOnly;
  document.querySelectorAll('[data-notification-permission]').forEach(button => { button.hidden = !available; });
  document.querySelectorAll('[data-notification-test]').forEach(button => { button.disabled = !available; });
  document.querySelectorAll('[data-settings-reminder]').forEach(row => {
    const toggle = row.querySelector('[data-reminder-enabled]'), time = row.querySelector('[data-reminder-time]');
    if (!toggle || !time) return;
    const sync = () => { time.disabled = !toggle.checked; window.LifeFlowTimePicker?.sync(row); };
    toggle.addEventListener('change', sync); sync();
  });
  const master = document.getElementById('notification_enabled');
  const masterHint = document.querySelector('[data-master-off]');
  if (master && masterHint) {
    const sync = () => { masterHint.hidden = master.checked; };
    master.addEventListener('change', sync); sync();
  }
  document.querySelectorAll('[data-settings-save-form]').forEach(form => {
    const snapshot = () => JSON.stringify(Array.from(form.querySelectorAll('input,select')).map(input => [input.name, input.type === 'checkbox' ? input.checked : input.value]));
    const initial = snapshot(), status = form.querySelector('[data-settings-save-status]');
    const sync = () => { if (status && page) status.textContent = snapshot() === initial && form.dataset.settingsDraft !== '1' ? page.dataset.statusSaved : page.dataset.statusUnsaved; };
    form.addEventListener('input', sync); form.addEventListener('change', sync);
  });
  const themeForm = document.querySelector('[data-theme-form]');

  function notify(message) {
    if (toast && typeof toast.show === 'function') {
      toast.show(message);
      return;
    }

    alert(message);
  }

  if (themeForm) {
    let persistedTheme = document.body.dataset.theme === 'dark' ? 'dark' : 'light';
    let requestVersion = 0;
    let saveChain = Promise.resolve();

    themeForm.querySelectorAll('input[name="theme"]').forEach(input => {
      input.addEventListener('change', async () => {
        if (!input.checked) return;

        const selectedTheme = input.value === 'dark' ? 'dark' : 'light';
        const version = ++requestVersion;
        const formData = new FormData(themeForm);
        formData.set('theme', selectedTheme);
        document.body.dataset.theme = selectedTheme;
        if (themeStatus && page) themeStatus.textContent = page.dataset.themeSaving;

        const pendingSave = saveChain.catch(() => {}).then(async () => {
          const response = await fetch(themeForm.action, {
            method: 'POST',
            body: formData,
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          const payload = await response.json();
          if (!response.ok || !payload.ok) {
            throw new Error(payload.message || '화면 모드 저장에 실패했습니다.');
          }

          return payload.theme === 'dark' ? 'dark' : 'light';
        });
        saveChain = pendingSave;

        try {
          persistedTheme = await pendingSave;
          if (version === requestVersion && themeStatus && page) themeStatus.textContent = page.dataset.themeSaved;
        } catch (error) {
          if (version !== requestVersion) return;

          document.body.dataset.theme = persistedTheme;
          const fallbackInput = themeForm.querySelector(`input[name="theme"][value="${persistedTheme}"]`);
          if (fallbackInput) fallbackInput.checked = true;
          if (themeStatus) themeStatus.textContent = error instanceof Error ? error.message : '화면 모드 저장에 실패했습니다.';
          notify(error instanceof Error ? error.message : '화면 모드 저장에 실패했습니다.');
        }
      });
    });
  }

  document.querySelectorAll('[data-notification-permission]').forEach(button => {
    button.addEventListener('click', () => {
      if (bridge && bridge.requestPermission()) {
        notify(page?.dataset.permissionSent || '앱에 알림 권한 요청을 전달했습니다.');
        return;
      }

      notify(available ? page?.dataset.bridgeFailed : page?.dataset.appOnly);
    });
  });

  document.querySelectorAll('[data-notification-test]').forEach(button => {
    button.addEventListener('click', () => {
      if (bridge && bridge.showNotification('LifeFlow', '테스트 알림입니다.')) {
        notify(page?.dataset.testSent || '테스트 알림 요청을 앱에 전달했습니다. 실제 수신 여부를 확인해 주세요.');
        return;
      }

      notify(available ? page?.dataset.bridgeFailed : page?.dataset.appOnly);
    });
  });
})();
