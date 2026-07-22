(function () {
  function labelFor(state) {
    if (state === 'O') return '완료';
    if (state === 'X') return '미완료';
    return '미기록';
  }

  function markerFor(state, style) {
    if (state === 'O') return style === 'period' ? '●' : '✓';
    if (state === 'X') return '×';
    return '';
  }

  function apply(control, state) {
    if (!control) return;

    const normalizedState = state === 'O' || state === 'X' ? state : '';
    const stateLabel = labelFor(normalizedState);
    const marker = control.querySelector('[data-routine-state-marker]');
    const date = control.dataset.routineDate || '';
    const context = control.dataset.routineControlLabel || '루틴 상태 변경';

    control.dataset.state = normalizedState;
    control.classList.toggle('is-done', normalizedState === 'O');
    control.classList.toggle('is-failed', normalizedState === 'X');
    if (marker) marker.textContent = markerFor(normalizedState, control.dataset.routineMarkerStyle || 'check');
    control.title = date ? `${date} ${stateLabel}` : stateLabel;
    control.setAttribute('aria-label', `${context}, ${stateLabel}`);
  }

  window.LifeFlowRoutineState = { apply, labelFor, markerFor };
})();
