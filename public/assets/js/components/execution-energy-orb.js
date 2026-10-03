(function () {
  const link = document.querySelector('[data-energy-orb]');
  const value = document.querySelector('[data-energy-orb-value]');
  if (!link || !value) return;

  const setMotion = (x, y) => {
    link.style.setProperty('--energy-sway-x', `${Math.max(-4, Math.min(4, x)).toFixed(2)}px`);
    link.style.setProperty('--energy-sway-y', `${Math.max(-2, Math.min(2, y)).toFixed(2)}px`);
    link.style.setProperty('--energy-tilt', `${Math.max(-5, Math.min(5, x * .8)).toFixed(2)}deg`);
  };

  fetch('/execution-energy/status', { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() : null)
    .then(data => {
      if (!data?.ok) return;
      value.textContent = String(data.currentCapacity);
      link.style.setProperty('--energy-level', `${Math.max(0, Math.min(100, data.percent))}%`);
      link.style.setProperty('--energy-liquid-opacity', Number(data.percent) > 0 ? '1' : '0');
      link.setAttribute('aria-label', `실행력 현재 ${data.currentCapacity}`);
    })
    .catch(() => { value.textContent = '–'; });

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
