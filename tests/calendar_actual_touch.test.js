// Run with Node.js: node tests/calendar_actual_touch.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/calendar.js', 'utf8');
const start = source.indexOf('  let actualTouchSelection = null;');
const end = source.indexOf("  actualDaygrid?.addEventListener('pointerdown'", start);
assert.ok(start >= 0 && end > start);
const listeners = {}, gridListeners = {};
const windowListeners = {};
let timer, opened = [], previews = [], bridgeCalls = [], conflict = false, prevented = false;
const context = {
  actualDaygrid:{addEventListener(type, fn){gridListeners[type] = fn;}, classList:{add(){}, remove(){}}},
  document:{addEventListener(type, fn){listeners[type] = fn;}},
  window:{addEventListener(type, fn){windowListeners[type]=fn;}, setTimeout(fn){timer = fn; return 1;}, clearTimeout(){timer = null;}, LifeFlowAndroidBridge:{setPullToRefreshEnabled(value){bridgeCalls.push(value); return true;}}},
  navigator:{}, calendarWorkMode:'normal', actualGridIndexFromPoint(x){return x;},
  updateWorkStatus(){}, clearActualPreview(){}, previewActualRange(start, end){previews.push([start, end]);},
  actualRangeFromStartAndPointer(start, end){return {start:Math.min(start, end), end:Math.max(start, end) + 1};},
  hasActualConflict(){return conflict;}, showCalendarToast(){}, openEventSheet(start, end){opened.push([start, end]);},
};
vm.runInNewContext(source.slice(start, end), context);
function touch(x, y = 0){return {identifier:1, clientX:x, clientY:y};}
function begin(x){gridListeners.touchstart({touches:[touch(x)], changedTouches:[touch(x)], target:{closest(){return null;}}});}
function finish(x, y = 0){prevented = false; listeners.touchend({changedTouches:[touch(x, y)], cancelable:true, preventDefault(){prevented = true;}});}
begin(42); finish(42);
assert.equal(opened.length, 0); assert.equal(prevented, false); assert.deepEqual(bridgeCalls, []);
begin(42); listeners.touchmove({changedTouches:[touch(42, 10)]}); finish(42, 10);
assert.equal(opened.length, 0); assert.deepEqual(bridgeCalls, []);
begin(42); timer(); finish(42);
assert.deepEqual(opened.pop(), [42, 43]); assert.equal(prevented, true); assert.deepEqual(bridgeCalls, [false, true]);
begin(42); timer(); listeners.touchmove({changedTouches:[touch(44)], cancelable:true, preventDefault(){prevented = true;}}); finish(44);
assert.deepEqual(opened.pop(), [42, 45]); assert.deepEqual(previews.at(-1), [42, 45]);
begin(42); timer(); listeners.touchcancel({changedTouches:[touch(42)]}); finish(42);
assert.equal(opened.length, 0); assert.deepEqual(bridgeCalls, [false, true, false, true, false, true]);
conflict = true; begin(42); timer(); finish(42); assert.equal(opened.length, 0);
context.calendarWorkMode = 'tag-paint'; begin(42); finish(42); assert.equal(opened.length, 0);
context.calendarWorkMode = 'normal'; conflict = false;
const pointerEnd = source.indexOf('  window.LifeFlowCalendarResizeHold(actualLayer', end);
assert.ok(pointerEnd > end);
vm.runInNewContext('let actualSelection = null;\n' + source.slice(end, pointerEnd), context);
function pointer(type, x){return {pointerType:type, pointerId:2, isPrimary:true, button:0, clientX:x, clientY:0, target:{closest(){return null;}}, preventDefault(){}};}
gridListeners.pointerdown(pointer('mouse', 42)); gridListeners.pointerup(pointer('mouse', 42));
assert.equal(opened.length, 0, 'Short mouse click must not register');
gridListeners.pointerdown(pointer('mouse',42)); timer(); gridListeners.pointerup(pointer('mouse',42));
assert.deepEqual(opened.pop(), [42,43], 'Long mouse click registers a single cell');
gridListeners.pointerdown(pointer('mouse', 42)); gridListeners.pointermove(pointer('mouse', 44)); gridListeners.pointerup(pointer('mouse', 44));
assert.equal(opened.length,0,'Immediate mouse drag must not register');
gridListeners.pointerdown(pointer('mouse',42)); timer(); gridListeners.pointermove(pointer('mouse',44)); gridListeners.pointerup(pointer('mouse',44));
assert.deepEqual(opened.pop(),[42,45],'Drag after long mouse hold registers a range');
gridListeners.pointerdown(pointer('mouse',42)); windowListeners.scroll(); gridListeners.pointerup(pointer('mouse',42)); assert.equal(opened.length,0,'Scroll cancels mouse entry');
gridListeners.pointerdown(pointer('mouse',42)); timer(); gridListeners.pointercancel(pointer('mouse',42)); gridListeners.pointerup(pointer('mouse',42)); assert.equal(opened.length,0,'Pointer cancellation never registers');
gridListeners.pointerdown(pointer('touch', 42)); gridListeners.pointerup(pointer('touch', 42));
assert.equal(opened.length, 0, 'Touch pointer compatibility events must not create entries');
process.stdout.write('PASS actual grid short touch, scroll, single-cell and range long press, cancellation and conflict\n');
