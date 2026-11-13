const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/calendar.js', 'utf8');
const begin = source.indexOf('  const actualToPlanButton =');
const end = source.indexOf('  function cancelCalendarDelayHold()', begin);
assert.ok(begin >= 0 && end > begin);

function setup() {
  let confirm = async () => false;
  let response = async () => ({ok:false, json:async()=>({ok:false,message:'시간 겹침'})});
  const button = {disabled:false, addEventListener(type, fn) { this[type] = fn; }};
  const controls = [button, {disabled:false}, {disabled:true}];
  const form = {querySelector:()=>({value:'csrf'}), querySelectorAll:()=>controls, addEventListener(type,fn) { this[type] = fn; }};
  const id = {value:'7'};
  const sheet = {hidden:false,querySelector:()=>button};
  const requests = [], redirects = [], messages = [];
  class Body { constructor(){this.fields={};} set(k,v){this.fields[k]=v;} }
  const context = {
    eventEditSheet:sheet, editEventId:id, actualEventDeleteSubmitting:false,
    document:{getElementById:()=>form}, FormData:Body, TypeError, SyntaxError,
    window:{LifeFlowUI:{confirm:args=>confirm(args)},location:{assign:url=>redirects.push(url)}},
    fetch:async(url,options)=>{requests.push({url,options});return response();},
    preserveCalendarScroll(){},cancelCalendarDelayHold(){},showCalendarToast:m=>messages.push(m),formatGridTime:i=>String(i),
  };
  vm.runInNewContext(source.slice(begin,end)+'\nglobalThis.convertAt = requestActualToPlanConversion;',context);
  return {button,controls,form,id,sheet,requests,redirects,messages,convertAt:context.convertAt,setConfirm:fn=>confirm=fn,setResponse:fn=>response=fn};
}
module.exports = (async () => {
  const s = setup();
  await s.button.click();
  assert.equal(s.requests.length,0,'Cancel sends no request');
  assert.equal(s.button.disabled,false);
  let release;
  s.setConfirm(()=>new Promise(resolve=>release=resolve));
  const pending = s.button.click();
  s.id.value = '8'; release(true); await pending;
  assert.equal(s.requests.length,0,'A different opened event is never converted');
  s.setConfirm(async args=>{
    assert.match(args.message,/삭제/,'Native fallback retains the warnings');
    assert.match(args.question,/\n계획 일정으로 전환할까요/);
    assert.ok(args.warning.includes('꼭 확인'), 'Conversion explicitly prompts users to read warnings');
    assert.equal(args.noticeItems.length,4,'Sheet notices include unsaved-edit guidance');
    return true;
  });
  await s.button.click();
  assert.equal(s.requests.length,1);
  assert.equal(s.messages[0],'시간 겹침');
  assert.equal(s.controls[1].disabled,false,'Failure restores enabled controls');
  assert.equal(s.controls[2].disabled,true,'Failure preserves pre-existing disabled state');
  const body = s.requests[0].options.body.fields;
  assert.equal(body.event_id,'8'); assert.equal(body.discard_actual,'1'); assert.equal(body._csrf_token,'csrf');
  assert.equal(Object.keys(body).length,3,'Only the saved event and explicit discard consent are sent');
  s.setResponse(async()=>{throw new TypeError('Failed to fetch');});
  await s.button.click();
  assert.match(s.messages.at(-1),/새로고침/,'Ambiguous network failure asks to verify saved result');
  let respond;
  s.setResponse(()=>new Promise(resolve=>respond=resolve));
  const saving = s.button.click();
  await new Promise(resolve => setImmediate(resolve));
  let prevented = false;
  s.form.submit({preventDefault(){prevented=true;}});
  assert.equal(prevented,true,'Normal form submit is blocked during conversion');
  const count = s.requests.length;
  await s.button.click();
  assert.equal(s.requests.length,count,'Double click is ignored');
  respond({ok:true,json:async()=>({ok:true,redirect:'/calendar?date=2026-11-11&mode=plan'})});
  await saving;
  assert.equal(s.redirects.length,1);
  assert.match(s.redirects[0],/mode=plan/);
  assert.equal(s.button.disabled,true,'Success stays guarded until navigation');
  const drag = setup();
  drag.sheet.hidden = true;
  drag.setConfirm(async args=>{
    assert.match(args.question,/90~96\n/);
    assert.equal(args.noticeItems.length,3,'Drag warnings are separate from the question');
    return false;
  });
  await drag.convertAt('12',[90,96]);
  assert.equal(drag.requests.length,0,'Drag confirmation cancel sends no request');
  drag.setConfirm(async()=>true);
  await drag.convertAt('12',[90,96]);
  const target = drag.requests[0].options.body.fields;
  assert.equal(target.event_id,'12','Drag uses source event independent of closed sheet');
  assert.equal(target.start_index,'90');assert.equal(target.end_index,'96');
})();
