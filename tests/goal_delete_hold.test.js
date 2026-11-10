// Run with Node.js: node tests/goal_delete_hold.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/goal.js', 'utf8');
const start = source.indexOf('  function cancelGoalDeleteHold()');
const end = source.indexOf("  document.querySelectorAll('[data-goal-linked-records]')", start);
assert.ok(start >= 0 && end > start);
let now=0, next=0, submits=0;
const timers=new Map(), messages=[];
const button={dataset:{goalDelete:'delete7'},disabled:false,handlers:{},classes:new Set(),
  classList:{add(name){button.classes.add(name);},remove(name){button.classes.delete(name);}},
  addEventListener(name,fn){this.handlers[name]=fn;},setPointerCapture(){},getBoundingClientRect(){return {left:0,right:100,top:0,bottom:44};}};
const layer={hidden:false};
const ctx={layer,goalDeleteHold:null,document:{hidden:false,querySelectorAll(){return [button];},getElementById(){return {requestSubmit(){submits++;}};},addEventListener(){}},
  window:{performance:{now:()=>now},setTimeout(fn){const id=++next;timers.set(id,fn);return id;},clearTimeout(id){timers.delete(id);},LifeFlowToast:{show(msg){messages.push(msg);}}}};
vm.runInNewContext(source.slice(start,end),ctx);
const down=()=>button.handlers.pointerdown({isPrimary:true,pointerType:'touch',pointerId:1});
down(); now=200; button.handlers.pointerup({pointerId:1});
assert.equal(submits,0); assert.match(messages[0],/1\.5초/); assert.equal(button.classes.has('is-holding'),false);
timers.clear(); down(); button.handlers.pointercancel({pointerId:1}); assert.equal(timers.size,0);
down(); button.handlers.pointermove({pointerId:1,clientX:120,clientY:10}); assert.equal(timers.size,0);
button.handlers.keydown({key:'Enter',repeat:false,preventDefault(){}}); now+=1500;
const fire=[...timers.values()].at(-1); timers.clear(); fire();
assert.equal(submits,1); assert.equal(button.disabled,true);
console.log('PASS goal delayed delete short press, cancellation and keyboard hold');
