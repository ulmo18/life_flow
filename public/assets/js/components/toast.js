(function () {
  const toast = document.getElementById('toast');
  const text = document.getElementById('toastText');
  const action = document.getElementById('toastAction');
  let toastTimer = null;

  function hide() {
    if (!toast) {
      return;
    }

    toast.classList.remove('show');
  }

  function show(message, options = {}) {
    if (!toast || !text || !action) {
      return;
    }

    clearTimeout(toastTimer);
    text.textContent = message;

    if (options.actionText && typeof options.onAction === 'function') {
      action.textContent = options.actionText;
      action.hidden = false;
      action.onclick = () => {
        options.onAction();
        hide();
      };
    } else {
      action.hidden = true;
      action.onclick = null;
    }

    toast.classList.remove('show');
    void toast.offsetWidth;
    toast.classList.add('show');

    toastTimer = setTimeout(hide, options.duration || 5000);
  }

  window.LifeFlowToast = { show, hide };

  const initialToast = document.querySelector('[data-toast-message]');
  if (initialToast instanceof HTMLElement && initialToast.dataset.toastMessage) {
    setTimeout(() => show(initialToast.dataset.toastMessage), 0);
  }
})();
