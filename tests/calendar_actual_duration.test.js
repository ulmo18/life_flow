const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync('public/assets/js/pages/calendar.js','utf8');
const begin=source.indexOf('  function syncActualDuration(form) {');
const end=source.indexOf('  function syncActualFormSummary(form)',begin);
const context={};vm.createContext(context);vm.runInContext(source.slice(begin,end)+'globalThis.refresh=syncActualDuration;',context);
const start={value:'73'},finish={value:'77'},output={textContent:''};
const form={querySelector(selector){return selector==='[data-actual-duration]'?output:selector.includes('start_index')?start:finish;}};
context.refresh(form);assert.equal(output.textContent,'40분');
start.value='143';finish.value='144';context.refresh(form);assert.equal(output.textContent,'10분','24:00 is a valid end boundary');
start.value='0';finish.value='144';context.refresh(form);assert.equal(output.textContent,'1440분');
for(const range of [['10','10'],['12','6'],['','6'],['0','145']]){[start.value,finish.value]=range;context.refresh(form);assert.equal(output.textContent,'시간 확인','Invalid intervals never produce a misleading duration');}
context.refresh({querySelector(){return null;}});
console.log('PASS Actual duration updates, end-of-day boundaries, invalid intervals and absent output');
