(function () {
  const page = document.querySelector('[data-todo-page]');
  const layer = document.querySelector('[data-todo-layer]');
  const timeForm = document.querySelector('[data-todo-time-form]');
  const itemIdInput = timeForm?.querySelector('[data-todo-item-id]');
  const startInput = timeForm?.querySelector('select[name="start_index"]');
  const endInput = timeForm?.querySelector('select[name="end_index"]');
  const message = document.querySelector('[data-todo-time-message]');
  if (!page || !layer || !timeForm || !itemIdInput || !startInput || !endInput) return;

  function close() {
    layer.hidden = true;
    document.body.classList.remove('is-menu-open');
  }

  function open(itemId, startIndex, endIndex, text) {
    itemIdInput.value = itemId;
    startInput.value = String(startIndex);
    endInput.value = String(endIndex);
    if (message && text) message.textContent = text;
    layer.hidden = false;
    document.body.classList.add('is-menu-open');
    startInput.focus();
  }

  async function submit(body) {
    const response = await fetch('/todo/complete', {
      method: 'POST',
      body,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();
    if (payload.needsTime) {
      open(body.get('daily_plan_item_id'), payload.startIndex, payload.endIndex, payload.message);
      return;
    }
    if (!response.ok || !payload.ok) throw new Error(payload.message || page.dataset.messageFallback);
    window.LifeFlowToast?.show?.(payload.message);
    window.location.reload();
  }

  document.querySelectorAll('[data-todo-complete]').forEach(button => {
    button.addEventListener('click', async () => {
      const body = new FormData();
      body.append('_csrf_token', timeForm.querySelector('input[name="_csrf_token"]').value);
      body.append('daily_plan_item_id', button.dataset.itemId || '');
      if (button.dataset.startIndex !== '' && button.dataset.endIndex !== '') {
        body.append('start_index', button.dataset.startIndex);
        body.append('end_index', button.dataset.endIndex);
      }
      button.disabled = true;
      try { await submit(body); }
      catch (error) { window.LifeFlowToast?.show?.(error.message || page.dataset.messageFallback); }
      finally { button.disabled = false; }
    });
  });

  timeForm.addEventListener('submit', async event => {
    event.preventDefault();
    const submitButton = timeForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    try { await submit(new FormData(timeForm)); }
    catch (error) { window.LifeFlowToast?.show?.(error.message || page.dataset.messageFallback); }
    finally { if (submitButton) submitButton.disabled = false; }
  });

  document.querySelectorAll('[data-todo-close]').forEach(button => button.addEventListener('click', close));
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !layer.hidden) close(); });
})();
