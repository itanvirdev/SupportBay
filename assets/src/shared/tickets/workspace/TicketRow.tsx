import type { WorkspaceMode, WorkspaceTicket } from './types';

export function TicketTableHeader({mode,allSelected,toggleAll}:{mode:WorkspaceMode;allSelected:boolean;toggleAll:()=>void}) {
  return <div className="sbay-ticket-row sbay-ticket-row--header">{mode==='staff'?<><input aria-label="Select all tickets" checked={allSelected} onChange={toggleAll} type="checkbox"/><span/></>:null}<span>Title</span><span>Reply</span>{mode==='staff'?<span>Agent</span>:null}<span>Date</span></div>;
}

interface Props {mode:WorkspaceMode;ticket:WorkspaceTicket;selected:boolean;toggle:()=>void;open:()=>void;showDepartments:boolean;showCategories:boolean;statusLabels:Record<string,string>}
export function TicketRow({mode,ticket,selected,toggle,open,showDepartments,showCategories,statusLabels}:Props) {
  return <div className="sbay-ticket-row">
    {mode==='staff'?<><input aria-label={`Select ${ticket.subject}`} checked={selected} onChange={toggle} type="checkbox"/><span className="sbay-ticket-avatar">{ticket.customer_avatar_url?<img src={ticket.customer_avatar_url} alt=""/>:'◉'}</span></>:null}
    <button className="sbay-ticket-title" onClick={open}><strong>{ticket.subject} {ticket.customer_name?<small>by {ticket.customer_name}</small>:null}</strong>{ticket.latest_reply_excerpt?<small className="sbay-ticket-excerpt">{ticket.latest_reply_excerpt}</small>:null}<span><i>{statusLabels[ticket.status]??ticket.status}</i> #{ticket.track_id}{showDepartments&&ticket.department_name?` · ${ticket.department_name}`:''}{showCategories?` · ${ticket.category_name||'Uncategorized'}`:''} · {ticket.priority}{mode==='staff'&&ticket.tags?.map(tag=><em className="sbay-ticket-tag" style={{borderColor:tag.color??undefined}} key={tag.id}>{tag.name}</em>)}</span></button>
    <span className="sbay-ticket-replies">{ticket.reply_count??0}{mode==='staff'&&ticket.needs_reply?<i className="sbay-need-reply">Need Reply</i>:null}{mode==='customer'&&ticket.has_support_reply?<i className="sbay-support-replied">Agent Replied</i>:null}</span>
    {mode==='staff'?<span>{ticket.agent_name||'Unassigned'}</span>:null}<span>{new Date(ticket.updated_at||ticket.created_at).toLocaleDateString()}</span>
  </div>;
}
