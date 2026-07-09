(async function () {
  const daygrid = document.getElementById('planDaygrid');
  const layer = document.getElementById('planEditorLayer');
  const form = document.getElementById('planEditorForm');
  const blocksInput = document.getElementById('planBlocksInput');
  const nameInput = document.getElementById('planName');
  const sourcePlanInput = document.querySelector('input[name="source_plan_group_id"]');
  const goalOptionsJson = document.getElementById('planGoalOptionsJson');
  const ui = window.LifeFlowUI;

  if (!daygrid || !layer || !form || !blocksInput || !nameInput) {
    return;
  }

  let isDragging = false;
  let startIndex = null;
  let endIndex = null;
  let selectedCells = [];
  let blocks = parseInitialBlocks();
  let allowSubmit = false;
  const goalOptions = parseGoalOptions();

  function parseGoalOptions() {
    if (!goalOptionsJson) {
      return [];
    }

    try {
      const parsed = JSON.parse(goalOptionsJson.textContent || '[]');
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function parseInitialBlocks() {
    try {
      const parsed = JSON.parse(blocksInput.value || '[]');
      return Array.isArray(parsed)
        ? parsed
            .map(block => ({
              title: String(block.title || '').trim(),
              importance: normalizeImportance(String(block.importance || 'D')),
              goal_id: normalizeGoalId(block.goal_id),
              start_index: Number(block.start_index),
              end_index: Number(block.end_index),
            }))
            .filter(block => block.title
              && Number.isInteger(block.start_index)
              && Number.isInteger(block.end_index)
              && block.start_index >= 0
              && block.end_index <= 144
              && block.start_index < block.end_index)
        : [];
    } catch (error) {
      return [];
    }
  }

  function normalizeImportance(value) {
    const importance = String(value || 'D').trim().toUpperCase();
    return ['A', 'B', 'C', 'D'].includes(importance) ? importance : 'D';
  }

  function normalizeGoalId(value) {
    const goalId = Number(value);
    return Number.isInteger(goalId) && goalId > 0 ? goalId : null;
  }

  function goalLabel(goalId) {
    const goal = goalOptions.find(option => Number(option.id) === Number(goalId));
    return goal ? String(goal.label || goal.title || '') : '';
  }

  function importanceLabel(value) {
    switch (normalizeImportance(value)) {
      case 'A':
        return 'A';
      case 'B':
        return 'B';
      case 'C':
        return 'C';
      default:
        return 'D';
    }
  }

  function importanceClass(value) {
    return `importance-${normalizeImportance(value).toLowerCase()}`;
  }

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
      const cell = daygrid.querySelector(`.cell[data-index="${i}"]`);
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
    const segments = [];

    for (let i = start; i < end;) {
      const row = Math.floor(i / 6);
      const col = i % 6;
      const span = Math.min(6 - col, end - i);

      segments.push({ row, col, span });
      i += span;
    }

    return segments;
  }

  function overlaps(newBlock) {
    return blocks.some(block => newBlock.start_index < block.end_index && newBlock.end_index > block.start_index);
  }

  function syncInput() {
    blocks.sort((a, b) => a.start_index - b.start_index);
    blocksInput.value = JSON.stringify(blocks);
  }

  async function promptBlockDetails(block) {
    if (ui && typeof ui.promptSheet === 'function') {
      return ui.promptSheet({
        title: '계획 블록',
        label: `${indexToTime(block.start_index)} ~ ${indexToTime(block.end_index)}`,
        value: '',
        placeholder: '예: 집중 업무',
        confirmText: '추가',
        cancelText: '취소',
        requiredMessage: '계획 블록 이름을 입력해주세요.',
        showImportance: true,
        importance: 'D',
        showGoal: goalOptions.length > 0,
        goalOptions,
        goalId: null,
      });
    }

    const title = prompt(`${indexToTime(block.start_index)} ~ ${indexToTime(block.end_index)} 계획명`, '');
    if (!title) {
      return null;
    }

    return { title, importance: 'D', goalId: null };
  }

  function renderBlocks() {
    layer.innerHTML = '';

    blocks.forEach((block, blockIndex) => {
      splitRange(block.start_index, block.end_index).forEach(seg => {
        const div = document.createElement('button');
        div.type = 'button';
        div.className = `event plan-editor-event ${importanceClass(block.importance)}`;
        div.style.setProperty('--row', seg.row);
        div.style.setProperty('--col', seg.col);
        div.style.setProperty('--span', seg.span);
        div.dataset.blockIndex = String(blockIndex);
        const linkedGoalLabel = goalLabel(block.goal_id);
        div.dataset.uiTooltip = linkedGoalLabel ? `${block.title} · ${linkedGoalLabel}` : block.title;
        div.setAttribute('aria-label', `${block.title} ${indexToTime(block.start_index)}-${indexToTime(block.end_index)}`);

        const badge = document.createElement('span');
        badge.className = 'importance-badge';
        badge.textContent = importanceLabel(block.importance);

        const title = document.createElement('span');
        title.className = 'event-title';
        title.textContent = block.title;

        div.appendChild(badge);
        div.appendChild(title);

        if (linkedGoalLabel) {
          const goalBadge = document.createElement('span');
          goalBadge.className = 'plan-goal-mini-badge';
          goalBadge.textContent = '목표';
          div.appendChild(goalBadge);
        }

        div.addEventListener('click', async () => {
          const confirmed = ui && typeof ui.confirm === 'function'
            ? await ui.confirm({
                title: '계획 블록 삭제',
                message: `${block.title} 블록을 삭제할까요?`,
                confirmText: '삭제',
                cancelText: '취소',
              })
            : confirm('선택한 계획 블록을 삭제하시겠습니까?');

          if (confirmed) {
            blocks.splice(blockIndex, 1);
            syncInput();
            renderBlocks();
          }
        });

        layer.appendChild(div);
      });
    });

    syncInput();
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

    const block = {
      start_index: Math.min(startIndex, endIndex),
      end_index: Math.max(startIndex, endIndex) + 1,
    };

    clearSelection();

    if (overlaps(block)) {
      ui?.show?.('겹치는 계획 블록은 추가할 수 없습니다.');
      return;
    }

    const details = await promptBlockDetails(block);
    if (!details) {
      return;
    }

    const title = String(details.title || '').trim().slice(0, 80);
    if (!title) {
      return;
    }

    block.title = title;
    block.importance = normalizeImportance(details.importance);
    block.goal_id = normalizeGoalId(details.goalId);
    blocks.push(block);
    renderBlocks();
  });

  daygrid.addEventListener('pointercancel', () => {
    isDragging = false;
    clearSelection();
  });

  form.addEventListener('submit', async event => {
    if (allowSubmit) {
      allowSubmit = false;
      return;
    }

    event.preventDefault();
    syncInput();

    if (blocks.length === 0) {
      ui?.show?.('계획 블록을 1개 이상 추가해주세요.');
      return;
    }

    const summary = sourcePlanInput
      ? '수정 저장 시 기존 계획은 숨김 처리되고 새 버전으로 저장됩니다. 기존 캘린더와 목표 연결은 이전 버전을 계속 참조합니다.'
      : '계획을 저장할까요?';

    const confirmed = ui && typeof ui.confirm === 'function'
      ? await ui.confirm({
          title: '계획 저장',
          message: summary,
          confirmText: '저장',
          cancelText: '취소',
        })
      : confirm('계획 저장을 완료하시겠습니까?');

    if (confirmed) {
      allowSubmit = true;
      form.requestSubmit();
    }
  });

  renderBlocks();
})();
