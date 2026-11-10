// Run with Node.js: node tests/goal_touch_drag.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/goal.js', 'utf8');
const start = source.indexOf('  let touchGoalDrag = null;');
const end = source.indexOf("  document.querySelectorAll('[data-goal-drag]').forEach(handle => {\n    handle.addEventListener('dragstart'", start);
assert.ok(start >= 0 && end > start);
function classes(){const values=new Set();return {add:v=>values.add(v),remove:v=>values.delete(v),contains:v=>values.has(v)};}
const sourceItem={classList:classes(),contains(item){return item===this || item?.owner===this;}};
const before={classList:classes()}, main={classList:classes()};
const other={dataset:{goalId:'9',parentId:'2'},querySelector(){return before;}};
const row={closest(){return other;},getBoundingClientRect(){return {top:100,height:50};},querySelector(){return main;}};
const target={closest(selector){return selector==='.goal-node-row'?row:null;}};
let point=target, moves=[];
const handle={dataset:{goalDrag:'7'},handlers:{},closest(){return sourceItem;},addEventListener(type,fn){this.handlers[type]=fn;},setPointerCapture(){}};
const doc={hidden:false,body:{classList:classes()},querySelectorAll(selector){return selector.includes('goal-drag')?[handle]:[before,main];},elementFromPoint(){return point;},addEventListener(){}};
const context={document:doc,window:{innerHeight:800,scrollBy(){}},draggedGoalId:null,moveGoal(parent,previous){moves.push([parent,previous]);}};
vm.runInNewContext(source.slice(start,end),context);
const down=()=>handle.handlers.pointerdown({pointerType:'touch',isPrimary:true,pointerId:1,preventDefault(){}});
const move=y=>handle.handlers.pointermove({pointerId:1,clientX:50,clientY:y,preventDefault(){}});
down(); move(105); assert.equal(before.classList.contains('is-drop-target'),true); handle.handlers.pointerup({pointerId:1});
assert.deepEqual(moves,[['2','9']]); assert.equal(sourceItem.classList.contains('is-dragging'),false);
down(); move(130); assert.equal(main.classList.contains('is-drop-target'),true); handle.handlers.pointerup({pointerId:1});
assert.deepEqual(moves,[['2','9'],['9','']]);
down(); move(130); handle.handlers.pointercancel({pointerId:1}); assert.equal(moves.length,2);
point={closest(selector){return selector==='.goal-node-row'?{closest(){return sourceItem;},getBoundingClientRect(){return {top:100,height:50};}}:null;}};
down(); move(130); handle.handlers.pointerup({pointerId:1}); assert.equal(moves.length,2);
console.log('PASS touch goal reorder, nesting, cancellation and self-drop');
