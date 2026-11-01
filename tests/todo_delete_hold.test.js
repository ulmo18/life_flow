// Run with Node.js: node tests/todo_delete_hold.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/todo.js', 'utf8');
const start = source.indexOf("  const todoDeleteButton = document.querySelector('[data-todo-delete]');");
const end = source.indexOf('  function setEditGoal(', start);
assert.ok(start >= 0 && end > start);
let clock = 0;
let timerId = 0;
const timers = new Map();
let requests = 0;
let messages = [];
let fail = false;
let reloaded = false;
const saveButton = {disabled: false};
const button = {
  disabled: false, handlers: {}, classes: new Set(),
  classList: {add(value) { button.classes.add(value); }, remove(value) { button.classes.delete(value); }},
  addEventListener(name, fn) { this.handlers[name] = fn; },
  setPointerCapture() {}, getBoundingClientRect() { return {left: 0, right: 100, top: 0, bottom: 50}; },
};
const editLayer = {hidden: false};
const ctx = {
  document: {querySelector: () => button, addEventListener() {}, hidden: false},
  editLayer, editId: {value: '7'}, todoDeleteHold: null, todoDeletePending: false,
  editForm: {querySelector(selector) { return selector.includes('_csrf_token') ? {value:'csrf'} : saveButton; }},
  FormData: class { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } },
  fetch: async (_url, request) => { requests++; assert.equal(request.body.values.daily_plan_item_id, '7'); return {ok: !fail, json: async () => fail ? {ok: false, message: '실패'} : {ok: true}}; },
  window: {
    performance: {now: () => clock},
    setTimeout(fn) { const id = ++timerId; timers.set(id, fn); return id; },
    clearTimeout(id) { timers.delete(id); },
    LifeFlowToast: {show(message) { messages.push(message); }},
    location: {reload() { reloaded = true; }},
  },
};
vm.runInNewContext(source.slice(start, end), ctx);
const down = () => button.handlers.pointerdown({isPrimary: true, pointerType: 'touch', pointerId: 1});
const up = () => button.handlers.pointerup({pointerId: 1});
const fire = () => { const fn = [...timers.values()].at(-1); timers.clear(); fn(); };
(async () => {
  down(); clock = 200; up();
  assert.equal(requests, 0);
  assert.match(messages[0], /1\.5초/);
  assert.equal(button.classes.has('is-holding'), false);
  timers.clear();
  down(); button.handlers.pointercancel({pointerId: 1});
  assert.equal(timers.size, 0);
  fail = true; down(); clock += 1500; fire(); await new Promise(resolve => setImmediate(resolve));
  assert.equal(requests, 1);
  assert.equal(button.disabled, false);
  assert.equal(saveButton.disabled, false);
  assert.equal(messages.at(-1), '실패');
  fail = false;
  button.handlers.keydown({key:'Enter', repeat:false, preventDefault() {}});
  clock += 1500; fire(); await new Promise(resolve => setImmediate(resolve));
  assert.equal(requests, 2);
  assert.equal(reloaded, true);
  console.log('PASS delayed deletion short press, cancellation, failure and keyboard success');
})().catch(error => { console.error(error); process.exitCode = 1; });
