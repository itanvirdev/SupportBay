import { useEffect, useMemo, useState } from 'react';

export interface SavedReply { id:number;title:string;content:string;category?:string|null;usage_count?:number;last_used_at?:string|null;last_used_by?:number|null }

interface Props {
  load: () => Promise<SavedReply[]>;
  disabled: boolean;
  hasDraft: boolean;
  select: (reply: SavedReply) => void;
  track: (id: number) => Promise<void>;
}

export function SavedReplyPicker({ load, disabled, hasDraft, select, track }: Props) {
  const [items,setItems]=useState<SavedReply[]>([]);
  const [query,setQuery]=useState('');
  const [category,setCategory]=useState('');
  const [open,setOpen]=useState(false);
  const [loading,setLoading]=useState(false);
  const [loaded,setLoaded]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const categories=useMemo(()=>Array.from(new Set(items.map(item=>item.category).filter((value):value is string=>Boolean(value)))).sort(),[items]);
  const visible=useMemo(()=>items.filter(item=>(!category||item.category===category)&&`${item.title} ${item.content.replace(/<[^>]*>/g,' ')}`.toLowerCase().includes(query.toLowerCase())),[items,query,category]);

  useEffect(()=>{if(!open||loaded||loading)return;setLoading(true);setError(null);load().then(setItems).catch(reason=>setError(reason instanceof Error?reason.message:'Saved replies could not be loaded.')).finally(()=>{setLoaded(true);setLoading(false);});},[open,loaded,load,loading]);

  return <div className="sbay-saved-replies">
    <button type="button" disabled={disabled} aria-expanded={open} onClick={()=>setOpen(value=>!value)}>Saved Replies</button>
    {open?<div className="sbay-saved-replies__panel">
      <label>Find a saved reply<input type="search" value={query} onChange={event=>setQuery(event.target.value)} placeholder="Search saved replies…" autoFocus/></label>{categories.length?<label>Category<select value={category} onChange={event=>setCategory(event.target.value)}><option value="">All Categories</option>{categories.map(item=><option key={item}>{item}</option>)}</select></label>:null}
      {loading?<p>Loading saved replies…</p>:error?<p role="alert">{error}</p>:visible.length===0?<p>No saved replies found.</p>:<ul>{visible.map(reply=><li key={reply.id}><button type="button" onClick={async()=>{if(hasDraft&&!window.confirm('Replace the current draft with this saved reply?'))return;setError(null);try{await track(reply.id);select(reply);setOpen(false);setQuery('');setCategory('');}catch(reason){setError(reason instanceof Error?reason.message:'Saved reply insertion could not be recorded.');}}}><strong>{reply.title}</strong><span>{reply.category?`${reply.category} · `:''}{reply.content.replace(/<[^>]*>/g,' ').trim().slice(0,120)}</span></button></li>)}</ul>}
    </div>:null}
  </div>;
}
