(() => {
  'use strict';
  const SLOT = 10, COLS = 6, TOTAL = 144, KEY = 'lifeflow.day-flow.v1';
  const seed = { version: 1, activities: [
    { id:'a-focus', title:'집중 업무', source:'today' }, { id:'a-exercise', title:'운동', source:'today' },
    { id:'a-reading', title:'독서', source:'frequent' }, { id:'a-review', title:'하루 회고', source:'routine' }
  ], planBlocks: [], actualEvents: [], retrospect: { date: today(), keep: '', problem: '', try: '' } };
  const $ = (selector) => document.querySelector(selector);
  const ui = { screen:'plan', source:'today', mode:'idle', placement:null, resize:null, planDrag:null, actualRange:null, editingActual:null, editingPlan:null };
  let data = load();
  const planGrid = $('#plan-grid'), actualPlanGrid = $('#actual-plan-grid'), actualGrid = $('#actual-grid');

  function today() { const date=new Date(); return `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`; }
  function load() { try { const saved = JSON.parse(localStorage.getItem(KEY)); if (saved && saved.version === 1 && Array.isArray(saved.activities) && Array.isArray(saved.planBlocks) && Array.isArray(saved.actualEvents)) { saved.retrospect ||= { date:today(), keep:'', problem:'', try:'' }; return saved; } } catch (_) {} return structuredClone(seed); }
  function save() { try { localStorage.setItem(KEY, JSON.stringify(data)); } catch (_) { setStatus('브라우저 저장소를 사용할 수 없어 현재 화면에서만 유지됩니다.'); } }
  function id(prefix) { return `${prefix}-${crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`}`; }
  function setStatus(text) { $('#status-message').textContent = text; }
  function clamp(n) { return Math.max(0, Math.min(TOTAL - 1, n)); }
  function range(start, end) { return { start:Math.min(start,end), end:Math.max(start,end) + 1 }; }
  function validRange(value) { return value && value.start >= 0 && value.end <= TOTAL && value.start < value.end; }
  function time(index) { const minutes = index * SLOT; return `${String(Math.floor(minutes / 60)).padStart(2,'0')}:${String(minutes % 60).padStart(2,'0')}`; }
  function rangeText(start,end) { return `${time(start)} ~ ${time(end)} (${(end-start)*SLOT}분)`; }
  function activityTitle(activityId) { return data.activities.find((item) => item.id === activityId)?.title || '삭제된 활동'; }
  function row(index) { return Math.floor(index / COLS); }
  function col(index) { return index % COLS; }
  function blockPieces(start, end) { const pieces=[]; for(let current=start;current<end;){const rowEnd=Math.min(end,(row(current)+1)*COLS);pieces.push({start:current,end:rowEnd,style:{gridColumn:`${col(current)+1} / ${col(rowEnd-1)+2}`,gridRow:`${row(current)+1} / ${row(current)+2}`}});current=rowEnd;} return pieces; }
  function overlaps(items,start,end,exceptId) { return items.some((item) => item.id !== exceptId && start < item.endIndex && end > item.startIndex); }
  function cellIndex(grid,event) { const rect = grid.getBoundingClientRect(), x=event.clientX-rect.left, y=event.clientY-rect.top; const usable=rect.width - Number.parseFloat(getComputedStyle(grid).paddingLeft); if (x < Number.parseFloat(getComputedStyle(grid).paddingLeft) || y < 26 || y >= 26 + 24*42) return null; const index=Math.floor((y-26)/42)*COLS + Math.floor((x-Number.parseFloat(getComputedStyle(grid).paddingLeft))/(usable/COLS)); return index >= 0 && index < TOTAL ? index : null; }
  function gridMarkup(grid, blocks, kind, preview) {
    grid.replaceChildren();
    const minutes=document.createElement('div'); minutes.className='minute-labels'; minutes.innerHTML='<span>00</span><span>10</span><span>20</span><span>30</span><span>40</span><span>50</span>'; grid.append(minutes);
    const hours=document.createElement('div'); hours.className='hours'; for(let h=0;h<24;h++){const label=document.createElement('div');label.className='hour';label.textContent=`${String(h).padStart(2,'0')}:00`;hours.append(label);} grid.append(hours);
    const cells=document.createElement('div'); cells.className='cells'; for(let i=0;i<TOTAL;i++){const cell=document.createElement('div');cell.className='cell';cell.dataset.index=i;if(preview && i>=preview.start && i<preview.end)cell.classList.add('preview');cells.append(cell);} grid.append(cells);
    const layer=document.createElement('div'); layer.className='block-layer';
    blocks.forEach((item) => {
      const pieces=blockPieces(item.startIndex,item.endIndex);
      pieces.forEach((piece,index) => {
        const button=document.createElement('button'); button.type='button'; button.className=`time-block ${kind}-block`; button.dataset.id=item.id; Object.assign(button.style,piece.style);
        button.innerHTML=index===0?`<strong>${escapeHtml(item.title || activityTitle(item.activityId))}</strong><small>${time(item.startIndex)}–${time(item.endIndex)}</small>`:'';
        if(kind !== 'readonly' && index===0) button.innerHTML += '<span class="resize-handle start" data-edge="start"></span>';
        if(kind !== 'readonly' && index===pieces.length-1) button.innerHTML += '<span class="resize-handle end" data-edge="end"></span>';
        layer.append(button);
      });
    });
    if(preview && validRange(preview)) { blockPieces(preview.start,preview.end).forEach((part,index)=>{const piece=document.createElement('div');piece.className='time-block drag-preview';Object.assign(piece.style,part.style);if(index===0)piece.textContent=rangeText(preview.start,preview.end);layer.append(piece);}); const info=document.createElement('span');info.className='selection-info'; info.style.left=`${(col(preview.start)/6)*100}%`;info.style.top=`${row(preview.start)*42}px`;info.textContent=rangeText(preview.start,preview.end);layer.append(info); }
    grid.append(layer);
  }
  function escapeHtml(value) { const el=document.createElement('span');el.textContent=value;return el.innerHTML; }
  function render() {
    const planPreview = ui.mode === 'placingPlan' || (ui.resize?.kind === 'plan') ? ui.placement || ui.resize?.draft : null;
    const actualPreview = ui.mode === 'selectingActual' ? ui.actualRange : (ui.resize?.kind === 'actual' ? ui.resize.draft : (ui.planDrag?.preview || null));
    gridMarkup(planGrid, data.planBlocks.map((b)=>({...b,title:activityTitle(b.activityId)})), 'plan', planPreview);
    gridMarkup(actualPlanGrid, data.planBlocks.map((b)=>({...b,title:activityTitle(b.activityId)})), 'readonly');
    gridMarkup(actualGrid, data.actualEvents, 'actual', actualPreview);
    const list=$('#activity-list'); list.replaceChildren(); data.activities.filter((item)=>item.source===ui.source).forEach((item)=>{const b=document.createElement('button');b.type='button';b.className='activity';b.dataset.id=item.id;b.textContent=item.title;list.append(b);});
    $('#plan-count').textContent=`${data.planBlocks.length}개`; $('#actual-count').textContent=`${data.actualEvents.length}개`;
    renderRetrospect();
  }
  function minutes(item) { return (item.endIndex-item.startIndex)*SLOT; }
  function rangeList(items) { const list=document.createElement('ul');list.className='range-list';items.forEach((item)=>{const row=document.createElement('li');row.textContent=`${time(item.startIndex)} ~ ${time(item.endIndex)} · ${minutes(item)}분`;list.append(row);});return list; }
  function renderRetrospect() {
    const validPlanIds=new Set(data.planBlocks.map((block)=>block.id));
    const linked=data.actualEvents.filter((event)=>event.planBlockId && validPlanIds.has(event.planBlockId));
    const unlinked=data.actualEvents.filter((event)=>!event.planBlockId || !validPlanIds.has(event.planBlockId));
    const summary=$('#retrospect-summary');summary.replaceChildren();
    [
      ['계획된 전체 시간',data.planBlocks.reduce((sum,item)=>sum+minutes(item),0)],
      ['기록된 실제 시간',data.actualEvents.reduce((sum,item)=>sum+minutes(item),0)],
      ['계획 연결 실제 시간',linked.reduce((sum,item)=>sum+minutes(item),0)],
      ['계획 외 실제 기록',unlinked.reduce((sum,item)=>sum+minutes(item),0)]
    ].forEach(([label,value])=>{const item=document.createElement('div');item.className='summary-item';item.innerHTML=`<span>${label}</span><strong>${value}분</strong>`;summary.append(item);});
    const comparison=$('#retrospect-comparison');comparison.replaceChildren();
    const plansByActivity=new Map();data.planBlocks.forEach((block)=>{if(!plansByActivity.has(block.activityId))plansByActivity.set(block.activityId,[]);plansByActivity.get(block.activityId).push(block);});
    if(!plansByActivity.size && !unlinked.length){const empty=document.createElement('p');empty.className='empty-note';empty.textContent='아직 비교할 계획 또는 실제 기록이 없습니다.';comparison.append(empty);}
    plansByActivity.forEach((plans,activityId)=>{
      const planIds=new Set(plans.map((plan)=>plan.id));const actuals=linked.filter((event)=>planIds.has(event.planBlockId));
      const planned=plans.reduce((sum,item)=>sum+minutes(item),0), actual=actuals.reduce((sum,item)=>sum+minutes(item),0), difference=actual-planned;
      const item=document.createElement('article');item.className='comparison-item';
      const head=document.createElement('div');head.className='comparison-head';const title=document.createElement('h3');title.textContent=activityTitle(activityId);const diff=document.createElement('span');diff.className=`difference ${difference < 0 ? 'negative' : difference > 0 ? 'positive' : ''}`;diff.textContent=`계획 대비 ${difference > 0 ? '+' : ''}${difference}분`;head.append(title,diff);item.append(head);
      const summaryText=document.createElement('p');summaryText.className='muted';summaryText.textContent=`계획 ${planned}분 · 연결된 실제 ${actual}분${actuals.length ? '' : ' · 실제 기록 없음'}`;item.append(summaryText);
      const details=document.createElement('details');const label=document.createElement('summary');label.textContent='계획·실제 시간 구간 보기';details.append(label);const planLabel=document.createElement('p');planLabel.textContent='계획';details.append(planLabel,rangeList(plans));const actualLabel=document.createElement('p');actualLabel.textContent=actuals.length?'연결된 실제 기록':'연결된 실제 기록 없음';details.append(actualLabel);if(actuals.length)details.append(rangeList(actuals));item.append(details);comparison.append(item);
    });
    if(unlinked.length){const item=document.createElement('article');item.className='comparison-item';const head=document.createElement('div');head.className='comparison-head';const title=document.createElement('h3');title.textContent='계획 없이 기록한 활동';const total=unlinked.reduce((sum,event)=>sum+minutes(event),0);const value=document.createElement('span');value.className='difference';value.textContent=`${total}분`;head.append(title,value);item.append(head);const details=document.createElement('details');const label=document.createElement('summary');label.textContent='계획 외 실제 기록 보기';details.append(label);const list=document.createElement('ul');list.className='range-list';unlinked.forEach((event)=>{const row=document.createElement('li');row.textContent=`${event.title} · ${time(event.startIndex)} ~ ${time(event.endIndex)} · ${minutes(event)}분`;list.append(row);});details.append(list);item.append(details);comparison.append(item);}
    const retrospect=data.retrospect || {date:today(),keep:'',problem:'',try:''};$('#kpt-keep').value=retrospect.keep || '';$('#kpt-problem').value=retrospect.problem || '';$('#kpt-try').value=retrospect.try || '';$('#kpt-saved-status').textContent=retrospect.date ? `${retrospect.date} 초안` : '';
  }
  function setScreen(screen) { resetInteraction(); ui.screen=screen; $('#plan-screen').classList.toggle('is-hidden',screen!=='plan'); $('#actual-screen').classList.toggle('is-hidden',screen!=='actual'); $('#retrospect-screen').classList.toggle('is-hidden',screen!=='retrospect'); document.querySelectorAll('.screen-tab').forEach((tab)=>tab.classList.toggle('is-active',tab.dataset.screen===screen)); save(); render(); }
  function resetInteraction() { ui.mode='idle'; ui.placement=null; ui.resize=null; ui.planDrag=null; ui.actualRange=null; }
  function createPlan(activityId, value) { if (!validRange(value) || overlaps(data.planBlocks,value.start,value.end)) { setStatus('다른 계획 일정과 겹칩니다.'); return false; } data.planBlocks.push({id:id('plan'),activityId,startIndex:value.start,endIndex:value.end});save();resetInteraction();setStatus('계획 일정이 저장되었습니다. 실제 기록하기에서 바로 사용할 수 있습니다.');render();return true; }
  function createActual(title,value,planBlockId=null) { if (!validRange(value) || overlaps(data.actualEvents,value.start,value.end)) { setStatus('다른 실제 일정과 겹칩니다.'); return false; } data.actualEvents.push({id:id('actual'),title,startIndex:value.start,endIndex:value.end,planBlockId});save();resetInteraction();render();return true; }
  function resize(item, items, draft) { return validRange(draft) && !overlaps(items,draft.start,draft.end,item.id); }
  function startResize(kind,item,edge,index) { ui.resize={kind,itemId:item.id,edge,original:{start:item.startIndex,end:item.endIndex},draft:{start:item.startIndex,end:item.endIndex},pointer:null}; }
  function updateResize(index) { const r=ui.resize;if(!r)return; if(r.edge==='start')r.draft.start=Math.min(index,r.original.end-1);else r.draft.end=Math.max(index+1,r.original.start+1); }
  function commitResize() { const r=ui.resize;if(!r)return; const items=r.kind==='plan'?data.planBlocks:data.actualEvents;const item=items.find((v)=>v.id===r.itemId);if(item && resize(item,items,r.draft)){item.startIndex=r.draft.start;item.endIndex=r.draft.end;save();setStatus('시간 범위가 변경되었습니다.');}else setStatus('겹치는 일정 때문에 범위를 변경하지 못했습니다.');resetInteraction();render(); }
  function openActual(event) { ui.editingActual=event?.id || null; const dialog=$('#actual-dialog'), editing=event || {title:'',startIndex:ui.actualRange.start,endIndex:ui.actualRange.end,planBlockId:''}; $('#dialog-title').textContent=event?'실제 일정 수정':'실제 일정 기록'; $('#dialog-range').textContent=rangeText(editing.startIndex,editing.endIndex); $('#actual-title').value=editing.title; const select=$('#plan-link');select.replaceChildren(new Option('연결 안 함',''));data.planBlocks.forEach((p)=>select.append(new Option(`${activityTitle(p.activityId)} · ${rangeText(p.startIndex,p.endIndex)}`,p.id)));select.value=editing.planBlockId || '';$('#delete-actual').hidden=!event;dialog.showModal(); }
  function openPlan(block) { ui.editingPlan=block.id;$('#plan-dialog-range').textContent=rangeText(block.startIndex,block.endIndex);const select=$('#plan-activity');select.replaceChildren();data.activities.forEach((activity)=>select.append(new Option(activity.title,activity.id)));select.value=block.activityId;$('#plan-dialog').showModal(); }
  function attachPlanEvents() {
    $('#activity-list').addEventListener('pointerdown',(e)=>{const source=e.target.closest('.activity');if(!source)return;ui.mode='dragActivity';ui.planDrag={activityId:source.dataset.id,pointer:e.pointerId};setStatus('계획 시간표의 시작 칸에서 놓으세요.');});
    planGrid.addEventListener('pointermove',(e)=>{if(ui.mode==='placingPlan'){const idx=cellIndex(planGrid,e);if(idx!==null){ui.placement=range(ui.placement.start,idx);render();}}if(ui.resize?.kind==='plan'){const idx=cellIndex(planGrid,e);if(idx!==null){updateResize(idx);render();}}});
    planGrid.addEventListener('pointerup',(e)=>{const idx=cellIndex(planGrid,e);if(ui.mode==='dragActivity'){if(idx!==null){ui.mode='placingPlan';ui.placement={start:idx,end:idx+1};setStatus('마우스를 움직여 길이를 정한 뒤 다시 클릭해 저장하세요.');render();}return;}if(ui.mode==='placingPlan'){if(idx!==null)createPlan(ui.planDrag.activityId,ui.placement);return;}if(ui.resize?.kind==='plan'){commitResize();}});
    planGrid.addEventListener('pointerdown',(e)=>{const handle=e.target.closest('.resize-handle');const block=e.target.closest('.plan-block');if(handle&&block){e.stopPropagation();const item=data.planBlocks.find((b)=>b.id===block.dataset.id);startResize('plan',item,handle.dataset.edge);planGrid.setPointerCapture(e.pointerId);return;}if(block&&ui.mode==='idle'){openPlan(data.planBlocks.find((b)=>b.id===block.dataset.id));}});
    planGrid.addEventListener('pointercancel',()=>{if(ui.resize||ui.mode==='placingPlan'){resetInteraction();render();setStatus('계획 입력을 취소했습니다.');}});
  }
  function attachActualEvents() {
    actualPlanGrid.addEventListener('pointerdown',(e)=>{const block=e.target.closest('.time-block');if(!block)return;const plan=data.planBlocks.find((b)=>b.id===block.dataset.id);if(!plan)return;ui.planDrag={planId:plan.id,pointer:e.pointerId,preview:null};actualPlanGrid.setPointerCapture(e.pointerId);setStatus('실제 시간표의 원하는 시작 시간으로 끌어 놓으세요.');});
    actualPlanGrid.addEventListener('pointermove',(e)=>{if(!ui.planDrag)return;const idx=cellIndex(actualGrid,e);const plan=data.planBlocks.find((b)=>b.id===ui.planDrag.planId);if(idx!==null&&plan){ui.planDrag.preview={start:idx,end:Math.min(TOTAL,idx+plan.endIndex-plan.startIndex)};render();}});
    actualPlanGrid.addEventListener('pointerup',(e)=>{if(!ui.planDrag)return;const idx=cellIndex(actualGrid,e);const plan=data.planBlocks.find((b)=>b.id===ui.planDrag.planId);if(idx!==null&&plan){const v={start:idx,end:Math.min(TOTAL,idx+plan.endIndex-plan.startIndex)};createActual(activityTitle(plan.activityId),v,plan.id);}else{resetInteraction();render();}});
    actualGrid.addEventListener('pointerdown',(e)=>{const handle=e.target.closest('.resize-handle');const block=e.target.closest('.actual-block');if(handle&&block){e.stopPropagation();const item=data.actualEvents.find((b)=>b.id===block.dataset.id);startResize('actual',item,handle.dataset.edge);actualGrid.setPointerCapture(e.pointerId);return;}if(block){openActual(data.actualEvents.find((b)=>b.id===block.dataset.id));return;}const idx=cellIndex(actualGrid,e);if(idx===null)return;ui.mode='selectingActual';ui.actualRange={start:idx,end:idx+1};actualGrid.setPointerCapture(e.pointerId);render();});
    actualGrid.addEventListener('pointermove',(e)=>{if(ui.mode==='selectingActual'){const idx=cellIndex(actualGrid,e);if(idx!==null){ui.actualRange=range(ui.actualRange.start,idx);render();}}if(ui.resize?.kind==='actual'){const idx=cellIndex(actualGrid,e);if(idx!==null){updateResize(idx);render();}}});
    actualGrid.addEventListener('pointerup',(e)=>{if(ui.mode==='selectingActual'){const idx=cellIndex(actualGrid,e);if(idx!==null){ui.actualRange=range(ui.actualRange.start,idx);render();openActual();}else{resetInteraction();render();}return;}if(ui.resize?.kind==='actual')commitResize();});
    actualGrid.addEventListener('pointercancel',()=>{if(ui.mode==='selectingActual'||ui.resize){resetInteraction();render();setStatus('실제 일정 입력을 취소했습니다.');}});
  }
  document.querySelectorAll('.screen-tab').forEach((tab)=>tab.addEventListener('click',()=>setScreen(tab.dataset.screen)));
  document.querySelectorAll('.source-tab').forEach((tab)=>tab.addEventListener('click',()=>{ui.source=tab.dataset.source;document.querySelectorAll('.source-tab').forEach((t)=>t.classList.toggle('is-active',t===tab));render();}));
  $('#activity-form').addEventListener('submit',(e)=>{e.preventDefault();const input=$('#activity-title');const title=input.value.trim();if(!title)return;data.activities.push({id:id('activity'),title,source:ui.source});save();input.value='';render();});
  $('#actual-form').addEventListener('submit',(e)=>{e.preventDefault();const title=$('#actual-title').value.trim();if(!title)return;const link=$('#plan-link').value||null;if(ui.editingActual){const item=data.actualEvents.find((v)=>v.id===ui.editingActual);item.title=title;item.planBlockId=link;save();}else if(!createActual(title,ui.actualRange,link))return;$('#actual-dialog').close();resetInteraction();render();});
  $('#delete-actual').addEventListener('click',()=>{data.actualEvents=data.actualEvents.filter((v)=>v.id!==ui.editingActual);save();$('#actual-dialog').close();render();});
  $('#delete-plan').addEventListener('click',()=>{data.planBlocks=data.planBlocks.filter((v)=>v.id!==ui.editingPlan);data.actualEvents.forEach((v)=>{if(v.planBlockId===ui.editingPlan)v.planBlockId=null;});save();$('#plan-dialog').close();render();});
  $('#plan-form').addEventListener('submit',(e)=>{e.preventDefault();const block=data.planBlocks.find((item)=>item.id===ui.editingPlan);if(block){block.activityId=$('#plan-activity').value;save();}$('#plan-dialog').close();render();});
  $('#kpt-form').addEventListener('submit',(e)=>{e.preventDefault();data.retrospect={date:today(),keep:$('#kpt-keep').value,problem:$('#kpt-problem').value,try:$('#kpt-try').value};save();$('#kpt-saved-status').textContent=`${data.retrospect.date} 저장됨`;setStatus('KPT 회고를 저장했습니다.');});
  $('#actual-dialog').addEventListener('close',()=>{if($('#actual-dialog').returnValue==='cancel'){resetInteraction();render();}});
  $('#reset-button').addEventListener('click',()=>{if(confirm('오늘의 계획과 실제 기록을 모두 초기화할까요?')){data=structuredClone(seed);save();resetInteraction();render();setStatus('오늘 데이터를 초기화했습니다.');}});
  attachPlanEvents(); attachActualEvents(); render();
})();
