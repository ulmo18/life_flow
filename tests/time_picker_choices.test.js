const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/components/time-picker.js', 'utf8');
const start = source.indexOf('  function choices(');
const end = source.indexOf('  function sync(', start);
class HTMLInputElement {
  constructor(values = {}) { Object.assign(this, {type:'time', min:'', max:'', step:''}, values); }
}
const context = {HTMLInputElement, pad: value => String(value).padStart(2, '0')};
vm.runInNewContext(source.slice(start, end) + 'globalThis.choices = choices;', context);
const all = context.choices(new HTMLInputElement());
assert.equal(all.length, 1440);
assert.ok(all.some(option => option.value === '09:17'), 'Existing arbitrary-minute alarms remain selectable');
assert.equal(all[0].value, '00:00');
assert.equal(all.at(-1).value, '23:59');
assert.ok(!all.some(option => option.hour === 24), 'Alarm times never include 24:00');
const limited = context.choices(new HTMLInputElement({min:'09:15', max:'09:30', step:'300'}));
assert.equal(limited.map(option => option.value).join(','), '09:15,09:20,09:25,09:30');
const calendar = context.choices({options:[{disabled:false, textContent:'23:50',value:'143'}, {disabled:false,textContent:'24:00',value:'144'}, {disabled:true,textContent:'12:00',value:'72'}]});
assert.equal(calendar.length, 2);
assert.equal(calendar.at(-1).value, '144', 'Calendar keeps its index value and midnight endpoint');
console.log('PASS common time picker minute resolution, input bounds and Calendar index compatibility');
