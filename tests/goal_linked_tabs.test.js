// Run with Node.js: node tests/goal_linked_tabs.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/goal.js', 'utf8');
const sheetStart = source.indexOf('  function openPanel(');
const sheetEnd = source.indexOf("  document.querySelectorAll('[data-goal-date]')", sheetStart);
assert.ok(sheetStart >= 0 && sheetEnd > sheetStart);
const layer = {hidden:true};
let focused = '';
const sheets = ['detail-1','edit-1'].map(name=>({dataset:{goalSheet:name},hidden:true,querySelector(selector){return {focus(){focused=name+':'+selector;}};}}));
const openers = sheets.map(sheet=>({dataset:{goalOpen:sheet.dataset.goalSheet},handlers:{},addEventListener(type,fn){this.handlers[type]=fn;},focus(){focused=this.dataset.goalOpen;}}));
const closer = {handlers:{},addEventListener(type,fn){this.handlers[type]=fn;}};
vm.runInNewContext(source.slice(sheetStart,sheetEnd), {
  layer, panels:sheets, cancelGoalDeleteHold(){},
  document:{body:{classList:{add(){},remove(){}}},querySelectorAll(selector){return selector.includes('goal-open')?openers:[closer];}},
});
openers[0].handlers.click(); assert.equal(sheets[0].hidden,false); assert.equal(sheets[1].hidden,true); assert.equal(focused,'detail-1:[data-goal-close]');
openers[1].handlers.click(); assert.equal(sheets[0].hidden,true); assert.equal(sheets[1].hidden,false); assert.equal(focused,'edit-1:input[name="title"]');
closer.handlers.click({preventDefault(){}}); assert.equal(layer.hidden,true); assert.ok(sheets.every(sheet=>sheet.hidden)); assert.equal(focused,'detail-1');
const dateStart = source.indexOf("  document.querySelectorAll('[data-goal-date]')");
const dateEnd = source.indexOf('  function cancelGoalDeleteHold()', dateStart);
assert.ok(dateStart >= 0 && dateEnd > dateStart);
const dateInput = {handlers:{},addEventListener(type,fn){this.handlers[type]=fn;}};
let dateOpened = false, prevented = false;
const dateWindow = {LifeFlowDatePicker:{open(input, trigger){dateOpened = input === dateInput && trigger === dateInput; return true;}}};
vm.runInNewContext(source.slice(dateStart,dateEnd), {document:{querySelectorAll(){return [dateInput];}},window:dateWindow});
dateInput.handlers.click({preventDefault(){prevented=true;}});
assert.equal(dateOpened,true); assert.equal(prevented,true);
dateWindow.LifeFlowDatePicker = {open(){return false;}}; prevented=false;
dateInput.handlers.click({preventDefault(){prevented=true;}}); assert.equal(prevented,false, 'Native date input remains a fallback.');
const start = source.indexOf("  document.querySelectorAll('[data-goal-linked-records]')");
const end = source.indexOf('  function collapsedIds()', start);
assert.ok(start >= 0 && end > start);
const names = ['plans', 'todos', 'routines'];
const tabs = names.map(name => ({dataset:{goalRecordTab:name}, attrs:{}, handlers:{},
  addEventListener(type, fn){this.handlers[type]=fn;}, setAttribute(key,value){this.attrs[key]=value;}, focus(){this.focused=true;}}));
const panels = names.map(name => ({dataset:{goalRecordPanel:name}, hidden:name !== 'plans'}));
const group = {querySelectorAll(selector){return selector.includes('tab]') ? tabs : panels;}};
vm.runInNewContext(source.slice(start,end), {document:{querySelectorAll(){return [group];}}});
tabs[1].handlers.click();
assert.equal(panels[0].hidden,true); assert.equal(panels[1].hidden,false);
assert.equal(tabs[1].attrs['aria-selected'],'true');
tabs[1].handlers.keydown({key:'ArrowRight',preventDefault(){}});
assert.equal(panels[2].hidden,false); assert.equal(tabs[2].focused,true);
tabs[2].handlers.keydown({key:'Home',preventDefault(){}});
assert.equal(panels[0].hidden,false); assert.equal(tabs[0].tabIndex,0);
const toggleStart = source.indexOf("  const toggleAll = document.querySelector('[data-goal-toggle-all]');");
const toggleEnd = source.indexOf("  document.querySelectorAll('[data-goal-child-add]')", toggleStart);
assert.ok(toggleStart >= 0 && toggleEnd > toggleStart);
const toggle = {attrs:{}, handlers:{}, setAttribute(key,value){this.attrs[key]=value;}, addEventListener(type,fn){this.handlers[type]=fn;}};
const nodes = [{collapsed:true},{collapsed:false}];
nodes.forEach(node=>{node.classList={contains(){return node.collapsed;}};});
let saved = '';
vm.runInNewContext(source.slice(toggleStart,toggleEnd), {
  document:{querySelector(){return toggle;}}, collapsibleGoalNodes:nodes,
  setCollapsed(node,value){node.collapsed=value;}, localStorage:{setItem(_key,value){saved=value;}},
  collapsedKey:'goal', collapsed:new Set(), JSON,
});
assert.equal(toggle.attrs['aria-pressed'],'false');
toggle.handlers.click(); assert.ok(nodes.every(node=>!node.collapsed)); assert.equal(toggle.attrs['aria-pressed'],'true');
toggle.handlers.click(); assert.ok(nodes.every(node=>node.collapsed)); assert.equal(toggle.attrs['aria-pressed'],'false'); assert.ok(saved);
console.log('PASS goal linked-record tabs and keyboard navigation');
