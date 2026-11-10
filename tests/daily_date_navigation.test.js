// Run with Node.js: node tests/daily_date_navigation.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/components/app-layout.js', 'utf8');
const start = source.indexOf('  const dailyNavigator =');
const end = source.indexOf("  document.querySelectorAll('[data-daily-date-swipe]')", start);
assert.ok(start >= 0 && end > start);
const block = source.slice(start, end);
function run(current, stored, session = 'session-a') {
  const data = { [`lifeflow.daily-date.${session}`]:stored };
  const links = ['/retrospect', '/routine', '/todo', '/calendar?focus=current', '/calendar?date=2020-01-01'].map(href => ({ href:`http://localhost${href}` }));
  const navigator = { dataset:{ dailyDateSession:session }, querySelectorAll() { return links; } };
  const document = { querySelector(selector) {
    if (selector === '[data-daily-date-session]') return navigator;
    if (selector === '[data-daily-context-date]') return current ? { dataset:{ dailyContextDate:current } } : null;
    if (selector === '[data-daily-date-picker], [data-calendar-date-picker]') return { dataset:{ today:'2026-10-30' } };
    return null;
  } };
  const sessionStorage = { setItem(key,value) { data[key] = value; }, getItem(key) { return data[key] || null; } };
  vm.runInNewContext(block, { document, sessionStorage, window:{ location:{ href:'http://localhost/' } }, URL });
  return { links, data };
}
let result = run('2026-10-27', '2026-10-29');
assert.equal(result.links[0].href, '/retrospect?date=2026-10-27');
assert.equal(result.links[3].href, '/calendar?date=2026-10-27');
assert.equal(result.links[4].href, 'http://localhost/calendar?date=2020-01-01');
assert.equal(result.data['lifeflow.daily-date.session-a'], '2026-10-27');
result = run(null, '2026-10-30');
assert.equal(result.links[3].href, '/calendar?focus=current&date=2026-10-30');
result = run(null, null, 'new-session');
assert.equal(result.links[0].href, 'http://localhost/retrospect');
process.stdout.write('PASS daily date navigation session and explicit date precedence\n');

// Opening a published report must not replace the daily navigation date.
{
  const data = { 'lifeflow.daily-date.session-a':'2026-10-27' };
  const input = { dataset:{today:'2026-11-06'}, value:'2026-11-06', setAttribute() {} };
  const value = { textContent:'', classList:{toggle() {}} };
  const weekday = { textContent:'' };
  const swipe = { dataset:{} };
  const today = {hidden:true};
  const arrows = [{href:'http://localhost/retrospect?view=history'}, {href:'http://localhost/retrospect?view=history'}];
  const reportLink = {href:'http://localhost/retrospect?view=history'};
  const preserved = { querySelector(selector) { return ({'[data-daily-date-picker]':input, '.daily-date-value':value, '.daily-date-weekday':weekday, '[data-daily-date-swipe]':swipe, '.daily-today-link':today})[selector] || null; }, querySelectorAll() { return arrows; } };
  const nav = {dataset:{dailyDateSession:'session-a'}, querySelectorAll() { return []; }};
  const doc = {querySelector(selector) { return selector === '[data-daily-date-session]' ? nav : selector === '[data-daily-context-preserve]' ? preserved : null; }, querySelectorAll() { return [reportLink]; }};
  const store = {getItem(key) { return data[key]; }, setItem(key,value) { data[key]=value; }};
  vm.runInNewContext(block, { document:doc, sessionStorage:store, window:{location:{href:'http://localhost/retrospect/report?date=2025-01-01'}}, URL, Date });
  assert.equal(input.value,'2026-10-27');
  assert.equal(value.textContent,'10.27');
  assert.equal(swipe.dataset.prevDate,'2026-10-26');
  assert.equal(data['lifeflow.daily-date.session-a'],'2026-10-27');
  assert.match(reportLink.href, /date=2026-10-27/);
}
