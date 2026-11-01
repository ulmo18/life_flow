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
layer.querySelector = selector => selector === '.todo-sheet' ? {scrollTop: 50} : close;
const options = ['pending', 'completed', 'cancelled', 'postponed', 'in_progress', 'external'].map(value => {
  const option = element({todoStatusOption: value}); option.textContent = value; return option;
});
let failure = false;
let malformed = false;
let message = '';
const ctx = {
  document: {
    body: {classList: {add() {}, remove() {}}},
    querySelector(selector) { return selector.includes('layer') ? layer : selector.includes('status-note') ? note : selector.includes('status-record') ? record : title; },
    querySelectorAll(selector) { return selector.includes('status-option') ? options : selector.includes('status-close') ? [close] : [button, sibling]; },
  },
  timeForm: {querySelector: () => ({value: 'csrf'})},
  FormData: class { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } },
  SyntaxError,
  TypeError,
  fetch: async (_url, request) => ({ok: !failure, json: async () => malformed ? JSON.parse('<html>') : failure ? {ok: false, message: '저장 실패'} : {ok: true, status: request.body.values.status}}),
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
  console.log('PASS item status sheet, shared plans, success and failed save');
})().catch(error => { console.error(error); process.exitCode = 1; });
