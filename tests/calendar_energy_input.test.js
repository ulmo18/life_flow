// Run with Node.js: node tests/calendar_energy_input.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Element {
  constructor(data = {}) { this.dataset = data; this.value = ''; this.hidden = false; this.disabled = false; this.textContent = ''; this.attributes = {}; this.listeners = {}; this.classList = { toggle() {} }; }
  setAttribute(name, value) { this.attributes[name] = value; }
  addEventListener(name, fn) { (this.listeners[name] ||= []).push(fn); }
  emit(name) { const event = { preventDefault() { this.prevented = true; }, stopImmediatePropagation() {}, prevented:false }; for (const fn of this.listeners[name] || []) fn(event); return event; }
  focus() {}
  closest() { return { open:false }; }
}
const nodes = {};
for (const key of ['type','amount','value','selected','keep','controls','mode','slider','slider-panel','direct','direct-input','sign','message','hint']) nodes[key] = new Element();
const directions = [new Element({ energyDirection:'recover' }), new Element({ energyDirection:'consume' })];
const ticks = [0, 25, 50, 75, 100].map(n => new Element({ energyTick:String(n) }));
const summary = new Element();
const root = new Element();
root.querySelector = selector => nodes[selector.match(/\[data-energy-(.+)\]/)?.[1]];
root.querySelectorAll = selector => selector === '[data-energy-direction]' ? directions : ticks;
const form = new Element();
form.querySelector = selector => selector === '[data-energy-fields]' ? root : summary;
const context = { window:{}, console };
vm.runInNewContext(fs.readFileSync('public/assets/js/components/calendar-energy-input.js', 'utf8'), context);
const control = context.window.LifeFlowCalendarEnergy.create(form);
assert.equal(nodes.type.value, 'consume'); assert.equal(nodes.value.value, '25'); assert.equal(nodes.slider.disabled, false); assert.equal(summary.textContent, '−25');
directions[1].emit('click'); directions[0].emit('click'); assert.equal(form.emit('submit').prevented, true);
ticks[2].emit('click'); assert.equal(nodes.type.value, 'recover'); assert.equal(nodes.value.value, '50');
directions[1].emit('click'); assert.equal(nodes.type.value, 'consume'); assert.equal(nodes.value.value, '50');
nodes.mode.emit('click'); nodes['direct-input'].value = '40'; nodes['direct-input'].emit('input'); assert.equal(nodes.value.value, '40');
nodes.mode.emit('click'); assert.equal(nodes.direct.hidden, true); assert.equal(nodes.value.value, '40'); assert.equal(nodes.slider.attributes['aria-valuetext'], '미선택');
nodes.mode.emit('click'); nodes['direct-input'].value = '12'; nodes['direct-input'].emit('input'); assert.equal(form.emit('submit').prevented, true);
nodes.mode.emit('click'); assert.equal(nodes.direct.hidden, true); assert.equal(nodes.value.value, '40');
control.reset(); nodes.mode.emit('click'); nodes['direct-input'].value = '24'; nodes['direct-input'].emit('input'); nodes['direct-input'].emit('blur'); nodes.mode.emit('click');
assert.equal(nodes.direct.hidden, true); assert.equal(nodes.value.value, '25'); assert.equal(nodes.type.value, 'consume'); assert.equal(nodes.message.textContent, '');
control.reset('recover', '30', false); nodes['direct-input'].value = '24'; nodes['direct-input'].emit('input'); nodes.mode.emit('click');
assert.equal(nodes.direct.hidden, true); assert.equal(nodes.value.value, '30'); assert.equal(nodes.type.value, 'recover'); assert.equal(nodes.slider.attributes['aria-valuetext'], '미선택');
nodes.mode.emit('click'); assert.equal(nodes['direct-input'].value, '30');
control.reset('recover', '125', false); assert.equal(nodes.value.value, '125'); assert.equal(form.emit('submit').prevented, false);
control.reset('', '', true); assert.equal(nodes.type.value, 'none');
control.reset(); ticks[0].emit('click'); assert.equal(nodes.type.value, 'none'); assert.equal(summary.textContent, '0');
ticks[2].emit('click'); assert.equal(nodes.type.value, 'consume'); assert.equal(nodes.value.value, '50');
process.stdout.write('PASS compact energy input state transitions\n');
