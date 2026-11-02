// Run with Node.js: node tests/todo_recurring_delete_hold.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/todo.js', 'utf8');
const start = source.indexOf('  function cancelRecurringHold()');
const end = source.indexOf('  recurringForm?.addEventListener', start);
assert.ok(start >= 0 && end > start);
let clock = 0, nextTimer = 0, requests = 0, fail = false, destination = '';
const timers = new Map(), messages = [];
const button = {hidden:false, disabled:false, handlers:{}, classes:new Set(),
  classList:{add(value){button.classes.add(value);}, remove(value){button.classes.delete(value);}},
  addEventListener(name, fn){this.handlers[name]=fn;}, setPointerCapture(){},
  getBoundingClientRect(){return {left:0,right:100,top:0,bottom:50};}};
const submit = {disabled:false};
const context = {
  recurringDelete:button, recurringSubmit:submit, recurringLayer:{hidden:false}, recurringId:{value:'7'},
  recurringSaving:false, recurringHold:null, ignoreRecurringClick:false,
  recurringForm:{querySelector(){return {value:'csrf'};}}, todoDeleteMessage:'1.5초',
  showTodoDeleteMessage(value){messages.push(value);}, URL,
  document:{hidden:false, addEventListener(){}},
  FormData:class {constructor(){this.values={};} set(key,value){this.values[key]=value;}},
  fetch:async (url, request) => {requests++; assert.equal(url,'/plan/block-template/delete'); assert.equal(request.body.values.block_template_id,'7'); return {ok:!fail, json:async()=>fail?{ok:false,message:'실패'}:{ok:true}};},
  window:{performance:{now:()=>clock}, setTimeout(fn){const id=++nextTimer; timers.set(id,fn); return id;}, clearTimeout(id){timers.delete(id);}, location:{href:'http://localhost/todo?date=2026-10-20',assign(value){destination=value;}}},
};
vm.runInNewContext(source.slice(start,end),context);
const down=()=>button.handlers.pointerdown({isPrimary:true,pointerType:'touch',pointerId:1});
const fire=()=>{const fn=[...timers.values()].at(-1); timers.clear(); fn();};
(async()=>{
  down(); clock=200; button.handlers.pointerup({pointerId:1});
  assert.equal(requests,0); assert.match(messages[0],/1\.5초/); assert.equal(button.classes.has('is-holding'),false);
  timers.clear(); down(); button.handlers.pointercancel({pointerId:1}); assert.equal(timers.size,0);
  fail=true; down(); clock+=1500; fire(); await new Promise(resolve=>setImmediate(resolve));
  assert.equal(requests,1); assert.equal(button.disabled,false); assert.equal(submit.disabled,false); assert.equal(messages.at(-1),'실패');
  fail=false; button.handlers.keydown({key:'Enter',repeat:false,preventDefault(){}}); clock+=1500; fire(); await new Promise(resolve=>setImmediate(resolve));
  assert.equal(requests,2); assert.equal(new URL(destination).searchParams.get('date'),'2026-10-20'); assert.equal(new URL(destination).searchParams.get('tab'),'recurring');
  console.log('PASS recurring delayed deletion short press, cancellation, failure and keyboard success');
})().catch(error=>{console.error(error); process.exitCode=1;});
