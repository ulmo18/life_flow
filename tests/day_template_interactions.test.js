const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/components/time-grid-selection.js','utf8');
function setup(requireLongPress) {
  const listeners={}, timers=new Map(), windowListeners={}; let next=0; const selected=[];
  class Element { constructor(index){this.dataset={index:String(index)};this.classList={contains:name=>name==='cell',add(){},remove(){}};} closest(){return null;} }
  const cell=new Element(42), grid=new Element();
  Object.assign(grid,{addEventListener:(name,fn)=>listeners[name]=fn,contains:()=>true,querySelector:()=>cell,setPointerCapture(){},hasPointerCapture:()=>false,releasePointerCapture(){}});
  const context={HTMLElement:Element,Element,navigator:{},document:{elementsFromPoint:()=>[cell],addEventListener(){}},window:{setTimeout(fn){timers.set(++next,fn);return next;},clearTimeout:id=>timers.delete(id),addEventListener:(name,fn)=>windowListeners[name]=fn}};
  vm.runInNewContext(source,context);context.window.LifeFlowTimeGrid.create({grid,requireLongPress,onSelect:range=>selected.push(range)});
  const event=(type='mouse',x=0)=>({target:cell,pointerId:1,pointerType:type,isPrimary:true,button:0,clientX:x,clientY:0,preventDefault(){}});
  return {listeners,windowListeners,selected,event,hold(){const pending=[...timers.values()];timers.clear();pending.forEach(fn=>fn());}};
}
let q=setup(true);q.listeners.pointerdown(q.event());q.listeners.pointerup(q.event());q.hold();assert.equal(q.selected.length,0,'short mouse click does not register');
q.listeners.pointerdown(q.event());q.hold();q.listeners.pointerup(q.event());assert.equal(q.selected.length,1);assert.equal(q.selected[0].start,42);assert.equal(q.selected[0].end,43);
for(const type of ['mouse','touch']) {
  q=setup(true);q.listeners.pointerdown(q.event(type));q.listeners.pointermove(q.event(type,12));q.hold();q.listeners.pointerup(q.event(type,12));assert.equal(q.selected.length,0,'scroll motion cancels '+type+' hold');
  q.listeners.pointerdown(q.event(type));q.windowListeners.scroll();q.hold();q.listeners.pointerup(q.event(type));assert.equal(q.selected.length,0,'scroll event cancels '+type+' hold');
  q.listeners.pointerdown(q.event(type));q.hold();q.listeners.pointercancel(q.event(type));q.listeners.pointerup(q.event(type));assert.equal(q.selected.length,0,'cancelled pointer does not register');
}
q=setup(false);q.listeners.pointerdown(q.event());q.listeners.pointerup(q.event());assert.equal(q.selected.length,1,'Calendar default mouse selection remains immediate');
const plan=fs.readFileSync('public/assets/js/pages/plan.js','utf8');
const block={start_index:42,end_index:48}; const context={blocks:[block]};
vm.runInNewContext(plan.slice(plan.indexOf('  function valid('),plan.indexOf('  function sync('))+'globalThis.valid=valid;',context);
assert.equal(context.valid({start_index:48,end_index:54}),true,'adjacent schedules allowed');
assert.equal(context.valid({start_index:47,end_index:54}),false,'overlaps rejected');
assert.equal(context.valid({start_index:143,end_index:144}),true,'midnight endpoint supported');
for(const range of [{start_index:-1,end_index:1},{start_index:143,end_index:145},{start_index:48,end_index:48}])assert.equal(context.valid(range),false);
assert.equal(context.valid({start_index:43,end_index:49},block),true,'editing excludes only the current object');
console.log('PASS day-template long press, scroll/cancel, default compatibility, overlap and midnight bounds');
