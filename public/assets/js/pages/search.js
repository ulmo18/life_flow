(function () {
  const type = document.getElementById('searchType');
  const from = document.getElementById('searchFrom');
  const to = document.getElementById('searchTo');
  const summary = document.querySelector('[data-search-filter-summary]');
  function syncFilters() {
    if (!type || !from || !to || !summary) return;
    const period = !from.value && !to.value ? '전체 기간'
      : (from.value || '시작일 미지정').replaceAll('-', '.') + ' ~ ' + (to.value || '종료일 미지정').replaceAll('-', '.');
    summary.textContent = (type.selectedOptions[0]?.textContent || '전체') + ' · ' + period;
  }
  [type, from, to].forEach(input => input?.addEventListener('change', syncFilters));
  syncFilters();
  document.querySelectorAll('[data-search-date]').forEach(input => {
    input.addEventListener('click', event => { if (window.LifeFlowDatePicker?.open(input)) event.preventDefault(); });
    input.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') { if (window.LifeFlowDatePicker?.open(input)) event.preventDefault(); }
    });
  });
  const dialog = document.getElementById('searchDetailDialog');
  const data = document.getElementById('searchDetailData');
  if (!dialog || !data || typeof dialog.showModal !== 'function') return;
  let items; try { items = JSON.parse(data.textContent); } catch (_) { return; }
  let opener;
  document.querySelectorAll('[data-search-result]').forEach(link => link.addEventListener('click', event => {
    const item = items[Number(link.dataset.searchResult)];
    if (!item) return;
    event.preventDefault(); opener = link;
    dialog.querySelector('#searchDetailTitle').textContent = item.title;
    dialog.querySelector('[data-search-meta]').textContent = item.typeLabel + ' · ' + item.date;
    const content = dialog.querySelector('[data-search-content]'); content.replaceChildren();
    for (const detail of item.details || []) {
      const label = document.createElement('dt'), value = document.createElement('dd');
      label.textContent = detail.label; value.textContent = detail.value; content.append(label, value);
    }
    if (!content.childElementCount) { const value = document.createElement('dd'); value.textContent = '추가 상세 내용이 없습니다.'; content.append(value); }
    const visit = dialog.querySelector('[data-search-visit]'); visit.href = link.getAttribute('href');
    visit.textContent = item.typeLabel + ' 페이지로 이동';
    dialog.showModal(); dialog.querySelector('[data-search-close]').focus({preventScroll:true});
  }));
  dialog.querySelectorAll('[data-search-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
  dialog.addEventListener('close', () => opener?.focus({preventScroll:true}));
})();
