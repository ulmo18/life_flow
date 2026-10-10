(function () {
  const toggleButton = document.querySelector('[data-menu-toggle]');
  const closeButtons = document.querySelectorAll('[data-menu-close]');
  const aside = document.getElementById('appAside');
  const overlay = document.querySelector('.app-overlay');
  const searchToggle = document.querySelector('[data-header-search-toggle]');
  const searchPanel = document.querySelector('[data-header-search-panel]');
  const searchInput = document.querySelector('[data-header-search-input]');
  const searchClose = document.querySelector('[data-header-search-close]');

  if (!toggleButton || !aside || !overlay) {
    return;
  }

  function setMenu(open) {
    if (open) setSearch(false);
    aside.classList.toggle('is-open', open);
    aside.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    overlay.hidden = !open;
    document.body.classList.toggle('is-menu-open', open);
  }

  function setSearch(open) {
    if (!searchPanel || !searchToggle) return;
    searchPanel.hidden = !open;
    searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      setMenu(false);
      window.setTimeout(() => searchInput?.focus(), 0);
    }
  }

  toggleButton.addEventListener('click', () => {
    setMenu(!aside.classList.contains('is-open'));
  });

  searchToggle?.addEventListener('click', () => setSearch(searchPanel?.hidden !== false));
  searchClose?.addEventListener('click', () => setSearch(false));
  searchPanel?.addEventListener('submit', event => {
    if (!searchInput) return;
    searchInput.value = searchInput.value.trim();
    if (searchInput.value.length < 2) {
      event.preventDefault();
      searchInput.setCustomValidity(searchPanel.dataset.minimumMessage || '검색어를 두 글자 이상 입력해주세요.');
      searchInput.reportValidity();
      return;
    }
    searchInput.setCustomValidity('');
  });
  searchInput?.addEventListener('input', () => searchInput.setCustomValidity(''));

  closeButtons.forEach(button => {
    button.addEventListener('click', () => setMenu(false));
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      setSearch(false);
      setMenu(false);
    }
  });
})();
