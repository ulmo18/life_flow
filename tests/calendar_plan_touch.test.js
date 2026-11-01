// Run with Node.js: node tests/calendar_plan_touch.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/calendar.js', 'utf8');
const start = source.indexOf('  let planCreateRange = null;');
const end = source.indexOf("  daygrid.addEventListener('pointerdown'", start);
assert.ok(start >= 0 && end > start);
const listeners = {}, gridListeners = {};
let timer, opened = [], conflict = false, prevented = false;
const context = {
  daygrid:{ addEventListener(type, fn) { gridListeners[type] = fn; }, classList:{ add() {}, remove() {} }, querySelectorAll() { return []; }, querySelector() { return null; } },
  document:{ addEventListener(type, fn) { listeners[type] = fn; }, hidden:false },
  window:{ setTimeout(fn) { timer = fn; return 1; }, clearTimeout() { timer = null; }, addEventListener() {} },
  navigator:{}, rangeSavePending:false, planPlacementSaving:false, calendarWorkMode:'normal', selectedPlanBlockSource:null,
  gridIndexFromPoint(x) { return x; }, updateWorkStatus() {},
  hasOccupiedRange() { return conflict; }, showCalendarToast() {},
  openCalendarPlanCreate(range) { opened.push({ start:range.start, end:range.end }); },
};
vm.runInNewContext(source.slice(start, end), context);
function touch(x, y = 0) { return { identifier:1, clientX:x, clientY:y }; }
function begin(x) { gridListeners.touchstart({ touches:[touch(x)], changedTouches:[touch(x)], target:{ closest() { return null; } } }); }
function finish(x, y = 0) { prevented = false; listeners.touchend({ changedTouches:[touch(x, y)], cancelable:true, preventDefault() { prevented = true; } }); }
begin(42); finish(42); assert.equal(opened.length, 0); assert.equal(prevented, false, 'Short touch must not suppress native interaction');
begin(42); timer(); finish(42); assert.deepEqual(opened.pop(), { start:42, end:43 }); assert.equal(prevented, true);
begin(42); listeners.touchmove({ changedTouches:[touch(42, 10)] }); finish(42, 10); assert.equal(opened.length, 0);
begin(42); finish(42, 10); assert.equal(opened.length, 0);
begin(42); timer(); finish(44); assert.deepEqual(opened.pop(), { start:42, end:45 });
conflict = true; begin(42); timer(); finish(42); assert.equal(opened.length, 0);
context.calendarWorkMode = 'plan-placement'; context.selectedPlanBlockSource = {};
begin(42); finish(42); assert.equal(opened.length, 0);
process.stdout.write('PASS plan grid short touch, scroll, single-cell and range long press, conflict and selected source\n');
