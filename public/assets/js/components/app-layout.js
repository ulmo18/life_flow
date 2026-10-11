(function () {
  const toggleButtons = Array.from(document.querySelectorAll('[data-menu-toggle]'));
  const closeButtons = document.querySelectorAll('[data-menu-close]');
  const aside = document.getElementById('appAside');
  const overlay = document.querySelector('.app-overlay');
  const searchToggles = Array.from(document.querySelectorAll('[data-header-search-toggle]'));
  const searchPanel = document.querySelector('[data-header-search-panel]');
  const searchInput = document.querySelector('[data-header-search-input]');
  const searchClose = document.querySelector('[data-header-search-close]');
  const siteHeader = document.querySelector('[data-site-header]');

  if (toggleButtons.length === 0 || !aside || !overlay) {
    return;
  }

  function setMenu(open) {
    if (open) setSearch(false);
    aside.classList.toggle('is-open', open);
    aside.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggleButtons.forEach(button => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
    overlay.hidden = !open;
    document.body.classList.toggle('is-menu-open', open);
  }

  function setSearch(open) {
    if (!searchPanel || searchToggles.length === 0) return;
    searchPanel.hidden = !open;
    searchToggles.forEach(button => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
    siteHeader?.classList.toggle('is-search-open', open);
    if (open) {
      setMenu(false);
      window.setTimeout(() => searchInput?.focus(), 0);
    }
  }

  toggleButtons.forEach(button => button.addEventListener('click', () => {
    setMenu(!aside.classList.contains('is-open'));
  }));

  searchToggles.forEach(button => button.addEventListener('click', () => setSearch(searchPanel?.hidden !== false)));
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

  document.querySelectorAll('[data-daily-date-picker]').forEach(input => {
    input.addEventListener('change', () => input.form?.requestSubmit());
  });

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
