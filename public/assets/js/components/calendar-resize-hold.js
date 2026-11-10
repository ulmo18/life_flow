(function () {
  // Touch keeps native scrolling until the hold activates. Changing touch-action
  // during a gesture cannot reclaim an already-started browser scroll.
  window.LifeFlowCalendarResizeHold = function (layer, options) {
    let hold = null;
    const clear = () => {
      if (!hold) return;
      window.clearTimeout(hold.timer);
      const current = hold;
      hold = null;
      if (current.pointerId !== undefined) {
        try { layer.releasePointerCapture(current.pointerId); } catch (_) { /* Already released. */ }
      }
    };
    const cancel = () => {
      const active = hold?.active;
      clear();
      if (active) options.cancel();
    };
    function start(event, point, touch) {
      if (hold || !options.allowed()) return;
      const handle = event.target.closest(options.selector);
      const block = handle?.closest(options.blockSelector);
      if (!block) return;
      const current = {x:point.clientX, y:point.clientY, touch, identifier:point.identifier, pointerId:touch ? undefined : event.pointerId, active:false};
      hold = current;
      current.timer = window.setTimeout(() => {
        if (hold !== current || !options.allowed()) { cancel(); return; }
        current.active = options.begin(block, handle) !== false;
        if (!current.active) { clear(); return; }
        if (!touch) {
          try { layer.setPointerCapture(current.pointerId); } catch (_) { cancel(); }
        }
      }, 450);
    }
    function move(event, point) {
      if (!hold) return;
      if (!hold.active) {
        if (Math.hypot(point.clientX - hold.x, point.clientY - hold.y) >= 8) cancel();
        return;
      }
      if (event.cancelable) event.preventDefault();
      options.move(point.clientX, point.clientY);
    }
    function finish(event) {
      const active = hold?.active;
      clear();
      if (!active) return;
      if (event.cancelable) event.preventDefault();
      options.finish();
    }
    layer.addEventListener('pointerdown', event => {
      if (event.pointerType === 'touch' || event.isPrimary === false || event.button !== 0) return;
      start(event, event, false);
    });
    document.addEventListener('pointermove', event => {
      if (hold && !hold.touch && event.pointerId === hold.pointerId) move(event, event);
    });
    document.addEventListener('pointerup', event => {
      if (hold && !hold.touch && event.pointerId === hold.pointerId) finish(event);
    });
    document.addEventListener('pointercancel', event => {
      if (hold && !hold.touch && event.pointerId === hold.pointerId) cancel();
    });
    layer.addEventListener('touchstart', event => {
      if (event.touches.length === 1) start(event, event.changedTouches[0], true);
    }, {passive:true});
    document.addEventListener('touchstart', event => { if (event.touches.length !== 1) cancel(); }, {passive:true});
    document.addEventListener('touchmove', event => {
      if (!hold?.touch) return;
      const point = Array.from(event.changedTouches).find(point => point.identifier === hold.identifier);
      if (point) move(event, point);
    }, {passive:false});
    document.addEventListener('touchend', event => {
      if (hold?.touch && Array.from(event.changedTouches).some(point => point.identifier === hold.identifier)) finish(event);
    }, {passive:false});
    document.addEventListener('touchcancel', cancel, {passive:true});
    layer.addEventListener('contextmenu', event => {
      if (event.target.closest(options.selector)) event.preventDefault();
    });
    window.addEventListener('scroll', () => { if (hold && !hold.active) cancel(); }, {capture:true, passive:true});
    window.addEventListener('blur', cancel);
    document.addEventListener('visibilitychange', () => { if (document.hidden) cancel(); });
  };
})();
