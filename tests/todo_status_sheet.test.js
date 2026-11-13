// Run with Node.js: node tests/todo_status_sheet.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('public/assets/js/pages/todo.js', 'utf8');
const start = source.indexOf("  const statusLayer = document.querySelector('[data-todo-status-layer]');");
const end = source.indexOf("  document.querySelectorAll('[data-todo-edit-open]')", start);
assert.ok(start >= 0 && end > start);
const element = (dataset = {}) => ({
  dataset, hidden: false, disabled: false, attrs: {}, handlers: {}, textContent: '',
  addEventListener(type, fn) { this.handlers[type] = fn; },
  setAttribute(key, value) { this.attrs[key] = value; },
  getAttribute(key) { return this.attrs[key]; },
  focus() { this.focused = true; },
});
const layer = element(); layer.hidden = true;
const title = element();
const note = element();
const record = {replaceChildren(value) { this.content = value; }};
const template = {content: {cloneNode() { return {copied: true}; }}};
const button = element({itemId: '42', savedStatus: 'pending', blockTitle: '계획', blockTime: '09:00–10:00'});
const sibling = element({itemId: '42', savedStatus: 'pending', blockTitle: '계획', blockTime: '14:00–15:00'});
button.closest = sibling.closest = () => null;
button.parentElement = sibling.parentElement = {querySelector: () => template};
const buttonSymbol = {textContent: '—'};
const siblingSymbol = {textContent: '—'};
button.querySelector = () => buttonSymbol;
sibling.querySelector = () => siblingSymbol;
const close = element();
const followup = element(), followupLabel = element(), followupInput = element(), followupMessage = element(), saveActions = element(), saveButton = element();
followupInput.value = '';
const fields = {'[data-status-followup]':followup,'[data-status-followup-label]':followupLabel,'[data-status-followup-note]':followupInput,'[data-status-followup-message]':followupMessage,'[data-status-save-actions]':saveActions,'[data-status-save]':saveButton};
layer.querySelector = selector => fields[selector] || (selector === '.todo-sheet' ? {scrollTop:50} : close);
const options = ['pending', 'completed', 'cancelled', 'postponed', 'in_progress', 'external'].map(value => {
  const option = element({todoStatusOption: value}); option.textContent = value; return option;
});
let failure = false;
let malformed = false;
let message = '';
let sortCount = 0;
let lastBody;
const settle = () => new Promise(setImmediate);
const ctx = {
  sortTodoRows() { sortCount++; },
  document: {
    body: {classList: {add() {}, remove() {}}},
    querySelector(selector) { return selector.includes('layer') ? layer : selector.includes('status-note') ? note : selector.includes('status-record') ? record : title; },
    querySelectorAll(selector) { return selector.includes('status-option') ? options : selector.includes('status-close') ? [close] : [button, sibling]; },
  },
  timeForm: {querySelector: () => ({value: 'csrf'})},
  FormData: class { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } },
  SyntaxError,
  TypeError,
  fetch: async (url, request) => { if(url.startsWith('/todo/followup?')) return {ok:true,json:async()=>({ok:true,note:'기존 메모',supported:true})}; lastBody=request.body.values;return {ok: !failure, json: async () => malformed ? JSON.parse('<html>') : failure ? {ok: false, message: '저장 실패'} : {ok: true, status: request.body.values.status}}; },
  window: {LifeFlowToast: {show(value) { message = value; }}},
};
vm.runInNewContext(source.slice(start, end), ctx);
(async () => {
  button.handlers.click();
  assert.equal(layer.hidden, false);
  assert.equal(record.content.copied, true, 'Related records appear in the same sheet.');
  assert.equal(options[1].disabled, false);
  await options[2].handlers.click();
  assert.equal(button.dataset.savedStatus, 'cancelled');
  assert.equal(sibling.dataset.savedStatus, 'cancelled');
  assert.equal(buttonSymbol.textContent, 'X');
  assert.equal(sortCount, 1, 'Successful status changes reapply the current sort.');
  assert.equal(layer.hidden, true);
  button.dataset.statusEditable = '0';
  button.handlers.click();
  assert.equal(layer.hidden, false, 'Read-only source still opens its records.');
  assert.ok(options.every(option => option.disabled), 'Read-only source cannot change status.');
  assert.match(note.textContent, /상태를 변경할 수 없는/);
  close.handlers.click();
  delete button.dataset.statusEditable;
  button.handlers.click();
  assert.equal(options[1].disabled, false);
  failure = true;
  await options[1].handlers.click();
  assert.equal(button.dataset.savedStatus, 'cancelled');
  assert.equal(layer.hidden, false);
  assert.equal(message, '저장 실패');
  assert.equal(sortCount, 1, 'Failed status changes do not reorder rows.');
  failure = false;
  malformed = true;
  await options[1].handlers.click();
  assert.equal(button.dataset.savedStatus, 'cancelled');
  assert.match(message, /새로고침 후 확인/);
  assert.doesNotMatch(message, /Unexpected token/);
  malformed = false;
  failure = false;
  await options[1].handlers.click();
  assert.equal(button.dataset.savedStatus, 'completed');
  assert.equal(sibling.dataset.savedStatus, 'completed');
  assert.equal(buttonSymbol.textContent, 'V');
  assert.equal(layer.hidden, true);
  button.handlers.click(); await settle();
  await options[4].handlers.click();
  assert.equal(button.dataset.savedStatus, 'completed', 'Selecting progress only changes the draft');
  assert.equal(layer.hidden, false);
  assert.equal(followup.hidden, false);
  assert.equal(followupInput.value, '기존 메모', 'Existing note loads before editing');
  followupInput.value = '다음 단계';
  await saveButton.handlers.click();
  assert.equal(button.dataset.savedStatus, 'in_progress');
  assert.equal(lastBody.note, '다음 단계', 'State and note share one request');
  assert.equal(layer.hidden, true);
  assert.ok(options.every(option => !option.disabled));
  button.handlers.click(); await settle();
  await options[3].handlers.click();
  assert.equal(options[3].getAttribute('aria-pressed'), 'true');
  assert.match(followupLabel.textContent, /미룬 이유/);
  assert.equal(button.dataset.savedStatus, 'in_progress');
  followupInput.value = '';
  failure = true; await saveButton.handlers.click();
  assert.equal(layer.hidden, false, 'Save failure preserves the draft and sheet');
  assert.equal(button.dataset.savedStatus, 'in_progress');
  failure = false; await saveButton.handlers.click();
  assert.equal(button.dataset.savedStatus, 'postponed');
  assert.equal(lastBody.note, '', 'Memo is optional');
  assert.equal(layer.hidden, true);
  console.log('PASS item status sheet, shared plans, success and failed save');
})().catch(error => { console.error(error); process.exitCode = 1; });
