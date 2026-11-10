// Run with Node.js: node tests/plan_reminder_controls.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function widget() {
  const styles=new Set();
  const fields={
    '[data-reminder-enabled]':{checked:false,disabled:false},
    '[data-reminder-minutes]':{value:'5',disabled:true},
    '[data-plan-reminder-save]':{disabled:false},
    '[data-reminder-csrf]':{value:'csrf'},
    '[data-reminder-options]':{}, '[data-reminder-gates]':{}, '[data-reminder-summary]':{},
    '[data-reminder-clock]':{},
    '[data-reminder-message]':{textContent:'',classList:{add(v){styles.add(v);},remove(v){styles.delete(v);}}},
  };
  const choices=['off','0','5','15'].map(value=>({dataset:{reminderChoice:value},setAttribute(name,value){this[name]=value;}}));
  const container={dataset:{blockId:'7',supported:'1',masterEnabled:'1',calendarEnabled:'1'},querySelector(s){return fields[s];},querySelectorAll(){return choices;}};
  for(const selector of ['[data-reminder-summary]','[data-reminder-clock]']) fields[selector].append=function(node){this.textContent+=node.textContent;};
  choices.forEach(choice=>{choice.closest=()=>container;});
  fields['[data-plan-reminder-save]'].closest=()=>container;
  return {container,fields,styles,choices};
}
const current=widget(),copy=widget(),segment={dataset:{planBlockId:'7'}};
const handlers={},sync=[];
let requests=0,release,mode='success',lastBody;
const context={
  URLSearchParams,Set,Number,String,Boolean,Error,
  document:{createElement(){return{textContent:''};},addEventListener(type,fn){handlers[type]=fn;},querySelectorAll(s){
    if(s==='[data-plan-reminder]')return[current.container];
    if(s==='template[data-todo-record-template]')return[{content:{querySelectorAll(){return[copy.container];}}}];
    return[segment];
  }},
  window:{LifeFlowAndroidBridge:{syncNotifications(p){sync.push(p);}}},
  fetch:async(_url,options)=>{
    requests++;lastBody=options.body;
    await new Promise(resolve=>{release=resolve;});
    return {ok:mode!=='failure',async json(){
      if(mode==='malformed')throw new Error('Unexpected server HTML');
      if(mode==='failure')return{ok:false,message:'저장 실패'};
      return{ok:true,reminder:{blockId:7,enabled:lastBody.get('reminder_enabled')==='1',minutesBefore:Number(lastBody.get('reminder_minutes_before')),supported:true},notificationSyncPayload:{scope:'calendar_plan',specific:[]}};
    }};
  },
};
vm.runInNewContext(fs.readFileSync('public/assets/js/components/plan-reminder.js','utf8'),context);
const api=context.window.LifeFlowPlanReminder;
const click=()=>handlers.click({target:{closest(selector){return selector==='[data-reminder-choice]' ? null : current.fields['[data-plan-reminder-save]'];}}});
(async()=>{
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:0,supported:true,timing:{startLabel:'01/02 00:10',choices:{0:'01/02 00:10',60:'01/01 23:10'}}});
  assert.equal(current.fields['[data-reminder-clock]'].textContent,'계획 시작 01/02 00:10 · 알림 01/02 00:10');
  current.container.dataset.clockDisplay='time';
  const timing={startLabel:'01/02 00:10',choices:{0:'01/02 00:10',60:'01/01 23:10'}};
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:0,supported:true,timing});
  assert.equal(current.fields['[data-reminder-clock]'].textContent,'알림 00:10');
  assert.equal(current.fields['[data-reminder-summary]'].textContent,'시작할 때 · 00:10');
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:60,supported:true,timing});
  assert.equal(current.fields['[data-reminder-clock]'].textContent,'알림 전날 23:10','Midnight keeps a relative-day cue without a calendar date');
  api.set(current.container,{blockId:7,enabled:false,minutesBefore:60,supported:true,timing});
  assert.equal(current.fields['[data-reminder-clock]'].textContent,'','Disabled reminders do not emphasize an alarm time');
  delete current.container.dataset.clockDisplay;
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:0,supported:true,timing});
  current.container.dataset.masterEnabled='0';
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:0,supported:true});
  assert.equal(current.fields['[data-reminder-gates]'].hidden,false);
  assert.equal(current.fields['[data-reminder-options]'].hidden,true);
  await click();assert.equal(requests,0,'Global settings gate saving and show the Settings route');
  current.container.dataset.masterEnabled='1';
  current.container.dataset.calendarEnabled='0';
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:0,supported:true});
  assert.equal(current.fields['[data-plan-reminder-save]'].disabled,true);
  current.container.dataset.calendarEnabled='1';
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:5,supported:true});
  await handlers.click({target:{closest(){return current.choices[1];}}});
  assert.equal(current.choices[1]['aria-pressed'],'true','Start-time choice sets zero minutes');
  assert.equal(current.fields['[data-reminder-minutes]'].value,'0');
  let first=click();await click();assert.equal(requests,1,'Pending save cannot be duplicated');
  assert.equal(lastBody.get('_csrf_token'),'csrf');assert.equal(lastBody.get('daily_plan_block_id'),'7');
  release();await first;
  assert.equal(copy.fields['[data-reminder-minutes]'].value,'0','Reopened template uses saved time');
  assert.equal(copy.fields['[data-reminder-enabled]'].checked,true);
  assert.equal(segment.dataset.planReminderMinutes,'0');assert.equal(sync.length,1);
  current.fields['[data-reminder-enabled]'].checked=false;
  handlers.change({target:{matches(){return true;},closest(){return current.container;}}});
  assert.equal(current.fields['[data-reminder-minutes]'].disabled,true);
  first=click();release();await first;
  assert.equal(copy.fields['[data-reminder-enabled]'].checked,false,'Disable persists without affecting another block');
  mode='failure';first=click();release();await first;
  assert.equal(current.fields['[data-reminder-message]'].textContent,'저장 실패');
  assert.equal(current.fields['[data-plan-reminder-save]'].disabled,false,'Failure can be retried');
  mode='malformed';first=click();release();await first;
  assert.equal(current.fields['[data-reminder-message]'].textContent,'저장 응답을 확인하지 못했습니다. 다시 시도해 주세요.');
  api.set(current.container,{blockId:7,enabled:false,minutesBefore:5,supported:false});
  assert.equal(current.fields['[data-plan-reminder-save]'].disabled,true,'Unsupported schema is read-only');
  let finishClock;
  context.fetch=async()=>new Promise(resolve=>{finishClock=resolve;});
  api.set(current.container,{blockId:7,enabled:true,minutesBefore:5,supported:true,timing:null});
  const oldClock=api.load(current.container);
  api.set(current.container,{blockId:8,enabled:true,minutesBefore:5,supported:true,timing:{startLabel:'01/02 11:00',choices:{5:'01/02 10:55'}}});
  finishClock({ok:true,json:async()=>({ok:true,reminder:{timing:{startLabel:'old',choices:{5:'old'}}}})});
  await oldClock;
  assert.match(current.fields['[data-reminder-clock]'].textContent,/10:55/,'A stale lookup cannot overwrite a newly opened block');
  console.log('PASS block reminder save, independent fields, zero lead, template refresh, sync, deduplication and retry');
})().catch(error=>{console.error(error);process.exitCode=1;});
