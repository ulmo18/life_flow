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
