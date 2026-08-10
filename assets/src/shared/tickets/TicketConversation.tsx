import { FormEvent, useState } from 'react';
import { FilePicker } from '../../react/components/FilePicker';
import { RichTextEditor } from '../editor/RichTextEditor';

export interface ConversationTicket { id:number;track_id:string;subject:string;status:string;priority:string;source:string;created_at:string;assigned_agent_id?:number|null }
export interface ConversationMessage { id:number;author_type:string;type:string;content:string;created_at:string }
export interface TicketAttachment { id:number;message_id:number;original_name:string;file_size:number;mime_type:string }
export interface TicketContext {
  customer:{id:number;name:string;email:string;avatar_url:string|null;state:string}|null;
  information:{agent:string|null;department:string|null;priority:string;status:string;source:string};
  purchase:{provider:string;reference:string;product_id:string|null;product_name:string|null;license_type:string|null;purchased_at:string|null;support_expires_at:string|null;status:string}|null;
  activities:Array<{id:number;label:string;description:string|null;actor_type:string;created_at:string}>;
  attachments:TicketAttachment[];
  options:{departments:Array<{id:number;name:string}>;agents:Array<{id:number;name:string}>};
}
interface Props { ticket:ConversationTicket;messages:ConversationMessage[];context?:TicketContext;back:()=>void;submit:(content:string,type:'reply'|'internal_note',files:File[],close:boolean)=>Promise<void>;transition:()=>Promise<void>;download:(file:TicketAttachment)=>Promise<void>;mutate:(action:string,value:string|number)=>Promise<void>;merge?:(targetId:number)=>Promise<void> }

export function TicketConversation({ticket,messages,context,back,submit,transition,download,mutate,merge}:Props) {
  const [drafts,setDrafts]=useState({reply:'',internal_note:''});
  const [type,setType]=useState<'reply'|'internal_note'>('reply');
  const [files,setFiles]=useState<File[]>([]);
  const [busy,setBusy]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [mergeTarget,setMergeTarget]=useState('');
  const send=async(event:FormEvent,close=false)=>{event.preventDefault();setBusy(true);setError(null);try{await submit(drafts[type],type,files,close);setDrafts(current=>({...current,[type]:''}));setFiles([]);}catch(reason){setError(reason instanceof Error?reason.message:'Message could not be sent.');}finally{setBusy(false);}};
  const information=context?.information??{agent:null,department:null,priority:ticket.priority,status:ticket.status,source:ticket.source};

  return <section className="sbay-conversation">
    <button className="sbay-back" onClick={back}>← Back to tickets</button>
    <header className="sbay-conversation__header"><div><small>Ticket #{ticket.track_id}</small><h1>{ticket.subject}</h1><p>Opened {new Date(ticket.created_at).toLocaleDateString()}</p></div><div><span>{ticket.status}</span><button onClick={()=>void transition()}>{ticket.status==='closed'?'Reopen ticket':'Close ticket'}</button></div></header>
    <div className="sbay-conversation__grid"><main className="sbay-conversation__thread">
      {messages.map(message=><article className={message.type==='internal_note'?'is-note':''} key={message.id}><header><strong>{message.type==='internal_note'?'Internal note':message.author_type==='customer'?'Customer':'Support team'}</strong><time>{new Date(message.created_at).toLocaleString()}</time></header><div className="sbay-rich-content" dangerouslySetInnerHTML={{__html:message.content}} />{context?.attachments.filter(file=>file.message_id===message.id).map(file=><button type="button" className="sbay-message-file" onClick={()=>void download(file)} key={file.id}>📎 {file.original_name} <small>{Math.ceil(file.file_size/1024)} KB</small></button>)}</article>)}
      {ticket.status!=='closed'?<form className={`sbay-conversation__composer is-${type}`} onSubmit={send}><div><button type="button" className={type==='reply'?'is-active':''} onClick={()=>setType('reply')}>Reply</button><button type="button" className={type==='internal_note'?'is-active':''} onClick={()=>setType('internal_note')}>Internal Note</button></div><RichTextEditor key={type} value={drafts[type]} onChange={value=>setDrafts(current=>({...current,[type]:value}))} disabled={busy}/>{type==='reply'?<FilePicker files={files} onChange={setFiles} disabled={busy}/>:null}{error?<p role="alert">{error}</p>:null}<div className="sbay-composer-actions"><button disabled={busy||drafts[type].trim()===''}>{busy?'Sending…':type==='reply'?'Submit Reply':'Add Internal Note'}</button>{type==='reply'?<button type="button" disabled={busy||drafts[type].trim()===''} onClick={event=>void send(event,true)}>Submit & Close Ticket</button>:null}<button type="button" onClick={()=>{setDrafts(current=>({...current,[type]:''}));setFiles([]);}}>Cancel</button></div></form>:<p>This ticket is closed to new replies.</p>}
    </main><aside className="sbay-conversation__sidebar">
      <section><h2>Ticket User</h2>{context?.customer?<div className="sbay-ticket-user"><span>{context.customer.name.charAt(0)}</span><strong>{context.customer.name}</strong><a href={`mailto:${context.customer.email}`}>{context.customer.email}</a><small>{context.customer.state}</small></div>:<p>No linked customer</p>}</section>
      <section><h2>Information</h2><dl>{Object.entries(information).map(([label,value])=><div key={label}><dt>{label}</dt><dd>{value||'Unassigned'}</dd></div>)}</dl></section>
      {context?.purchase?<section><h2>Additional Data</h2><dl><div><dt>Reference</dt><dd>{context.purchase.reference}</dd></div><div><dt>Product</dt><dd>{context.purchase.product_name||context.purchase.product_id||'—'}</dd></div><div><dt>License</dt><dd>{context.purchase.license_type||'—'}</dd></div><div><dt>Support</dt><dd>{context.purchase.support_expires_at?new Date(context.purchase.support_expires_at).toLocaleDateString():'—'}</dd></div><div><dt>Provider</dt><dd>{context.purchase.provider}</dd></div><div><dt>Verification</dt><dd>{context.purchase.status}</dd></div></dl></section>:null}
      {context?<section><h2>Ticket Actions</h2><div className="sbay-ticket-controls"><label>Agent<select defaultValue="" onChange={event=>void mutate('assignment',event.target.value)}><option value="">Unassigned</option>{context.options.agents.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label><label>Department<select defaultValue="" onChange={event=>void mutate('department',event.target.value)}><option value="" disabled>Select department</option>{context.options.departments.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label><label>Priority<select defaultValue={ticket.priority} onChange={event=>void mutate('priority',event.target.value)}>{['normal','medium','high','urgent'].map(item=><option key={item}>{item}</option>)}</select></label><button type="button" onClick={()=>void mutate('state','trash')}>Move to Trash</button><button type="button" onClick={()=>void mutate('state','active')}>Restore</button>{merge?<div className="sbay-ticket-merge"><label>Merge into ticket ID<input min="1" inputMode="numeric" type="number" value={mergeTarget} onChange={event=>setMergeTarget(event.target.value)}/></label><button type="button" disabled={busy||Number(mergeTarget)<=0||Number(mergeTarget)===ticket.id} onClick={async()=>{setBusy(true);setError(null);try{await merge(Number(mergeTarget));}catch(reason){setError(reason instanceof Error?reason.message:'Tickets could not be merged.');setBusy(false);}}}>Merge Ticket</button></div>:null}</div></section>:null}
      <section><h2>Ticket Logs ({context?.activities.length??0})</h2><ol className="sbay-ticket-logs">{context?.activities.map(activity=><li key={activity.id}><strong>{activity.label}</strong>{activity.description?<span>{activity.description}</span>:null}<time>{new Date(activity.created_at).toLocaleString()}</time></li>)}</ol></section>
    </aside></div>
  </section>;
}
