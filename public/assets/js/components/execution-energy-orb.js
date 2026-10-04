(function () {
  const link = document.querySelector('[data-energy-orb]');
  const value = document.querySelector('[data-energy-orb-value]');
  if (!link || !value) return;

  const storageKey = link.dataset.energyCacheKey || 'life-flow.energy';
  const currentDate = link.dataset.energyDate || '';
  let cached = null;

  function readCache() {
    try {
      const parsed = JSON.parse(window.sessionStorage.getItem(storageKey) || 'null');
      return parsed && (!currentDate || parsed.date === currentDate)
        && typeof parsed.currentCapacity === 'number' && typeof parsed.percent === 'number'
        ? parsed
        : null;
    } catch (error) {
      return null;
    }
  }

  function writeCache(data) {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify({
        date: data.date || '',
        currentCapacity: Number(data.currentCapacity),
        percent: Number(data.percent),
      }));
    } catch (error) {
      // Storage can be unavailable in private or restricted WebViews.
    }
  }

  function applyEnergy(data, animate) {
    const capacity = Number(data.currentCapacity);
    const percent = Math.max(0, Math.min(100, Number(data.percent)));
    if (!Number.isFinite(capacity) || !Number.isFinite(percent)) return;

    if (!animate) link.classList.remove('is-energy-ready');
    value.textContent = String(capacity);
    link.style.setProperty('--energy-level', `${percent}%`);
    link.style.setProperty('--energy-liquid-opacity', percent > 0 ? '1' : '0');
    link.setAttribute('aria-label', `실행력 현재 ${capacity}`);
    link.classList.add('is-energy-hydrated');

    if (!animate) {
      void link.offsetHeight;
      link.classList.add('is-energy-ready');
    }
  }

  cached = readCache();
  if (cached) applyEnergy(cached, false);

  fetch('/execution-energy/status', { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() : null)
    .then(data => {
      if (!data?.ok) return;
      const sameDate = !cached?.date || !data.date || cached.date === data.date;
      const shouldAnimate = Boolean(cached && sameDate && cached.currentCapacity !== Number(data.currentCapacity));
      if (!sameDate) cached = null;
      applyEnergy(data, shouldAnimate);
      writeCache(data);
    })
    .catch(() => {
      if (!cached) value.textContent = '–';
    });

  const setMotion = (x, y) => {
    link.style.setProperty('--energy-sway-x', `${Math.max(-4, Math.min(4, x)).toFixed(2)}px`);
    link.style.setProperty('--energy-sway-y', `${Math.max(-2, Math.min(2, y)).toFixed(2)}px`);
    link.style.setProperty('--energy-tilt', `${Math.max(-5, Math.min(5, x * .8)).toFixed(2)}deg`);
  };

  link.addEventListener('pointermove', event => {
    const bounds = link.getBoundingClientRect();
    setMotion(((event.clientX - bounds.left) / bounds.width - .5) * 7, 0);
  });
  link.addEventListener('pointerleave', () => setMotion(0, 0));

  if ('DeviceOrientationEvent' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.addEventListener('deviceorientation', event => {
      if (typeof event.gamma !== 'number' || typeof event.beta !== 'number') return;
      setMotion(event.gamma / 10, event.beta / 26);
    }, { passive: true });
  }
})();
