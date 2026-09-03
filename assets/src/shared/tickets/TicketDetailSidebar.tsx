import {useState} from 'react';
import type {ConversationTicket,TicketContext} from './TicketConversation';

interface Props {
  ticket:ConversationTicket;
  context:TicketContext;
  statusLabels:Record<string,string>;
  mutate:(action:string,value:unknown)=>Promise<void>;
  transition:(action:'resolve'|'close'|'reopen')=>Promise<void>;
  openCustomer?:(id:number)=>void;
}

const relative=(value:string)=>{const seconds=Math.max(0,Math.floor((Date.now()-new Date(value.replace(' ','T')).getTime())/1000));if(seconds<60)return '1 minute ago';const minutes=Math.floor(seconds/60);if(minutes<60)return `${minutes} minute${minutes===1?'':'s'} ago`;const hours=Math.floor(minutes/60);if(hours<24)return `${hours} hour${hours===1?'':'s'} ago`;const days=Math.floor(hours/24);if(days<30)return `${days} day${days===1?'':'s'} ago`;const months=Math.floor(days/30);return `${months} month${months===1?'':'s'} ago`};
const date=(value:string|null)=>value?new Date(value.replace(' ','T')).toLocaleDateString(undefined,{month:'short',day:'2-digit',year:'numeric'}):'—';

function Card({title,edit,children}:{title:string;edit?:()=>void;children:React.ReactNode}){return <section className="sbay-detail-card"><header><h2>{title}</h2>{edit?<button type="button" aria-label={`Edit ${title}`} onClick={edit}>✎</button>:null}</header>{children}</section>}

export function TicketDetailSidebar({ticket,context,statusLabels,mutate,transition,openCustomer}:Props){
  const [editing,setEditing]=useState(false);
  const [busy,setBusy]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const permissions=context.permissions;
  const canEditInformation=permissions.assign||permissions.category||permissions.priority||permissions.status||permissions.tags;
  const update=async(action:string,value:unknown)=>{setBusy(true);setError(null);try{await mutate(action,value);}catch(reason){setError(reason instanceof Error?reason.message:'Ticket information could not be updated.');}finally{setBusy(false);}};
  const updateStatus=async(status:string)=>{setBusy(true);setError(null);try{if(status==='resolved')await transition('resolve');else if(status==='closed')await transition('close');else if(['resolved','closed'].includes(ticket.status))await transition('reopen');setEditing(false);}catch(reason){setError(reason instanceof Error?reason.message:'Ticket status could not be updated.');}finally{setBusy(false);}};
  return <aside className="sbay-conversation__sidebar">
    <section className="sbay-ticket-id-card"><strong>#{ticket.track_id}</strong><span aria-label="Private ticket">▣</span></section>
    <Card title="Ticket User">
      {context.customer?<div className="sbay-ticket-user"><span>{context.customer.avatar_url?<img src={context.customer.avatar_url} alt=""/>:context.customer.name.charAt(0)}</span><strong>{context.customer.name}</strong>{context.customer.email?<a href={`mailto:${context.customer.email}`}>{context.customer.email}</a>:null}{openCustomer?<button type="button" onClick={()=>openCustomer(context.customer!.id)}>View customer profile</button>:null}</div>:<p>No linked customer</p>}
    </Card>
    <Card title="Information" edit={canEditInformation?()=>setEditing(true):undefined}>
      <dl><div><dt>Agent</dt><dd>{context.information.agent||'Unassigned'}</dd></div><div><dt>Category</dt><dd>{context.information.category||'Uncategorized'}</dd></div><div><dt>Priority</dt><dd>{context.information.priority}</dd></div><div><dt>Status</dt><dd>{statusLabels[ticket.status]??context.information.status}</dd></div>{context.tags.length?<div><dt>Tags</dt><dd className="sbay-detail-tags">{context.tags.map(tag=><i style={{borderColor:tag.color??undefined}} key={tag.id}>{tag.name}</i>)}</dd></div>:null}</dl>
    </Card>
    {context.purchase?<Card title="Additional Data"><dl><div><dt>Purchase Code/Key</dt><dd>{context.purchase.reference}</dd></div><div><dt>Product Name</dt><dd>{context.purchase.product_name||context.purchase.product_id||'—'}</dd></div><div><dt>License Type</dt><dd>{context.purchase.license_type||'—'}</dd></div><div><dt>Support Time</dt><dd>{date(context.purchase.support_expires_at)}</dd></div><div><dt>Provider</dt><dd>{context.purchase.provider}</dd></div></dl></Card>:null}
    <Card title={`Ticket Logs (${context.activities.length})`}><ol className="sbay-ticket-logs">{context.activities.map(activity=><li key={activity.id}><strong>{activity.label}</strong>{activity.description?<span>{activity.description}</span>:null}<time title={new Date(activity.created_at.replace(' ','T')).toLocaleString()}>◷ {relative(activity.created_at)}</time></li>)}</ol></Card>
    {editing?<div className="sbay-ticket-edit-modal" role="dialog" aria-modal="true" aria-label="Edit Information"><form onSubmit={event=>{event.preventDefault();setEditing(false);}}><header><h2>Edit Information</h2><button type="button" onClick={()=>setEditing(false)} aria-label="Close">×</button></header>{permissions.assign?<label>Agent<select disabled={busy} value={ticket.assigned_agent_id??''} onChange={event=>void update('assignment',event.target.value)}><option value="">Unassigned</option>{context.options.agents.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}{permissions.category?<label>Category<select disabled={busy} value={ticket.category_id??''} onChange={event=>void update('category',event.target.value)}><option value="">Uncategorized</option>{context.options.categories.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}{permissions.priority?<label>Priority<select disabled={busy} value={ticket.priority} onChange={event=>void update('priority',event.target.value)}>{['normal','medium','high','urgent'].map(item=><option value={item} key={item}>{item[0].toUpperCase()+item.slice(1)}</option>)}</select></label>:null}{permissions.status?<label>Status<select disabled={busy} value={ticket.status} onChange={event=>void updateStatus(event.target.value)}>{['open','resolved','closed'].map(item=><option value={item} key={item}>{statusLabels[item]??item[0].toUpperCase()+item.slice(1)}</option>)}</select></label>:null}{permissions.tags?<label>Tags<span className="sbay-edit-tags">{context.tags.map(tag=><button type="button" disabled={busy} onClick={()=>void update('tag_remove',tag.id)} key={tag.id}>{tag.name} ×</button>)}<select disabled={busy} defaultValue="" onChange={event=>{if(event.target.value)void update('tag_add',event.target.value);event.target.value='';}}><option value="">Add tag…</option>{context.options.tags.filter(tag=>!context.tags.some(current=>current.id===tag.id)).map(tag=><option value={tag.id} key={tag.id}>{tag.name}</option>)}</select></span></label>:null}{error?<p role="alert">{error}</p>:null}<footer><button type="button" onClick={()=>setEditing(false)}>Cancel</button><button disabled={busy}>{busy?'Saving…':'Done'}</button></footer></form></div>:null}
  </aside>;
}
