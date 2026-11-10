// Run with Node.js: node tests/calendar_plan_block_quick_edit.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('public/assets/js/pages/calendar.js', 'utf8');
const begin = source.indexOf('  let planBlockChangePending = false;');
const end = source.indexOf('  let pendingGoalSelect', begin);
assert.ok(begin >= 0 && end > begin);

class Input { constructor(value) { this.value = value; this.checked = false; this.disabled = false; } }
class Body {
  constructor(form) { this.values = form ? { _csrf_token: 'token', daily_plan_block_id: form.blockId.value } : {}; }
  set(key, value) { this.values[key] = value; }
  get(key) { return this.values[key]; }
}
const classes = (...initial) => ({ values: new Set(initial), add(x) { this.values.add(x); }, remove(x) { this.values.delete(x); }, toggle(x, enabled) { if (enabled) this.add(x); else this.remove(x); } });
const statusInputs = ['pending', 'completed', 'cancelled', 'postponed', 'in_progress', 'external'].map(value => new Input(value));
const importanceInputs = ['A', 'B', 'C', 'D'].map(value => new Input(value));
const statusGroup = { dataset: {}, querySelectorAll: () => statusInputs, querySelector: () => statusInputs[1], addEventListener(_type, fn) { this.handler = fn; } };
const importanceGroup = { dataset: {}, querySelectorAll: () => importanceInputs, addEventListener(_type, fn) { this.handler = fn; } };
const linkControls = { hidden: false };
const statusNote = {};
const linkNote = {};
const blockId = { value: '42' };
const itemId = { value: '17' };
const form = { action: '/calendar/plan-block/details', querySelector(selector) { return selector.includes('_csrf_token') ? {value:'token'} : selector === '#calendarPlanBlockItemId' ? itemId : blockId; } };
statusGroup.closest = () => form;
importanceGroup.closest = () => form;
const deleteForm = { action:'/calendar/plan-block/delete', blockId, querySelector: () => blockId, addEventListener(_type, fn) { this.handler = fn; }, requestSubmit() { this.result = this.handler({currentTarget:this, preventDefault(){}}); } };
const deleteButton = { classList:classes(), listeners:{}, addEventListener(type, fn) { this.listeners[type] = fn; }, setPointerCapture() {}, getBoundingClientRect() { return {left:0, top:0, right:100, bottom:50}; } };
const detachButton = { classList:classes(), listeners:{}, addEventListener(type, fn) { this.listeners[type] = fn; }, setPointerCapture() {}, getBoundingClientRect() { return {left:0, top:0, right:100, bottom:50}; }, closest() { return form; } };
const actualDeleteButton = { classList:classes(), listeners:{}, addEventListener(type, fn) { this.listeners[type] = fn; }, setPointerCapture() {}, getBoundingClientRect() { return {left:0, top:0, right:100, bottom:50}; } };
const actualEditSheet = {hidden:true, querySelector:() => actualDeleteButton};
const actualDeleteForm = { submissions:0, addEventListener(_type, fn) { this.handler = fn; }, requestSubmit() { let prevented = false; this.handler({preventDefault(){prevented = true;}}); if (!prevented) this.submissions++; } };
const badge = { textContent:'B' };
const statusBadge = { remove() { segment.statusBadge = null; } };
const segment = {
  dataset:{planBlockId:'42', planItemId:'17', planItemTitle:'계획', planItemImportance:'B', planManualStatus:'pending', planItemStartIndex:'12', planItemEndIndex:'18', planItemLinked:'1'},
  classList:classes('importance-b'), statusBadge,
  querySelector(selector) { return selector === '.calendar-importance-badge' ? badge : this.statusBadge; },
  setAttribute(name, value) { this[name] = value; },
  remove() { this.removed = true; },
};
const actual = { dataset:{eventDailyPlanBlockId:'42', eventDailyPlanItemId:'17'}, classList:classes('is-linked') };
const planLayer = { querySelectorAll: () => segment.removed ? [] : [segment] };
const actualLayer = { querySelectorAll: () => [actual] };
const title = {};
const sheetTitle = {};
const inheritedParts = {'[data-plan-inherited-importance]':{}, '[data-plan-inherited-importance-label]':{}, '[data-plan-inherited-status-icon]':{}, '[data-plan-inherited-status-label]':{}};
const inherited = {hidden:true, setAttribute(){}, querySelector: selector => inheritedParts[selector] || null};
const statusContainer = {hidden:false};
const fields = {'#calendarPlanBlockItemId':itemId, '#calendarPlanBlockId':blockId, '#calendarPlanBlockDeleteId':blockId, '#calendarPlanBlockStart':{}, '#calendarPlanBlockEnd':{}, '[data-plan-block-title]':title, '#planBlockEditSheetTitle':sheetTitle, '#calendarPlanBlockStatus':statusGroup, '[data-plan-block-status-note]':statusNote, '[data-plan-block-inherited]':inherited, '[data-plan-block-status-container]':statusContainer, '[data-plan-block-importance]':importanceGroup, '[data-plan-block-actual-link-controls]':linkControls, '[data-plan-block-link-count]':linkNote, '[data-plan-block-delete]':deleteButton, '[data-plan-block-detach]':detachButton, '[name="_csrf_token"]':{value:'token'}};
const sheet = { hidden:false, querySelector: selector => fields[selector] || null, querySelectorAll: () => [statusGroup, importanceGroup, detachButton, deleteButton] };
const management = {open:true, addEventListener(_type, fn){this.handler=fn;}};
fields['[data-plan-block-management]'] = management;
fields['[data-plan-block-actual-summary]'] = linkNote;
let response = {ok:true, json:async () => ({ok:true, message:'저장됨'})};
let requests = [];
let closed = 0;
let message = '';
let now = 0;
let nextTimer = 1;
const timers = new Map();
const documentListeners = {};
const windowListeners = {};
function advance(ms) {
  now += ms;
  for (const [id, timer] of Array.from(timers)) {
    if (timer.at <= now) { timers.delete(id); timer.fn(); }
  }
}
const context = {
  planBlockEditSheet:sheet, eventEditSheet:actualEditSheet, planLayer, actualLayer, HTMLInputElement:Input, FormData:Body,
  document:{querySelectorAll: selector => selector === '[data-plan-block-id]' ? [segment] : [], getElementById: id => id === 'calendarEventDeleteForm' ? actualDeleteForm : deleteForm, createElement: () => ({setAttribute(){}}), addEventListener(type, fn) { documentListeners[type] = fn; }, hidden:false},
  page:{dataset:{calendarDate:'2026-11-01'}},
  checkRadio(group, _name, value) { (group === sheet ? importanceInputs : group.querySelectorAll('input')).forEach(input => { input.checked = input.value === value; }); },
  openPanel() { sheet.hidden = false; }, closePanels() { closed++; sheet.hidden = true; },
  showCalendarToast(value) { message = value; }, window:{LifeFlowToast:{show(value){message = value;}}, alert(value){message = value;}, addEventListener(type, fn) { windowListeners[type] = fn; }, performance:{now:() => now}, setTimeout(fn, delay) { const id = nextTimer++; timers.set(id, {fn, at:now + delay}); return id; }, clearTimeout(id) { timers.delete(id); }},
  formatGridTime: value => String(value), syncPlanLinkSummary() {}, preserveCalendarScroll() {},
  fetch:async (url, options) => { requests.push({url, body:options.body}); return response; }, TypeError, SyntaxError,
};
vm.createContext(context);
vm.runInContext(source.slice(begin, end) + '\nglobalThis.openBlock = openPlanBlockEditSheet;', context);

module.exports = (async () => {
  context.openBlock({dataset:{planBlockId:'42', planItemId:'17', planItemImportance:'A', planManualStatus:'completed', planBlockActualCount:'0', planFollowsTodo:'1'}});
  assert.equal(statusContainer.hidden, false, 'Linked To-do state can be edited here.');
  assert.equal(statusInputs[1].disabled, false, 'To-do completion does not require an Actual link.');
  assert.equal(importanceGroup.hidden, true, 'Inherited importance cannot be edited.');
  assert.equal(inheritedParts['[data-plan-inherited-importance]'].textContent, 'A', 'Importance icon displays the inherited value.');
  assert.equal(statusInputs[1].checked, true, 'Selected status shows completion without a duplicate summary.');
  assert.equal(management.open, false, 'Management starts collapsed when opening a block.');
  assert.equal(linkNote.textContent, '실제 기록 없음');
  context.openBlock({dataset:{planBlockId:'42', planItemId:'17', planItemImportance:'B', planManualStatus:'pending', planBlockActualCount:'0'}});
  assert.equal(statusContainer.hidden, false, 'Template-only block keeps status controls.');
  assert.equal(importanceGroup.hidden, false, 'Template-only block keeps importance controls.');
  assert.equal(linkControls.hidden, true, 'Unlinked blocks hide Actual controls');
  assert.equal(statusInputs[1].disabled, true, 'Completion needs an Actual link');
  context.openBlock({dataset:{planBlockId:'42', planItemId:'17', planItemImportance:'B', planManualStatus:'pending', planBlockActualCount:'1', planBlockActualMinutes:'40'}});
  assert.equal(linkNote.textContent, '실제 기록 1건 · 40분', 'Overview contains count and duration.');
  assert.equal(linkControls.hidden, false);
  assert.equal(statusInputs[1].disabled, false);

  statusInputs[4].checked = true;
  await statusGroup.handler({currentTarget:statusGroup, target:statusInputs[4]});
  assert.equal(closed, 1, 'Successful status closes the sheet');
  assert.equal(segment.dataset.planManualStatus, 'in_progress');
  assert.equal(requests.at(-1).url, '/calendar/plan-block/status');

  sheet.hidden = false;
  statusInputs[4].checked = false;
  statusInputs[0].checked = true;
  response = {ok:false, json:async () => ({ok:false, message:'상태 저장 실패'})};
  await statusGroup.handler({currentTarget:statusGroup, target:statusInputs[0]});
  assert.equal(closed, 1, 'Failed status keeps the sheet open');
  assert.equal(statusInputs[4].checked, true, 'Failed status restores the previous selection');
  assert.equal(message, '상태 저장 실패');

  importanceGroup.dataset.savedImportance = 'B';
  importanceInputs[0].checked = true;
  let finishSave;
  response = {ok:true, json:() => new Promise(resolve => { finishSave = resolve; })};
  const beforeSave = requests.length;
  const pendingSave = importanceGroup.handler({currentTarget:importanceGroup, target:importanceInputs[0]});
  await Promise.resolve();
  await importanceGroup.handler({currentTarget:importanceGroup, target:importanceInputs[0]});
  assert.equal(requests.length, beforeSave + 1, 'A pending save blocks duplicate requests');
  finishSave({ok:true});
  await pendingSave;
  assert.equal(closed, 2, 'Successful importance closes the sheet');
  assert.equal(segment.dataset.planItemImportance, 'A');
  assert.equal(badge.textContent, 'A');
  assert.equal(requests.at(-1).body.get('importance'), 'A');

  sheet.hidden = false;
  importanceGroup.dataset.savedImportance = 'A';
  importanceInputs[1].checked = true;
  response = {ok:false, json:async () => ({ok:false, message:'저장 실패'})};
  await importanceGroup.handler({currentTarget:importanceGroup, target:importanceInputs[1]});
  assert.equal(closed, 2, 'Failure keeps the sheet open');
  assert.equal(importanceInputs[0].checked, true, 'Failure restores importance');
  assert.equal(message, '저장 실패');

  response = {ok:true, json:async () => ({ok:true, message:'저장됨'})};
  const pointer = {isPrimary:true, pointerType:'touch', pointerId:1, button:0, clientX:50, clientY:25};
  const before = requests.length;
  detachButton.listeners.pointerdown(pointer);
  assert.equal(detachButton.classList.values.has('is-holding'), true, 'Detachment hold shows progress');
  advance(1499);
  detachButton.listeners.pointerup(pointer);
  assert.equal(requests.length, before, 'Short detachment hold sends no request');
  assert.match(message, /연결을 해제하려면 1\.5초/);
  assert.equal(detachButton.classList.values.has('is-holding'), false, 'Short release clears detachment progress');
  detachButton.listeners.pointerdown(pointer);
  detachButton.listeners.pointercancel(pointer);
  advance(1500);
  assert.equal(requests.length, before, 'Detachment pointer cancellation sends no request');
  detachButton.listeners.pointerdown(pointer);
  detachButton.listeners.pointermove({...pointer, clientX:101});
  advance(1500);
  assert.equal(requests.length, before, 'Leaving the detachment button cancels the hold');

  response = {ok:false, json:async () => ({ok:false, message:'연결 해제 실패'})};
  detachButton.listeners.keydown({key:'Enter', repeat:false, preventDefault(){}});
  advance(1500);
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(closed, 2, 'Failed detachment keeps the sheet open');
  assert.equal(actual.dataset.eventDailyPlanBlockId, '42', 'Failed detachment preserves the link');
  assert.equal(message, '연결 해제 실패');

  response = {ok:true, json:async () => ({ok:true, message:'저장됨'})};
  segment.dataset.planFollowsTodo = '1';
  segment.dataset.planManualStatus = 'completed';
  detachButton.listeners.pointerdown(pointer);
  advance(1500);
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(requests.at(-1).url, '/calendar/plan-block/detach');
  assert.equal(actual.dataset.eventDailyPlanBlockId, '', 'Actual record is retained with its link cleared');
  assert.equal(segment.dataset.planManualStatus, 'completed', 'Detachment retains inherited To-do state.');
  assert.equal(closed, 3);

  sheet.hidden = false;
  const beforeShort = requests.length;
  deleteButton.listeners.pointerdown(pointer);
  detachButton.listeners.pointerdown({...pointer, pointerId:2});
  assert.equal(detachButton.classList.values.has('is-holding'), false, 'Only one delay button can be held at a time');
  detachButton.listeners.pointerup({...pointer, pointerId:2});
  assert.equal(deleteButton.classList.values.has('is-holding'), true, 'Hold shows progress');
  advance(1499);
  deleteButton.listeners.pointerup(pointer);
  assert.equal(deleteButton.classList.values.has('is-holding'), false, 'Early release clears progress');
  assert.equal(requests.length, beforeShort, 'Early release sends no request');
  assert.match(message, /1\.5초 이상/);

  deleteButton.listeners.pointerdown(pointer);
  deleteButton.listeners.pointercancel(pointer);
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Pointer cancellation sends no request');
  deleteButton.listeners.pointerdown(pointer);
  deleteButton.listeners.lostpointercapture(pointer);
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Losing pointer capture cancels the hold');
  deleteButton.listeners.pointerdown(pointer);
  deleteButton.listeners.pointermove({...pointer, clientX:101});
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Leaving the button cancels the hold');
  deleteButton.listeners.pointerdown(pointer);
  context.document.hidden = true;
  documentListeners.visibilitychange();
  context.document.hidden = false;
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Hiding the page cancels the hold');
  deleteButton.listeners.pointerdown(pointer);
  windowListeners.blur();
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Window blur cancels the hold');
  deleteButton.listeners.pointerdown(pointer);
  context.cancelCalendarDelayHold();
  advance(1500);
  assert.equal(requests.length, beforeShort, 'Closing the sheet cancels the hold');
  deleteButton.listeners.pointerdown(pointer);
  context.setPlanBlockChangePending(true);
  advance(1500);
  context.setPlanBlockChangePending(false);
  assert.equal(requests.length, beforeShort, 'Another pending save cancels the hold');
  deleteButton.listeners.keydown({key:'Enter', repeat:false, preventDefault(){}});
  advance(1000);
  deleteButton.listeners.keyup({key:'Enter', preventDefault(){}});
  assert.equal(requests.length, beforeShort, 'Short keyboard hold sends no request');
  assert.match(message, /1\.5초 이상/);
  await deleteForm.handler({currentTarget:deleteForm, preventDefault() {}});
  assert.equal(requests.length, beforeShort, 'Direct form submission cannot bypass the hold');

  response = {ok:false, json:async () => ({ok:false, message:'삭제 실패'})};
  deleteButton.listeners.pointerdown(pointer);
  advance(1500);
  await deleteForm.result;
  assert.notEqual(segment.removed, true, 'Failed delete keeps the Plan block');
  assert.equal(closed, 3, 'Failed delete keeps the sheet open');
  assert.equal(message, '삭제 실패');
  response = {ok:true, json:async () => ({ok:true, message:'저장됨'})};
  deleteButton.listeners.keydown({key:' ', repeat:false, preventDefault(){}});
  now += 1500; // A delayed timer must not turn a full hold into a short press.
  deleteButton.listeners.keyup({key:' ', preventDefault(){}});
  await deleteForm.result;
  assert.equal(segment.removed, true, 'Successful delete removes the Plan block');
  assert.equal(closed, 4, 'Successful delete closes the sheet');
  actualEditSheet.hidden = false;
  const actualBefore = actualDeleteForm.submissions;
  actualDeleteButton.listeners.pointerdown(pointer);
  assert.equal(actualDeleteButton.classList.values.has('is-holding'), true, 'Actual deletion shows progress');
  advance(1499);
  actualDeleteButton.listeners.pointerup(pointer);
  assert.equal(actualDeleteForm.submissions, actualBefore, 'Short Actual hold must not submit');
  assert.equal(actualDeleteButton.classList.values.has('is-holding'), false, 'Short Actual hold clears progress');
  assert.match(message, /1\.5초 이상/);
  actualDeleteButton.listeners.pointerdown(pointer);
  actualDeleteButton.listeners.pointercancel(pointer);
  advance(1500);
  assert.equal(actualDeleteForm.submissions, actualBefore, 'Cancelled Actual hold must not submit');
  actualDeleteButton.listeners.pointerdown(pointer);
  actualDeleteButton.listeners.pointermove({...pointer, clientX:101});
  advance(1500);
  assert.equal(actualDeleteForm.submissions, actualBefore, 'Moving outside cancels Actual deletion');
  actualDeleteForm.requestSubmit();
  assert.equal(actualDeleteForm.submissions, actualBefore, 'Direct Actual form submission cannot bypass the hold');
  actualDeleteButton.listeners.keydown({key:'Enter', repeat:false, preventDefault(){}});
  advance(1000);
  actualDeleteButton.listeners.keyup({key:'Enter', preventDefault(){}});
  assert.equal(actualDeleteForm.submissions, actualBefore, 'Short keyboard hold must not submit Actual deletion');
  actualDeleteButton.listeners.pointerdown(pointer);
  advance(1500);
  assert.equal(actualDeleteForm.submissions, actualBefore + 1, 'Full hold submits Actual deletion once');
  actualDeleteButton.listeners.pointerup(pointer);
  actualDeleteButton.listeners.click({detail:1, preventDefault(){}});
  assert.equal(actualDeleteForm.submissions, actualBefore + 1, 'Release and click cannot double-submit');
  response = {ok:true, json:async () => ({ok:true})};
  context.openBlock({dataset:{...segment.dataset, planFollowsTodo:'1', planTodoStatusEditable:'0'}});
  assert.equal(statusInputs.every(input => input.disabled), true, 'Deleted To-do retains a read-only final state');
  context.openBlock({dataset:{...segment.dataset, planFollowsTodo:'1', planBlockActualCount:'0'}});
  statusInputs[1].checked = true;
  await statusGroup.handler({currentTarget:statusGroup, target:statusInputs[1]});
  assert.equal(requests.at(-1).url, '/todo/status', 'Linked blocks update the source To-do endpoint');
  assert.equal(requests.at(-1).body.get('daily_plan_item_id'), '17');
  assert.equal(requests.at(-1).body.get('status'), 'completed');
  assert.equal(segment.dataset.planManualStatus, 'completed');
  process.stdout.write('PASS calendar Plan block quick edit controls\n');
})();
