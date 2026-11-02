// Run with Node.js: node tests/todo_recurring_tab.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/todo.js', 'utf8');
const start = source.indexOf('  const listTabs =');
const end = source.indexOf('\n  function close()', start);
assert.ok(start >= 0 && end > start);
const tabs = ['items', 'recurring'].map(name => ({
  dataset: {todoListTab: name}, attrs: {}, handlers: {},
  setAttribute(key, value) { this.attrs[key] = value; },
  addEventListener(type, callback) { this.handlers[type] = callback; },
  focus() { this.focused = true; },
}));
const panels = ['items', 'recurring'].map(name => ({dataset: {todoListPanel: name}, hidden: name !== 'items'}));
const adds = ['items', 'recurring'].map(name => ({dataset: {todoAddTab: name}, hidden: name !== 'items'}));
vm.runInNewContext(source.slice(start, end), {
  page: {querySelectorAll(selector) { return selector.includes('tab]') ? tabs : panels; }},
  document: {querySelectorAll() { return adds; }},
  Array,
});
tabs[1].handlers.click();
assert.equal(panels[1].hidden, false);
assert.equal(panels[0].hidden, true);
assert.equal(adds[1].hidden, false);
assert.equal(adds[0].hidden, true);
tabs[1].handlers.keydown({key: 'ArrowLeft', preventDefault() {}});
assert.equal(adds[0].hidden, false);
assert.equal(adds[1].hidden, true);
assert.equal(tabs[0].focused, true);
console.log('PASS tab-specific add actions and keyboard navigation');

const formStart = source.indexOf('  function closeRecurring()');
const formEnd = source.indexOf("  document.querySelectorAll('[data-todo-template-form]')", formStart);
assert.ok(formStart >= 0 && formEnd > formStart);
const opener = {handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; }, focus() {}};
const editOpener = {dataset:{templateId:'7',title:'반복 업무',importance:'B',goalId:'3',duration:'9',enabled:'1'}, handlers:{}, addEventListener(type, fn) { this.handlers[type] = fn; }, focus() {}};
const close = {handlers: {}, addEventListener(type, fn) { this.handlers[type] = fn; }};
const submit = {disabled: false};
const importance = {value:'B',checked:false};
const form = {action: '/todo/recurring', handlers: {}, elements: {title: {value:''}, goal_id: {value:''}}, reset() {}, querySelector(selector) { return selector.includes('_csrf_token') ? {value:'csrf'} : submit; }, querySelectorAll() { return [importance]; }, addEventListener(type, fn) { this.handlers[type] = fn; }};
const layer = {hidden: true};
const del = {hidden:true, disabled:false, handlers:{}, classList:{add(){},remove(){}}, addEventListener(type, fn) { this.handlers[type]=fn; }};
const enabled = {hidden:true, disabled:false, dataset:{}, handlers:{}, addEventListener(type, fn) { this.handlers[type]=fn; }};
let fail = false;
let message = '';
let destination = '';
let requests = 0;
let lastRequest;
let resolveResponse;
const context = {
  recurringLayer: layer, recurringForm: form, recurringSaving: false, recurringDelete: del,
  recurringId: {value:''}, recurringDuration: {value:''}, recurringSubmit: submit, recurringEnabled:enabled,
  recurringHeading: {textContent:''}, recurringTrigger:null, recurringHold:null, ignoreRecurringClick:false, URL,
  document: {
    body: {classList: {add() {}, remove() {}}},
    querySelector: () => opener, querySelectorAll: selector => selector.includes('recurring-close') ? [close] : (selector.includes('recurring-edit') ? [editOpener] : []), getElementById: () => ({focus() {}}), addEventListener() {},
  },
  window: {
    setTimeout(fn) { fn(); },
    LifeFlowToast: {show(value) { message = value; }},
    location: {href: 'http://localhost/todo?date=2026-10-20', assign(value) { destination = value; }},
  },
  FormData: class {constructor(){this.values={};} set(key,value){this.values[key]=value;}},
  showTodoDeleteMessage(value) { message = value; },
  fetch: async (url, request) => { requests++; lastRequest = {url, request}; await new Promise(resolve => { resolveResponse = resolve; }); return {ok: !fail, json: async () => fail ? {ok: false, message: '저장 실패'} : {ok: true}}; },
};
vm.runInNewContext(source.slice(formStart, formEnd), context);
(async () => {
  opener.handlers.click({currentTarget:opener});
  assert.equal(layer.hidden, false);
  assert.equal(enabled.hidden, true);
  assert.equal(del.hidden, true);
  fail = true;
  const first = form.handlers.submit({preventDefault() {}});
  await form.handlers.submit({preventDefault() {}});
  assert.equal(requests, 1);
  resolveResponse(); await first;
  assert.equal(message, '저장 실패');
  assert.equal(layer.hidden, false);
  assert.equal(submit.disabled, false);
  fail = false;
  const second = form.handlers.submit({preventDefault() {}});
  resolveResponse(); await second;
  assert.equal(new URL(destination).searchParams.get('date'), '2026-10-20');
  assert.equal(new URL(destination).searchParams.get('tab'), 'recurring');
  context.recurringSaving = false;
  editOpener.handlers.click();
  assert.equal(form.action, '/plan/block-template/update');
  assert.equal(context.recurringId.value, '7');
  assert.equal(context.recurringDuration.value, '9');
  assert.equal(form.elements.title.value, '반복 업무');
  assert.equal(form.elements.goal_id.value, '3');
  assert.equal(importance.checked, true);
  assert.equal(del.hidden, false);
  assert.equal(enabled.hidden, false);
  assert.equal(enabled.dataset.nextEnabled, '0');
  assert.equal(enabled.textContent, '미사용으로 변경');
  fail = true;
  const failedToggle = enabled.handlers.click(); resolveResponse(); await failedToggle;
  assert.equal(message, '저장 실패');
  assert.equal(enabled.disabled, false);
  assert.equal(layer.hidden, false);
  fail = false;
  const successfulToggle = enabled.handlers.click(); resolveResponse(); await successfulToggle;
  assert.equal(lastRequest.url, '/plan/block-template/enabled');
  assert.equal(lastRequest.request.body.values.block_template_id, '7');
  assert.equal(lastRequest.request.body.values.enabled, '0');
  assert.equal(new URL(destination).searchParams.get('tab'), 'recurring');
  assert.equal(new URL(destination).searchParams.get('date'), '2026-10-20');
  console.log('PASS recurring creation failure, duplicate prevention and return date');
})().catch(error => { console.error(error); process.exitCode = 1; });
