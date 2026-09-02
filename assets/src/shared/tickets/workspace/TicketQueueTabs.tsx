import type { TicketQueryParams, WorkspaceMode } from './types';

interface Props {mode:WorkspaceMode;query:TicketQueryParams;update:(changes:Partial<TicketQueryParams>)=>void;refresh:()=>void;createTicket?:()=>void}

export function TicketQueueTabs({mode,query,update,refresh,createTicket}:Props) {
  return <div className="sbay-ticket-tabs" role="tablist" aria-label="Ticket queues">
    <button className={!query.assignment&&query.state!=='trash'?'is-active':''} onClick={()=>update({assignment:'',agentId:'',state:'active',status:''})}>▤ All Tickets</button>
    {mode==='staff'?<button className={query.assignment==='mine'?'is-active':''} onClick={()=>update({assignment:'mine',agentId:'',state:'active',status:''})}>♙ My Tickets</button>:null}
    {mode==='staff'?<button className={query.assignment==='unassigned'?'is-active':''} onClick={()=>update({assignment:'unassigned',agentId:'',state:'active',status:''})}>◉ Unassigned</button>:null}
    {mode==='staff'?<button className={query.state==='trash'?'is-active':''} onClick={()=>update({assignment:'',state:'trash',status:''})}>♲ Trashed</button>:null}
    <div className="sbay-ticket-tabs__actions"><button aria-label="Refresh tickets" onClick={refresh}>↻</button>{createTicket?<button className="is-primary" onClick={createTicket}>＋ Add Ticket</button>:null}</div>
  </div>;
}
