(function () {
  const layer = document.querySelector('[data-goal-review-layer]');
  if (!layer) return;
  const sheets = Array.from(layer.querySelectorAll('[data-goal-review-sheet]'));
  let trigger = null;
  function close() {
    layer.hidden = true;
    sheets.forEach(sheet => { sheet.hidden = true; });
    document.body.classList.remove('is-ui-open');
    trigger?.focus();
    trigger = null;
  }
  document.querySelectorAll('[data-goal-review-open]').forEach(button => {
    button.addEventListener('click', () => {
      const sheet = sheets.find(item => item.dataset.goalReviewSheet === button.dataset.goalReviewOpen);
      if (!sheet) return;
      trigger = button;
      sheets.forEach(item => { item.hidden = item !== sheet; });
      layer.hidden = false;
      sheet.scrollTop = 0;
      document.body.classList.add('is-ui-open');
      sheet.querySelector('[data-goal-review-close]')?.focus();
    });
  });
  layer.querySelectorAll('[data-goal-review-close]').forEach(button => button.addEventListener('click', close));
  document.addEventListener('keydown', event => {
    if (layer.hidden) return;
    if (event.key === 'Escape') { close(); return; }
    if (event.key !== 'Tab') return;
    const sheet = sheets.find(item => !item.hidden);
    const controls = sheet ? Array.from(sheet.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')) : [];
    if (controls.length === 0) return;
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
})();
