(function () {
  const daygrid = document.getElementById('daygrid');
  const actualLayer = document.getElementById('actualLayer');
  const ui = window.LifeFlowUI;

  if (!daygrid || !actualLayer) {
    return;
  }

  let isDragging = false;
  let startIndex = null;
  let endIndex = null;
  let selectedCells = [];
  let createdSegments = [];

  function clearSelection() {
    selectedCells.forEach(cell => cell.classList.remove('selecting'));
    selectedCells = [];
  }

  function cellFromPoint(x, y) {
    const el = document.elementFromPoint(x, y);
    return el && el.classList.contains('cell') ? el : null;
  }

  function selectRange(start, end) {
    clearSelection();

    const min = Math.min(start, end);
    const max = Math.max(start, end);

    for (let i = min; i <= max; i++) {
      const cell = document.querySelector(`.cell[data-index="${i}"]`);
      if (cell) {
        cell.classList.add('selecting');
        selectedCells.push(cell);
      }
    }
  }

  function indexToTime(index) {
    const minutes = index * 10;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }

  function splitRange(start, end) {
    const min = Math.min(start, end);
    const max = Math.max(start, end) + 1;
    const segments = [];

    for (let i = min; i < max;) {
      const row = Math.floor(i / 6);
      const col = i % 6;
      const span = Math.min(6 - col, max - i);

      segments.push({ row, col, span });
      i += span;
    }

    return segments;
  }

  function createActualEvent(start, end, title) {
    const segments = splitRange(start, end);
    const els = [];

    segments.forEach(seg => {
      const div = document.createElement('div');
      div.className = 'event actual-event';
      div.style.setProperty('--row', seg.row);
      div.style.setProperty('--col', seg.col);
      div.style.setProperty('--span', seg.span);
      div.textContent = title;

      actualLayer.appendChild(div);
      els.push(div);
    });

    return els;
  }

  async function promptActualTitle(start, end) {
    if (ui && typeof ui.promptSheet === 'function') {
      return ui.promptSheet({
        title: '실제 일정 이름',
        label: `${indexToTime(start)} ~ ${indexToTime(end)}`,
        placeholder: '예: 회의',
        confirmText: '추가',
        cancelText: '취소',
        requiredMessage: '실제 일정 이름을 입력해주세요.',
      });
    }

    return prompt(`${indexToTime(start)} ~ ${indexToTime(end)} 일정명`, '');
  }

  daygrid.addEventListener('pointerdown', e => {
    const cell = cellFromPoint(e.clientX, e.clientY);
    if (!cell) return;

    isDragging = true;
    daygrid.setPointerCapture(e.pointerId);

    startIndex = Number(cell.dataset.index);
    endIndex = startIndex;

    selectRange(startIndex, endIndex);
  });

  daygrid.addEventListener('pointermove', e => {
    if (!isDragging) return;

    const cell = cellFromPoint(e.clientX, e.clientY);
    if (!cell) return;

    endIndex = Number(cell.dataset.index);
    selectRange(startIndex, endIndex);
  });

  daygrid.addEventListener('pointerup', async () => {
    if (!isDragging) return;

    isDragging = false;

    const start = Math.min(startIndex, endIndex);
    const end = Math.max(startIndex, endIndex) + 1;
    const title = await promptActualTitle(start, end);

    if (!title) {
      clearSelection();
      return;
    }

    createdSegments = createActualEvent(startIndex, endIndex, title);
    clearSelection();

    window.LifeFlowToast?.show('일정이 추가되었습니다.', {
      actionText: '실행 취소',
      onAction() {
        createdSegments.forEach(el => el.remove());
        createdSegments = [];
      },
    });
  });

  daygrid.addEventListener('pointercancel', () => {
    isDragging = false;
    clearSelection();
  });
})();
