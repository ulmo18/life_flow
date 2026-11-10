(function () {
  const pending = new Set();
  function setStatus(widget, status) {
    if (!widget) return;
    widget.dataset.status = status;
    widget.hidden = widget.dataset.editable !== '1' || !(['in_progress', 'postponed'].includes(status) || status === 'pending' && widget.dataset.hasOrigin === '1');
    widget.querySelector('[data-followup-label]').textContent = status === 'pending' ? '이어받은 메모 · 다음 행동' : status === 'postponed' ? '연기 사유 · 다음 행동' : '마무리를 위한 다음 행동';
  }
  async function open(widget, item) {
    if (!widget) return;
    const ticket = String(Number(widget.dataset.ticket || 0) + 1);
    widget.dataset.ticket = ticket;
    widget.dataset.itemId = String(item.itemId);
    widget.dataset.editable = item.editable ? '1' : '0';
    widget.dataset.hasOrigin = '0';
    widget.querySelector('[data-followup-note]').value = '';
    widget.querySelector('[data-followup-note]').disabled = true;
    widget.querySelector('[data-followup-save]').disabled = true;
    const message = widget.querySelector('[data-followup-message]');
    message.textContent = '';
    setStatus(widget, item.status);
    if (!item.editable) return;
    if (pending.has(String(item.itemId))) { message.textContent = '저장 중…'; return; }
    try {
      const response = await fetch('/todo/followup?daily_plan_item_id=' + encodeURIComponent(item.itemId), {headers:{Accept:'application/json'}});
      const result = await response.json();
      if (widget.dataset.ticket !== ticket) return;
      if (!response.ok || !result.ok) throw new Error(result.message || '메모를 불러오지 못했습니다.');
      widget.querySelector('[data-followup-note]').value = result.note;
      widget.dataset.hasOrigin = result.sourceId ? '1' : '0';
      setStatus(widget, widget.dataset.status);
      widget.querySelector('[data-followup-note]').disabled = !result.supported;
      widget.querySelector('[data-followup-save]').disabled = !result.supported || pending.has(String(item.itemId));
      if (!result.supported) message.textContent = '메모 저장을 위한 데이터베이스 업데이트가 필요합니다.';
    } catch (error) {
      if (widget.dataset.ticket === ticket) message.textContent = error.message;
    }
  }
  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-followup-save]');
    if (!button || button.disabled) return;
    const widget = button.closest('[data-todo-followup]');
    const id = widget.dataset.itemId;
    if (pending.has(id)) return;
    const ticket = widget.dataset.ticket;
    const message = widget.querySelector('[data-followup-message]');
    const note = widget.querySelector('[data-followup-note]');
    const body = new URLSearchParams({_csrf_token:widget.querySelector('[data-followup-csrf]').value, daily_plan_item_id:id, note:note.value});
    pending.add(id); button.disabled = true; note.disabled = true;
    message.textContent = '저장 중…';
    try {
      const response = await fetch('/todo/followup', {method:'POST', body, headers:{Accept:'application/json'}});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || '메모를 저장하지 못했습니다.');
      if (widget.dataset.ticket === ticket) {
        note.value = result.note;
        message.textContent = '다음 날 이어 볼 메모를 저장했습니다.';
      }
    } catch (error) {
      if (widget.dataset.ticket === ticket) message.textContent = error.message || '메모를 저장하지 못했습니다.';
    } finally {
      pending.delete(id);
      if (widget.dataset.ticket === ticket) { button.disabled = false; note.disabled = false; }
      else if (widget.dataset.itemId === id) open(widget, {itemId:id, status:widget.dataset.status, editable:widget.dataset.editable === '1'});
    }
  });
  window.LifeFlowTodoFollowup = {open, setStatus};
})();
