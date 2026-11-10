(function () {
  const toggleButtons = Array.from(document.querySelectorAll('[data-menu-toggle]'));
  const closeButtons = document.querySelectorAll('[data-menu-close]');
  const aside = document.getElementById('appAside');
  const overlay = document.querySelector('.app-overlay');
  const searchToggles = Array.from(document.querySelectorAll('[data-header-search-toggle]'));
  const searchPanel = document.querySelector('[data-header-search-panel]');
  const searchInput = document.querySelector('[data-header-search-input]');
  const searchClose = document.querySelector('[data-header-search-close]');
  const searchBackdrop = document.querySelector('[data-header-search-backdrop]');
  const siteHeader = document.querySelector('[data-site-header]');
  const appShell = document.querySelector('.app-shell');
  let searchInertStates = [];
  let searchReturnFocus = null;

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
    if (open && searchPanel.hidden === false) return;
    if (!open && searchPanel.hidden) return;
    if (open) {
      setMenu(false);
      searchReturnFocus = document.activeElement;
      searchInertStates = Array.from(appShell?.children || []).filter(element => element !== siteHeader && element !== searchBackdrop).map(element => [element, element.inert]);
      searchInertStates.forEach(([element]) => { element.inert = true; });
    } else {
      searchInertStates.forEach(([element, wasInert]) => { element.inert = wasInert; });
      searchInertStates = [];
    }
    searchPanel.hidden = !open;
    if (searchBackdrop) searchBackdrop.hidden = !open;
    searchToggles.forEach(button => button.setAttribute('aria-expanded', open ? 'true' : 'false'));
    siteHeader?.classList.toggle('is-search-open', open);
    document.body.classList.toggle('is-header-search-open', open);
    if (open) {
      window.setTimeout(() => { if (!searchPanel.hidden) searchInput?.focus(); }, 0);
    } else if (searchReturnFocus instanceof HTMLElement) searchReturnFocus.focus({ preventScroll:true });
  }

  toggleButtons.forEach(button => button.addEventListener('click', () => {
    setMenu(!aside.classList.contains('is-open'));
  }));

  searchToggles.forEach(button => button.addEventListener('click', () => setSearch(searchPanel?.hidden !== false)));
  searchClose?.addEventListener('click', () => setSearch(false));
  searchBackdrop?.addEventListener('click', () => setSearch(false));
  searchPanel?.addEventListener('keydown', event => {
    if (event.key !== 'Tab') return;
    const controls = Array.from(searchPanel.querySelectorAll('input:not(:disabled), button:not(:disabled)'));
    if (!controls.length) return;
    if (event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls[controls.length - 1].focus(); }
    else if (!event.shiftKey && document.activeElement === controls[controls.length - 1]) { event.preventDefault(); controls[0].focus(); }
  });
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
    input.addEventListener('click', event => {
      if (window.LifeFlowDatePicker?.open(input)) event.preventDefault();
    });
    input.addEventListener('change', () => input.form?.requestSubmit());
  });

  const dailyNavigator = document.querySelector('[data-daily-date-session]');
  if (dailyNavigator) {
    const key = `lifeflow.daily-date.${dailyNavigator.dataset.dailyDateSession}`;
    const preservedContext = document.querySelector('[data-daily-context-preserve]');
    const current = (preservedContext ? '' : document.querySelector('[data-daily-context-date]')?.dataset.dailyContextDate)
      || document.querySelector('[data-calendar-date]')?.dataset.calendarDate;
    let selected = /^\d{4}-\d{2}-\d{2}$/.test(current || '') ? current : '';
    try {
      if (selected) sessionStorage.setItem(key, selected);
      else selected = sessionStorage.getItem(key) || '';
    } catch (error) { /* Navigation retains its default URLs when storage is unavailable. */ }
    if (preservedContext) {
      const input = preservedContext.querySelector('[data-daily-date-picker]');
      selected = /^\d{4}-\d{2}-\d{2}$/.test(selected) ? selected : (input?.dataset.today || '');
      if (input && /^\d{4}-\d{2}-\d{2}$/.test(selected)) {
        const [year, month, day] = selected.split('-').map(Number);
        const date = new Date(year, month - 1, day, 12);
        const shift = amount => { const next = new Date(year, month - 1, day + amount, 12); return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`; };
        input.value = selected;
        input.setAttribute('aria-label', `${year}년 ${month}월 ${day}일 날짜 선택`);
        const value = preservedContext.querySelector('.daily-date-value');
        if (value) { value.textContent = `${month}.${day}`; value.classList.toggle('is-saturday', date.getDay() === 6); value.classList.toggle('is-holiday', date.getDay() === 0); }
        const weekday = preservedContext.querySelector('.daily-date-weekday');
        if (weekday) weekday.textContent = ['일', '월', '화', '수', '목', '금', '토'][date.getDay()];
        const swipe = preservedContext.querySelector('[data-daily-date-swipe]');
        if (swipe) { swipe.dataset.prevDate = shift(-1); swipe.dataset.nextDate = shift(1); }
        preservedContext.querySelectorAll('.daily-date-arrow').forEach((link, index) => { const url = new URL(link.href); url.searchParams.set('date', shift(index === 0 ? -1 : 1)); link.href = url.href; });
        const todayLink = preservedContext.querySelector('.daily-today-link');
        if (todayLink) todayLink.hidden = selected === input.dataset.today;
        document.querySelectorAll('[data-report-context-link]').forEach(link => { const url = new URL(link.href); url.searchParams.set('date', selected); link.href = url.href; });
      }
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(selected)) dailyNavigator.querySelectorAll('a.bottom-nav-item').forEach(link => {
      const url = new URL(link.href, window.location.href);
      if (!['/calendar', '/todo', '/routine', '/retrospect'].includes(url.pathname) || url.searchParams.has('date')) return;
      url.searchParams.set('date', selected);
      const today = document.querySelector('[data-daily-date-picker], [data-calendar-date-picker]')?.dataset.today;
      if (url.pathname === '/calendar' && selected !== today) url.searchParams.delete('focus');
      link.href = `${url.pathname}${url.search}${url.hash}`;
    });
  }

  document.querySelectorAll('[data-daily-date-swipe]').forEach(zone => {
    let gesture = null, suppressClick = false;
    function finishGesture(x, y) {
      if (!gesture) return;
      const dx = x - gesture.x, dy = y - gesture.y;
      gesture = null;
      if (Math.abs(dx) < 48 || Math.abs(dx) <= Math.abs(dy)) return;
      const date = dx > 0 ? zone.dataset.prevDate : zone.dataset.nextDate;
      if (!/^\d{4}-\d{2}-\d{2}$/.test(date || '')) return;
      suppressClick = true;
      window.setTimeout(() => { suppressClick = false; }, 500);
      const form = zone.querySelector('form');
      const url = new URL(form.action, window.location.href);
      url.searchParams.set('date', date);
      const view = form.querySelector('input[name="view"]')?.value;
      if (view) url.searchParams.set('view', view);
      const mode = form.querySelector('input[name="mode"]')?.value;
      if (mode) url.searchParams.set('mode', mode);
      window.location.href = url.href;
    }
    if (window.PointerEvent) {
      zone.addEventListener('pointerdown', event => { if (event.isPrimary && (event.pointerType !== 'mouse' || event.button === 0)) gesture = { id:event.pointerId, x:event.clientX, y:event.clientY }; });
      document.addEventListener('pointerup', event => { if (gesture?.id === event.pointerId) finishGesture(event.clientX, event.clientY); });
      document.addEventListener('pointercancel', event => { if (gesture?.id === event.pointerId) gesture = null; });
    } else {
      zone.addEventListener('touchstart', event => { if (event.touches.length === 1) gesture = { x:event.touches[0].clientX, y:event.touches[0].clientY }; }, { passive:true });
      document.addEventListener('touchend', event => { if (event.changedTouches.length === 1) finishGesture(event.changedTouches[0].clientX, event.changedTouches[0].clientY); else gesture = null; }, { passive:true });
      document.addEventListener('touchcancel', () => { gesture = null; }, { passive:true });
    }
    zone.addEventListener('dragstart', event => event.preventDefault());
    zone.addEventListener('click', event => { if (suppressClick) { event.preventDefault(); event.stopPropagation(); suppressClick = false; } }, true);
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
