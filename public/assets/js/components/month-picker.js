(function () {
  const historyMonthInput = document.getElementById('historyMonth');
  const historyMonthOpen = document.querySelector('[data-history-month-open]');
  const historyMonthDialog = document.getElementById('historyMonthDialog');
  historyMonthInput?.addEventListener('change', () => {
    if (!historyMonthInput.value || !historyMonthInput.form) return;
    if (typeof historyMonthInput.form.requestSubmit === 'function') historyMonthInput.form.requestSubmit();
    else historyMonthInput.form.submit();
  });
  if (historyMonthInput && historyMonthOpen && historyMonthDialog && typeof historyMonthDialog.showModal === 'function') {
    const yearLabel = historyMonthDialog.querySelector('[data-history-year]');
    const monthGrid = historyMonthDialog.querySelector('[data-history-month-grid]');
    let year = 0;
    function updateMonthLabel() {
      const [selectedYear, selectedMonth] = historyMonthInput.value.split('-');
      historyMonthOpen.textContent = `${selectedYear}년 ${Number(selectedMonth)}월 ▾`;
      historyMonthOpen.setAttribute('aria-label', `${selectedYear}년 ${Number(selectedMonth)}월 선택`);
    }
    function renderMonths() {
      yearLabel.textContent = `${year}년`;
      historyMonthDialog.querySelector('[data-history-year-prev]').disabled = year <= 1;
      historyMonthDialog.querySelector('[data-history-year-next]').disabled = year >= 9999;
      monthGrid.replaceChildren();
      for (let month = 1; month <= 12; month++) {
        const value = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`;
        const button = document.createElement('button');
        button.type = 'button'; button.textContent = `${month}월`;
        if (value === historyMonthInput.value) { button.classList.add('is-selected'); button.setAttribute('aria-pressed', 'true'); }
        button.addEventListener('click', () => { historyMonthInput.value = value; updateMonthLabel(); historyMonthDialog.close(); historyMonthInput.dispatchEvent(new Event('change', {bubbles:true})); });
        monthGrid.append(button);
      }
    }
    historyMonthOpen.addEventListener('click', () => { year = Number(historyMonthInput.value.slice(0, 4)); renderMonths(); historyMonthDialog.showModal(); (monthGrid.querySelector('.is-selected') || monthGrid.querySelector('button'))?.focus(); });
    historyMonthDialog.querySelector('[data-history-year-prev]').addEventListener('click', () => { if (year > 1) { year--; renderMonths(); } });
    historyMonthDialog.querySelector('[data-history-year-next]').addEventListener('click', () => { if (year < 9999) { year++; renderMonths(); } });
    historyMonthDialog.querySelector('[data-history-month-close]').addEventListener('click', () => historyMonthDialog.close());
    historyMonthDialog.addEventListener('click', event => { if (event.target === historyMonthDialog) historyMonthDialog.close(); });
    historyMonthDialog.addEventListener('close', () => historyMonthOpen.focus({ preventScroll:true }));
    updateMonthLabel(); historyMonthInput.hidden = true; historyMonthOpen.hidden = false;
  }
})();
