// Run with Node.js: node tests/calendar_range_save.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('public/assets/js/pages/calendar.js', 'utf8');
const begin = source.indexOf('  async function saveRange(');
const end = source.indexOf('  function submitPlanBlockUpdate(', begin);
assert.ok(begin >= 0 && end > begin);
const implementation = source.slice(begin, end);
class FormDataStub { constructor() { this.values = {}; } set(key, value) { this.values[key] = value; } }
let response;
let requests = 0;
let success = 0;
let message = '';
let lastRequest;
const context = {
  document:{ querySelector:() => ({ value:'token' }) },
  page:{ dataset:{ calendarDate:'2026-10-30' } },
  FormData:FormDataStub,
  TypeError,
  showCalendarToast:value => { message = value; },
  fetch:async (url, options) => { requests++; lastRequest = { url, options }; if (response instanceof Error) throw response; return response; },
};
vm.createContext(context);
vm.runInContext(`let rangeSavePending = false; ${implementation} globalThis.save = saveRange;`, context);

module.exports = (async () => {
  response = { ok:true, json:async () => ({ ok:true }) };
  await context.save('/calendar/plan-block/update', { start_index:12 }, () => { success++; });
  assert.equal(success, 1);
  assert.equal(lastRequest.options.body.values._csrf_token, 'token');
  assert.equal(lastRequest.options.body.values.start_index, '12');
  response = { ok:false, json:async () => ({ ok:false, message:'시간 충돌' }) };
  await context.save('/calendar/event/time-range', {}, () => { success++; });
  assert.equal(success, 1);
  assert.equal(message, '시간 충돌');
  let finish;
  response = { ok:true, json:() => new Promise(resolve => { finish = resolve; }) };
  const pending = context.save('/calendar/event/time-range', {}, () => { success++; });
  await Promise.resolve();
  await context.save('/calendar/event/time-range', {}, () => { success++; });
  assert.equal(requests, 3);
  finish({ ok:true });
  await pending;
  assert.equal(success, 2);
  response = new TypeError('network unavailable');
  await context.save('/calendar/event/time-range', {}, () => { success++; });
  assert.equal(success, 2);
  assert.match(message, /저장 결과를 확인하지 못했습니다/);
  process.stdout.write('PASS calendar range save success, failure and duplicate guard\n');
})();
