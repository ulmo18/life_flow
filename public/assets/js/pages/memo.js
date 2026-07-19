(function () {
  const layer = document.querySelector('[data-memo-layer]');
  if (!layer) return;

  const addSheet = document.querySelector('[data-memo-add-sheet]');
  const editSheet = document.querySelector('[data-memo-edit-sheet]');
  const addInput = document.getElementById('memoAddContent');
  const editInput = document.getElementById('memoEditContent');
  const editId = document.getElementById('memoEditId');

  function focusInput(input) {
    if (!input) return;
    try { input.focus({ preventScroll: true }); } catch (error) { input.focus(); }
    requestAnimationFrame(() => input.scrollIntoView({ block: 'nearest' }));
  }

  function openSheet(sheet, input) {
    layer.hidden = false;
    addSheet.hidden = sheet !== addSheet;
    editSheet.hidden = sheet !== editSheet;
    document.body.classList.add('is-ui-open');
    focusInput(input);
  }

  function closeSheets() {
    if (document.activeElement instanceof HTMLElement && layer.contains(document.activeElement)) {
      document.activeElement.blur();
    }
    layer.hidden = true;
    addSheet.hidden = true;
    editSheet.hidden = true;
    document.body.classList.remove('is-ui-open');
  }

  document.querySelector('[data-memo-add-open]')?.addEventListener('click', () => openSheet(addSheet, addInput));
  document.querySelectorAll('[data-memo-edit-open]').forEach(button => {
    button.addEventListener('click', () => {
      editId.value = button.dataset.memoId || '';
      editInput.value = button.closest('.memo-card')?.querySelector('[data-memo-content-value]')?.value || '';
      openSheet(editSheet, editInput);
    });
  });
  document.querySelectorAll('[data-memo-close]').forEach(button => button.addEventListener('click', closeSheets));

  document.querySelectorAll('[data-memo-delete-form]').forEach(form => {
    let allowSubmit = false;
    form.addEventListener('submit', async event => {
      if (allowSubmit) return;
      event.preventDefault();
      const confirmed = window.LifeFlowUI?.confirm
        ? await window.LifeFlowUI.confirm({
            title: '메모 삭제',
            message: '메모를 휴지통으로 이동할까요?',
            confirmText: '삭제',
            cancelText: '취소',
          })
        : confirm('메모를 휴지통으로 이동할까요?');
      if (confirmed) {
        allowSubmit = true;
        form.requestSubmit();
      }
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !layer.hidden) closeSheets();
  });

  const editError = document.querySelector('[data-memo-edit-error]');
  if (editError) {
    editId.value = editError.dataset.memoId || '';
    editInput.value = editError.value || '';
    openSheet(editSheet, editInput);
  } else if (addSheet.querySelector('.field-error')) {
    openSheet(addSheet, addInput);
  }
})();
