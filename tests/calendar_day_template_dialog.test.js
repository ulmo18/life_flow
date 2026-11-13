const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync('public/assets/js/pages/calendar.js','utf8');
const start=source.indexOf('  if (planPicker && planSettingsSheet instanceof HTMLDialogElement)'),end=source.indexOf('  function applyRoutineState',start);
assert.ok(start>=0&&end>start);
class Dialog {constructor(){this.handlers={};this.hidden=true;this.open=false;}addEventListener(name,fn){this.handlers[name]=fn;}showModal(){this.open=true;}close(){this.open=false;this.handlers.close?.();}querySelectorAll(){return [cancel];}}
const opener={handlers:{},addEventListener(name,fn){this.handlers[name]=fn;},focus(){this.focused=true;}},cancel={addEventListener(name,fn){this[name]=fn;}},submit={disabled:false};
const picker={handlers:{},reset(){this.resetCalled=true;},addEventListener(name,fn){this.handlers[name]=fn;},querySelector(){return submit;}};
const dialog=new Dialog(),modes=[];let closed=0;
vm.runInNewContext(source.slice(start,end),{planPicker:picker,planSettingsSheet:dialog,HTMLDialogElement:Dialog,document:{querySelectorAll:()=>[opener]},setCalendarWorkMode:(...args)=>modes.push(args),close:()=>closed++});
opener.handlers.click();assert.equal(dialog.open,true);assert.equal(dialog.hidden,false);assert.equal(picker.resetCalled,true);assert.equal(modes.length,0);
cancel.click();assert.equal(dialog.open,false);assert.equal(opener.focused,true);assert.equal(modes.length,0,'cancel preserves placement mode');
opener.handlers.click();picker.handlers.submit();assert.equal(dialog.open,false);assert.equal(modes[0][0],'normal');assert.equal(modes[0][1],true);assert.equal(closed,1);assert.equal(submit.disabled,true);
assert.equal(picker.handlers.change,undefined,'template selection never auto-submits');
const view=fs.readFileSync('app/Views/pages/calendar/index.php','utf8');
assert.ok(view.includes('href="/todo?tab=recurring"'));assert.ok(!view.includes('data-plan-settings-open'));
const markup=view.slice(view.indexOf('<dialog class="lf-date-dialog calendar-template-dialog"'));
assert.match(markup,/name="plan_group_id" required/);assert.match(markup,/type="checkbox" required/);assert.match(markup,/name="_csrf_token"/);assert.match(markup,/name="date"/);
console.log('PASS template dialog explicit selection, consent, cancellation, mode reset and POST fields');
