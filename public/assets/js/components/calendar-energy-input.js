(function () {
  const SLIDER_VALUES = [0, 25, 50, 75, 100];
  const MAX_VALUE = 100;

  function create(form) {
    const root = form.querySelector('[data-energy-fields]');
    if (!root) return null;
    const type = root.querySelector('[data-energy-type]');
    const amount = root.querySelector('[data-energy-amount]');
    const value = root.querySelector('[data-energy-value]');
    const controls = root.querySelector('[data-energy-controls]');
    const directions = Array.from(root.querySelectorAll('[data-energy-direction]'));
    const modeButton = root.querySelector('[data-energy-mode]');
    const slider = root.querySelector('[data-energy-slider]');
    const sliderPanel = root.querySelector('[data-energy-slider-panel]');
    const ticks = Array.from(root.querySelectorAll('[data-energy-tick]'));
    const direct = root.querySelector('[data-energy-direct]');
    const directInput = root.querySelector('[data-energy-direct-input]');
    const sign = root.querySelector('[data-energy-sign]');
    const message = root.querySelector('[data-energy-message]');
    const hint = root.querySelector('[data-energy-hint]');
    let state = null;
    let directBaseline = null;

    function validValue(raw) { return /^\d+$/.test(String(raw)) && Number(raw) >= 5 && Number(raw) <= MAX_VALUE && Number(raw) % 5 === 0; }
    function legacyAllowed() { return state.legacy && state.direction === state.originalDirection && String(state.raw) === state.originalRaw; }
    function render(error = '') {
      const hasDirection = Boolean(state.direction);
      const isValid = validValue(state.raw) || legacyAllowed();
      const numeric = isValid ? Number(state.raw) : null;
      const prefix = state.direction === 'recover' ? '+' : '−';
      root.dataset.energyDirection = state.keep ? 'none' : (state.direction || 'none');
      directions.forEach(button => button.setAttribute('aria-pressed', String(!state.keep && button.dataset.energyDirection === state.direction)));
      modeButton.disabled = !hasDirection;
      modeButton.textContent = state.mode === 'direct' ? '슬라이더' : '직접 입력';
      slider.disabled = !hasDirection && !state.keep;
      ticks.forEach(tick => { tick.disabled = !hasDirection && Number(tick.dataset.energyTick) !== 0; tick.setAttribute('aria-pressed', String(state.keep ? Number(tick.dataset.energyTick) === 0 : (hasDirection && numeric !== null && Number(tick.dataset.energyTick) === numeric))); });
      const onSlider = state.keep || (numeric !== null && SLIDER_VALUES.includes(numeric));
      slider.classList.toggle('is-unselected', !onSlider);
      if (onSlider) slider.value = state.keep ? '0' : String(numeric);
      slider.setAttribute('aria-valuetext', state.keep ? '유지' : (onSlider ? `${prefix}${numeric}` : '미선택'));
      sliderPanel.hidden = state.mode === 'direct';
      direct.hidden = state.mode !== 'direct';
      hint.classList.toggle('is-visible', state.mode === 'direct');
      directInput.disabled = !hasDirection;
      directInput.value = state.raw;
      sign.textContent = prefix;
      message.textContent = error || (!hasDirection && !state.keep ? '방향을 선택해 주세요' : '');
      message.hidden = !message.textContent;
      if (state.keep) { type.value = 'none'; amount.value = '25'; value.value = ''; }
      else if (!hasDirection) { type.value = 'consume'; amount.value = '25'; value.value = ''; }
      else { type.value = state.direction; amount.value = 'custom'; value.value = isValid ? String(numeric) : ''; }
      const summary = form.querySelector('[data-energy-summary-main]');
      if (summary) {
        summary.textContent = state.keep ? '0' : (hasDirection && numeric !== null ? `${prefix}${numeric}` : (hasDirection ? '수치 선택' : '기본 −25'));
        summary.classList.toggle('is-consume', !state.keep && (!hasDirection || state.direction === 'consume'));
        summary.classList.toggle('is-recover', !state.keep && state.direction === 'recover');
        summary.classList.toggle('is-none', state.keep);
      }
    }
    function reset(direction = 'consume', raw = '25', isKeep = false) {
      const numeric = Number(raw);
      state = { direction, raw:String(raw), keep:isKeep, mode:raw !== '' && !SLIDER_VALUES.includes(numeric) ? 'direct' : 'slider', legacy:raw !== '' && numeric > MAX_VALUE, originalDirection:direction, originalRaw:String(raw) };
      directBaseline = { direction:state.direction, raw:state.raw, keep:state.keep };
      directInput.max = state.legacy ? String(numeric) : String(MAX_VALUE);
      render();
    }
    directions.forEach(button => button.addEventListener('click', () => {
      const next = button.dataset.energyDirection;
      if (state.keep) { state.keep = false; state.direction = next; }
      else if (state.direction === next) { state.direction = ''; state.raw = ''; state.mode = 'slider'; }
      else state.direction = next;
      render();
    }));
    modeButton.addEventListener('click', () => {
      if (state.mode === 'direct') {
        if (!state.keep && !validValue(state.raw) && !legacyAllowed()) {
          Object.assign(state, directBaseline);
        }
        state.mode = 'slider';
      } else {
        directBaseline = { direction:state.direction, raw:state.raw, keep:state.keep };
        state.mode = 'direct';
      }
      render();
      if (state.mode === 'direct') directInput.focus({ preventScroll:true });
    });
    function setSlider(raw) { if (Number(raw) === 0) { state.keep = true; render(); return; } if (!state.direction) return; state.keep = false; state.raw = String(raw); render(); }
    slider.addEventListener('input', () => setSlider(slider.value));
    slider.addEventListener('change', () => setSlider(slider.value));
    ticks.forEach(tick => tick.addEventListener('click', () => setSlider(tick.dataset.energyTick)));
    directInput.addEventListener('input', () => { state.raw = directInput.value; render(); });
    directInput.addEventListener('blur', () => { if (state.direction && (!validValue(state.raw) && !legacyAllowed())) render(state.raw === '' ? '수치를 입력해 주세요.' : '입력값을 확인해 주세요.'); });
    form.addEventListener('submit', event => {
      if (state.keep || !state.direction || validValue(state.raw) || legacyAllowed()) return;
      event.preventDefault(); event.stopImmediatePropagation();
      const disclosure = root.closest('details');
      if (disclosure) disclosure.open = true;
      render(state.raw === '' ? '수치를 선택해 주세요.' : '입력값을 확인해 주세요.');
      (state.mode === 'direct' ? directInput : slider).focus({ preventScroll:true });
    }, true);
    reset();
    return { reset, render };
  }
  window.LifeFlowCalendarEnergy = { create };
})();
