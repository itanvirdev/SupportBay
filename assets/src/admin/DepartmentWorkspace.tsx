import { FormEvent, useCallback, useEffect, useState } from 'react';
import { adminDelete, adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface Department {
  id:number; name:string; slug:string; description:string|null; status:'active'|'inactive';
  sort_order:number; default_priority:'normal'|'medium'|'high'|'urgent';
}
const empty:Department={id:0,name:'',slug:'',description:null,status:'active',sort_order:0,default_priority:'normal'};

export function DepartmentWorkspace(){
  const [items,setItems]=useState<Department[]>([]);
  const [selected,setSelected]=useState<Department|null>(null);
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);
  const load=useCallback(async()=>{setLoading(true);setError(null);try{const response=await adminGet<Department[]>('departments');setItems(response.data);}catch(reason){setError(reason instanceof Error?reason.message:'Departments could not be loaded.');}finally{setLoading(false);}},[]);
  useEffect(()=>{void load();},[load]);
  const save=async(event:FormEvent)=>{event.preventDefault();if(!selected)return;setSaving(true);setError(null);setNotice(null);try{const payload={name:selected.name,description:selected.description,status:selected.status,sort_order:selected.sort_order,default_priority:selected.default_priority};const response=selected.id?await adminPut<Department>(`departments/${selected.id}`,payload):await adminPost<Department>('departments',payload);setSelected(null);setNotice(`Department ${response.data.name} saved.`);await load();}catch(reason){setError(reason instanceof Error?reason.message:'Department could not be saved.');}finally{setSaving(false);}};
  const remove=async(item:Department)=>{if(item.slug==='support'||!window.confirm(`Delete “${item.name}”?`))return;setSaving(true);setError(null);setNotice(null);try{await adminDelete(`departments/${item.id}`);if(selected?.id===item.id)setSelected(null);setNotice('Department deleted.');await load();}catch(reason){setError(reason instanceof Error?reason.message:'Department could not be deleted.');}finally{setSaving(false);}};

  return <section className="sbay-department-settings">
    <header><div><small>Ticket routing</small><h2>Departments</h2><p>Support is the permanent fallback. Customers choose a department only when additional active departments exist.</p></div><button type="button" onClick={()=>{setSelected({...empty});setError(null);}}>Add New</button></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <div className="sbay-department-table"><div className="is-header"><span>Name</span><span>Priority</span><span>Order</span><span>Status</span><span>Action</span></div>{loading?<Preloader label="Loading departments…" />:items.map(item=><div key={item.id}><span><strong>{item.name}</strong>{item.slug==='support'?<small>Default</small>:null}</span><span>{item.default_priority}</span><span>{item.sort_order}</span><span className={`is-${item.status}`}>{item.status}</span><span className="sbay-department-actions"><button type="button" onClick={()=>setSelected({...item})}>Edit</button>{item.slug!=='support'?<button type="button" className="is-danger" disabled={saving} onClick={()=>void remove(item)}>Delete</button>:null}</span></div>)}</div>
    {selected?<div className="sbay-department-modal" role="dialog" aria-modal="true" aria-labelledby="sbay-department-title"><form onSubmit={save}><header><h3 id="sbay-department-title">{selected.id?'Edit Department':'Add Department'}</h3><button type="button" aria-label="Close" onClick={()=>setSelected(null)}>×</button></header><label>Name<input required maxLength={100} disabled={selected.slug==='support'} value={selected.name} onChange={event=>setSelected({...selected,name:event.target.value})}/></label><label>Description<textarea rows={3} value={selected.description??''} onChange={event=>setSelected({...selected,description:event.target.value||null})}/></label><label>Default priority<select value={selected.default_priority} onChange={event=>setSelected({...selected,default_priority:event.target.value as Department['default_priority']})}><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></label><label>Sort order<input type="number" min="0" value={selected.sort_order} onChange={event=>setSelected({...selected,sort_order:Number(event.target.value)||0})}/></label><label className="sbay-switch-row"><input type="checkbox" role="switch" disabled={selected.slug==='support'} checked={selected.status==='active'} onChange={event=>setSelected({...selected,status:event.target.checked?'active':'inactive'})}/><span>Status</span></label><footer><button type="button" onClick={()=>setSelected(null)}>Cancel</button><button disabled={saving||!selected.name.trim()}>{saving?'Saving…':'Save Department'}</button></footer></form></div>:null}
  </section>;
}
