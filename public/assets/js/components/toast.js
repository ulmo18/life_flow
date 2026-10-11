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
    toast.dataset.variant = options.variant === 'celebration' ? 'celebration' : '';
    const duration = Number.isFinite(options.duration) ? Math.max(1000, options.duration) : 5000;
    toast.style.setProperty('--toast-duration', `${duration}ms`);

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

    toastTimer = setTimeout(hide, duration);
  }

  window.LifeFlowToast = { show, hide };

  const initialToast = document.querySelector('[data-toast-message]');
  if (initialToast instanceof HTMLElement && initialToast.dataset.toastMessage) {
    setTimeout(() => show(initialToast.dataset.toastMessage, {
      variant: initialToast.dataset.toastVariant || '',
      duration: initialToast.dataset.toastVariant === 'celebration' ? 6000 : 5000,
    }), 0);
  }
})();
