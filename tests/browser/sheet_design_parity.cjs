// Requires an authenticated, disposable local QA app with timed Plan and Actual fixtures.
const fs=require('node:fs/promises'),path=require('node:path'),assert=require('node:assert/strict');
async function main(){
 const fixture={date:process.env.LIFEFLOW_QA_DATE};
 if(!/^\d{4}-\d{2}-\d{2}$/.test(fixture.date||''))throw Error('Set LIFEFLOW_QA_DATE to a seeded Plan/Actual date.');
 const output=process.env.LIFEFLOW_QA_OUTPUT || path.join(require('node:os').tmpdir(),'lifeflow-sheet-parity');await fs.mkdir(output,{recursive:true});
 const targets=await(await fetch((process.env.LIFEFLOW_QA_CDP_URL||'http://127.0.0.1:9225')+'/json')).json();
 const ws=new WebSocket(targets.find(x=>x.type==='page').webSocketDebuggerUrl);
 await new Promise((r,j)=>{ws.onopen=r;ws.onerror=j});
 let id=0;const pending=new Map();
 ws.onmessage=e=>{let m=JSON.parse(e.data);if(pending.has(m.id)){let p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(Error(JSON.stringify(m.error))):p.resolve(m.result)}};
 function cdp(method,params={}){return new Promise((resolve,reject)=>{let n=++id;pending.set(n,{resolve,reject});ws.send(JSON.stringify({id:n,method,params}))})}
 async function ev(expression){let r=await cdp('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value}
 const pause=ms=>new Promise(r=>setTimeout(r,ms)); async function wait(expression){for(let i=0;i<200;i++){if(await ev(expression))return;await pause(100)}throw Error("Timed out: "+expression)}
 await cdp('Page.enable');
 await cdp('Page.addScriptToEvaluateOnNewDocument',{source:'window.__errors=[];window.addEventListener("error",e=>window.__errors.push(e.message));window.addEventListener("unhandledrejection",e=>window.__errors.push(String(e.reason)));window.__writes=[];const originalFetch=window.fetch;window.fetch=(url,options)=>{if(options?.method==="POST")window.__writes.push(String(url));return originalFetch(url,options)};'});
 await cdp('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
 await cdp('Emulation.setTouchEmulationEnabled',{enabled:true});
 async function nav(route){await cdp('Page.navigate',{url:(process.env.LIFEFLOW_QA_BASE_URL||'http://127.0.0.1:18081')+route});await pause(600);for(let i=0;i<100;i++){if(await ev('document.readyState')==='complete')break;await pause(100)}}


 const report={shells:[],choices:true,saveWidth:true,chevrons:true,delayButtons:true,keyboard:true};
 const shellFields=['width','paddingTop','paddingRight','paddingBottom','paddingLeft','borderTopLeftRadius','borderBottomLeftRadius','backgroundColor','boxShadow'];
 async function styles(selector,properties,pseudo){return ev('('+String(function(selector,props,pseudo){const s=getComputedStyle(document.querySelector(selector),pseudo);return Object.fromEntries(props.map(p=>[p,s[p]]));})+')('+JSON.stringify(selector)+','+JSON.stringify(properties)+','+JSON.stringify(pseudo||null)+')')}
 for(const width of [320,390,1024])for(const theme of ['light','dark']){
  await cdp('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:width<768});
  await nav('/todo?date='+fixture.date);
  await ev('document.body.dataset.theme='+JSON.stringify(theme)+';document.querySelector("[data-todo-create-open]").click()');
  const reference=await styles('[data-todo-create-layer] .ui-standard-sheet',shellFields);
  const overlay=await styles('[data-todo-create-layer] .todo-overlay',['backgroundColor','backdropFilter']);
  const referenceHold=await styles('[data-todo-delete]',['borderRadius','borderTopWidth','borderTopColor','backgroundColor','fontWeight']);
  await nav('/calendar?date='+fixture.date);
  await ev('document.body.dataset.theme='+JSON.stringify(theme)+';document.querySelector("[data-plan-block-id]").click()');
  assert.deepEqual(await styles('.calendar-local-overlay',['backgroundColor','backdropFilter']),overlay);
  for(const attr of ['data-plan-block-edit-sheet','data-plan-item-sheet','data-plan-item-edit-sheet','data-event-sheet','data-event-edit-sheet']){
   await ev('document.querySelectorAll(".calendar-sheet").forEach(s=>s.hidden=true);document.querySelector("['+attr+']").hidden=false');
   assert.deepEqual(await styles('['+attr+']',shellFields),reference,attr+' must match To-do shell');
   assert.equal(await ev('(()=>{const b=document.querySelector("['+attr+'] .calendar-actual-body");return !b||b.scrollWidth<=b.clientWidth+1})()'),true,'Structured Actual bodies must not scroll horizontally');
   const inside=await ev('(()=>{let r=document.querySelector("['+attr+']").getBoundingClientRect();return r.left>=-1&&r.right<=innerWidth+1&&r.bottom<=innerHeight+1})()');assert.equal(inside,true);
  }
  await nav('/calendar?date='+fixture.date);
  await ev('document.body.dataset.theme='+JSON.stringify(theme)+';document.querySelector("[data-plan-block-id]").click();document.querySelector("[data-plan-reminder] summary").click();document.querySelector("[data-plan-block-management] summary").click()');
  const choiceProps=['borderRadius','backgroundColor','borderTopColor','borderTopWidth','minHeight','fontSize','fontWeight'];
  assert.equal(await ev('Array.from(document.querySelectorAll(".calendar-sheet form[hidden]")).every(f=>getComputedStyle(f).display==="none"&&f.getClientRects().length===0)'),true,'Hidden forms must not create a grid row or padding');
  assert.equal(await ev('(()=>{const a=document.querySelector("[data-plan-reminder]>summary").getBoundingClientRect(),b=document.querySelector("[data-plan-block-management]>summary").getBoundingClientRect();return Math.abs(a.height-b.height)<1})()'),true,'Reminder and management rows must have matching insets');
  assert.deepEqual(await styles('#calendarPlanBlockStatus input:not(:checked)+span',choiceProps),await styles('[data-reminder-choice="5"]',choiceProps));
  assert.deepEqual(await styles('#calendarPlanBlockStatus input:checked+span',choiceProps),await styles('[data-reminder-choice=off]',choiceProps));
  assert.equal(await ev('(()=>{const b=document.querySelector("[data-plan-reminder-save]");return Math.abs(b.getBoundingClientRect().width-b.parentElement.getBoundingClientRect().width)<1})()'),true);
  const chevronProps=['content','width','height','fontSize','fontWeight','lineHeight','color'];
  const arrow=await styles('[data-plan-reminder]>summary',chevronProps,'::after');
  assert.deepEqual(await styles('[data-plan-block-management]>summary',chevronProps,'::after'),arrow);
  const holdProps=['borderRadius','borderTopWidth','borderTopColor','backgroundColor','fontWeight'];
  const delay=await styles('[data-plan-block-delete]',holdProps);
  assert.deepEqual(delay,referenceHold,'Management hold action must match To-do deletion');
  if(width===390&&theme==='light'||width===320&&theme==='dark')await cdp('Page.captureScreenshot',{format:'png'}).then(r=>fs.writeFile(path.join(output,'plan-'+width+'-'+theme+'.png'),Buffer.from(r.data,'base64')));
  await nav('/calendar?date='+fixture.date);
  await ev('document.body.dataset.theme='+JSON.stringify(theme)+';document.querySelector("[data-event-open]").click()');
  assert.deepEqual(await styles('[data-event-edit-sheet] .calendar-actual-disclosure>summary',chevronProps,'::after'),arrow);
  assert.deepEqual(await styles('[data-actual-event-delete]',['position','overflow','isolation']),await styles('[data-plan-block-delete]',['position','overflow','isolation']),'Text-style Actual deletion retains the shared hold protection');
  if(width===390&&theme==='light'||width===320&&theme==='dark')await cdp('Page.captureScreenshot',{format:'png'}).then(r=>fs.writeFile(path.join(output,'actual-'+width+'-'+theme+'.png'),Buffer.from(r.data,'base64')));
  assert.deepEqual(await ev('__errors'),[]);
  report.shells.push({width,theme,matched:5});
 }
 await cdp('Emulation.setDeviceMetricsOverride',{width:390,height:480,deviceScaleFactor:1,mobile:true});await pause(300);
 assert.equal(await ev('(()=>{const r=document.querySelector("[data-event-edit-sheet]").getBoundingClientRect(),a=document.querySelector("#calendarEventEditForm .ui-form-sheet-actions").getBoundingClientRect();return r.bottom<=innerHeight+1&&a.bottom<=innerHeight+1&&a.top>=0})()'),true);
 await fs.writeFile(path.join(output,'unified-report.json'),JSON.stringify(report,null,2));console.log(JSON.stringify(report));ws.close();
}
main().catch(error=>{console.error(error);process.exit(1)});
