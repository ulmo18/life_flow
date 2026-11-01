// Run with Node.js: node tests/retrospect_composition_help.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/retrospect.js', 'utf8');
const start = source.indexOf("  const compositionInfo =");
const end = source.indexOf('  function openModal(', start);
assert.ok(start >= 0 && end > start);
const events = {};
const button = { attributes:{}, contains(target) { return target === this; }, setAttribute(k,v) { this.attributes[k] = v; }, addEventListener(name,fn) { events.button = fn; }, focus() { this.focused = true; } };
const help = { hidden:true, contains(target) { return target === this; } };
const document = { querySelector() { return button; }, getElementById() { return help; }, addEventListener(name,fn) { events[name] = fn; } };
vm.runInNewContext(source.slice(start,end), { document });
events.button(); assert.equal(help.hidden, false); assert.equal(button.attributes['aria-expanded'], 'true');
events.click({ target:button }); assert.equal(help.hidden, false);
events.click({ target:{} }); assert.equal(help.hidden, true); assert.equal(button.attributes['aria-expanded'], 'false');
events.button(); events.keydown({ key:'Escape' }); assert.equal(help.hidden, true); assert.equal(button.focused, true);
process.stdout.write('PASS composition help toggle, outside click and Escape\n');
