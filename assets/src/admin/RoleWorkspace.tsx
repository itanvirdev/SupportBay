import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { adminDelete, adminGet, adminPost, adminPut } from './api';

interface SupportRole {
  slug:string; name:string; description:string|null; status:'active'|'inactive';
  built_in:boolean; editable:boolean; support_role:boolean; capabilities:string[]; user_count:number;
}
type CapabilityGroups = Record<string,Record<string,string>>;

const emptyRole:SupportRole={slug:'',name:'',description:null,status:'active',built_in:false,editable:true,support_role:true,capabilities:[],user_count:0};

export function RoleWorkspace(){
  const [roles,setRoles]=useState<SupportRole[]>([]);
  const [groups,setGroups]=useState<CapabilityGroups>({});
  const [required,setRequired]=useState<string[]>([]);
  const [editing,setEditing]=useState<SupportRole|null>(null);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=useCallback(async()=>{setError(null);try{const response=await adminGet<SupportRole[]>('roles');setRoles(response.data);setGroups((response.meta.capability_groups??{}) as CapabilityGroups);setRequired((response.meta.required_capabilities??[]) as string[]);}catch(reason){setError(reason instanceof Error?reason.message:'Roles could not be loaded.');}},[]);
  useEffect(()=>{void load();},[load]);
  const allCapabilities=useMemo(()=>Object.values(groups).flatMap(group=>Object.keys(group)),[groups]);
  const presets=roles.filter(role=>['sbay_agent','sbay_manager'].includes(role.slug));
  const toggle=(capability:string)=>{if(required.includes(capability))return;setEditing(current=>current?({...current,capabilities:current.capabilities.includes(capability)?current.capabilities.filter(item=>item!==capability):[...current.capabilities,capability]}):current);};
  const save=async(event:FormEvent)=>{event.preventDefault();if(!editing?.editable)return;setSaving(true);setError(null);setNotice(null);try{const payload={name:editing.name,description:editing.description,status:editing.status,support_role:editing.support_role,capabilities:editing.support_role?editing.capabilities:[]};const updating=roles.some(role=>role.slug===editing.slug);const response=updating?await adminPut<SupportRole>(`roles/${editing.slug}`,payload):await adminPost<SupportRole>('roles',payload);setEditing(null);setNotice(`Role ${response.data.name} saved.`);await load();}catch(reason){setError(reason instanceof Error?reason.message:'Role could not be saved.');}finally{setSaving(false);}};
  const remove=async(role:SupportRole)=>{if(!role.editable||role.user_count||!window.confirm(`Delete “${role.name}”?`))return;setSaving(true);setError(null);try{await adminDelete(`roles/${role.slug}`);if(editing?.slug===role.slug)setEditing(null);setNotice('Role deleted.');await load();}catch(reason){setError(reason instanceof Error?reason.message:'Role could not be deleted.');}finally{setSaving(false);}};
  const isExisting=editing!==null&&roles.some(role=>role.slug===editing.slug);

  return <section className="sbay-role-settings">
    <header><div><small>Access control</small><h2>User Roles</h2><p>Configure SupportBay permissions. Assign team members through WordPress Users.</p></div><button type="button" onClick={()=>{setEditing({...emptyRole,capabilities:[...required]});setError(null);}}>Add New</button></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}{notice?<p className="sbay-admin-success" role="status">{notice}</p>:null}
    <div className="sbay-role-table"><div className="is-header"><span>Name</span><span>Users</span><span>Status</span><span>Action</span></div>{roles.map(role=><div key={role.slug}><span><strong>{role.name}</strong>{role.built_in?<small>Built-in</small>:null}</span><span>{role.user_count}</span><span className={`is-${role.status}`}>{role.status}</span><span className="sbay-role-actions"><button type="button" onClick={()=>setEditing({...role,capabilities:[...role.capabilities]})}>{role.editable?'Edit':'View'}</button>{role.editable?<button type="button" className="is-danger" disabled={saving||role.user_count>0} title={role.user_count?'Remove assigned users before deleting this role.':'Delete role'} onClick={()=>void remove(role)}>Delete</button>:null}</span></div>)}</div>
    {editing?<div className="sbay-role-modal" role="dialog" aria-modal="true" aria-labelledby="sbay-role-title"><form onSubmit={save}><header><h3 id="sbay-role-title">{editing.editable?(isExisting?'Edit User Role':'Add User Role'):'View User Role'}</h3><button type="button" aria-label="Close" onClick={()=>setEditing(null)}>×</button></header><label>Name<input required maxLength={190} disabled={!editing.editable} value={editing.name} onChange={event=>setEditing({...editing,name:event.target.value})}/></label><label>Description<textarea rows={3} disabled={!editing.editable} value={editing.description??''} onChange={event=>setEditing({...editing,description:event.target.value||null})}/></label><label className="sbay-switch-row"><input type="checkbox" role="switch" disabled={!editing.editable} checked={editing.support_role} onChange={event=>setEditing({...editing,support_role:event.target.checked,capabilities:event.target.checked?(editing.capabilities.length?editing.capabilities:[...required]):[]})}/><span>Support Agent or Manager.</span></label>{editing.support_role?<section className="sbay-role-capabilities"><header><strong>Capabilities</strong><div><label><input type="checkbox" disabled={!editing.editable} checked={allCapabilities.length>0&&allCapabilities.every(capability=>editing.capabilities.includes(capability))} onChange={event=>setEditing({...editing,capabilities:event.target.checked?[...allCapabilities]:[...required]})}/> All Capabilities</label>{editing.editable&&presets.length?<select aria-label="Capability preset" defaultValue="" onChange={event=>{const preset=roles.find(role=>role.slug===event.target.value);if(preset)setEditing({...editing,capabilities:[...preset.capabilities]});}}><option value="">Preset</option>{presets.map(role=><option value={role.slug} key={role.slug}>{role.name}</option>)}</select>:null}</div></header>{Object.entries(groups).map(([category,capabilities])=><fieldset key={category}><legend>{category}</legend>{Object.entries(capabilities).map(([capability,label])=><label key={capability}><input type="checkbox" disabled={!editing.editable||required.includes(capability)} checked={editing.capabilities.includes(capability)||required.includes(capability)} onChange={()=>toggle(capability)}/>{label}</label>)}</fieldset>)}</section>:null}{editing.editable?<label className="sbay-switch-row"><input type="checkbox" role="switch" checked={editing.status==='active'} onChange={event=>setEditing({...editing,status:event.target.checked?'active':'inactive'})}/><span>Status</span></label>:null}<footer><button type="button" onClick={()=>setEditing(null)}>Cancel</button>{editing.editable?<button disabled={saving||!editing.name.trim()}>{saving?'Saving…':'Save Role'}</button>:null}</footer></form></div>:null}
  </section>;
}
