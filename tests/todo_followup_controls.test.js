const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const fields = {
  '[data-followup-note]': {value:'',disabled:false},
  '[data-followup-save]': {disabled:false},
  '[data-followup-label]': {textContent:''},
  '[data-followup-message]': {textContent:''},
  '[data-followup-csrf]': {value:'token'},
};
const widget={dataset:{},hidden:true,querySelector(selector){return fields[selector];}};
fields['[data-followup-save]'].closest=()=>widget;
const handlers={};
let saved='담당자 확인 후 제출',posts=0,release,fail=false,unsupported=false,queued=false,reads=[];
const context={window:{},URLSearchParams,
  document:{addEventListener(type,fn){handlers[type]=fn;}},
  fetch:async(url,options={})=>{
    if(options.method==='POST'){
      posts++;assert.equal(options.body.get('_csrf_token'),'token');
      await new Promise(resolve=>{release=resolve;});
      if(!fail)saved=options.body.get('note').trim();
      return{ok:!fail,json:async()=>fail?{ok:false,message:'저장 실패'}:{ok:true,note:saved}};
    }
    if(queued)return new Promise(resolve=>reads.push(note=>resolve({ok:true,json:async()=>({ok:true,note,supported:true})})));
    return{ok:true,json:async()=>({ok:true,note:saved,supported:!unsupported,sourceId:1})};
  },
};
vm.runInNewContext(fs.readFileSync('public/assets/js/components/todo-followup.js','utf8'),context);
const api=context.window.LifeFlowTodoFollowup;
const click=()=>handlers.click({target:{closest(){return fields['[data-followup-save]'];}}});
(async()=>{
  await api.open(widget,{itemId:7,status:'postponed',editable:true});
  assert.equal(widget.hidden,false);assert.equal(fields['[data-followup-note]'].value,saved);
  assert.match(fields['[data-followup-label]'].textContent,/연기 사유/);
  fields['[data-followup-note]'].value='다음 단계 작성';api.setStatus(widget,'in_progress');
  assert.equal(fields['[data-followup-note]'].value,'다음 단계 작성','Changing state preserves the note draft');
  let request=click();await click();assert.equal(posts,1,'Pending note save cannot be duplicated');
  release();await request;assert.equal(saved,'다음 단계 작성');
  fail=true;fields['[data-followup-note]'].value='재시도할 메모';request=click();release();await request;
  assert.equal(fields['[data-followup-message]'].textContent,'저장 실패');
  assert.equal(fields['[data-followup-note]'].value,'재시도할 메모');assert.equal(fields['[data-followup-save]'].disabled,false);
  fail=false;request=click();await api.open(widget,{itemId:7,status:'postponed',editable:true});
  assert.equal(fields['[data-followup-save]'].disabled,true,'Reopen waits for an in-flight save');
  release();await request;await new Promise(setImmediate);
  assert.equal(fields['[data-followup-save]'].disabled,false);assert.equal(fields['[data-followup-note]'].value,'재시도할 메모');
  await api.open(widget,{itemId:7,status:'pending',editable:true});
  assert.equal(widget.hidden,false,'A registered task exposes its inherited context even while pending');
  api.setStatus(widget,'completed');assert.equal(widget.hidden,true);
  queued=true;const first=api.open(widget,{itemId:7,status:'in_progress',editable:true});const second=api.open(widget,{itemId:8,status:'postponed',editable:true});
  reads[0]('old');await first;reads[1]('new');await second;
  assert.equal(fields['[data-followup-note]'].value,'new','Stale source lookup cannot overwrite another task');
  queued=false;unsupported=true;await api.open(widget,{itemId:8,status:'postponed',editable:true});
  assert.equal(fields['[data-followup-save]'].disabled,true,'Missing schema is read-only');
  await api.open(widget,{itemId:8,status:'postponed',editable:false});assert.equal(widget.hidden,true,'Deleted source cannot edit context');
  console.log('PASS follow-up context, state changes, ownership-facing controls, deduplication, retry, reopen and stale reads');
})().catch(error=>{console.error(error);process.exitCode=1;});
