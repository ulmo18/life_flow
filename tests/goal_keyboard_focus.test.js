// Run with Node.js: node tests/goal_keyboard_focus.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/goal.js', 'utf8');
const start = source.lastIndexOf("  document.addEventListener('keydown', event => {");
assert.ok(start >= 0);
let handler, focused, closed = '', nativeDialog = false, sharedDialog = false;
const controls = ['first', 'last'].map(name => ({getClientRects(){return [1];},focus(){focused=name;}}));
const hidden = {getClientRects(){return [];},focus(){throw new Error('Hidden control focused');}};
const panel = {hidden:false,querySelectorAll(){return [...controls,hidden];}};
const createLayer = {hidden:true,querySelector(){return panel;}};
const document = {
  activeElement:controls[1], body:{classList:{remove(){}}},
  addEventListener(_type,fn){handler=fn;},
  querySelector(selector){return selector.includes('lf-date-dialog') && nativeDialog ? {} : null;},
  getElementById(){return {hidden:!sharedDialog};},
};
vm.runInNewContext(source.slice(start,source.lastIndexOf('\n})();')), {
  document,createLayer,panels:[panel],layer:{hidden:false},
  closePanels(){closed='panel';},closeGoalCreate(){closed='create';},
});
function key(key,shiftKey=false){let prevented=false;handler({key,shiftKey,preventDefault(){prevented=true;}});return prevented;}
assert.equal(key('Tab'),true);assert.equal(focused,'first');
document.activeElement=controls[0];assert.equal(key('Tab',true),true);assert.equal(focused,'last');
nativeDialog=true;focused=null;assert.equal(key('Tab'),false);assert.equal(focused,null);
nativeDialog=false;sharedDialog=true;assert.equal(key('Escape'),false);assert.equal(closed,'');
sharedDialog=false;key('Escape');assert.equal(closed,'panel');
createLayer.hidden=false;document.activeElement=controls[1];assert.equal(key('Tab'),true);assert.equal(focused,'first');
key('Escape');assert.equal(closed,'create');
console.log('PASS Goal keyboard containment, hidden controls, nested dialogs and Escape');
