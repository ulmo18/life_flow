(() => {
  'use strict';

  const ROWS = 24;
  const COLS = 6;
  const CELL_COUNT = ROWS * COLS;
  const planBlocks = [
    { id: 'plan-001', taskId: 'task-002', title: '아침 스트레칭', startIndex: 42, endIndex: 45 },
    { id: 'plan-002', taskId: 'task-001', title: '개발 공부', startIndex: 60, endIndex: 66 },
    { id: 'plan-003', taskId: 'task-003', title: '점심 식사', startIndex: 72, endIndex: 78 },
    { id: 'plan-004', taskId: 'task-001', title: '개발 공부', startIndex: 84, endIndex: 93 },
    { id: 'plan-005', taskId: 'task-004', title: '운동', startIndex: 108, endIndex: 114 },
  ];

  const planGrid = document.querySelector('[data-plan-grid]');
  const actualGrid = document.querySelector('[data-actual-grid]');
  const scrollAreas = document.querySelectorAll('.grid-scroll');
  const status = document.querySelector('[data-status]');
  const total = document.querySelector('[data-total]');
  const dialog = document.querySelector('[data-editor]');
  const editorKind = document.querySelector('[data-editor-kind]');
  const editorHeading = document.querySelector('[data-editor-heading]');
  const editorRange = document.querySelector('[data-editor-range]');
  const titleInput = document.querySelector('[data-title]');
  const linkToggle = document.querySelector('[data-link-toggle]');
  const planPicker = document.querySelector('[data-plan-picker]');
  const planOptions = document.querySelector('[data-plan-options]');
  const deleteButton = document.querySelector('[data-delete]');

  const state = {
    actualBlocks: [],
    mode: 'idle',
    planDrag: null,
    drag: null,
    resize: null,
    touchStart: null,
    editorRange: null,
    editingId: null,
    selectedActualId: null,
    suppressBlockClickUntil: 0,
    syncing: false,
  };

  function timeAt(index) {
    const minutes = index * 10;
    return `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
  }

  function rangeText(range) {
    return `${timeAt(range.startIndex)} ~ ${timeAt(range.endIndex)}`;
  }

  function rangeFor(startIndex, endIndex) {
    return { startIndex: Math.min(startIndex, endIndex), endIndex: Math.max(startIndex, endIndex) + 1 };
  }

  function indexToGridPosition(index) {
    return {
      row: Math.floor(index / COLS),
      col: index % COLS,
    };
  }

  function overlaps(first, second) {
    return first.startIndex < second.endIndex && second.startIndex < first.endIndex;
  }

  function isConflicting(range, ignoredId = null) {
    return state.actualBlocks.some((block) => block.id !== ignoredId && overlaps(range, block));
  }

  function getDisplayedActualBlock(block) {
    if (state.resize && state.resize.id === block.id) {
      return { ...block, ...state.resize.candidate };
    }
    return block;
  }

  function blockParts(block) {
    const parts = [];
    let cursor = block.startIndex;
    while (cursor < block.endIndex) {
      const rowEnd = Math.min(block.endIndex, (Math.floor(cursor / COLS) + 1) * COLS);
      parts.push({ startIndex: cursor, endIndex: rowEnd });
      cursor = rowEnd;
    }
    return parts;
  }

  function createGrid(grid, type) {
    const minutes = document.createElement('div');
    minutes.className = 'minutes';
    for (let minute = 0; minute < COLS; minute += 1) {
      const label = document.createElement('span');
      label.textContent = `${String(minute * 10).padStart(2, '0')}분`;
      minutes.append(label);
    }

    const hourLabels = document.createElement('div');
    hourLabels.className = 'hour-labels';
    for (let row = 0; row < ROWS; row += 1) {
      const hour = document.createElement('span');
      hour.className = 'hour-label';
      hour.textContent = timeAt(row * COLS);
      hourLabels.append(hour);
    }

    const cells = document.createElement('div');
    cells.className = 'cells';
    for (let index = 0; index < CELL_COUNT; index += 1) {
      const cell = document.createElement('div');
      cell.className = 'cell';
      cell.dataset.index = String(index);
      cells.append(cell);
    }

    const blocks = document.createElement('div');
    blocks.className = 'blocks';
    grid.replaceChildren(minutes, hourLabels, cells, blocks);

    const source = type === 'plan' ? planBlocks : state.actualBlocks.map(getDisplayedActualBlock);
    source.forEach((block) => renderBlock(blocks, block, type));
    if (type === 'actual' && state.planDrag && state.planDrag.startIndex !== null) {
      renderBlock(blocks, {
        id: 'plan-drop-preview',
        title: state.planDrag.plan.title,
        startIndex: state.planDrag.startIndex,
        endIndex: state.planDrag.endIndex,
      }, 'preview');
    }
    renderSelectionPreview(blocks, type);
    paintSelection(grid, type);
  }

  function renderSelectionPreview(layer, type) {
    const range = state.drag?.range;
    if (type !== 'actual' || !range) return;

    const conflict = isConflicting(range);
    blockParts(range).forEach((part) => {
      const start = indexToGridPosition(part.startIndex);
      const end = indexToGridPosition(part.endIndex - 1);
      const preview = document.createElement('div');
      preview.className = `selection-preview${conflict ? ' conflict' : ''}`;
      preview.style.gridRow = `${start.row + 1}`;
      preview.style.gridColumn = `${start.col + 1} / ${end.col + 2}`;
      preview.style.pointerEvents = 'none';
      preview.style.background = conflict ? 'rgba(181, 78, 69, .26)' : 'rgba(255, 94, 91, .30)';
      preview.style.boxShadow = conflict
        ? 'inset 0 0 0 2px rgba(181, 78, 69, .72)'
        : 'inset 0 0 0 2px rgba(255, 94, 91, .78)';
      layer.append(preview);
    });
  }

  function renderBlock(layer, block, type) {
    const linkedPlanId = state.actualBlocks.find((actual) => actual.id === state.selectedActualId)?.planBlockId;
    const parts = blockParts(block);
    parts.forEach((part, partIndex) => {
      const element = document.createElement('div');
      element.className = `block ${type}`;
      if (parts.length === 1) {
        element.classList.add('only');
      } else if (partIndex === 0) {
        element.classList.add('first');
      } else if (partIndex === parts.length - 1) {
        element.classList.add('last');
      }
      element.dataset.blockId = block.id;
      const start = indexToGridPosition(part.startIndex);
      const end = indexToGridPosition(part.endIndex - 1);
      element.style.gridRow = `${start.row + 1}`;
      element.style.gridColumn = `${start.col + 1} / ${end.col + 2}`;

      if (type === 'plan' && linkedPlanId === block.id) {
        element.classList.add('linked');
      }
      if (type === 'preview' && !state.planDrag?.valid) {
        element.classList.add('invalid');
      }
      if (type === 'plan') {
        element.tabIndex = 0;
        element.setAttribute('role', 'button');
        element.setAttribute('aria-label', `${block.title}, ${rangeText(block)}. 실제 일정으로 끌어 놓기`);
        element.addEventListener('pointerdown', (event) => beginPlanDrag(event, block.id));
      }
      if (type === 'actual') {
        element.tabIndex = 0;
        element.setAttribute('role', 'button');
        element.setAttribute('aria-label', `${block.title}, ${rangeText(block)}`);
        element.addEventListener('click', () => openEditorForBlock(block.id));
        element.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openEditorForBlock(block.id);
          }
        });
      }

      if (partIndex === 0) {
        const label = document.createElement('span');
        label.className = 'block-label';
        label.textContent = `${block.title} · ${rangeText(block)}`;
        element.append(label);
      }

      if (type === 'actual' && block.planBlockId && partIndex === 0) {
        const link = document.createElement('span');
        link.className = 'block-link';
        link.textContent = '연결';
        link.setAttribute('aria-label', '계획 일정 연결됨');
        element.append(link);
      }

      if (type === 'actual' && partIndex === 0) {
        element.append(createHandle('start', block.id));
      }
      if (type === 'actual' && partIndex === parts.length - 1) {
        element.append(createHandle('end', block.id));
      }
      layer.append(element);
    });
  }

  function createHandle(edge, blockId) {
    const handle = document.createElement('span');
    handle.className = `handle ${edge}`;
    handle.title = edge === 'start' ? '시작 시간 조절' : '종료 시간 조절';
    handle.addEventListener('pointerdown', (event) => {
      event.preventDefault();
      event.stopPropagation();
      beginResize(blockId, edge, event);
    });
    return handle;
  }

  function paintSelection(grid, type) {
    const range = state.drag?.range || state.touchStart;
    if (type !== 'actual' || !range) return;
    const conflict = isConflicting(range);
    grid.querySelectorAll('.cell').forEach((cell) => {
      const index = Number(cell.dataset.index);
      if (index >= range.startIndex && index < range.endIndex) {
        cell.classList.add(conflict ? 'conflict' : 'selecting');
      }
    });
  }

  function render() {
    createGrid(planGrid, 'plan');
    createGrid(actualGrid, 'actual');
    const minutes = state.actualBlocks.reduce((sum, block) => sum + (block.endIndex - block.startIndex) * 10, 0);
    total.textContent = `기록 ${state.actualBlocks.length}건 · ${Math.floor(minutes / 60)}시간 ${minutes % 60}분`;
  }

  function setStatus(message) {
    status.textContent = message;
  }

  function releaseActualPointerCapture(pointerId) {
    if (actualGrid.hasPointerCapture?.(pointerId)) {
      actualGrid.releasePointerCapture(pointerId);
    }
  }

  function resetEditorState() {
    state.editorRange = null;
    state.editingId = null;
    state.selectedActualId = null;
    state.mode = 'idle';
  }

  function actualCellAtPoint(clientX, clientY) {
    return document.elementsFromPoint(clientX, clientY).find((element) => (
      element.classList?.contains('cell') && actualGrid.contains(element)
    )) || null;
  }

  function cellIndexFromEvent(event) {
    const targetCell = event.target.closest?.('.cell');
    const cell = targetCell && actualGrid.contains(targetCell) ? targetCell : actualCellAtPoint(event.clientX, event.clientY);
    return cell ? Number(cell.dataset.index) : null;
  }

  function beginPlanDrag(event, planId) {
    if (event.button !== 0 || event.pointerType === 'touch' || dialog.open || state.mode !== 'idle') return;
    const plan = planBlocks.find((block) => block.id === planId);
    if (!plan) return;

    event.preventDefault();
    event.stopPropagation();
    const source = event.currentTarget;
    state.planDrag = {
      pointerId: event.pointerId,
      source,
      plan,
      duration: plan.endIndex - plan.startIndex,
      startIndex: null,
      endIndex: null,
      valid: false,
      conflict: false,
    };
    state.mode = 'draggingPlanBlock';
    source.classList.add('is-dragging');
    source.setPointerCapture?.(event.pointerId);
    source.addEventListener('pointermove', movePlanDrag);
    source.addEventListener('pointerup', dropPlanBlock, { once: true });
    source.addEventListener('pointercancel', cancelPlanDrag, { once: true });
    source.addEventListener('lostpointercapture', cancelPlanDrag, { once: true });
    setStatus(`“${plan.title}” 계획을 실제 시간표에 놓아 복사하세요.`);
  }

  function movePlanDrag(event) {
    const planDrag = state.planDrag;
    if (!planDrag || event.pointerId !== planDrag.pointerId) return;
    const cell = actualCellAtPoint(event.clientX, event.clientY);
    if (!cell) {
      planDrag.startIndex = null;
      planDrag.endIndex = null;
      planDrag.valid = false;
      planDrag.conflict = false;
      createGrid(actualGrid, 'actual');
      setStatus('실제 시간표의 빈 시간 칸 위에 놓아 복사하세요.');
      return;
    }

    planDrag.startIndex = Number(cell.dataset.index);
    planDrag.endIndex = planDrag.startIndex + planDrag.duration;
    planDrag.conflict = planDrag.endIndex > CELL_COUNT || isConflicting(planDrag);
    planDrag.valid = !planDrag.conflict;
    createGrid(actualGrid, 'actual');
    setStatus(planDrag.valid
      ? `${timeAt(planDrag.startIndex)} ~ ${timeAt(planDrag.endIndex)}에 복사합니다.`
      : '하루 경계를 넘거나 기존 실제 일정과 겹쳐 배치할 수 없습니다.');
  }

  function clearPlanDrag() {
    const planDrag = state.planDrag;
    if (!planDrag) return null;
    planDrag.source.classList.remove('is-dragging');
    planDrag.source.removeEventListener('pointermove', movePlanDrag);
    state.planDrag = null;
    state.mode = 'idle';
    if (planDrag.source.hasPointerCapture?.(planDrag.pointerId)) {
      planDrag.source.releasePointerCapture(planDrag.pointerId);
    }
    return planDrag;
  }

  function dropPlanBlock(event) {
    if (!state.planDrag || event.pointerId !== state.planDrag.pointerId) return;
    movePlanDrag(event);
    const planDrag = clearPlanDrag();
    if (!planDrag.valid || planDrag.startIndex === null) {
      setStatus('계획 블록 복사를 취소했습니다.');
      render();
      return;
    }

    const id = `actual-${Date.now()}`;
    state.actualBlocks.push({
      id,
      title: planDrag.plan.title,
      startIndex: planDrag.startIndex,
      endIndex: planDrag.endIndex,
      planBlockId: planDrag.plan.id,
    });
    state.selectedActualId = id;
    setStatus('계획 블록을 실제 일정으로 복사했습니다. 양끝 핸들로 시간을 조절할 수 있습니다.');
    render();
  }

  function cancelPlanDrag() {
    if (!state.planDrag) return;
    clearPlanDrag();
    setStatus('계획 블록 복사를 취소했습니다.');
    render();
  }

  function updateDrag(event) {
    if (!state.drag || event.pointerId !== state.drag.pointerId) return;
    const cell = actualCellAtPoint(event.clientX, event.clientY);
    if (!cell) return;
    state.drag.range = rangeFor(state.drag.startIndex, Number(cell.dataset.index));
    const minutes = (state.drag.range.endIndex - state.drag.range.startIndex) * 10;
    setStatus(`시간 선택 · ${rangeText(state.drag.range)} · ${minutes}분`);
    render();
  }

  function cancelSelection(message = '시간 선택을 취소했습니다.') {
    if (!state.drag) return;
    const pointerId = state.drag.pointerId;
    state.drag = null;
    state.mode = 'idle';
    releaseActualPointerCapture(pointerId);
    setStatus(message);
    render();
  }

  function beginSelection(event) {
    if (event.button !== 0 || event.pointerType === 'touch' || dialog.open || state.mode !== 'idle' || state.touchStart) return;
    if (event.target.closest?.('.block')) return;
    const index = cellIndexFromEvent(event);
    if (index === null) return;
    event.preventDefault();
    state.touchStart = null;
    state.drag = { pointerId: event.pointerId, startIndex: index, range: { startIndex: index, endIndex: index + 1 } };
    state.mode = 'selectingActualRange';
    actualGrid.setPointerCapture?.(event.pointerId);
    setStatus(`시간 선택 · ${rangeText(state.drag.range)} · 10분`);
    render();
  }

  function endSelection(event) {
    if (!state.drag || event.pointerId !== state.drag.pointerId) return;
    const releaseCell = actualCellAtPoint(event.clientX, event.clientY);
    if (!releaseCell) {
      cancelSelection('시간표 밖에서 놓아 시간 선택을 취소했습니다.');
      return;
    }
    state.drag.range = rangeFor(state.drag.startIndex, Number(releaseCell.dataset.index));
    const range = state.drag.range;
    state.drag = null;
    releaseActualPointerCapture(event.pointerId);
    if (isConflicting(range)) {
      state.mode = 'idle';
      setStatus('이미 기록된 실제 일정과 겹칩니다. 다른 시간을 선택해 주세요.');
      render();
      return;
    }
    openCreateEditor(range);
  }

  function handleTouchSelection(event) {
    if (event.pointerType !== 'touch' || dialog.open || (state.mode !== 'idle' && state.mode !== 'selectingActualRange')) return;
    if (event.target.closest?.('.block')) return;
    const index = cellIndexFromEvent(event);
    if (index === null) return;
    if (!state.touchStart) {
      state.touchStart = { startIndex: index, endIndex: index + 1 };
      state.mode = 'selectingActualRange';
      setStatus('종료 시간을 탭해 실제 일정 범위를 선택해 주세요.');
      render();
      return;
    }
    const range = rangeFor(state.touchStart.startIndex, index);
    state.touchStart = null;
    if (isConflicting(range)) {
      state.mode = 'idle';
      setStatus('이미 기록된 실제 일정과 겹칩니다. 다른 시간을 선택해 주세요.');
      render();
      return;
    }
    openCreateEditor(range);
  }

  function openCreateEditor(range) {
    state.editorRange = range;
    state.editingId = null;
    state.selectedActualId = null;
    state.mode = 'modalOpen';
    editorKind.textContent = '새 실제 일정';
    editorHeading.textContent = '실제 일정 기록';
    editorRange.textContent = rangeText(range);
    titleInput.value = '';
    deleteButton.hidden = true;
    renderPlanOptions(null, range);
    dialog.showModal();
    titleInput.focus();
    setStatus('실제 일정의 제목과 연결할 계획을 선택할 수 있습니다.');
    render();
  }

  function openEditorForBlock(blockId) {
    if (dialog.open || state.mode !== 'idle' || performance.now() < state.suppressBlockClickUntil) return;
    const block = state.actualBlocks.find((item) => item.id === blockId);
    if (!block) return;
    state.editorRange = { startIndex: block.startIndex, endIndex: block.endIndex };
    state.editingId = block.id;
    state.selectedActualId = block.id;
    state.mode = 'modalOpen';
    editorKind.textContent = '실제 일정 수정';
    editorHeading.textContent = '실제 일정 수정';
    editorRange.textContent = rangeText(block);
    titleInput.value = block.title;
    deleteButton.hidden = false;
    renderPlanOptions(block.planBlockId || null, block);
    dialog.showModal();
    render();
  }

  function renderPlanOptions(selectedPlanId, range) {
    const recommended = planBlocks.filter((plan) => overlaps(plan, range));
    linkToggle.checked = Boolean(selectedPlanId);
    planPicker.hidden = !linkToggle.checked;
    planOptions.replaceChildren();
    planBlocks.forEach((plan) => {
      const label = document.createElement('label');
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'planBlockId';
      radio.value = plan.id;
      radio.checked = selectedPlanId === plan.id;
      const note = recommended.some((item) => item.id === plan.id) ? ' · 추천' : '';
      label.className = 'plan-option';
      label.append(radio, document.createTextNode(`${plan.title} (${rangeText(plan)})${note}`));
      planOptions.append(label);
    });
  }

  function saveEditor() {
    const title = titleInput.value.trim();
    if (!title || !state.editorRange) return;
    const chosenPlanId = linkToggle.checked ? document.querySelector('input[name="planBlockId"]:checked')?.value || null : null;
    if (state.editingId) {
      const block = state.actualBlocks.find((item) => item.id === state.editingId);
      if (block) {
        block.title = title;
        block.planBlockId = chosenPlanId;
      }
      setStatus('실제 일정 정보를 수정했습니다. 시간 변경은 양끝 핸들을 사용해 주세요.');
    } else {
      state.actualBlocks.push({
        id: `actual-${Date.now()}`,
        title,
        startIndex: state.editorRange.startIndex,
        endIndex: state.editorRange.endIndex,
        planBlockId: chosenPlanId,
      });
      setStatus('실제 일정을 기록했습니다. 이 시제품의 데이터는 새로고침하면 초기화됩니다.');
    }
    resetEditorState();
    render();
  }

  function deleteEditorBlock() {
    if (!state.editingId) return;
    state.actualBlocks = state.actualBlocks.filter((block) => block.id !== state.editingId);
    state.selectedActualId = null;
    resetEditorState();
    setStatus('실제 일정을 삭제했습니다.');
    render();
  }

  function beginResize(blockId, edge, event) {
    if (dialog.open || state.mode !== 'idle') return;
    const block = state.actualBlocks.find((item) => item.id === blockId);
    if (!block) return;
    state.resize = {
      id: blockId,
      edge,
      pointerId: event.pointerId,
      original: { startIndex: block.startIndex, endIndex: block.endIndex },
      candidate: { startIndex: block.startIndex, endIndex: block.endIndex },
      conflict: false,
    };
    state.mode = 'resizingActualBlock';
    state.selectedActualId = blockId;
    actualGrid.setPointerCapture?.(event.pointerId);
    document.body.classList.add('resizing');
    setStatus('핸들을 드래그해 실제 일정을 조절하세요. 최소 길이는 10분입니다.');
  }

  function updateResize(event) {
    if (!state.resize || event.pointerId !== state.resize.pointerId) return;
    const cell = actualCellAtPoint(event.clientX, event.clientY);
    if (!cell) return;
    const index = Number(cell.dataset.index);
    const candidate = { ...state.resize.original };
    if (state.resize.edge === 'start') {
      candidate.startIndex = Math.min(index, candidate.endIndex - 1);
    } else {
      candidate.endIndex = Math.max(index + 1, candidate.startIndex + 1);
    }
    candidate.startIndex = Math.max(0, candidate.startIndex);
    candidate.endIndex = Math.min(CELL_COUNT, candidate.endIndex);
    state.resize.candidate = candidate;
    state.resize.conflict = isConflicting(candidate, state.resize.id);
    render();
  }

  function endResize(event) {
    if (!state.resize || event.pointerId !== state.resize.pointerId) return;
    const releaseCell = actualCellAtPoint(event.clientX, event.clientY);
    if (!releaseCell) {
      cancelResize('시간표 밖에서 놓아 시간 변경을 취소했습니다.');
      return;
    }
    updateResize(event);
    const resize = state.resize;
    state.resize = null;
    state.mode = 'idle';
    releaseActualPointerCapture(event.pointerId);
    document.body.classList.remove('resizing');
    state.suppressBlockClickUntil = performance.now() + 80;
    if (resize.conflict) {
      setStatus('다른 실제 일정과 겹쳐 시간 변경을 취소했습니다.');
    } else {
      const block = state.actualBlocks.find((item) => item.id === resize.id);
      if (block) Object.assign(block, resize.candidate);
      setStatus('실제 일정 시간을 변경했습니다.');
    }
    render();
  }

  function cancelResize(message = '시간 변경을 취소했습니다.') {
    if (!state.resize) return;
    const pointerId = state.resize.pointerId;
    state.resize = null;
    state.mode = 'idle';
    releaseActualPointerCapture(pointerId);
    document.body.classList.remove('resizing');
    state.suppressBlockClickUntil = performance.now() + 80;
    setStatus(message);
    render();
  }

  actualGrid.addEventListener('pointerdown', beginSelection);
  actualGrid.addEventListener('pointermove', (event) => {
    if (state.drag) updateDrag(event);
    if (state.resize) updateResize(event);
  });
  actualGrid.addEventListener('pointerup', (event) => {
    if (state.drag) endSelection(event);
    if (state.resize) endResize(event);
  });
  actualGrid.addEventListener('pointercancel', (event) => {
    if (state.drag && event.pointerId === state.drag.pointerId) {
      cancelSelection();
    }
    if (state.resize && event.pointerId === state.resize.pointerId) cancelResize();
  });
  actualGrid.addEventListener('lostpointercapture', (event) => {
    const pointerId = event.pointerId;
    requestAnimationFrame(() => {
      if (state.drag?.pointerId === pointerId) cancelSelection('포인터 캡처가 해제되어 시간 선택을 취소했습니다.');
      if (state.resize?.pointerId === pointerId) cancelResize('포인터 캡처가 해제되어 시간 변경을 취소했습니다.');
    });
  });
  actualGrid.addEventListener('click', handleTouchSelection);
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (state.planDrag) {
      cancelPlanDrag();
    } else if (state.resize) {
      cancelResize();
    } else if (state.drag || state.touchStart) {
      if (state.drag) {
        cancelSelection();
        return;
      }
      state.touchStart = null;
      state.mode = 'idle';
      setStatus('시간 선택을 취소했습니다.');
      render();
    }
  });

  linkToggle.addEventListener('change', () => {
    planPicker.hidden = !linkToggle.checked;
  });
  dialog.addEventListener('close', () => {
    if (dialog.returnValue === 'save') saveEditor();
    if (dialog.returnValue === 'delete') deleteEditorBlock();
    if (dialog.returnValue !== 'save' && dialog.returnValue !== 'delete') {
      resetEditorState();
      setStatus('입력을 취소했습니다.');
      render();
    }
  });

  scrollAreas.forEach((area) => {
    area.addEventListener('scroll', () => {
      if (state.syncing) return;
      state.syncing = true;
      scrollAreas.forEach((other) => {
        if (other !== area) other.scrollTop = area.scrollTop;
      });
      requestAnimationFrame(() => { state.syncing = false; });
    });
  });

  render();
  setStatus('왼쪽 계획 블록은 실제 시간표로 끌어 복사하고, 오른쪽 빈 시간은 드래그해 직접 기록하세요.');
})();
