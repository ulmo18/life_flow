// Run with Node.js: node tests/todo_sort.test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/js/pages/todo.js', 'utf8');
const start = source.indexOf("  const todoSortList = page.querySelector('[data-todo-sort-list]');");
const end = source.indexOf("  const statusLayer =", start);
assert.ok(start >= 0 && end > start);
const rows = [
  ['D', 'external'], ['A', 'cancelled'], ['B', 'completed'],
  ['A', 'pending'], ['C', 'postponed'], ['D', 'in_progress'],
].map(([importance, status], index) => ({
  id:index, dataset:{sortImportance:importance}, status:{dataset:{savedStatus:status}},
  querySelector() { return this.status; },
}));
const list = {rows:[...rows], querySelectorAll(){return this.rows;}, append(row){this.rows.splice(this.rows.indexOf(row),1); this.rows.push(row);}};
const buttons = ['importance','status'].map(mode=>({dataset:{todoSort:mode}, attrs:{}, addEventListener(_name,fn){this.click=fn;}, setAttribute(key,value){this.attrs[key]=value;}}));
const context = {page:{querySelector(){return list;},querySelectorAll(){return buttons;}}};
vm.runInNewContext(source.slice(start,end),context);
buttons[0].click();
assert.deepEqual(list.rows.map(row=>row.dataset.sortImportance),['A','A','B','C','D','D']);
assert.deepEqual(list.rows.filter(row=>row.dataset.sortImportance==='A').map(row=>row.id),[1,3]);
buttons[1].click();
assert.deepEqual(list.rows.map(row=>row.status.dataset.savedStatus),['pending','in_progress','completed','cancelled','postponed','external']);
assert.equal(buttons[1].attrs['aria-pressed'],'true');
rows[0].status.dataset.savedStatus='pending';
context.sortTodoRows();
assert.deepEqual(list.rows.map(row=>row.status.dataset.savedStatus),['pending','pending','in_progress','completed','cancelled','postponed']);
assert.deepEqual(list.rows.slice(0,2).map(row=>row.id),[0,3]);
console.log('PASS importance and status ordering, stable ties, and status update');
