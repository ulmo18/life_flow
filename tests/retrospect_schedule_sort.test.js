// Run with Node.js: node tests/retrospect_schedule_sort.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/retrospect.js', 'utf8');
const start = source.indexOf('  let sortController = null;');
const end = source.indexOf("  document.querySelectorAll('[data-retrospect-routine-toggle-form]')", start);
assert.ok(start >= 0 && end > start);
const callbacks = {};
const requests = [];
const locations = [];
const error = { hidden:true, textContent:'' };
const panel = { hidden:false, busy:false, querySelector() { return error; }, setAttribute() { this.busy = true; }, removeAttribute() { this.busy = false; }, replaceWith(next) { this.replaced = next; } };
const oldDetails = { replaceWith(next) { this.replaced = next; } };
const detailLayers = { schedule:oldDetails };
const newPanel = { hidden:true, querySelector() { return { focus() {} }; }, querySelectorAll() { return []; } };
const newDetails = {};
const document = { addEventListener(name, callback) { callbacks[name] = callback; }, querySelector() { return null; } };
const window = { location:{ href:'http://localhost/retrospect?date=2026-11-01', origin:'http://localhost' }, history:{ state:null, replaceState(_s,_t,url) { locations.push(url); } } };
class DOMParser { parseFromString(html) { return { querySelector() { return html === 'panel' ? newPanel : html === 'details' ? newDetails : null; } }; } }
function fetch(url) { return new Promise(resolve => requests.push({ url, resolve })); }
vm.runInNewContext(source.slice(start,end), { document, window, detailLayers, DOMParser, fetch, URL, AbortController });
function click(sort) {
  const link = { href:`http://localhost/retrospect?date=2026-11-01&edit=1&sort=${sort}#schedule`, closest() { return panel; } };
  return callbacks.click({ target:{ closest() { return link; } }, button:0, preventDefault() {} });
}
module.exports = (async () => {
  const first = click('time'); const second = click('tag');
  assert.equal(requests.length, 2);
  assert.match(requests[1].url, /edit=1/);
  requests[1].resolve({ ok:true, json:async () => ({ ok:true, panel:'panel', details:'details' }) });
  await second;
  assert.equal(panel.replaced, newPanel);
  assert.equal(oldDetails.replaced, newDetails);
  assert.equal(newPanel.hidden, false);
  assert.match(locations[0], /sort=tag/);
  requests[0].resolve({ ok:true, json:async () => ({ ok:true, panel:'panel', details:'details' }) });
  await first;
  assert.equal(locations.length, 1, 'An older response must not override the latest selection.');
  panel.replaced = null;
  const failed = click('importance');
  requests[2].resolve({ ok:false, json:async () => ({ ok:false, message:'다시 시도해 주세요.' }) });
  await failed;
  assert.equal(panel.replaced, null, 'A failed request must retain the existing list.');
  assert.equal(error.hidden, false);
  assert.equal(error.textContent, '일정 정렬에 실패했습니다. 다시 시도해 주세요.');
  process.stdout.write('PASS asynchronous sorting, request order and error handling\n');
})().catch(error => { process.exitCode = 1; throw error; });
