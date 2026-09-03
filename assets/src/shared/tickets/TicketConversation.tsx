import { FormEvent, useEffect, useRef, useState } from 'react';
import { FilePicker } from '../../react/components/FilePicker';
import { RichTextEditor } from '../editor/RichTextEditor';
import type { SavedReply } from './SavedReplyPicker';
import { renderSavedReply } from './savedReplyRenderer';
import {TicketDetailSidebar} from './TicketDetailSidebar';

export interface ConversationTicket { id:number;track_id:string;subject:string;status:string;state?:string;priority:string;source:string;created_at:string;category_id?:number|null;assigned_agent_id?:number|null }
export interface ConversationMessage { id:number;author_type:string;author_name?:string|null;author_avatar_url?:string|null;type:string;content:string;created_at:string }
export interface TicketAttachment { id:number;message_id:number;original_name:string;file_size:number;mime_type:string }
export interface TicketCustomField { id:number;name:string;type:'text'|'textarea'|'number'|'select'|'checkbox'|'date'|'email'|'url';options:string[];is_required:boolean;is_active:boolean;value:string|null }
export interface TicketContext {
  customer:{id:number;name:string;email:string;avatar_url:string|null;state:string}|null;
  information:{agent:string|null;category:string|null;priority:string;status:string;source:string};
  purchase:{provider:string;reference:string;product_id:string|null;product_name:string|null;license_type:string|null;purchased_at:string|null;support_expires_at:string|null;status:string}|null;
  activities:Array<{id:number;label:string;description:string|null;actor_type:string;created_at:string}>;
  attachments:TicketAttachment[];
  tags:Array<{id:number;name:string;color:string|null}>;
  custom_fields:TicketCustomField[];
  options:{categories:Array<{id:number;name:string}>;tags:Array<{id:number;name:string;color:string|null}>;custom_fields:Array<{id:number;name:string;type:string;options:string[];category_ids:number[]}>;agents:Array<{id:number;name:string}>};
  permissions:{reply:boolean;internal_note:boolean;assign:boolean;category:boolean;tags:boolean;custom_fields:boolean;priority:boolean;status:boolean;email:boolean;verification:boolean;delete:boolean};
}
interface Props { ticket:ConversationTicket;messages:ConversationMessage[];context?:TicketContext;statusLabels?:Record<string,string>;back:()=>void;refresh?:()=>void;submit:(content:string,type:'reply'|'internal_note',files:File[],close:boolean)=>Promise<void>;transition:(action:'resolve'|'close'|'reopen')=>Promise<void>;download:(file:TicketAttachment)=>Promise<Blob>;previewAttachments?:boolean;mutate:(action:string,value:unknown)=>Promise<void>;permDelete?:()=>Promise<void>;loadSavedReplies?:()=>Promise<SavedReply[]>;trackSavedReply?:(id:number)=>Promise<void>;openCustomer?:(id:number)=>void }

export function TicketConversation({ticket,messages,context,statusLabels={},back,refresh,submit,transition,download,previewAttachments=false,mutate,permDelete,loadSavedReplies,trackSavedReply,openCustomer}:Props) {
  const [drafts,setDrafts]=useState({reply:'',internal_note:''});
  const [type,setType]=useState<'reply'|'internal_note'>(context&&!context.permissions.reply&&context.permissions.internal_note?'internal_note':'reply');
  const [files,setFiles]=useState<File[]>([]);
  const [busy,setBusy]=useState(false);
  const [error,setError]=useState<string|null>(null);
  const [preview,setPreview]=useState<{url:string;name:string;mime:string}|null>(null);
  const [savedReplies,setSavedReplies]=useState<SavedReply[]>([]);
  const [savedRepliesOpen,setSavedRepliesOpen]=useState(false);
  const [composerOpen,setComposerOpen]=useState(false);
  const [priorityOpen,setPriorityOpen]=useState(false);
  const [assignOpen,setAssignOpen]=useState(false);
  const [deleteConfirmOpen,setDeleteConfirmOpen]=useState(false);
  const [deleting,setDeleting]=useState(false);
  const priorityRef=useRef<HTMLButtonElement>(null);
  const priorityPopoverRef=useRef<HTMLDivElement>(null);
  const assignRef=useRef<HTMLButtonElement>(null);
  const assignPopoverRef=useRef<HTMLDivElement>(null);
  const openAttachment=async(file:TicketAttachment)=>{const blob=await download(file);const url=URL.createObjectURL(blob);if(previewAttachments&&(file.mime_type.startsWith('image/')||file.mime_type==='application/pdf')){setPreview({url,name:file.original_name,mime:file.mime_type});return;}const link=document.createElement('a');link.href=url;link.download=file.original_name;link.click();window.setTimeout(()=>URL.revokeObjectURL(url),0);};
  const send=async(event:FormEvent,close=false)=>{event.preventDefault();setBusy(true);setError(null);try{await submit(drafts[type],type,files,close);setDrafts(current=>({...current,[type]:''}));setFiles([]);setComposerOpen(false);}catch(reason){setError(reason instanceof Error?reason.message:'Message could not be sent.');}finally{setBusy(false);}};
  const information=context?.information??{agent:null,category:null,priority:ticket.priority,status:ticket.status,source:ticket.source};
  const savedReplyContext={customer_name:context?.customer?.name,customer_email:context?.customer?.email,ticket_id:ticket.id,track_id:ticket.track_id,ticket_subject:ticket.subject,ticket_priority:ticket.priority,ticket_status:ticket.status,agent_name:information.agent,category_name:information.category,product_name:context?.purchase?.product_name,license_type:context?.purchase?.license_type,support_expires_at:context?.purchase?.support_expires_at};
  const savedReplyPreview=(content:string)=>renderSavedReply(content,savedReplyContext).replace(/<[^>]*>/g,' ').replace(/&nbsp;/g,' ').trim();
  useEffect(()=>{if(!loadSavedReplies||!trackSavedReply)return;loadSavedReplies().then(setSavedReplies).catch(()=>setSavedReplies([]));},[loadSavedReplies,trackSavedReply]);
  useEffect(()=>{
    function onClick(event:MouseEvent){
      const target=event.target as Node;
      if(priorityOpen){
        const insideTrigger=priorityRef.current?.contains(target);
        const insidePopover=priorityPopoverRef.current?.contains(target);
        if(!insideTrigger&&!insidePopover)setPriorityOpen(false);
      }
      if(assignOpen){
        const insideTrigger=assignRef.current?.contains(target);
        const insidePopover=assignPopoverRef.current?.contains(target);
        if(!insideTrigger&&!insidePopover)setAssignOpen(false);
      }
    }
    function onKeyDown(event:KeyboardEvent){
      if(event.key==='Escape'){
        setPriorityOpen(false);
        setAssignOpen(false);
      }
    }
    document.addEventListener('mousedown',onClick);
    document.addEventListener('keydown',onKeyDown);
    return()=>{document.removeEventListener('mousedown',onClick);document.removeEventListener('keydown',onKeyDown);};
  },[priorityOpen,assignOpen]);
  const insertSavedReply=(saved:SavedReply)=>{if(drafts.reply.trim()!==''&&!window.confirm('Replace the current draft with this saved reply?'))return;void trackSavedReply?.(saved.id).then(()=>{setDrafts(current=>({...current,reply:renderSavedReply(saved.content,savedReplyContext)}));setSavedRepliesOpen(false);}).catch(reason=>setError(reason instanceof Error?reason.message:'Saved reply insertion could not be recorded.'));};

  return <section className="sbay-conversation">
    <header className="sbay-conversation__header"><div className="sbay-conversation__title"><button type="button" onClick={back} aria-label="Back to tickets">←</button><h1>{ticket.subject}</h1></div><div className="sbay-conversation__toolbar"><span>{context?.permissions.reply?<button type="button" onClick={()=>{setType('reply');setComposerOpen(true);}}>Reply</button>:null}{context?.permissions.internal_note?<button type="button" onClick={()=>{setType('internal_note');setComposerOpen(true);}}>Note</button>:null}{context?.permissions.priority?<div className="sbay-toolbar-field"><button ref={priorityRef} type="button" className="sbay-toolbar-trigger" onClick={()=>{setPriorityOpen(v=>!v);setAssignOpen(false);}} title="Change priority"><i className={`sbay-priority-dot is-${ticket.priority}`}>⚑</i> <span className="sbay-toolbar-label">{ticket.priority}</span></button>{priorityOpen?<div ref={priorityPopoverRef} className="sbay-inline-popover"><button type="button" className={ticket.priority==='normal'?'is-active':''} onClick={()=>{void mutate('priority','normal');setPriorityOpen(false);}}>Normal</button><button type="button" className={ticket.priority==='medium'?'is-active':''} onClick={()=>{void mutate('priority','medium');setPriorityOpen(false);}}>Medium</button><button type="button" className={ticket.priority==='high'?'is-active':''} onClick={()=>{void mutate('priority','high');setPriorityOpen(false);}}>High</button><button type="button" className={ticket.priority==='urgent'?'is-active':''} onClick={()=>{void mutate('priority','urgent');setPriorityOpen(false);}}>Urgent</button></div>:null}</div>:<i className={`sbay-priority-dot is-${ticket.priority}`} title={`${ticket.priority} priority`}>⚑</i>}{context?.permissions.assign?<div className="sbay-toolbar-field"><button ref={assignRef} type="button" className="sbay-toolbar-trigger" onClick={()=>{setAssignOpen(v=>!v);setPriorityOpen(false);}} title="Change assignee"><span className="sbay-toolbar-label">{context?.information.agent||'—'}</span></button>{assignOpen?<div ref={assignPopoverRef} className="sbay-inline-popover"><button type="button" className={!ticket.assigned_agent_id?'is-active':''} onClick={()=>{void mutate('assignment','');setAssignOpen(false);}}>Unassigned</button>{context?.options.agents.map(agent=><button type="button" key={agent.id} className={ticket.assigned_agent_id===agent.id?'is-active':''} onClick={()=>{void mutate('assignment',agent.id);setAssignOpen(false);}}>{agent.name}</button>)}</div>:null}</div>:context?.information.agent?<b title={context.information.agent}>{context.information.agent.charAt(0)}</b>:null}</span><span>{refresh?<button type="button" onClick={()=>void refresh()} aria-label="Reload ticket">↻</button>:null}{context?.permissions.status?(ticket.status==='closed'||ticket.status==='resolved'?<button type="button" onClick={()=>void transition('reopen')}>Reopen</button>:<button type="button" onClick={()=>void transition('close')}>Close</button>):null}<details><summary aria-label="More ticket actions">⋮</summary><div>{context?.permissions.status?<button type="button" onClick={()=>void mutate('state',ticket.state==='trash'?'active':'trash')}>{ticket.state==='trash'?'Restore Ticket':'Move to Trash'}</button>:null}{context?.permissions.delete?<button type="button" onClick={()=>setDeleteConfirmOpen(true)}>🗑️ Delete Ticket</button>:null}</div></details>{deleteConfirmOpen?<div className="sbay-delete-confirm-modal"><div><header><h3>Delete Ticket</h3><button type="button" onClick={()=>setDeleteConfirmOpen(false)} aria-label="Close">×</button></header><p>Are you sure you want to permanently delete this ticket? This action cannot be undone.</p><footer><button type="button" onClick={()=>setDeleteConfirmOpen(false)}>Cancel</button><button type="button" onClick={()=>{setDeleting(true);void permDelete?.().then(()=>{setDeleting(false);setDeleteConfirmOpen(false)})}} disabled={deleting}>{deleting?'Deleting…':'Delete'}</button></footer></div></div>:null}</span></div></header>
    <div className="sbay-conversation__grid"><main className="sbay-conversation__thread">
      {messages.map((message,index)=>{const customer=message.author_type==='customer';const note=message.type==='internal_note';const name=customer?(context?.customer?.name||'Customer'):(message.author_name||'Support team');const avatar=customer?context?.customer?.avatar_url:message.author_avatar_url;return <article className={note?'is-note':customer?'is-customer':'is-agent'} key={message.id}><em>{note?'Note by Agent':customer&&index===0?'Starter':customer?'Customer':'Agent'}</em><header><span className="sbay-message-author"><i>{avatar?<img src={avatar} alt=""/>:name.charAt(0)}</i><strong>{name}</strong></span><time>{new Date(message.created_at.replace(' ','T')).toLocaleString()}</time></header><div className="sbay-rich-content" dangerouslySetInnerHTML={{__html:message.content}} />{context?.attachments.filter(file=>file.message_id===message.id).map(file=><button type="button" className="sbay-message-file" onClick={()=>void openAttachment(file)} key={file.id}>📎 {file.original_name} <small>{Math.ceil(file.file_size/1024)} KB</small></button>)}</article>})}
      {preview?<div className="sbay-attachment-preview" role="dialog" aria-modal="true" aria-label={preview.name}><header><strong>{preview.name}</strong><button type="button" aria-label="Close preview" onClick={()=>{URL.revokeObjectURL(preview.url);setPreview(null);}}>×</button></header>{preview.mime==='application/pdf'?<iframe src={preview.url} title={preview.name}/>:<img src={preview.url} alt={preview.name}/>}</div>:null}
      {composerOpen&&!['resolved','closed'].includes(ticket.status)&&(context?.permissions.reply||context?.permissions.internal_note)?<form className={`sbay-conversation__composer is-${type}`} onSubmit={send}><div className="sbay-composer-tabs">{context?.permissions.reply?<button type="button" className={type==='reply'?'is-active':''} onClick={()=>setType('reply')}>Reply</button>:null}{context?.permissions.internal_note?<button type="button" className={type==='internal_note'?'is-active':''} onClick={()=>setType('internal_note')}>Internal Note</button>:null}</div><RichTextEditor key={type} value={drafts[type]} onChange={value=>setDrafts(current=>({...current,[type]:value}))} disabled={busy} showSavedReplies={type==='reply'&&Boolean(trackSavedReply)} onSavedRepliesClick={()=>setSavedRepliesOpen(true)}/>{type==='reply'?<FilePicker files={files} onChange={setFiles} disabled={busy}/>:null}{error?<p role="alert">{error}</p>:null}<div className="sbay-composer-actions"><button disabled={busy||drafts[type].trim()===''}>{busy?'Sending…':type==='reply'?'Submit Reply':'Add Internal Note'}</button>{type==='reply'?<button type="button" disabled={busy||drafts[type].trim()===''} onClick={event=>void send(event,true)}>Submit & Close Ticket</button>:null}<button type="button" onClick={()=>{setDrafts(current=>({...current,[type]:''}));setFiles([]);setComposerOpen(false);}}>Cancel</button></div></form>:null}
      {savedRepliesOpen?<div className="sbay-insert-saved-replies-modal" role="dialog" aria-modal="true" aria-label="Insert Saved Replies"><div><header><h2>Insert Saved Replies</h2><button type="button" onClick={()=>setSavedRepliesOpen(false)} aria-label="Close">×</button></header>{savedReplies.length?<ul>{savedReplies.map(reply=><li key={reply.id}><button type="button" onClick={()=>insertSavedReply(reply)}><strong>{reply.title}</strong><span>{savedReplyPreview(reply.content).slice(0,180)}</span></button></li>)}</ul>:<p>No saved replies are available.</p>}</div></div>:null}
    </main>{context?<TicketDetailSidebar ticket={ticket} context={context} statusLabels={statusLabels} mutate={mutate} transition={transition} openCustomer={openCustomer}/>:null}</div>
  </section>;
}
