const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../public/assets/js/pages/calendar.js'), 'utf8');
const start = source.indexOf('  const timetablePair =');
const end = source.indexOf("  document.querySelectorAll('[data-calendar-view]')", start);
assert(start >= 0 && end > start);
const handlers = {}; const pair = { addEventListener: (type, fn) => handlers[type] = fn };
let now = 0;
const ctx = { clearActualPointerSelection() {}, clearActualTouchSelection() {}, document:{ querySelector: () => pair }, window:{ matchMedia: () => ({ matches:true }) }, performance:{ now: () => now }, calendarWorkMode:'normal', calendarView:'both', setCalendarView: v => ctx.calendarView = v };
vm.runInNewContext(source.slice(start,end), ctx);
const event = (x,y, interactive=false,type='touch') => ({pointerType:type,button:0,isPrimary:true,pointerId:1,clientX:x,clientY:y,target:{closest:()=>interactive}});
function drag(x,y, delayed=false, interactive=false,type='touch') { now=0; handlers.pointerdown(event(100,100,interactive,type)); now=delayed?400:50; handlers.pointermove(event(x,y,false,type)); handlers.pointerup(event(x,y,false,type)); }
drag(20,100); assert.equal(ctx.calendarView,'actual');
drag(180,100); assert.equal(ctx.calendarView,'both');
drag(180,100); assert.equal(ctx.calendarView,'plan');
drag(20,100,true); assert.equal(ctx.calendarView,'plan');
drag(20,100,false,true); assert.equal(ctx.calendarView,'plan');
drag(110,190); assert.equal(ctx.calendarView,'plan');
ctx.calendarWorkMode='plan-placement'; drag(20,100); assert.equal(ctx.calendarView,'plan');

ctx.calendarWorkMode="normal";ctx.calendarView="both";drag(20,100,false,false,"mouse");assert.equal(ctx.calendarView,"actual","Mouse drag changes split at mobile widths");drag(180,100,false,false,"pen");assert.equal(ctx.calendarView,"both","Pen drag also changes split");drag(180,100,true,false,"mouse");assert.equal(ctx.calendarView,"both","Held mouse gesture stays reserved for creation");
