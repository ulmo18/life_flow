(function () {
  const bridge = window.LifeFlowAndroidBridge;
  const toast = window.LifeFlowToast;
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
        } catch (error) {
          if (version !== requestVersion) return;

          document.body.dataset.theme = persistedTheme;
          const fallbackInput = themeForm.querySelector(`input[name="theme"][value="${persistedTheme}"]`);
          if (fallbackInput) fallbackInput.checked = true;
          notify(error instanceof Error ? error.message : '화면 모드 저장에 실패했습니다.');
        }
      });
    });
  }

  document.querySelectorAll('[data-notification-permission]').forEach(button => {
    button.addEventListener('click', () => {
      if (bridge && bridge.requestPermission()) {
        notify('앱 알림 권한 요청을 보냈습니다.');
        return;
      }

      notify('이 기능은 안드로이드 앱에서 사용할 수 있습니다.');
    });
  });

  document.querySelectorAll('[data-notification-test]').forEach(button => {
    button.addEventListener('click', () => {
      if (bridge && bridge.showNotification('LifeFlow', '테스트 알림입니다.')) {
        notify('테스트 알림을 보냈습니다.');
        return;
      }

      notify('이 기능은 안드로이드 앱에서 사용할 수 있습니다.');
    });
  });
})();
