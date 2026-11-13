const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const events=()=>({handlers:{},addEventListener(type,fn){(this.handlers[type]||=[]).push(fn);},emit(type,event={}){for(const fn of this.handlers[type]||[])fn(event);}});
const layer=events(),document=events(),window=events();layer.setPointerCapture=()=>{};layer.releasePointerCapture=()=>{};
let clock=0,next=0;const timers=new Map();window.setTimeout=(fn,delay)=>{timers.set(++next,{fn,at:clock+delay});return next;};window.clearTimeout=id=>timers.delete(id);
const conversions=[],moves=[],previews=[],messages=[],bridge=[],checks=[];window.LifeFlowAndroidBridge={setPullToRefreshEnabled:value=>{bridge.push(value);return true;}};
const block={dataset:{eventId:'9',eventStartIndex:'60',eventEndIndex:'66'},closest:()=>block};
const ctx={window,document,actualLayer:layer,actualMove:null,rangeSavePending:false,actualConversionPending:false,calendarWorkMode:'normal',suppressActualEventClick:false,gridIndexFromPoint:x=>x>=0&&x<200?x:null,actualGridIndexFromPoint:x=>x>=200&&x<400?x-200:null,clearActualPreview(){},clearPlanMovePreview(){},previewPlanMove:(s,e)=>previews.push(['plan',s,e]),previewActualRange:(s,e)=>previews.push(['actual',s,e]),hasActualConflict:()=>false,hasOccupiedRange:(...args)=>{checks.push(args);return !!ctx.conflict;},showCalendarToast:message=>messages.push(message),requestActualToPlanConversion:(id,range)=>conversions.push([id,...range]),submitActualRangeUpdate:(...args)=>moves.push(args)};
vm.runInNewContext(fs.readFileSync('public/assets/js/components/calendar-resize-hold.js','utf8'),ctx);
const source=fs.readFileSync('public/assets/js/pages/calendar.js','utf8');const start=source.indexOf('  function clearActualMove()'),end=source.indexOf('  function clearPlanCopyDrag()',start);assert.ok(start>=0&&end>start);vm.runInNewContext(source.slice(start,end),ctx);
function advance(ms){clock+=ms;for(const[id,timer]of timers)if(timer.at<=clock){timers.delete(id);timer.fn();}}
const target=(resize=false)=>({closest:selector=>selector==='[data-actual-block-resize]'?(resize?{}:null):block});
const event=(x,resize=false)=>({pointerId:1,pointerType:'mouse',isPrimary:true,button:0,clientX:x,clientY:0,target:target(resize),cancelable:true,preventDefault(){}});
const point=x=>({identifier:1,clientX:x,clientY:0});const touch=x=>({target:target(),touches:[point(x)],changedTouches:[point(x)],cancelable:true,preventDefault(){}});
function begin(x=260){advance(400);layer.emit('pointerdown',event(x));advance(450);}
layer.emit('pointerdown',event(260));document.emit('pointerup',event(260));advance(450);assert.equal(ctx.actualMove,null);assert.equal(ctx.suppressActualEventClick,false,'short click retains details');
layer.emit('pointerdown',event(260));document.emit('pointermove',event(270));advance(450);document.emit('pointerup',event(90));assert.equal(conversions.length,0,'immediate drag never converts');
begin(262);document.emit('pointermove',event(92));document.emit('pointerup',event(92));assert.deepEqual(conversions.pop(),['9',90,96],'duration and grab offset preserved');assert.deepEqual(previews.at(-1),['plan',90,96]);assert.equal(moves.length,0);
begin();document.emit('pointerup',event(60));assert.deepEqual(conversions.pop(),['9',60,66],'release position is checked without a final move event');
begin();document.emit('pointermove',event(80));document.emit('pointerup',event(280));assert.deepEqual(moves.pop(),['9',80,86],'release back on Actual moves instead of converting');
begin();document.emit('pointermove',event(70));document.emit('pointerup',event(500));assert.equal(conversions.length,0,'outside release cancels stale target');
for(const x of [140]){begin();document.emit('pointerup',event(x));assert.equal(conversions.length,0,'day overflow rejected');}
begin(262);document.emit('pointerup',event(1));assert.equal(conversions.length,0,'negative offset rejected');
begin();document.emit('pointermove',event(90));document.emit('pointercancel',event(90));document.emit('pointerup',event(90));assert.equal(conversions.length,0);
for(const [x,end]of [[0,6],[138,144]]){begin();document.emit('pointerup',event(x));assert.deepEqual(conversions.pop(),['9',x,end]);}
ctx.conflict=true;begin();const count=previews.length;document.emit('pointermove',event(90));assert.equal(previews.length,count,'conflicting Plan has no valid preview');document.emit('pointerup',event(90));assert.equal(conversions.length,0,'conflict prevents confirmation and request');assert.ok(messages.at(-1).includes('겹쳐'));
ctx.conflict=false;block.dataset.eventPlanBlockId='7';begin();document.emit('pointerup',event(90));assert.deepEqual(conversions.pop(),['9',90,96]);assert.deepEqual(checks.at(-1).slice(-2),['planBlockId','7'],'only the linked Plan is excluded');delete block.dataset.eventPlanBlockId;
layer.emit('touchstart',touch(260));document.emit('touchmove',touch(272));advance(450);document.emit('touchend',touch(90));assert.equal(conversions.length,0,'touch scroll cancels pending hold');
layer.emit('touchstart',touch(262));advance(450);document.emit('touchmove',touch(92));document.emit('touchend',touch(92));assert.deepEqual(conversions.pop(),['9',90,96]);assert.deepEqual(bridge,[false,true]);
layer.emit('touchstart',touch(260));advance(450);document.emit('keydown',{key:'Escape'});assert.equal(ctx.actualMove,null);assert.deepEqual(bridge.slice(-2),[false,true]);
layer.emit('pointerdown',event(260,true));advance(450);assert.equal(ctx.actualMove,null,'resize handles excluded');
ctx.actualConversionPending=true;begin();document.emit('pointerup',event(70));assert.equal(conversions.length,0);ctx.actualConversionPending=false;ctx.calendarWorkMode='tag-paint';begin();document.emit('pointerup',event(70));assert.equal(conversions.length,0);
console.log('PASS Actual drag hold, scroll/cancel, conflict rejection, linked exclusion, release point, duration and midnight bounds');
