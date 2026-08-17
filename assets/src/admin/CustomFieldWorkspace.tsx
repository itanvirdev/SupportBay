import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { adminDelete, adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

type FieldType = 'text'|'textarea'|'number'|'select'|'checkbox'|'date'|'email'|'url';
interface CustomField {
  id:number; name:string; slug:string; type:FieldType; options:string[];
  is_required:boolean; customer_visible:boolean; department_id:number|null;
  status:'active'|'inactive'; sort_order:number; updated_at:string;
}
interface Department { id:number; name:string }

const emptyField:CustomField={id:0,name:'',slug:'',type:'text',options:[],is_required:false,customer_visible:false,department_id:null,status:'active',sort_order:0,updated_at:''};
const types:FieldType[]=['text','textarea','number','select','checkbox','date','email','url'];

export function CustomFieldWorkspace() {
  const [items,setItems]=useState<CustomField[]>([]);
  const [departments,setDepartments]=useState<Department[]>([]);
  const [selected,setSelected]=useState<CustomField>(emptyField);
  const [query,setQuery]=useState('');
  const [status,setStatus]=useState('');
  const [type,setType]=useState('');
  const [department,setDepartment]=useState('');
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=useCallback(async()=>{
    setLoading(true);setError(null);
    try {
      const [fields,departmentResponse]=await Promise.all([
        adminGet<CustomField[]>('custom-fields'),
        adminGet<Department[]>('departments?status=active'),
      ]);
      setItems(fields.data);setDepartments(departmentResponse.data);
    } catch(reason) { setError(reason instanceof Error?reason.message:'Custom fields could not be loaded.'); }
    finally { setLoading(false); }
  },[]);
  useEffect(()=>{void load();},[load]);

  const departmentNames=useMemo(()=>new Map(departments.map(item=>[item.id,item.name])),[departments]);
  const visible=useMemo(()=>items.filter(item=>(!status||item.status===status)&&(!type||item.type===type)&&(!department||(department==='global'?item.department_id===null:item.department_id===Number(department)))&&`${item.name} ${item.slug} ${item.type}`.toLowerCase().includes(query.toLowerCase())),[items,query,status,type,department]);

  const save=async(event:FormEvent)=>{
    event.preventDefault();setSaving(true);setError(null);setNotice(null);
    const updating=selected.id>0;
    try {
      const payload={name:selected.name,slug:selected.slug||selected.name,type:selected.type,options:selected.type==='select'?selected.options:[],is_required:selected.is_required,customer_visible:selected.customer_visible,department_id:selected.department_id,status:selected.status,sort_order:selected.sort_order};
      const response=updating?await adminPut<CustomField>(`custom-fields/${selected.id}`,payload):await adminPost<CustomField>('custom-fields',payload);
      setSelected(response.data);setNotice(updating?'Custom field updated.':'Custom field created.');await load();
    } catch(reason) { setError(reason instanceof Error?reason.message:'Custom field could not be saved.'); }
    finally { setSaving(false); }
  };
  const remove=async()=>{
    if(!selected.id||!window.confirm(`Delete “${selected.name}”? Custom fields used by tickets cannot be deleted.`))return;
    setSaving(true);setError(null);setNotice(null);
    try { await adminDelete(`custom-fields/${selected.id}`);setSelected(emptyField);setNotice('Custom field deleted.');await load(); }
    catch(reason) { setError(reason instanceof Error?reason.message:'Custom field could not be deleted.'); }
    finally { setSaving(false); }
  };

  return <section className="sbay-category-settings sbay-custom-field-settings">
    <header><div><small>Ticket data</small><h2>Custom Fields</h2><p>Define structured information collected for support tickets.</p></div><button type="button" onClick={()=>{setSelected(emptyField);setError(null);setNotice(null);}}>Add Custom Field</button></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <div className="sbay-category-settings__grid"><aside>
      <label>Search<input type="search" value={query} onChange={event=>setQuery(event.target.value)} placeholder="Search custom fields…"/></label>
      <label>Status<select value={status} onChange={event=>setStatus(event.target.value)}><option value="">All Statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
      <label>Type<select value={type} onChange={event=>setType(event.target.value)}><option value="">All Types</option>{types.map(item=><option value={item} key={item}>{item}</option>)}</select></label>
      <label>Department scope<select value={department} onChange={event=>setDepartment(event.target.value)}><option value="">All Scopes</option><option value="global">Global</option>{departments.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      {loading?<Preloader label="Loading custom fields…" compact />:visible.length===0?<p>No custom fields found.</p>:<ul>{visible.map(item=><li key={item.id}><button type="button" className={selected.id===item.id?'is-active':''} onClick={()=>{setSelected(item);setError(null);setNotice(null);}}><i/><span><strong>{item.name}</strong><small>{item.type} · {item.department_id?departmentNames.get(item.department_id)??`Department #${item.department_id}`:'Global'} · {item.status}</small></span></button></li>)}</ul>}
    </aside><form onSubmit={save}>
      <label>Name<input required maxLength={100} value={selected.name} onChange={event=>setSelected(current=>({...current,name:event.target.value}))}/></label>
      <label>Slug<input maxLength={120} value={selected.slug} onChange={event=>setSelected(current=>({...current,slug:event.target.value}))} placeholder="Generated from name when empty"/></label>
      <div className="sbay-custom-field-settings__row"><label>Type<select value={selected.type} onChange={event=>setSelected(current=>({...current,type:event.target.value as FieldType,options:event.target.value==='select'?current.options:[]}))}>{types.map(item=><option value={item} key={item}>{item}</option>)}</select></label><label>Department scope<select value={selected.department_id??''} onChange={event=>setSelected(current=>({...current,department_id:Number(event.target.value)||null}))}><option value="">Global — all departments</option>{departments.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label></div>
      {selected.type==='select'?<label>Choices<textarea required rows={6} value={selected.options.join('\n')} onChange={event=>setSelected(current=>({...current,options:event.target.value.split('\n')}))} placeholder={'One choice per line\nFor example: Current'}/><small>One choice per line. Empty and duplicate choices are removed when saved.</small></label>:null}
      <div className="sbay-custom-field-settings__checks"><label><input type="checkbox" checked={selected.is_required} onChange={event=>setSelected(current=>({...current,is_required:event.target.checked}))}/>Required</label><label><input type="checkbox" checked={selected.customer_visible} onChange={event=>setSelected(current=>({...current,customer_visible:event.target.checked}))}/>Visible to customers</label></div>
      <div className="sbay-custom-field-settings__row"><label>Status<select value={selected.status} onChange={event=>setSelected(current=>({...current,status:event.target.value as CustomField['status']}))}><option value="active">Active</option><option value="inactive">Inactive</option></select></label><label>Sort order<input type="number" min="0" value={selected.sort_order} onChange={event=>setSelected(current=>({...current,sort_order:Number(event.target.value)||0}))}/></label></div>
      <p>Fields with ticket values cannot change type or be deleted. Deactivate them to preserve historical ticket data.</p>
      <div className="sbay-category-settings__actions"><button disabled={saving||selected.name.trim()===''||(selected.type==='select'&&!selected.options.some(option=>option.trim()!==''))}>{saving?'Saving…':selected.id?'Save Changes':'Create Custom Field'}</button>{selected.id?<button type="button" className="is-danger" disabled={saving} onClick={()=>void remove()}>Delete</button>:null}</div>
    </form></div>
  </section>;
}
