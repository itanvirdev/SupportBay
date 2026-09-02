import { useEffect, useState } from 'react';
import { adminGet, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';
import { RichTextEditor } from '../shared/editor/RichTextEditor';

interface NotificationTemplate {
  key:string; name:string; event:string; recipient_type:'customer'|'agent'|'manager'; status:'active'|'inactive'; subject:string; html_content:string; plain_text_content:string;
}
interface TemplateMeta { placeholders?:string[] }

const recipientLabel:Record<NotificationTemplate['recipient_type'],string>={agent:'Admin or Agent',manager:'Support Manager',customer:'Customer (Ticket Portal)'};
const eventLabel=(event:string)=>event.split('_').map(word=>word.charAt(0).toUpperCase()+word.slice(1)).join(' ');
const plainText=(html:string)=>html.replace(/<br\s*\/?\s*>/gi,'\n').replace(/<\/p>|<\/div>|<\/li>/gi,'\n').replace(/<[^>]*>/g,'').replace(/&nbsp;/g,' ').trim();

export function NotificationTemplateWorkspace() {
  const [templates,setTemplates]=useState<NotificationTemplate[]>([]);
  const [placeholders,setPlaceholders]=useState<string[]>([]);
  const [draft,setDraft]=useState<NotificationTemplate|null>(null);
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [notice,setNotice]=useState<string|null>(null);

  const load=async()=>{
    setLoading(true);setError(null);
    try {
      const response=await adminGet<NotificationTemplate[]>('admin/notification-templates');
      setTemplates(response.data);
      const meta=response.meta as TemplateMeta;
      setPlaceholders(Array.isArray(meta.placeholders)?meta.placeholders:[]);
    } catch(reason) { setError(reason instanceof Error?reason.message:'Email notifications could not be loaded.'); }
    finally { setLoading(false); }
  };
  useEffect(()=>{void load();},[]);

  const updateDraft=(changes:Partial<NotificationTemplate>)=>setDraft(current=>current?{...current,...changes}:current);
  const save=async()=>{
    if(!draft)return;
    setSaving(true);setError(null);
    try {
      const response=await adminPut<NotificationTemplate>(`admin/notification-templates/${draft.event}/${draft.recipient_type}`,{
        status:draft.status,subject:draft.subject,html_content:draft.html_content,plain_text_content:plainText(draft.html_content),
      });
      setTemplates(current=>current.map(template=>template.key===response.data.key?response.data:template));
      setDraft(null);setNotice('Email notification updated.');
    } catch(reason) { setError(reason instanceof Error?reason.message:'Email notification could not be updated.'); }
    finally { setSaving(false); }
  };

  return <section className="sbay-email-notifications">
    <header className="sbay-email-notifications__header"><div><h2>Email Notifications</h2><p>Templates are sent through your WordPress email configuration.</p></div><button type="button" aria-label="Refresh email notifications" onClick={()=>void load()} disabled={loading}>↻</button></header>
    {error?<p className="sbay-admin-error" role="alert">{error}</p>:null}
    {notice?<p className="sbay-admin-notice" role="status">{notice}</p>:null}
    {loading?<Preloader label="Loading email notifications…"/>:null}
    {!loading?<div className="sbay-email-notifications__table"><div className="sbay-email-notifications__row is-header"><span aria-hidden="true"/><strong>Title</strong><strong>Recipient</strong><strong>Status</strong><strong>Action</strong></div>{templates.map(template=><div className="sbay-email-notifications__row" key={template.key}><span className={`sbay-email-notifications__icon is-${template.recipient_type}`} aria-hidden="true">{template.recipient_type==='customer'?'✉':'♧'}</span><strong>{eventLabel(template.event)}</strong><span>{recipientLabel[template.recipient_type]}</span><span><i className={`sbay-email-notifications__status is-${template.status}`}>{template.status==='active'?'Active':'Inactive'}</i></span><button type="button" aria-label={`Edit ${eventLabel(template.event)} notification`} onClick={()=>{setDraft({...template});setError(null);setNotice(null);}}>✎</button></div>)}</div>:null}
    {draft?<div className="sbay-email-notification-modal" role="dialog" aria-modal="true" aria-labelledby="sbay-email-notification-title"><form onSubmit={event=>{event.preventDefault();void save();}}><header><div><h2 id="sbay-email-notification-title">Edit Email Notification</h2><p><span className={`sbay-email-notifications__icon is-${draft.recipient_type}`} aria-hidden="true">{draft.recipient_type==='customer'?'✉':'♧'}</span><strong>{eventLabel(draft.event)}</strong> — {recipientLabel[draft.recipient_type]}</p></div><button type="button" onClick={()=>setDraft(null)} aria-label="Close">×</button></header><label><span>Subject <b>*</b></span><input required value={draft.subject} onChange={event=>updateDraft({subject:event.target.value})}/></label><div className="sbay-email-notification-modal__editor"><span>Content <b>*</b></span><RichTextEditor key={draft.key} value={draft.html_content} onChange={html_content=>updateDraft({html_content})} disabled={saving} placeholderOptions={placeholders}/></div><label className="sbay-general-toggle"><input type="checkbox" role="switch" checked={draft.status==='active'} onChange={event=>updateDraft({status:event.target.checked?'active':'inactive'})}/><span>Status</span></label><footer><button type="button" onClick={()=>setDraft(null)} disabled={saving}>Cancel</button><button className="is-primary" disabled={saving||!draft.subject.trim()||!plainText(draft.html_content)}>{saving?'Updating…':'Update'}</button></footer></form></div>:null}
  </section>;
}
