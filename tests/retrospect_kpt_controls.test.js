// Run with Node.js: node tests/retrospect_kpt_controls.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/retrospect.js', 'utf8');
const start = source.indexOf('  if (kptSheet && kptForm) {');
const end = source.indexOf("  document.addEventListener('keydown', event => {", start);
assert.ok(start >= 0 && end > start);
function control() { return { hidden:false, textContent:'', handlers:{}, addEventListener(name, fn) { this.handlers[name] = fn; }, focus() {}, blur() {} }; }
const previous = control(), next = control(), publish = control(), collapse = control(), close = control(), opener = control();
const inputs = [control(), control(), control()];
const steps = inputs.map(input => ({ hidden:false, querySelector() { return input; } }));
const body = { hidden:false };
const progress = control();
const controls = { '[data-kpt-progress]':progress, '[data-kpt-previous]':previous, '[data-kpt-next]':next, '[data-kpt-publish]':publish, '[data-kpt-collapse]':collapse, '[data-kpt-close]':close, '.retrospect-kpt-body':body };
const kptForm = { dataset:{}, action:'/retrospect/draft', querySelector(selector) { return controls[selector] || null; }, querySelectorAll(selector) { return selector === '[data-kpt-step]' ? steps : selector === 'textarea' ? inputs : []; }, addEventListener() {} };
const classes = { add() {}, remove() {}, toggle() {} };
const kptSheet = { hidden:true, classList:classes, style:{ setProperty() {} } };
const retrospectPage = { classList:classes };
const document = { querySelector(selector) { return selector === '[data-kpt-open]' ? opener : null; }, activeElement:null };
const window = { setTimeout() { return 1; }, clearTimeout() {}, setInterval() { return 1; }, clearInterval() {}, visualViewport:null };
const fetch = async () => ({ ok:true, json:async () => ({ ok:true }) });
class FormData { constructor() {} }
vm.runInNewContext(source.slice(start,end), { document, window, fetch, FormData, kptSheet, kptForm, retrospectPage, Date });
function state(expected) { assert.deepEqual([close.hidden, previous.hidden, next.hidden, publish.hidden, collapse.textContent], expected); }
opener.handlers.click();
state([true, true, false, true, '기록 보기']);
next.handlers.click();
state([true, false, false, true, '기록 보기']);
next.handlers.click();
state([true, false, true, false, '기록 보기']);
collapse.handlers.click();
state([false, true, true, true, '돌아가기']);
collapse.handlers.click();
state([true, false, true, false, '기록 보기']);
collapse.handlers.click(); close.handlers.click();
assert.equal(kptSheet.hidden, true);
opener.handlers.click();
state([true, false, true, false, '기록 보기']);
const css = fs.readFileSync('public/assets/css/pages/retrospect.css', 'utf8');
assert.match(css, /\.retrospect-kpt-actions\s+\[hidden\]\s*\{\s*display\s*:\s*none/);
process.stdout.write('PASS KPT stage and collapsed controls\n');
