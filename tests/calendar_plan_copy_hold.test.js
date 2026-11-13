const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const events=()=>({handlers:{},addEventListener(type,fn){(this.handlers[type]||=[]).push(fn);},emit(type,event={}){for(const fn of this.handlers[type]||[])fn(event);}});
const layer=events(),document=events(),window=events();layer.setPointerCapture=()=>{};layer.releasePointerCapture=()=>{};
let time=0,next=0;const timers=new Map();window.setTimeout=(fn,delay)=>{timers.set(++next,{fn,at:time+delay});return next;};window.clearTimeout=id=>timers.delete(id);
const copies=[],moves=[],bridge=[];window.LifeFlowAndroidBridge={setPullToRefreshEnabled:value=>{bridge.push(value);return true;}};
const block={dataset:{planBlockId:'7',planItemId:'3',planItemStartIndex:'42',planItemEndIndex:'48'},closest:()=>block};
const context={window,document,planLayer:layer,planCopyDrag:null,suppressPlanBlockClick:false,rangeSavePending:false,calendarWorkMode:'normal',page:{classList:{add(){},remove(){}}},gridIndexFromPoint:x=>x<200?x:null,actualGridIndexFromPoint:x=>x>=200?x-200:null,isMobileActualMode:()=>false,clearActualPreview(){},clearPlanMovePreview(){},previewActualRange(){},previewPlanMove(){},hasActualConflict:()=>context.conflict,hasOccupiedRange:()=>context.conflict,showCalendarToast(){},submitPlanBlockCopy:(...args)=>copies.push(args),submitPlanBlockUpdate:(...args)=>moves.push(args)};
vm.runInNewContext(fs.readFileSync('public/assets/js/components/calendar-resize-hold.js','utf8'),context);
const source=fs.readFileSync('public/assets/js/pages/calendar.js','utf8');vm.runInNewContext(source.slice(source.indexOf('  function clearPlanCopyDrag()'),source.indexOf('  function setPlanEditScheduleType')),context);
function advance(ms){time+=ms;for(const[id,timer]of timers)if(timer.at<=time){timers.delete(id);timer.fn();}}
function target(handle=false){return{closest:selector=>selector==='[data-plan-block-resize]'?(handle?{}:null):block};}
const point=x=>({identifier:1,clientX:x,clientY:0});
const touch=(x,handle=false)=>({target:target(handle),touches:[point(x)],changedTouches:[point(x)],cancelable:true,preventDefault(){}});
const mouse=(x,handle=false)=>({target:target(handle),pointerType:'mouse',pointerId:2,isPrimary:true,button:0,clientX:x,clientY:0,cancelable:true,preventDefault(){}});
layer.emit('pointerdown',mouse(43));document.emit('pointerup',mouse(43));advance(450);assert.equal(context.planCopyDrag,null);assert.equal(context.suppressPlanBlockClick,false,'short click retains details');
layer.emit('pointerdown',mouse(43));document.emit('pointermove',mouse(60));advance(450);document.emit('pointerup',mouse(260));assert.equal(copies.length,0,'immediate drag does not copy');
layer.emit('touchstart',touch(43));document.emit('touchmove',touch(55));advance(450);document.emit('touchend',touch(260));assert.equal(copies.length,0,'touch scroll cancels hold');
layer.emit('pointerdown',mouse(43));advance(450);document.emit('pointermove',mouse(260));document.emit('pointerup',mouse(260));assert.deepEqual(copies.pop(),['7',60,66]);assert.equal(context.suppressPlanBlockClick,true,'trailing detail click suppressed');advance(400);
layer.emit('touchstart',touch(43));advance(450);document.emit('touchmove',touch(270));document.emit('touchend',touch(270));assert.deepEqual(copies.pop(),['7',70,76]);assert.deepEqual(bridge,[false,true],'native pull-to-refresh restored');advance(400);
layer.emit('pointerdown',mouse(43));advance(450);document.emit('pointermove',mouse(55));document.emit('pointerup',mouse(55));assert.deepEqual(moves.pop(),['3','7',54,60],'Plan movement preserves grab offset and duration');advance(400);
context.conflict=true;layer.emit('pointerdown',mouse(43));advance(450);document.emit('pointermove',mouse(260));document.emit('pointerup',mouse(260));assert.equal(copies.length,0,'conflicting drop is rejected');context.conflict=false;advance(400);
layer.emit('pointerdown',mouse(43));advance(450);document.emit('pointermove',mouse(343));document.emit('pointerup',mouse(343));assert.equal(copies.length,0,'copy beyond midnight is rejected');advance(400);
layer.emit('pointerdown',mouse(43,true));advance(450);assert.equal(context.planCopyDrag,null,'resize handles are excluded');
layer.emit('pointerdown',mouse(43));window.emit('scroll');advance(450);assert.equal(context.planCopyDrag,null,'scroll cancels pending copy');
layer.emit('touchstart',touch(43));advance(450);document.emit('keydown',{key:'Escape'});assert.equal(context.planCopyDrag,null);assert.deepEqual(bridge.slice(-2),[false,true]);
context.calendarWorkMode='plan-placement';layer.emit('pointerdown',mouse(43));advance(450);assert.equal(context.planCopyDrag,null,'work-mode gating retained');
console.log('PASS Plan copy/move long touch/click, scrolling, resize exclusion, cancellation, offset, overlap and bounds');
