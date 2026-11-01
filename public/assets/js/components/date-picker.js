(function () {
  const words = {
    ko: { title:'날짜 선택', previous:'이전 달', next:'다음 달', year:'연도', month:'월', today:'오늘로 이동', close:'닫기', days:['일','월','화','수','목','금','토'] },
    en: { title:'Choose date', previous:'Previous month', next:'Next month', year:'Year', month:'Month', today:'Go to today', close:'Close', days:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] },
    ja: { title:'日付を選択', previous:'前の月', next:'次の月', year:'年', month:'月', today:'今日へ', close:'閉じる', days:['日','月','火','水','木','金','土'] },
  };
  const locale = words[document.documentElement.lang] ? document.documentElement.lang : 'ko';
  const t = words[locale];
  let dialog, activeInput, returnFocus, shownYear, shownMonth;
  const pad = number => String(number).padStart(2, '0');
  const iso = date => `${String(date.getUTCFullYear()).padStart(4, '0')}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())}`;
  function utcDate(year, month, day) { const date = new Date(0); date.setUTCFullYear(year, month, day); date.setUTCHours(0, 0, 0, 0); return date; }
  function parse(value) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null;
    const date = new Date(`${value}T00:00:00Z`);
    return Number.isNaN(date.getTime()) || iso(date) !== value ? null : date;
  }
  function today() { const now = new Date(); return parse(activeInput.dataset.today) || new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate())); }
  function allowed(value) { return (!activeInput.min || value >= activeInput.min) && (!activeInput.max || value <= activeInput.max); }
  function choose(value) {
    if (!allowed(value)) return;
    const changed = activeInput.value !== value;
    activeInput.value = value;
    dialog.close();
    if (changed) {
      activeInput.dispatchEvent(new Event('input', { bubbles:true }));
      activeInput.dispatchEvent(new Event('change', { bubbles:true }));
    }
  }
  function render(focusDate = '') {
    const yearSelect = dialog.querySelector('[data-date-year]');
    const monthSelect = dialog.querySelector('[data-date-month]');
    yearSelect.replaceChildren();
    const minYear = parse(activeInput.min)?.getUTCFullYear() || 1;
    const maxYear = parse(activeInput.max)?.getUTCFullYear() || 9999;
    for (let year = Math.max(minYear, shownYear - 100); year <= Math.min(maxYear, shownYear + 100); year++) {
      const option = new Option(String(year), String(year), false, year === shownYear);
      yearSelect.add(option);
    }
    monthSelect.value = String(shownMonth);
    const grid = dialog.querySelector('[data-date-grid]');
    grid.replaceChildren();
    t.days.forEach(day => { const label = document.createElement('span'); label.className = 'lf-date-weekday'; label.textContent = day; grid.append(label); });
    const first = utcDate(shownYear, shownMonth, 1);
    const count = utcDate(shownYear, shownMonth + 1, 0).getUTCDate();
    for (let blank = 0; blank < first.getUTCDay(); blank++) grid.append(document.createElement('span'));
    const todayValue = iso(today());
    for (let day = 1; day <= count; day++) {
      const value = `${String(shownYear).padStart(4, '0')}-${pad(shownMonth + 1)}-${pad(day)}`;
      const button = document.createElement('button');
      button.type = 'button'; button.textContent = String(day); button.dataset.date = value;
      button.disabled = !allowed(value);
      button.setAttribute('aria-label', new Intl.DateTimeFormat(locale, { dateStyle:'full', timeZone:'UTC' }).format(parse(value)));
      if (value === activeInput.value) { button.classList.add('is-selected'); button.setAttribute('aria-pressed', 'true'); }
      if (value === todayValue) { button.classList.add('is-today'); button.setAttribute('aria-current', 'date'); }
      button.addEventListener('click', () => choose(value));
      grid.append(button);
    }
    const todayButton = dialog.querySelector('[data-date-today]');
    todayButton.disabled = !allowed(todayValue);
    if (focusDate) grid.querySelector(`[data-date="${focusDate}"]`)?.focus();
  }
  function setup() {
    dialog = document.createElement('dialog');
    dialog.className = 'lf-date-dialog';
    dialog.setAttribute('aria-labelledby', 'lfDateTitle');
    dialog.innerHTML = `<header class="lf-date-header"><strong id="lfDateTitle"></strong><button type="button" class="lf-date-close" data-date-close>×</button></header><div class="lf-date-body"><div class="lf-date-navigation"><button type="button" data-date-previous>‹</button><select data-date-year></select><select data-date-month></select><button type="button" data-date-next>›</button></div><div class="lf-date-grid" data-date-grid></div><button type="button" class="lf-date-today" data-date-today></button></div>`;
    dialog.querySelector('#lfDateTitle').textContent = t.title;
    dialog.querySelector('[data-date-close]').setAttribute('aria-label', t.close);
    dialog.querySelector('[data-date-previous]').setAttribute('aria-label', t.previous);
    dialog.querySelector('[data-date-next]').setAttribute('aria-label', t.next);
    dialog.querySelector('[data-date-year]').setAttribute('aria-label', t.year);
    const months = dialog.querySelector('[data-date-month]');
    months.setAttribute('aria-label', t.month);
    for (let month = 0; month < 12; month++) months.add(new Option(new Intl.DateTimeFormat(locale, { month:'long', timeZone:'UTC' }).format(new Date(Date.UTC(2024, month, 1))), String(month)));
    dialog.querySelector('[data-date-today]').textContent = t.today;
    dialog.querySelector('[data-date-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => returnFocus?.focus({ preventScroll:true }));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    function moveMonth(delta) { const date = utcDate(shownYear, shownMonth + delta, 1); if (date.getUTCFullYear() < 1 || date.getUTCFullYear() > 9999) return; shownYear = date.getUTCFullYear(); shownMonth = date.getUTCMonth(); render(); }
    dialog.querySelector('[data-date-previous]').addEventListener('click', () => moveMonth(-1));
    dialog.querySelector('[data-date-next]').addEventListener('click', () => moveMonth(1));
    dialog.querySelector('[data-date-year]').addEventListener('change', event => { shownYear = Number(event.target.value); render(); });
    months.addEventListener('change', event => { shownMonth = Number(event.target.value); render(); });
    dialog.querySelector('[data-date-today]').addEventListener('click', () => choose(iso(today())));
    dialog.querySelector('[data-date-grid]').addEventListener('keydown', event => {
      const offsets = { ArrowLeft:-1, ArrowRight:1, ArrowUp:-7, ArrowDown:7 };
      if (!(event.key in offsets) || !event.target.dataset.date) return;
      event.preventDefault();
      const date = parse(event.target.dataset.date); date.setUTCDate(date.getUTCDate() + offsets[event.key]);
      const value = iso(date); if (!allowed(value)) return;
      shownYear = date.getUTCFullYear(); shownMonth = date.getUTCMonth(); render(value);
    });
    document.body.append(dialog);
  }
  function open(input, trigger = input) {
    if (!(input instanceof HTMLInputElement) || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return false;
    if (!dialog) setup();
    if (dialog.open) return true;
    activeInput = input; returnFocus = trigger;
    let selected = parse(input.value) || today();
    if (input.min && iso(selected) < input.min && parse(input.min)) selected = parse(input.min);
    if (input.max && iso(selected) > input.max && parse(input.max)) selected = parse(input.max);
    shownYear = selected.getUTCFullYear(); shownMonth = selected.getUTCMonth();
    render();
    try { dialog.showModal(); } catch (error) { return false; }
    (dialog.querySelector('.is-selected:not(:disabled)') || dialog.querySelector('[data-date-close]')).focus({ preventScroll:true });
    return true;
  }
  window.LifeFlowDatePicker = { open };
})();
