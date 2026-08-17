import { useEffect, useState } from 'react';
import { adminGet, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface TicketSlaPolicy { enabled:boolean; first_response_minutes:Record<'normal'|'medium'|'high'|'urgent',number>; }
const priorities = ['urgent','high','medium','normal'] as const;

export function TicketSlaWorkspace() {
  const [policy,setPolicy]=useState<TicketSlaPolicy|null>(null);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);
  useEffect(()=>{adminGet<TicketSlaPolicy>('admin/ticket-sla-policy').then(response=>setPolicy(response.data)).catch(reason=>setError(reason instanceof Error?reason.message:'SLA policy could not be loaded.'));},[]);
  const save=async()=>{if(!policy)return;setSaving(true);setError(null);setNotice(null);try{const response=await adminPut<TicketSlaPolicy>('admin/ticket-sla-policy',policy);setPolicy(response.data);setNotice('Ticket SLA policy saved.');}catch(reason){setError(reason instanceof Error?reason.message:'SLA policy could not be saved.');}finally{setSaving(false);}};
  return <section className="sbay-sla-settings"><header><small>Ticket behavior</small><h2>First-response SLA</h2><p>Set calendar-time first-response targets by priority. Business-hour calendars and escalation are not enabled yet.</p></header>{error?<div className="sbay-admin-error" role="alert">{error}</div>:null}{notice?<div className="sbay-admin-notice" role="status">{notice}</div>:null}{policy?<><label className="sbay-switch"><input type="checkbox" checked={policy.enabled} onChange={event=>setPolicy({...policy,enabled:event.target.checked})}/>Enable SLA reporting</label><div>{priorities.map(priority=><label key={priority}><span>{priority}</span><input type="number" min="15" max="10080" value={policy.first_response_minutes[priority]} onChange={event=>setPolicy({...policy,first_response_minutes:{...policy.first_response_minutes,[priority]:Number(event.target.value)}})}/><small>minutes</small></label>)}</div><footer><button type="button" onClick={()=>void save()} disabled={saving}>{saving?'Saving…':'Save SLA policy'}</button></footer></>:<Preloader label="Loading SLA settings…" />}</section>;
}
