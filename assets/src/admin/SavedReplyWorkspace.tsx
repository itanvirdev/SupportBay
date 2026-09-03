import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { RichTextEditor } from '../shared/editor/RichTextEditor';
import { adminDelete, adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface SavedReply { id:number; title:string; content:string; category:string|null; status:'active'|'inactive'; usage_count:number; last_used_at:string|null; last_used_by:number|null; updated_at:string }
interface Placeholder { key:string; label:string }
const emptyReply:SavedReply={id:0,title:'',content:'',category:null,status:'active',usage_count:0,last_used_at:null,last_used_by:null,updated_at:''};

export function SavedReplyWorkspace() {
  const [items,setItems]=useState<SavedReply[]>([]);
  const [draft,setDraft]=useState<SavedReply|null>(null);
  const [placeholders,setPlaceholders]=useState<Placeholder[]>([]);
  const [selectedIds,setSelectedIds]=useState<number[]>([]);
  const [bulkAction,setBulkAction]=useState('');
  const [deleteConfirmation,setDeleteConfirmation]=useState<number[]|null>(null);
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=useCallback(async()=>{
    setLoading(true);setError(null);
    try {
      const [active,inactive]=await Promise.all([
        adminGet<SavedReply[]>('saved-replies?status=active'), adminGet<SavedReply[]>('saved-replies?status=inactive'),
      ]);
      setItems([...active.data,...inactive.data].sort((left,right)=>left.id-right.id));
      setPlaceholders(Array.isArray(active.meta.placeholders)?active.meta.placeholders as Placeholder[]:[]);
      setSelectedIds([]);
    } catch(reason) { setError(reason instanceof Error?reason.message:'Saved replies could not be loaded.'); }
    finally { setLoading(false); }
  },[]);
  useEffect(()=>{void load();},[load]);

  const placeholderOptions=useMemo(()=>placeholders.map(placeholder=>placeholder.key),[placeholders]);
  const updateDraft=(changes:Partial<SavedReply>)=>setDraft(current=>current?{...current,...changes}:current);
  const toggleSelection=(id:number)=>setSelectedIds(current=>current.includes(id)?current.filter(item=>item!==id):[...current,id]);
  const toggleAll=()=>setSelectedIds(current=>current.length===items.length?[]:items.map(item=>item.id));

  const save=async(event:FormEvent)=>{
    event.preventDefault(); if(!draft)return;
    setSaving(true);setError(null);setNotice(null);
    try {
      const payload={title:draft.title,content:draft.content,category:draft.category,status:draft.status};
      const response=draft.id?await adminPut<SavedReply>(`saved-replies/${draft.id}`,payload):await adminPost<SavedReply>('saved-replies',payload);
      setItems(current=>draft.id?current.map(item=>item.id===response.data.id?response.data:item):[...current,response.data].sort((left,right)=>left.id-right.id));
      setDraft(null);setNotice(draft.id?'Saved reply updated.':'Saved reply created.');
    } catch(reason) { setError(reason instanceof Error?reason.message:'Saved reply could not be saved.'); }
    finally { setSaving(false); }
  };
  const deleteItems=async(ids:number[])=>{
    setSaving(true);setError(null);setNotice(null);
    try { await Promise.all(ids.map(id=>adminDelete(`saved-replies/${id}`)));setItems(current=>current.filter(item=>!ids.includes(item.id)));setSelectedIds(current=>current.filter(id=>!ids.includes(id)));setBulkAction('');setNotice(ids.length===1?'Saved reply deleted.':'Saved replies deleted.'); }
    catch(reason) { setError(reason instanceof Error?reason.message:'Saved replies could not be deleted.'); }
    finally { setSaving(false); }
  };
  const remove=(reply:SavedReply)=>setDeleteConfirmation([reply.id]);
  const applyBulk=async()=>{
    if(!bulkAction||selectedIds.length===0)return;
    if(bulkAction==='delete'){setDeleteConfirmation(selectedIds);return;}
    setSaving(true);setError(null);setNotice(null);
    try {
      await Promise.all(selectedIds.map(async id=>{
        const item=items.find(reply=>reply.id===id);
        return item?adminPut<SavedReply>(`saved-replies/${id}`,{title:item.title,content:item.content,category:item.category,status:bulkAction}):null;
      }));
      setNotice(`Saved replies ${bulkAction==='active'?'activated':'deactivated'}.`);setBulkAction('');await load();
    } catch(reason) { setError(reason instanceof Error?reason.message:'Saved reply bulk action could not be completed.'); }
    finally { setSaving(false); }
  };

  return <section className="sbay-saved-replies">
    <header className="sbay-saved-replies__header"><h2>Saved Replies</h2><div><button type="button" aria-label="Refresh saved replies" onClick={()=>void load()} disabled={loading}>↻</button><button className="is-primary" type="button" onClick={()=>{setDraft({...emptyReply});setError(null);setNotice(null);}}>＋ Add New</button></div></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-notice" role="status">{notice}</p>:null}{loading?<Preloader label="Loading saved replies…"/>:null}
    {!loading?<><div className="sbay-saved-replies__table"><div className="sbay-saved-replies__row is-header"><input type="checkbox" aria-label="Select all saved replies" checked={items.length>0&&selectedIds.length===items.length} onChange={toggleAll}/><strong>ID</strong><strong>Title</strong><strong>Status</strong><strong>Action</strong></div>{items.map(reply=><div className="sbay-saved-replies__row" key={reply.id}><input type="checkbox" aria-label={`Select ${reply.title}`} checked={selectedIds.includes(reply.id)} onChange={()=>toggleSelection(reply.id)}/><span>{reply.id}</span><span><strong>{reply.title}</strong>{reply.category?<small>{reply.category}</small>:null}</span><span><i className={`sbay-saved-replies__status is-${reply.status}`}>{reply.status==='active'?'Active':'Inactive'}</i></span><span className="sbay-saved-replies__actions"><button type="button" aria-label={`Edit ${reply.title}`} onClick={()=>{setDraft({...reply});setError(null);setNotice(null);}}>✎</button><button type="button" className="is-danger" aria-label={`Delete ${reply.title}`} onClick={()=>void remove(reply)} disabled={saving}>🗑</button></span></div>)}{items.length===0?<p className="sbay-saved-replies__empty">No saved replies yet. Add one to reuse a response in ticket replies.</p>:null}<footer><div><select value={bulkAction} onChange={event=>setBulkAction(event.target.value)} aria-label="Bulk actions"><option value="">Bulk Actions</option><option value="active">Activate</option><option value="inactive">Deactivate</option><option value="delete">Delete</option></select><button type="button" onClick={()=>void applyBulk()} disabled={!bulkAction||selectedIds.length===0||saving}>Apply</button></div><span>Showing {items.length?`1 – ${items.length}`:'0'} of {items.length}</span></footer></div>{items.length>0?<nav className="sbay-saved-replies__pagination" aria-label="Saved reply pagination"><button type="button" disabled>‹</button><button type="button" className="is-current">1</button><button type="button" disabled>›</button></nav>:null}</>:null}
    {draft?<div className="sbay-saved-reply-modal" role="dialog" aria-modal="true" aria-labelledby="sbay-saved-reply-modal-title"><form onSubmit={save}><header><h2 id="sbay-saved-reply-modal-title">{draft.id?'Edit':'Add New'} Saved Reply</h2><button type="button" aria-label="Close" onClick={()=>setDraft(null)} disabled={saving}>×</button></header><label><span>Title <b>*</b></span><input required maxLength={190} value={draft.title} onChange={event=>updateDraft({title:event.target.value})}/></label><div className="sbay-saved-reply-modal__editor"><span>Content <b>*</b></span><RichTextEditor key={draft.id||'new'} value={draft.content} onChange={content=>updateDraft({content})} disabled={saving} placeholderOptions={placeholderOptions}/></div><details><summary>Optional organization</summary><div><label>Category<input maxLength={100} value={draft.category??''} onChange={event=>updateDraft({category:event.target.value||null})} placeholder="For example: Billing"/></label></div></details>{draft.id?<p className="sbay-saved-reply-modal__usage"><strong>{draft.usage_count}</strong> composer insertions{draft.last_used_at?` · Last used ${new Date(draft.last_used_at).toLocaleString()}`:''}</p>:null}<label className="sbay-general-toggle"><input type="checkbox" role="switch" checked={draft.status==='active'} onChange={event=>updateDraft({status:event.target.checked?'active':'inactive'})}/><span>Status</span></label><footer><button type="button" onClick={()=>setDraft(null)} disabled={saving}>Cancel</button><button className="is-primary" disabled={saving||!draft.title.trim()||!draft.content.replace(/<[^>]*>/g,'').trim()}>{saving?(draft.id?'Updating…':'Creating…'):(draft.id?'Update':'Create')}</button></footer></form></div>:null}
    {deleteConfirmation?<div className="sbay-saved-reply-delete-confirmation" role="alertdialog" aria-modal="true" aria-labelledby="sbay-saved-reply-delete-title"><div><header><span aria-hidden="true">ⓘ</span><h2 id="sbay-saved-reply-delete-title">Delete</h2></header><p>Are you sure want to delete?</p><footer><button type="button" onClick={()=>setDeleteConfirmation(null)} disabled={saving}>No</button><button type="button" className="is-danger" onClick={()=>{const ids=deleteConfirmation;setDeleteConfirmation(null);void deleteItems(ids);}} disabled={saving}>Yes</button></footer></div></div>:null}
  </section>;
}
