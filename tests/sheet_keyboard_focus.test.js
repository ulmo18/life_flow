// Run with Node.js: node tests/sheet_keyboard_focus.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
for (const name of ['memo', 'routine', 'routine-today', 'todo', 'plan-actions', 'calendar', 'ui']) {
  const file = name === 'ui' ? 'public/assets/js/components/ui.js' : `public/assets/js/pages/${name}.js`;
  const source = fs.readFileSync(file, 'utf8');
  const start = source.lastIndexOf("  document.addEventListener('keydown', event => {");
  const end = source.indexOf('\n  });', start) + '\n  });'.length;
  assert.ok(start >= 0 && end > start, name);
  let handler, focused, closed = false, shared = false, native = false;
  const controls = ['first', 'last'].map(value => ({getClientRects(){return [1];},focus(){focused=value;}}));
  const hidden = {getClientRects(){return [];},focus(){throw new Error('Hidden focus');}};
  const sheet = {hidden:false,querySelectorAll(){return [...controls,hidden];}};
  const layer = {hidden:false,querySelector(){return sheet;}};
  const document = {
    activeElement:controls[1], addEventListener(_type,fn){handler=fn;},
    querySelector(){return native ? {} : null;},getElementById(id){return id==='lifeFlowUiLayer'?{hidden:!shared}:null;},
  };
  vm.runInNewContext(source.slice(start,end), {
    document,layer,addSheet:sheet,editSheet:sheet,sheets:[sheet],activeMode:'modal',modal:sheet,sheet,
    quickLayer:{hidden:false,querySelector(){return sheet;}},
    carryoverLayer:{hidden:true},closeCarryovers(){closed=true;},statusLayer:{hidden:true},recurringLayer:{hidden:true},editLayer:{hidden:true},createLayer:{hidden:true},
    blockLayer:layer,calendarTodoCreateLayer:{hidden:true},eventForm:null,
    sourceDrag:null,actualSelection:null,actualResize:null,planCopyDrag:null,actualTouchSelection:null,planPlacement:null,
    HTMLElement:class {static [Symbol.hasInstance](value){return !!value;}},
    closeSheets(){closed=true;},closeQuickCreate(){closed=true;},closeIfOpen(){closed=true;},hideTooltip(){},
    close(){closed=true;},closePanels(){closed=true;},closeBlockSheet(){closed=true;},
  });
  function key(key,shiftKey=false,defaultPrevented=false){let prevented=false;handler({key,shiftKey,defaultPrevented,preventDefault(){prevented=true;},stopImmediatePropagation(){}});return prevented;}
  assert.equal(key('Tab'),true,name);assert.equal(focused,'first',name);
  document.activeElement=controls[0];assert.equal(key('Tab',true),true,name);assert.equal(focused,'last',name);
  if (name !== 'ui') {
    shared=true;assert.equal(key('Escape'),false);assert.equal(closed,false);
    shared=false;native=true;key('Escape');assert.equal(closed,false);
    native=false;key('Escape',false,true);assert.equal(closed,false);
  } else assert.equal(key('Escape'),true,'Shared Escape consumes the event');
  key('Escape');assert.equal(closed,true,name);
}
console.log('PASS sheet keyboard containment and nested-dialog priority');
