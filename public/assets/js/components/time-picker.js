(function () {
  let dialog, active, trigger, options, hour, minute;
  const controls = new WeakMap();
  const pad = value => String(value).padStart(2, '0');
  const targetSelector = 'select[data-time-picker], input[type="time"][data-time-picker]';
  function choices(select) {
    if (select instanceof HTMLInputElement && select.type === 'time') {
      const values = [];
      const step = select.step && select.step !== 'any' ? Number(select.step) / 60 : 1;
      if (!Number.isInteger(step) || step < 1) return values;
      const start = /^\d{2}:\d{2}$/.test(select.min) ? Number(select.min.slice(0,2)) * 60 + Number(select.min.slice(3)) : 0;
      const end = /^\d{2}:\d{2}$/.test(select.max) ? Number(select.max.slice(0,2)) * 60 + Number(select.max.slice(3)) : 1439;
      for (let index = start; index <= end; index += step) {
        const label = pad(Math.floor(index / 60)) + ':' + pad(index % 60);
        values.push({hour:Math.floor(index / 60), minute:index % 60, value:label, label});
      }
      return values;
    }
    return Array.from(select.options).filter(option => !option.disabled && /^(?:[01]\d|2[0-4]):[0-5]\d$/.test(option.textContent.trim())).map(option => {
      const [h, m] = option.textContent.trim().split(':').map(Number);
      return { hour:h, minute:m, value:option.value, label:option.textContent.trim() };
    });
  }
  function sync(root = document) {
    root.querySelectorAll(targetSelector).forEach(select => {
      const button = controls.get(select); if (!button) return;
      button.textContent = (select instanceof HTMLInputElement ? select.value : select.selectedOptions[0]?.textContent) || '시간 선택';
      button.disabled = select.disabled;
      button.setAttribute('aria-label', (select.dataset.timeLabel || '시간 선택') + ', ' + button.textContent);
    });
  }
  function render() {
    const hours = dialog.querySelector('[data-time-hours]'), minutes = dialog.querySelector('[data-time-minutes]');
    const hourScroll = hours.scrollTop, minuteScroll = minutes.scrollTop;
    hours.replaceChildren(); minutes.replaceChildren();
    for (const h of [...new Set(options.map(option => option.hour))]) {
      const button = document.createElement('button'); button.type = 'button'; button.textContent = pad(h) + '시';
      button.className = 'ui-choice-button'; button.setAttribute('aria-pressed', String(h === hour));
      button.addEventListener('click', () => { hour = h; if (!options.some(option => option.hour === hour && option.minute === minute)) minute = options.find(option => option.hour === hour).minute; render(); hours.querySelector('[aria-pressed="true"]').focus({preventScroll:true}); });
      hours.append(button);
    }
    for (const m of [...new Set(options.map(option => option.minute))].sort((a,b) => a-b)) {
      const button = document.createElement('button'); button.type = 'button'; button.textContent = pad(m) + '분';
      button.className = 'ui-choice-button'; button.setAttribute('aria-pressed', String(m === minute));
      button.disabled = !options.some(option => option.hour === hour && option.minute === m);
      button.addEventListener('click', () => { minute = m; render(); minutes.querySelector('[aria-pressed="true"]').focus({preventScroll:true}); });
      minutes.append(button);
    }
    hours.scrollTop = hourScroll; minutes.scrollTop = minuteScroll;
    dialog.querySelector('[data-time-preview]').textContent = pad(hour) + ':' + pad(minute);
  }
  function setup() {
    dialog = document.createElement('dialog'); dialog.className = 'lf-date-dialog lf-time-dialog';
    dialog.setAttribute('aria-labelledby', 'lfTimeTitle');
    dialog.innerHTML = '<header class="lf-date-header"><strong id="lfTimeTitle">시간 선택</strong><button type="button" class="lf-date-close" data-time-close aria-label="닫기">×</button></header><div class="lf-date-body"><output class="lf-time-preview" data-time-preview aria-live="polite"></output><div class="lf-time-columns"><section aria-labelledby="lfTimeHour"><h3 id="lfTimeHour">시</h3><div data-time-hours></div></section><section aria-labelledby="lfTimeMinute"><h3 id="lfTimeMinute">분</h3><div data-time-minutes></div></section></div></div><footer class="lf-time-actions"><button class="btn btn-secondary" type="button" data-time-close>취소</button><button class="btn btn-primary" type="button" data-time-apply>선택 완료</button></footer>';
    dialog.querySelectorAll('[data-time-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => trigger?.focus({preventScroll:true}));
    dialog.querySelector('[data-time-apply]').addEventListener('click', () => {
      const selected = options.find(option => option.hour === hour && option.minute === minute);
      if (!selected || !active.isConnected || active.disabled) return;
      const changed = active.value !== selected.value; active.value = selected.value;
      if (changed) { active.dispatchEvent(new Event('input', {bubbles:true})); active.dispatchEvent(new Event('change', {bubbles:true})); }
      sync(active.form || document); dialog.close();
    });
    document.body.append(dialog);
  }
  function open(select, opener = select) {
    if (!(select instanceof HTMLSelectElement || (select instanceof HTMLInputElement && select.type === 'time')) || select.disabled || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return false;
    const available = choices(select); if (!available.length) return false;
    if (!dialog) setup(); if (dialog.open) return false;
    active = select; trigger = opener; options = available;
    const selected = options.find(option => option.value === select.value) || options[0]; hour = selected.hour; minute = selected.minute;
    dialog.querySelector('#lfTimeTitle').textContent = select.dataset.timeLabel || '시간 선택';
    render(); dialog.showModal();
    const focus = dialog.querySelector('[data-time-hours] [aria-pressed="true"]'); focus.focus({preventScroll:true}); focus.scrollIntoView({block:'nearest'});
    return true;
  }
  function enhance(root = document) {
    if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
    root.querySelectorAll(targetSelector).forEach(select => {
      if (controls.has(select) || !choices(select).length) return;
      const button = document.createElement('button'); button.type = 'button'; button.className = 'input lf-time-trigger';
      for (const name of ['aria-describedby','aria-invalid']) if (select.hasAttribute(name)) button.setAttribute(name, select.getAttribute(name));
      button.setAttribute('aria-haspopup','dialog'); button.addEventListener('click', () => open(select, button));
      select.after(button); select.hidden = true; controls.set(select,button);
      select.addEventListener('change', () => sync(select.form || document));
      select.form?.addEventListener('reset', () => setTimeout(() => sync(select.form),0));
    }); sync(root);
  }
  window.LifeFlowTimePicker = {open, sync, enhance}; enhance();
})();
