(function () {
  const MOVE_THRESHOLD = 8;
  const LONG_PRESS_DELAY = 360;

  function create(options = {}) {
    const grid = options.grid;
    const ignoreSelector = options.ignoreSelector || 'button, input, select, textarea, a';
    const onSelect = typeof options.onSelect === 'function' ? options.onSelect : () => {};

    if (!(grid instanceof HTMLElement)) {
      return null;
    }

    let pointerSession = null;
    let selectedCells = [];
    let longPressTimer = null;
    let nativePullToRefreshSuspended = false;

    function cellFromPoint(x, y) {
      return document.elementsFromPoint(x, y).find(element => (
        element instanceof HTMLElement
        && element.classList.contains('cell')
        && grid.contains(element)
      )) || null;
    }

    function clearSelection() {
      selectedCells.forEach(cell => cell.classList.remove('selecting'));
      selectedCells = [];
    }

    function selectRange(start, end) {
      clearSelection();
      const min = Math.min(start, end);
      const max = Math.max(start, end);

      for (let index = min; index <= max; index += 1) {
        const cell = grid.querySelector(`.cell[data-index="${index}"]`);
        if (cell) {
          cell.classList.add('selecting');
          selectedCells.push(cell);
        }
      }
    }

    function cancelLongPress() {
      window.clearTimeout(longPressTimer);
      longPressTimer = null;
    }

    function shouldIgnore(event) {
      return event.target instanceof Element && Boolean(event.target.closest(ignoreSelector));
    }

    function suspendNativePullToRefresh(pointerType) {
      if (pointerType !== 'touch' || nativePullToRefreshSuspended) {
        return;
      }

      const bridge = window.LifeFlowAndroidBridge;
      if (bridge?.setPullToRefreshEnabled(false)) {
        nativePullToRefreshSuspended = true;
      }
    }

    function restoreNativePullToRefresh() {
      if (!nativePullToRefreshSuspended) {
        return;
      }

      const bridge = window.LifeFlowAndroidBridge;
      if (bridge?.setPullToRefreshEnabled(true)) {
        nativePullToRefreshSuspended = false;
      }
    }

    function resetSelection() {
      cancelLongPress();

      if (pointerSession) {
        try {
          if (grid.hasPointerCapture(pointerSession.pointerId)) {
            grid.releasePointerCapture(pointerSession.pointerId);
          }
        } catch (error) {
          // Pointer capture may already have ended in the native layer.
        }
      }

      pointerSession = null;
      clearSelection();
      grid.classList.remove('is-range-selecting');
      restoreNativePullToRefresh();
    }

    function beginSelection(event, cell) {
      const index = Number(cell.dataset.index);
      pointerSession = {
        pointerId: event.pointerId,
        pointerType: event.pointerType,
        startIndex: index,
        endIndex: index,
        startX: event.clientX,
        startY: event.clientY,
        moved: false,
        selecting: true,
      };
      suspendNativePullToRefresh(event.pointerType);
      selectRange(index, index);
      grid.classList.add('is-range-selecting');

      if (event.pointerType === 'touch' && navigator.vibrate) {
        navigator.vibrate(18);
      }

      try {
        grid.setPointerCapture(event.pointerId);
      } catch (error) {
        // The pointer may already belong to native scrolling.
      }
    }

    grid.addEventListener('pointerdown', event => {
      if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0) || shouldIgnore(event)) {
        return;
      }

      const cell = cellFromPoint(event.clientX, event.clientY);
      if (!cell) return;

      const immediateSelection = event.pointerType !== 'touch';
      if (immediateSelection) {
        beginSelection(event, cell);
        event.preventDefault();
        return;
      }

      pointerSession = {
        pointerId: event.pointerId,
        pointerType: event.pointerType,
        startIndex: Number(cell.dataset.index),
        endIndex: Number(cell.dataset.index),
        startX: event.clientX,
        startY: event.clientY,
        moved: false,
        selecting: false,
      };

      cancelLongPress();
      const pendingEvent = {
        pointerId: event.pointerId,
        pointerType: event.pointerType,
        clientX: event.clientX,
        clientY: event.clientY,
      };
      longPressTimer = window.setTimeout(() => {
        if (!pointerSession || pointerSession.pointerId !== pendingEvent.pointerId || pointerSession.moved) {
          return;
        }
        beginSelection(pendingEvent, cell);
      }, options.longPressDelay || LONG_PRESS_DELAY);
    });

    grid.addEventListener('pointermove', event => {
      if (!pointerSession || pointerSession.pointerId !== event.pointerId) return;

      const distanceX = Math.abs(event.clientX - pointerSession.startX);
      const distanceY = Math.abs(event.clientY - pointerSession.startY);
      if (Math.max(distanceX, distanceY) >= MOVE_THRESHOLD) {
        pointerSession.moved = true;
        if (!pointerSession.selecting) {
          cancelLongPress();
        }
      }

      if (!pointerSession.selecting) {
        return;
      }

      event.preventDefault();
      const cell = cellFromPoint(event.clientX, event.clientY);
      if (!cell) return;

      pointerSession.endIndex = Number(cell.dataset.index);
      selectRange(pointerSession.startIndex, pointerSession.endIndex);
    });

    grid.addEventListener('pointerup', event => {
      if (!pointerSession || pointerSession.pointerId !== event.pointerId) return;

      const completed = pointerSession;
      resetSelection();

      if (!completed.selecting) {
        return;
      }

      const start = Math.min(completed.startIndex, completed.endIndex);
      const end = Math.max(completed.startIndex, completed.endIndex) + 1;
      onSelect({ start, end, source: 'range' });
    });

    grid.addEventListener('pointercancel', event => {
      if (!pointerSession || pointerSession.pointerId !== event.pointerId) return;
      resetSelection();
    });

    grid.addEventListener('touchmove', event => {
      if (pointerSession?.selecting) {
        event.preventDefault();
      }
    }, { passive: false });

    grid.addEventListener('contextmenu', event => {
      if (pointerSession?.selecting || event.pointerType === 'touch' || event.sourceCapabilities?.firesTouchEvents) {
        event.preventDefault();
      }
    });

    window.addEventListener('pagehide', resetSelection);
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        resetSelection();
      }
    });

    return {
      cancel() {
        resetSelection();
      },
    };
  }

  window.LifeFlowTimeGrid = { create };
})();
