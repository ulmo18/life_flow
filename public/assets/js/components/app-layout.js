(function () {
  const toggleButton = document.querySelector('[data-menu-toggle]');
  const closeButtons = document.querySelectorAll('[data-menu-close]');
  const aside = document.getElementById('appAside');
  const overlay = document.querySelector('.app-overlay');

  if (!toggleButton || !aside || !overlay) {
    return;
  }

  function setMenu(open) {
    aside.classList.toggle('is-open', open);
    aside.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    overlay.hidden = !open;
    document.body.classList.toggle('is-menu-open', open);
  }

  toggleButton.addEventListener('click', () => {
    setMenu(!aside.classList.contains('is-open'));
  });

  closeButtons.forEach(button => {
    button.addEventListener('click', () => setMenu(false));
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      setMenu(false);
    }
  });
})();
