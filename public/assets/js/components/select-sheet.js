(function () {
  const controls = new WeakMap();
  let dialog, active, opener;
  const page = document.querySelector('[data-settings-page]');
  function sync(root = document) {
    root.querySelectorAll('select[data-select-sheet]').forEach(select => {
      const button = controls.get(select); if (!button) return;
      button.textContent = select.selectedOptions[0]?.textContent || '';
      button.disabled = select.disabled;
      button.setAttribute('aria-label', (select.labels?.[0]?.textContent.trim() || '') + ': ' + button.textContent);
    });
  }
  function render() {
    const query = dialog.querySelector('[data-select-search]').value.trim().toLocaleLowerCase();
    const list = dialog.querySelector('[data-select-options]'); list.replaceChildren();
    for (const option of active.options) {
      if (option.disabled || !((option.textContent + ' ' + option.value).toLocaleLowerCase().includes(query))) continue;
      const button = document.createElement('button'); button.type = 'button'; button.className = 'ui-choice-button';
      button.textContent = option.textContent; button.setAttribute('aria-pressed', String(option.value === active.value));
      button.addEventListener('click', () => {
        if (!active.isConnected || active.disabled) return;
        const changed = active.value !== option.value; active.value = option.value;
        if (changed) { active.dispatchEvent(new Event('input', {bubbles:true})); active.dispatchEvent(new Event('change', {bubbles:true})); }
        sync(active.form || document); dialog.close();
      }); list.append(button);
    }
    dialog.querySelector('[data-select-empty]').hidden = list.childElementCount > 0;
  }
  function setup() {
    dialog = document.createElement('dialog'); dialog.className = 'lf-date-dialog lf-select-dialog'; dialog.setAttribute('aria-labelledby','lfSelectTitle');
    dialog.innerHTML = '<header class="lf-date-header"><strong id="lfSelectTitle"></strong><button class="lf-date-close" type="button" data-select-close aria-label="닫기">×</button></header><div class="lf-select-body"><label class="sr-only" for="lfSelectSearch"></label><input class="input" id="lfSelectSearch" type="search" autocomplete="off" data-select-search><div class="lf-select-options" data-select-options></div><p class="muted" data-select-empty role="status" hidden></p></div>';
    dialog.querySelector('[data-select-close]').setAttribute('aria-label', page?.dataset.optionClose || 'Close');
    dialog.querySelector('label').textContent = page?.dataset.optionSearch || 'Search options';
    dialog.querySelector('[data-select-search]').placeholder = page?.dataset.optionSearch || 'Search options';
    dialog.querySelector('[data-select-empty]').textContent = page?.dataset.optionEmpty || 'No matching options.';
    dialog.querySelector('[data-select-search]').addEventListener('input', render);
    dialog.querySelector('[data-select-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => opener?.focus({preventScroll:true}));
    document.body.append(dialog);
  }
  function open(select, trigger = select) {
    if (!(select instanceof HTMLSelectElement) || select.disabled || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return false;
    if (!dialog) setup(); if (dialog.open) return false;
    active = select; opener = trigger;
    dialog.querySelector('#lfSelectTitle').textContent = select.labels?.[0]?.textContent.trim() || '';
    const search = dialog.querySelector('[data-select-search]'); search.value = ''; search.hidden = select.options.length <= 12;
    render(); dialog.showModal();
    const focus = search.hidden ? dialog.querySelector('[aria-pressed="true"]') || dialog.querySelector('[data-select-close]') : search;
    focus.focus({preventScroll:true}); if (search.hidden) focus.scrollIntoView({block:'nearest'});
    return true;
  }
  function enhance(root = document) {
    if (typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;
    root.querySelectorAll('select[data-select-sheet]').forEach(select => {
      if (controls.has(select)) return;
      const button = document.createElement('button'); button.type='button'; button.className='input lf-select-trigger'; button.setAttribute('aria-haspopup','dialog');
      for (const name of ['aria-describedby','aria-invalid']) if (select.hasAttribute(name)) button.setAttribute(name, select.getAttribute(name));
      button.addEventListener('click', () => open(select,button)); select.after(button); select.hidden=true; controls.set(select,button);
      select.addEventListener('change', () => sync(select.form || document));
      select.form?.addEventListener('reset', () => setTimeout(() => sync(select.form),0));
    }); sync(root);
  }
  window.LifeFlowSelectSheet={open,enhance,sync}; enhance();
})();
