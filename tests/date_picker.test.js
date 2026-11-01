// Run with Node.js: node tests/date_picker.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/components/date-picker.js', 'utf8')
  .replace('window.LifeFlowDatePicker = { open };', 'window.testDatePicker = { parse, iso, utcDate, allowed, setInput(value) { activeInput = value; } };');
const context = { document:{ documentElement:{ lang:'ko' } }, window:{}, Intl, Date };
vm.runInNewContext(source, context);
const picker = context.window.testDatePicker;
assert.equal(picker.iso(picker.parse('2024-02-29')), '2024-02-29');
assert.equal(picker.parse('2025-02-29'), null);
assert.equal(picker.iso(picker.utcDate(99, 1, 28)), '0099-02-28');
assert.equal(picker.iso(picker.utcDate(2026, 12, 1)), '2027-01-01');
picker.setInput({ min:'2026-10-10', max:'2026-10-20' });
assert.equal(picker.allowed('2026-10-09'), false);
assert.equal(picker.allowed('2026-10-10'), true);
assert.equal(picker.allowed('2026-10-20'), true);
assert.equal(picker.allowed('2026-10-21'), false);
process.stdout.write('PASS date picker date calculations and bounds\n');
