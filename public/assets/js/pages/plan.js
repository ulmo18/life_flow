(function () {
  const grid = document.getElementById('planDaygrid'), layer = document.getElementById('planEditorLayer');
  const form = document.getElementById('planEditorForm'), input = document.getElementById('planBlocksInput');
  const dialog = document.getElementById('planScheduleDialog'), editor = document.getElementById('planScheduleForm');
  if (!grid || !layer || !form || !input || !dialog || !window.LifeFlowTimeGrid) return;
  const $ = id => document.getElementById(id), ui = window.LifeFlowUI;
  const parse = value => { try { return JSON.parse(value || '[]'); } catch { return []; } };
  let blocks = parse(input.value), editing = null, selectedRange = null, tab = 'manual', gesture = null, suppressClick = 0, allowSubmit = false;
  const templates = parse($('planBlockTemplatesJson')?.textContent);
  const error = dialog.querySelector('[data-plan-schedule-error]');
  function valid(block, except = null) {
    return Number.isInteger(block.start_index) && Number.isInteger(block.end_index) && block.start_index >= 0 && block.end_index <= 144 && block.start_index < block.end_index && !blocks.some(other => other !== except && block.start_index < other.end_index && block.end_index > other.start_index);
  }
  function sync() { blocks.sort((a,b) => a.start_index-b.start_index); input.value = JSON.stringify(blocks); }
  function setTab(value) {
    tab = value;
    dialog.querySelectorAll('[data-plan-editor-tab]').forEach(button => { const active = button.dataset.planEditorTab === value; button.setAttribute('aria-selected', String(active)); button.tabIndex = active ? 0 : -1; });
    dialog.querySelectorAll('[data-plan-editor-panel]').forEach(panel => panel.hidden = panel.dataset.planEditorPanel !== value);
  }
  dialog.querySelectorAll('[data-plan-editor-tab]').forEach(button => {
    button.addEventListener('click', () => setTab(button.dataset.planEditorTab));
    button.addEventListener('keydown', event => { if (['ArrowLeft','ArrowRight'].includes(event.key)) { event.preventDefault(); setTab(tab === 'manual' ? 'template' : 'manual'); dialog.querySelector('[data-plan-editor-tab="'+tab+'"]').focus(); } });
  });
  function open(block, existing = null) {
    editing = existing; selectedRange = {start_index: block.start_index, end_index: block.end_index}; editor.reset(); error.hidden = true; setTab('manual');
    dialog.querySelector('[data-plan-schedule-tabs]').hidden = !!existing;
    $('planScheduleTitle').textContent = existing ? '템플릿 일정 수정' : '템플릿 일정 등록';
    $('planScheduleName').value = block.title || '';
    $('planScheduleGoal').value = block.goal_id || '';
    editor.querySelector('[name="importance"][value="'+(['A','B','C','D'].includes(block.importance) ? block.importance : 'D')+'"]').checked = true;
    dialog.querySelector('[data-plan-schedule-delete]').hidden = !existing;
    dialog.querySelector('[data-plan-schedule-save]').textContent = existing ? '수정 저장' : '일정 등록';
    dialog.showModal();
  }
  $('planScheduleTemplate').addEventListener('change', event => {
    const template = templates.find(item => Number(item.id) === Number(event.target.value));
    if (!template) return;
    $('planScheduleName').value = template.title;
    $('planScheduleGoal').value = template.goalId || template.goal_id || '';
    const radio = editor.querySelector('[name="importance"][value="'+template.importance+'"]'); if (radio) radio.checked = true;
  });
  dialog.querySelector('[data-plan-schedule-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog && (event.clientX < dialog.getBoundingClientRect().left || event.clientX > dialog.getBoundingClientRect().right || event.clientY < dialog.getBoundingClientRect().top || event.clientY > dialog.getBoundingClientRect().bottom)) dialog.close(); });
  editor.addEventListener('submit', event => {
    event.preventDefault();
    const block = { title: $('planScheduleName').value.trim(), importance: editor.querySelector('[name="importance"]:checked').value, goal_id: Number($('planScheduleGoal').value) || null, ...selectedRange };
    if (!block.title || (tab === 'template' && !editing && !$('planScheduleTemplate').value) || !valid(block, editing)) {
      error.textContent = '일정명과 반복 할 일 선택, 시간 범위를 확인해 주세요. 다른 일정과 겹칠 수 없어요.'; error.hidden = false; return;
    }
    if (editing) Object.assign(editing, block); else blocks.push(block);
    dialog.close(); render();
  });
  dialog.querySelector('[data-plan-schedule-delete]').addEventListener('click', () => {
    if (editing && window.confirm('이 템플릿 일정을 삭제할까요?')) { blocks = blocks.filter(block => block !== editing); dialog.close(); render(); }
  });
  function segments(block, callback) {
    for (let i=block.start_index; i<block.end_index;) { const span=Math.min(6-i%6, block.end_index-i); callback(Math.floor(i/6),i%6,span,i===block.start_index,i+span===block.end_index); i+=span; }
  }
  function render() {
    sync(); layer.replaceChildren();
    blocks.forEach((block,index) => segments(block,(row,col,span,first,last) => {
      const node=document.createElement('div'); node.className='event plan-event plan-editor-event importance-'+String(block.importance || 'D').toLowerCase(); node.dataset.blockIndex=index;
      node.style.setProperty('--row',row); node.style.setProperty('--col',col); node.style.setProperty('--span',span);
      const button=document.createElement('button'); button.type='button'; button.className='plan-event-body'; const badge=document.createElement('span'); badge.className='calendar-importance-badge is-neutral'; badge.textContent=block.importance || 'D'; const title=document.createElement('span'); title.className='event-title'; title.textContent=block.title; button.append(badge,title); button.setAttribute('aria-label',block.title+' 수정');
      button.addEventListener('click',()=> { if (Date.now()>suppressClick) open(block,block); });
      button.addEventListener('pointerdown',event=>begin(event,block,'move')); node.append(button);
      [['start',first],['end',last]].forEach(([edge,show])=> { if (!show) return; const handle=document.createElement('button'); handle.type='button'; handle.className='plan-block-resize-handle is-'+edge+' plan-resize-handle plan-resize-'+edge; handle.setAttribute('aria-label',block.title+' '+(edge==='start'?'시작':'종료')+' 시간 조정');  handle.addEventListener('pointerdown',event=>begin(event,block,edge)); handle.addEventListener('keydown',event=> { if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return; event.preventDefault(); const delta=({ArrowLeft:-1,ArrowRight:1,ArrowUp:-6,ArrowDown:6})[event.key]; const next={...block,[edge+'_index']:block[edge+'_index']+delta}; if(valid(next,block)){Object.assign(block,next);render();}else ui?.show?.('다른 일정과 겹치거나 하루 범위를 벗어났어요.'); }); node.append(handle); });
      layer.append(node);
    }));
  }
  function cellAt(x,y) { return document.elementsFromPoint(x,y).find(node=>node.matches?.('.cell[data-index]') && grid.contains(node)); }
  function cancelGesture() {
    if (!gesture) return; clearTimeout(gesture.timer);
    if (gesture.pullRefreshSuspended) window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(true);
    if (gesture.active) { suppressClick=Date.now()+500; try {grid.releasePointerCapture(gesture.id);} catch {} }
    gesture=null; grid.classList.remove('is-plan-dragging'); layer.querySelectorAll('.plan-drag-preview').forEach(node=>node.remove());
  }
  function begin(event,block,edge) {
    if (event.button!==0 || gesture) return;
    const cell=cellAt(event.clientX,event.clientY); if(!cell) return;
    const pending={id:event.pointerId,x:event.clientX,y:event.clientY,block,edge,anchor:Number(cell.dataset.index),active:false,next:null}; gesture=pending;
    pending.timer=setTimeout(()=> { if(gesture!==pending)return; pending.active=true; pending.pullRefreshSuspended=event.pointerType==='touch' && window.LifeFlowAndroidBridge?.setPullToRefreshEnabled(false); suppressClick=Date.now()+500; grid.classList.add('is-plan-dragging'); try { grid.setPointerCapture(pending.id); } catch { /* Scrolling may have released the pointer. */ } },450);
  }
  window.addEventListener('pointermove',event=> {
    if(!gesture || gesture.id!==event.pointerId)return;
    if(!gesture.active){if(Math.hypot(event.clientX-gesture.x,event.clientY-gesture.y)>8)cancelGesture();return;}
    event.preventDefault(); const cell=cellAt(event.clientX,event.clientY); gesture.next=null;
    layer.querySelectorAll('.plan-drag-preview').forEach(node=>node.remove()); if(!cell)return;
    const target=Number(cell.dataset.index), next={...gesture.block};
    if(gesture.edge==='move'){next.start_index+=target-gesture.anchor;next.end_index+=target-gesture.anchor;} else next[gesture.edge+'_index']=target+(gesture.edge==='end'?1:0);
    if(!valid(next,gesture.block))return; gesture.next=next;
    segments(next,(row,col,span)=> {const preview=document.createElement('div');preview.className='event plan-drag-preview';preview.style.setProperty('--row',row);preview.style.setProperty('--col',col);preview.style.setProperty('--span',span);layer.append(preview);});
  },{passive:false});
  window.addEventListener('pointerup',event=> {if(!gesture || gesture.id!==event.pointerId)return; const current=gesture; cancelGesture(); if(current.active && current.next){Object.assign(current.block,current.next);render();}});
  window.addEventListener('pointercancel',cancelGesture); window.addEventListener('scroll',cancelGesture,true); window.addEventListener('pagehide',cancelGesture);
  document.addEventListener('visibilitychange',()=> {if(document.hidden)cancelGesture();});
  grid.addEventListener('touchmove',event=> {if(gesture?.active)event.preventDefault();},{passive:false});
  grid.addEventListener('contextmenu',event=> {if(gesture)event.preventDefault();});
  window.LifeFlowTimeGrid.create({grid,requireLongPress:true,longPressDelay:450,ignoreSelector:'[data-block-index], button, input, select, textarea, a',onSelect:({start,end})=>open({start_index:start,end_index:end})});
  form.addEventListener('submit',async event=> {
    if(allowSubmit)return; event.preventDefault();sync(); if(!blocks.length){ui?.show?.('일정을 1개 이상 등록해 주세요.');return;}
    const message='하루 템플릿을 저장할까요? 이미 일정에 적용한 날짜별 계획은 바뀌지 않아요.';
    const confirmed=ui?.confirm ? await ui.confirm({title:'하루 템플릿 저장',message,confirmText:'저장',cancelText:'취소'}) : window.confirm(message);
    if(confirmed){allowSubmit=true;form.requestSubmit();}
  });
  render();
})();
