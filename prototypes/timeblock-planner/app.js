(() => {
  const activities = [
    { id: 'todo-study', title: '개발 공부', source: 'today' }, { id: 'todo-grocery', title: '장보기', source: 'today' },
    { id: 'frequent-exercise', title: '운동', source: 'frequent' }, { id: 'frequent-reading', title: '독서', source: 'frequent' }, { id: 'routine-stretch', title: '아침 스트레칭', source: 'routine' },
  ];
  const sourceLabels = { today: '오늘 할 일', frequent: '자주 쓰는 할 일', routine: '루틴' };
  const state = { mode: 'idle', tab: 'today', selectedId: null, blocks: [], preview: null, mobileStart: null, resize: null, dialogBlockId: null, suppressBlockClickUntil: 0, ignoreOutsideClickUntil: 0, ignoreSourceClickUntil: 0 };
  const list = document.querySelector('[data-activity-list]');
  const timeline = document.querySelector('[data-timeline]');
  const scroll = document.querySelector('[data-timeline-scroll]');
  const status = document.querySelector('[data-drag-status]');
  const total = document.querySelector('[data-plan-total]');
  const dialog = document.querySelector('[data-block-dialog]');
  const time = index => index === 144 ? '24:00' : `${String(Math.floor(index / 6)).padStart(2, '0')}:${String((index % 6) * 10).padStart(2, '0')}`;
  const duration = (start, end) => `${(end - start) * 10}분`;
  const activity = id => activities.find(item => item.id === id) || null;
  const mobileMode = () => matchMedia('(pointer: coarse)').matches || innerWidth <= 760;
  const blockById = id => state.blocks.find(block => block.id === id) || null;
  const overlaps = (start, end, excludeId = null) => state.blocks.some(block => block.id !== excludeId && start < block.end && end > block.start);
  const cellAt = (x, y) => document.elementsFromPoint(x, y).find(element => element instanceof HTMLElement && element.matches('.slot') && timeline.contains(element)) || null;
  const setStatus = (message, type = '') => { status.textContent = message; status.className = `drag-status ${type}`.trim(); };
  const makeId = () => `block-${globalThis.crypto?.randomUUID?.() || Date.now()}`;

  function displayedBlock(block) {
    return state.resize?.blockId === block.id ? { ...block, start: state.resize.start, end: state.resize.end } : block;
  }
  function blockParts(block) {
    const parts = [];
    for (let cursor = block.start; cursor < block.end;) {
      const end = Math.min(block.end, (Math.floor(cursor / 6) + 1) * 6);
      parts.push({ start: cursor, end }); cursor = end;
    }
    return parts;
  }
  function updateTotal() {
    const minutes = state.blocks.reduce((sum, block) => sum + (block.end - block.start) * 10, 0);
    total.textContent = `${state.blocks.length}개 블록 · ${minutes}분`;
  }
  function paintRange() {
    timeline.querySelectorAll('.slot.is-preview,.slot.is-conflict,.slot.is-resize-conflict').forEach(slot => slot.classList.remove('is-preview', 'is-conflict', 'is-resize-conflict'));
    const active = state.mode === 'placementPreview' || state.mobileStart !== null ? state.preview : state.resize;
    if (!active) return;
    for (let index = active.start; index < active.end; index += 1) {
      timeline.querySelector(`.slot[data-index="${index}"]`)?.classList.add(active.conflict ? (state.resize ? 'is-resize-conflict' : 'is-conflict') : 'is-preview');
    }
  }
  function makeHandle(edge, blockId) {
    const handle = document.createElement('button');
    handle.type = 'button'; handle.className = `resize-handle is-${edge}`; handle.dataset.edge = edge;
    handle.setAttribute('aria-label', edge === 'start' ? '시작 시간 조절' : '종료 시간 조절');
    handle.addEventListener('pointerdown', event => beginResize(event, blockId, edge));
    return handle;
  }
  function renderTimeline() {
    timeline.replaceChildren();
    const header = document.createElement('div');
    header.className = 'minute-header';
    header.innerHTML = '<span>시간</span>' + ['00', '10', '20', '30', '40', '50'].map(value => `<span>${value}</span>`).join('');
    timeline.append(header);
    const grid = document.createElement('div'); grid.className = 'time-grid';
    for (let hour = 0; hour < 24; hour += 1) {
      const label = document.createElement('div'); label.className = 'hour-label'; label.textContent = `${String(hour).padStart(2, '0')}시`; grid.append(label);
      for (let minute = 0; minute < 6; minute += 1) {
        const index = hour * 6 + minute; const cell = document.createElement('div');
        cell.className = 'slot'; cell.dataset.index = index; cell.setAttribute('aria-label', `${time(index)}부터 ${time(index + 1)}까지`);
        cell.innerHTML = `<span class="minute-mark">${String(minute * 10).padStart(2, '0')}</span>`; grid.append(cell);
      }
    }
    timeline.append(grid);
    const layer = document.createElement('div'); layer.className = 'blocks-layer';
    state.blocks.forEach(rawBlock => {
      const block = displayedBlock(rawBlock); const parts = blockParts(block);
      parts.forEach((part, position) => {
        const columnStart = (part.start % 6) + 2;
        const columnEnd = (part.end % 6 || 6) + 2;
        const partButton = document.createElement('div'); partButton.tabIndex = 0; partButton.setAttribute('role', 'button'); partButton.dataset.blockId = block.id;
        partButton.className = `block-part${position === 0 ? ' is-first' : ''}${position === parts.length - 1 ? ' is-last' : ''}${parts.length === 1 ? ' is-only' : ''}${state.resize?.blockId === block.id ? ' is-resizing' : ''}`;
        partButton.style.gridRow = String(Math.floor(part.start / 6) + 1); partButton.style.gridColumn = `${columnStart} / ${columnEnd}`;
        partButton.innerHTML = `<span>${position === 0 ? activity(block.activityId).title : ''}</span>`;
        if (position === 0) partButton.append(makeHandle('start', block.id));
        if (position === parts.length - 1) partButton.append(makeHandle('end', block.id));
        partButton.addEventListener('click', () => openDialog(block.id)); partButton.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openDialog(block.id); } }); layer.append(partButton);
      });
    });
    timeline.append(layer); paintRange(); updateTotal();
  }
  function renderActivities() {
    list.replaceChildren();
    activities.filter(item => item.source === state.tab).forEach(item => {
      const button = document.createElement('button'); button.type = 'button'; button.dataset.activityId = item.id;
      button.className = `activity${state.selectedId === item.id ? ' is-selected' : ''}`;
      button.innerHTML = `<span class="drag-handle" aria-hidden="true">⠿</span><b>${item.title}</b><small>끌어 배치</small>`;
      button.addEventListener('click', () => { if (performance.now() >= state.ignoreSourceClickUntil) selectActivity(item.id); }); button.addEventListener('pointerdown', beginSourceDrag); list.append(button);
    });
    document.querySelector('[data-source-count]').textContent = `${activities.length}개`;
  }
  function selectActivity(id) {
    if (state.mode !== 'idle') cancelInteraction();
    state.selectedId = id; state.mobileStart = null; renderActivities();
    setStatus(`${activity(id).title} 선택됨 · ${mobileMode() ? '시간표에서 시작과 종료 칸을 차례로 탭하세요.' : '시간표에 끌어 시작점을 놓으세요.'}`);
  }
  function beginSourceDrag(event) {
    if (mobileMode() || event.button !== 0 || state.mode !== 'idle') return;
    const source = event.currentTarget; state.selectedId = source.dataset.activityId; state.mode = 'draggingSource';
    state.preview = { activityId: state.selectedId, source, pointerId: event.pointerId };
    source.classList.add('is-dragging'); source.setPointerCapture?.(event.pointerId);
    source.addEventListener('pointermove', moveSourceDrag); source.addEventListener('pointerup', dropSource, { once: true }); source.addEventListener('pointercancel', () => cancelInteraction('드래그를 취소했습니다.'), { once: true });
    setStatus(`${activity(state.selectedId).title}을 시간표의 시작 칸에 놓으세요.`); event.preventDefault();
  }
  function moveSourceDrag(event) {
    if (state.mode !== 'draggingSource' || state.preview?.pointerId !== event.pointerId) return;
    timeline.querySelectorAll('.slot.is-preview').forEach(slot => slot.classList.remove('is-preview'));
    cellAt(event.clientX, event.clientY)?.classList.add('is-preview');
  }
  function dropSource(event) {
    if (state.mode !== 'draggingSource' || state.preview?.pointerId !== event.pointerId) return;
    const source = state.preview.source; const cell = cellAt(event.clientX, event.clientY);
    source.classList.remove('is-dragging'); source.removeEventListener('pointermove', moveSourceDrag);
    if (!cell) { cancelInteraction('시간표 밖에서 놓아 배치를 취소했습니다.'); return; }
    const start = Number(cell.dataset.index); state.mode = 'placementPreview';
    state.preview = { activityId: state.selectedId, start, end: start + 1, conflict: overlaps(start, start + 1) };
    state.ignoreOutsideClickUntil = performance.now() + 200;
    state.ignoreSourceClickUntil = performance.now() + 200;
    renderTimeline(); updatePlacementStatus();
  }
  function updatePlacementStatus() {
    const preview = state.preview; if (!preview) return;
    setStatus(`${activity(preview.activityId).title} · ${time(preview.start)}–${time(preview.end)} · ${duration(preview.start, preview.end)}${preview.conflict ? ' · 기존 블록과 겹칩니다' : ' · 클릭하여 확정'}`, preview.conflict ? 'is-conflict' : 'is-preview');
  }
  function updatePlacement(cell) {
    if (state.mode !== 'placementPreview' || !state.preview) return;
    state.preview.end = Math.max(state.preview.start + 1, Number(cell.dataset.index) + 1);
    state.preview.conflict = overlaps(state.preview.start, state.preview.end); paintRange(); updatePlacementStatus();
  }
  function confirmPlacement() {
    const preview = state.preview; if (!preview) return;
    if (preview.conflict) { setStatus('기존 계획 블록과 겹쳐 배치하지 않았습니다.', 'is-conflict'); return; }
    state.blocks.push({ id: makeId(), activityId: preview.activityId, start: preview.start, end: preview.end });
    state.mode = 'idle'; state.preview = null; renderTimeline(); renderActivities();
    const block = state.blocks.at(-1); setStatus(`${activity(block.activityId).title}을 ${time(block.start)}–${time(block.end)}에 배치했습니다.`);
  }
  function beginResize(event, blockId, edge) {
    if (mobileMode() || event.button !== 0 || state.mode !== 'idle') return;
    event.preventDefault(); event.stopPropagation(); const block = blockById(blockId); if (!block) return;
    state.mode = edge === 'start' ? 'resizingStart' : 'resizingEnd';
    state.resize = { blockId, edge, pointerId: event.pointerId, originalStart: block.start, originalEnd: block.end, start: block.start, end: block.end, conflict: false };
    state.suppressBlockClickUntil = performance.now() + 500; document.body.classList.add('is-resizing');
    renderTimeline(); updateResizeStatus();
  }
  function updateResize(cell) {
    const resize = state.resize; if (!resize) return; const index = Number(cell.dataset.index);
    if (resize.edge === 'start') resize.start = Math.min(Math.max(index, 0), resize.originalEnd - 1);
    else resize.end = Math.max(Math.min(index + 1, 144), resize.originalStart + 1);
    resize.conflict = overlaps(resize.start, resize.end, resize.blockId); renderTimeline(); updateResizeStatus();
  }
  function updateResizeStatus() {
    const resize = state.resize; if (!resize) return;
    setStatus(`시간 조절 · ${time(resize.start)}–${time(resize.end)} · ${duration(resize.start, resize.end)}${resize.conflict ? ' · 기존 블록과 겹칩니다' : ''}`, resize.conflict ? 'is-conflict' : 'is-preview');
  }
  function finishResize(event) {
    const resize = state.resize; if (!resize || resize.pointerId !== event.pointerId) return;
    const releaseCell = cellAt(event.clientX, event.clientY);
    if (!releaseCell || resize.conflict) { cancelInteraction(resize.conflict ? '기존 블록과 겹쳐 시간 조절을 취소했습니다.' : '시간표 밖에서 놓아 시간 조절을 취소했습니다.'); return; }
    const block = blockById(resize.blockId); if (block) { block.start = resize.start; block.end = resize.end; }
    state.mode = 'idle'; state.resize = null; document.body.classList.remove('is-resizing'); renderTimeline(); setStatus('블록 시간을 조절했습니다.');
  }
  function cancelInteraction(message = '') {
    state.preview?.source?.classList.remove('is-dragging'); state.mode = 'idle'; state.preview = null; state.resize = null; state.mobileStart = null; document.body.classList.remove('is-resizing'); renderTimeline();
    setStatus(message || (state.selectedId ? `${activity(state.selectedId).title} 선택됨 · 시간표에 배치하세요.` : '활동을 선택하거나 드래그해 배치해 보세요.'));
  }
  timeline.addEventListener('pointermove', event => {
    const cell = cellAt(event.clientX, event.clientY); if (!cell) return;
    if (state.mode === 'placementPreview') updatePlacement(cell);
    if (state.mode === 'resizingStart' || state.mode === 'resizingEnd') updateResize(cell);
  });
  timeline.addEventListener('click', event => {
    if (!(event.target instanceof HTMLElement)) return; const cell = event.target.closest('.slot'); if (!(cell instanceof HTMLElement)) return;
    if (mobileMode()) {
      if (!state.selectedId) return; const index = Number(cell.dataset.index);
      if (state.mobileStart === null) { state.mobileStart = index; state.preview = { start: index, end: index + 1, conflict: false }; paintRange(); setStatus(`${time(index)}부터 시작합니다. 종료 칸을 탭하세요.`, 'is-preview'); return; }
      const start = state.mobileStart; const end = Math.max(start + 1, index + 1); state.mobileStart = null; state.preview = null;
      if (overlaps(start, end)) { renderTimeline(); setStatus('기존 계획 블록과 겹쳐 배치하지 않았습니다.', 'is-conflict'); return; }
      state.blocks.push({ id: makeId(), activityId: state.selectedId, start, end }); renderTimeline(); setStatus(`${activity(state.selectedId).title}을 ${time(start)}–${time(end)}에 배치했습니다.`); return;
    }
    if (state.mode === 'placementPreview') confirmPlacement();
  });
  document.addEventListener('pointerup', event => { if (state.mode === 'resizingStart' || state.mode === 'resizingEnd') finishResize(event); });
  document.addEventListener('pointercancel', () => { if (state.mode === 'resizingStart' || state.mode === 'resizingEnd') cancelInteraction('시간 조절을 취소했습니다.'); });
  document.addEventListener('click', event => { if (state.mode === 'placementPreview' && performance.now() >= state.ignoreOutsideClickUntil && event.target instanceof Node && !timeline.contains(event.target)) cancelInteraction('시간표 밖을 눌러 배치를 취소했습니다.'); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && state.mode !== 'idle') cancelInteraction('작업을 취소했습니다.'); });
  function openDialog(id) {
    if (performance.now() < state.suppressBlockClickUntil || state.mode !== 'idle') return;
    const block = blockById(id); if (!block) return; const item = activity(block.activityId); state.dialogBlockId = id;
    document.querySelector('[data-dialog-title]').textContent = item.title; document.querySelector('[data-dialog-source]').textContent = sourceLabels[item.source]; document.querySelector('[data-dialog-time]').textContent = `${time(block.start)}–${time(block.end)}`; document.querySelector('[data-dialog-duration]').textContent = duration(block.start, block.end); dialog.showModal();
  }
  dialog.addEventListener('close', () => { if (dialog.returnValue !== 'delete' || !state.dialogBlockId) return; state.blocks = state.blocks.filter(block => block.id !== state.dialogBlockId); state.dialogBlockId = null; renderTimeline(); setStatus('계획 블록을 삭제했습니다. 원본 활동은 목록에 유지됩니다.'); });
  document.querySelector('[data-delete-block]').addEventListener('click', () => { dialog.returnValue = 'delete'; });
  document.querySelectorAll('[data-tab]').forEach(button => button.addEventListener('click', () => { state.tab = button.dataset.tab; document.querySelectorAll('[data-tab]').forEach(tab => tab.setAttribute('aria-selected', String(tab === button))); renderActivities(); }));
  document.querySelector('[data-add-form]').addEventListener('submit', event => { event.preventDefault(); const input = event.currentTarget.querySelector('input'); const title = input.value.trim(); if (!title) return; const id = `today-${Date.now()}`; activities.push({ id, title, source: 'today' }); state.tab = 'today'; input.value = ''; renderActivities(); selectActivity(id); });
  renderTimeline(); renderActivities(); scroll.scrollTop = 9 * 48;
})();
