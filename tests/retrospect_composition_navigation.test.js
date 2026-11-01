// Run with Node.js: node tests/retrospect_composition_navigation.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/retrospect.js', 'utf8');
const start = source.indexOf('  function selectRecordTab(');
const end = source.indexOf("  if (window.location.hash === '#schedule')", start);
assert.ok(start >= 0 && end > start);
function element(dataset) {
  const classes = new Set();
  return { dataset, hidden:false, attributes:{}, handlers:{}, classList:{ toggle(name, on) { if (on) classes.add(name); else classes.delete(name); }, remove(name) { classes.delete(name); }, contains(name) { return classes.has(name); } }, setAttribute(k,v) { this.attributes[k] = v; }, addEventListener(k,fn) { this.handlers[k] = fn; }, click() { this.handlers.click(); } };
}
const tabs = ['routine','schedule','memo'].map(x => element({ recordTab:x }));
const panels = ['routine','schedule','memo'].map(x => element({ recordPanel:x }));
const legend = ['routine','plan','actual','memo'].map(x => element({ compositionTarget:x }));
const rows = ['plan', 'plan actual', 'actual'].map(x => element({ compositionCategory:x }));
const document = { querySelectorAll(selector) { return ({ '[data-record-tab]':tabs, '[data-record-panel]':panels, '[data-composition-target]':legend, '[data-composition-category]':rows })[selector] || []; }, querySelector() { return { scrollIntoView() {} }; } };
vm.runInNewContext(source.slice(start, end), { document });
legend[1].click();
assert.equal(panels[1].hidden, false); assert.equal(rows[0].classList.contains('is-composition-match'), true); assert.equal(rows[2].classList.contains('is-composition-match'), false);
legend[2].click();
assert.equal(rows[0].classList.contains('is-composition-match'), false); assert.equal(rows[1].classList.contains('is-composition-match'), true); assert.equal(rows[2].classList.contains('is-composition-match'), true);
legend[3].click(); assert.equal(panels[2].hidden, false);
tabs[0].click(); assert.equal(panels[0].hidden, false); assert.equal(legend[3].attributes['aria-pressed'], 'false');
process.stdout.write('PASS composition legend and schedule categories\n');
