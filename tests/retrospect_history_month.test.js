// Run with Node.js: node tests/retrospect_history_month.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/components/month-picker.js', 'utf8');
const start = source.indexOf("  const historyMonthInput =");
const end = source.lastIndexOf("})();");
assert.ok(start >= 0 && end > start);
const element = () => ({handlers:{}, hidden:false, disabled:false, textContent:'', attrs:{}, classList:{add(){}},
  addEventListener(type, fn){this.handlers[type]=fn;}, setAttribute(key,value){this.attrs[key]=value;}, focus(){},
});
let submits = 0;
const input = element(); input.value='2026-11'; input.form={requestSubmit(){submits++;}};
input.dispatchEvent = event => input.handlers[event.type](event);
const open = element(), close = element(), prev = element(), next = element(), year = element(), grid = element();
grid.buttons=[]; grid.replaceChildren=function(){this.buttons=[];}; grid.append=function(button){this.buttons.push(button);}; grid.querySelector=function(){return this.buttons[0];};
const dialog=element(); dialog.showModal=()=>{}; dialog.close=()=>{};
dialog.querySelector = selector => ({'[data-history-year]':year,'[data-history-month-grid]':grid,'[data-history-year-prev]':prev,'[data-history-year-next]':next,'[data-history-month-close]':close})[selector];
const document={getElementById(id){return id==='historyMonth'?input:dialog;}, querySelector(){return open;}, createElement(){return element();}};
class Event {constructor(type){this.type=type;}}
vm.runInNewContext(source.slice(start,end), {document, Event});
assert.equal(input.hidden,true); assert.equal(open.hidden,false);
open.handlers.click(); assert.equal(grid.buttons.length,12);
grid.buttons[1].handlers.click();
assert.equal(input.value,'2026-02'); assert.equal(submits,1);
input.value='2025-03'; input.handlers.change(); assert.equal(submits,2);
input.value=''; input.handlers.change(); assert.equal(submits,2);
console.log('PASS month dialog and native month changes submit immediately');
