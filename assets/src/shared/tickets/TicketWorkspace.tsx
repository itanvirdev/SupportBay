import { FormEvent, useCallback, useEffect, useState } from 'react';

export interface WorkspaceTicket {
  id: number;
  track_id: string;
  subject: string;
  status: string;
  state?: string;
  priority: string;
  assigned_agent_id?: number | null;
  agent_name?: string | null;
  customer_name?: string | null;
  customer_avatar_url?: string | null;
  department_name?: string | null;
  reply_count?: number;
  needs_reply?: boolean;
  sla_state?: 'disabled'|'met'|'on_track'|'due_soon'|'breached';
  sla_target_minutes?: number;
  sla_due_at?: string | null;
  sla_remaining_minutes?: number | null;
  updated_at: string | null;
  created_at: string;
}

export interface TicketPage {
  items: WorkspaceTicket[];
  page: number;
  total: number;
  totalPages: number;
}

export interface TicketQueryParams {
  page: number;
  perPage: number;
  search: string;
  status: string;
  state: string;
  priority: string;
  assignment: string;
  agentId: string;
  departmentId: string;
  needReply: boolean;
  slaState: string;
  orderby: string;
  order: string;
}

interface TicketWorkspaceProps {
  mode: 'staff' | 'customer';
  load: (query: TicketQueryParams) => Promise<TicketPage>;
  openTicket: (ticket: WorkspaceTicket) => void;
  createTicket?: () => void;
  options?: {agents:Array<{id:number;name:string}>;departments:Array<{id:number;name:string}>};
  bulk?: (ticketIds: number[], action: string, value: string) => Promise<void>;
  openCustomers?: () => void;
  openVerifications?: () => void;
}

export function ticketQueryString(query: TicketQueryParams): string {
  return new URLSearchParams({
    page: String(query.page),
    per_page: String(query.perPage),
    search: query.search,
    status: query.status,
    state: query.state,
    priority: query.priority,
    assignment: query.assignment,
    agent_id: query.agentId,
    department_id: query.departmentId,
    need_reply: String(query.needReply),
    sla_state: query.slaState,
    orderby: query.orderby,
    order: query.order,
  }).toString();
}

const defaults: TicketQueryParams = {
  page: 1, perPage: 20, search: '', status: '', state: 'active', priority: '',
  assignment: '', agentId:'', departmentId:'', needReply:false, slaState:'', orderby: 'updated_at', order: 'desc',
};

const slaLabel = (ticket: WorkspaceTicket) => {
  if (ticket.sla_state === 'breached') return 'SLA breached';
  if (ticket.sla_state === 'due_soon') return `SLA due soon`;
  if (ticket.sla_state === 'on_track') return 'SLA on track';
  if (ticket.sla_state === 'met') return 'SLA met';
  return null;
};

export function TicketWorkspace({ mode, load, openTicket, createTicket, options, bulk, openCustomers, openVerifications }: TicketWorkspaceProps) {
  const [query, setQuery] = useState(defaults);
  const [draftSearch, setDraftSearch] = useState('');
  const [result, setResult] = useState<TicketPage | null>(null);
  const [selected, setSelected] = useState<number[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [refresh, setRefresh] = useState(0);
  const [bulkAction, setBulkAction] = useState('');
  const [bulkPending, setBulkPending] = useState(false);

  const update = (changes: Partial<TicketQueryParams>) => setQuery((current) => ({ ...current, ...changes, page: changes.page ?? 1 }));
  const reload = useCallback(() => {
    setError(null);
    load(query).then(setResult).catch((reason: unknown) => {
      setError(reason instanceof Error ? reason.message : 'Tickets could not be loaded.');
    });
  }, [load, query]);

  useEffect(() => { reload(); }, [reload, refresh]);
  useEffect(() => { setSelected([]); }, [result]);

  const submitSearch = (event: FormEvent) => {
    event.preventDefault();
    update({ search: draftSearch.trim() });
  };
  const applyBulkAction = async () => {
    if (!bulk || !selected.length || !bulkAction) return;
    const [action, value = ''] = bulkAction.split(':');
    setBulkPending(true);
    setError(null);
    try {
      await bulk(selected, action, value);
      setSelected([]);
      setBulkAction('');
      setRefresh((current) => current + 1);
    } catch (reason: unknown) {
      setError(reason instanceof Error ? reason.message : 'Bulk ticket action failed.');
    } finally {
      setBulkPending(false);
    }
  };
  const allSelected = Boolean(result?.items.length) && result?.items.every((ticket) => selected.includes(ticket.id));
  const first = result && result.total ? ((result.page - 1) * query.perPage) + 1 : 0;
  const last = result ? Math.min(result.page * query.perPage, result.total) : 0;

  return (
    <section className={`sbay-ticket-workspace sbay-ticket-workspace--${mode}`}>
      <div className="sbay-ticket-tabs" role="tablist" aria-label="Ticket queues">
        <button className={!query.assignment && query.state !== 'trash' ? 'is-active' : ''} onClick={() => update({ assignment: '', agentId: '', state: 'active', status: '' })}>▤ All Tickets</button>
        {mode === 'staff' ? <button className={query.assignment === 'mine' ? 'is-active' : ''} onClick={() => update({ assignment: 'mine', agentId: '', state: 'active', status: '' })}>♙ My Tickets</button> : null}
        {mode === 'staff' ? <button className={query.assignment === 'unassigned' ? 'is-active' : ''} onClick={() => update({ assignment: 'unassigned', agentId: '', state: 'active', status: '' })}>◉ Unassigned</button> : null}
        {mode === 'staff' ? <button className={query.state === 'trash' ? 'is-active' : ''} onClick={() => update({ assignment: '', state: 'trash', status: '' })}>♲ Trashed</button> : null}
        <div className="sbay-ticket-tabs__actions">
          <button aria-label="Refresh tickets" onClick={() => setRefresh((value) => value + 1)}>↻</button>
          {openCustomers ? <button onClick={openCustomers}>Customers</button> : null}
          {openVerifications ? <button onClick={openVerifications}>Verifications</button> : null}
          {createTicket ? <button className="is-primary" onClick={createTicket}>＋ Add Ticket</button> : null}
        </div>
      </div>

      <div className="sbay-ticket-filters">
        <div className="sbay-ticket-statuses">
          {['active', 'inactive'].map((state) => <button className={query.state === state && !query.status ? 'is-active' : ''} key={state} onClick={() => update({ state, status: '' })}>{state[0].toUpperCase() + state.slice(1)}</button>)}
          <button className={query.status === 'closed' ? 'is-active' : ''} onClick={() => update({ state: '', status: 'closed' })}>Closed</button>
          <button className={!query.state && !query.status ? 'is-active' : ''} onClick={() => update({ state: '', status: '' })}>All</button>
          {mode==='staff'?<button className={query.needReply?'is-active':''} onClick={()=>update({needReply:!query.needReply})}>Need Reply</button>:null}
        </div>
        <form onSubmit={submitSearch}><input aria-label="Search tickets" onChange={(event) => setDraftSearch(event.target.value)} placeholder="Search keyword or ticket ID" value={draftSearch} /><button aria-label="Submit search">⌕</button></form>
        <div className="sbay-ticket-filter-row">
          {mode==='staff'?<select aria-label="Department" value={query.departmentId} onChange={event=>update({departmentId:event.target.value})}><option value="">All Departments</option>{options?.departments.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select>:null}
          {mode==='staff'?<select aria-label="Agent" value={query.agentId} onChange={event=>update({agentId:event.target.value,assignment:''})}><option value="">All Agents</option>{options?.agents.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select>:null}
          {mode==='staff'?<select aria-label="SLA state" value={query.slaState} onChange={event=>update({slaState:event.target.value})}><option value="">All SLA States</option><option value="breached">SLA Breached</option><option value="due_soon">SLA Due Soon</option><option value="on_track">SLA On Track</option><option value="met">SLA Met</option></select>:null}
          <select aria-label="Priority" value={query.priority} onChange={(event) => update({ priority: event.target.value })}><option value="">All Priorities</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
          <select aria-label="Sort tickets" value={`${query.orderby}:${query.order}`} onChange={(event) => { const [orderby, order] = event.target.value.split(':'); update({ orderby, order }); }}><option value="updated_at:desc">Updated (Newest First)</option><option value="sla_due:asc">SLA Due First</option><option value="need_reply:desc">Need Reply First</option><option value="updated_at:asc">Updated (Oldest First)</option><option value="created_at:desc">Created (Newest First)</option><option value="priority:desc">Priority (Highest First)</option></select>
          <button disabled={query.search === '' && query.priority === '' && query.status === '' && query.state === 'active' && query.assignment === '' && query.agentId === '' && query.departmentId === '' && query.slaState === '' && !query.needReply} onClick={() => { setDraftSearch(''); setQuery(defaults); }}>Reset Filters</button>
        </div>
      </div>

      {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
      <div className="sbay-ticket-table">
        <div className="sbay-ticket-row sbay-ticket-row--header">
          {mode === 'staff' ? <input aria-label="Select all tickets" checked={allSelected} onChange={() => setSelected(allSelected ? [] : result?.items.map((ticket) => ticket.id) ?? [])} type="checkbox" /> : <span />}
          <span>Title</span><span>{mode==='staff'?'Reply':'Priority'}</span>{mode === 'staff' ? <span>Agent</span> : null}<span>Date</span>
        </div>
        {!result ? <p className="sbay-ticket-empty">Loading tickets…</p> : result.items.length === 0 ? <p className="sbay-ticket-empty">No tickets match these filters.</p> : result.items.map((ticket) => (
          <div className="sbay-ticket-row" key={ticket.id}>
            {mode === 'staff' ? <input aria-label={`Select ${ticket.subject}`} checked={selected.includes(ticket.id)} onChange={() => setSelected((current) => current.includes(ticket.id) ? current.filter((id) => id !== ticket.id) : [...current, ticket.id])} type="checkbox" /> : <span className="sbay-ticket-avatar">{ticket.subject.charAt(0)}</span>}
            <button className="sbay-ticket-title" onClick={() => openTicket(ticket)}><strong>{ticket.subject} {ticket.customer_name?<small>by {ticket.customer_name}</small>:null}</strong><span><i>{ticket.status}</i> #{ticket.track_id} · {ticket.department_name||'No department'} · {ticket.priority}{mode==='staff'&&slaLabel(ticket)?<em className={`sbay-sla-badge is-${ticket.sla_state}`} title={ticket.sla_due_at?`Due ${new Date(ticket.sla_due_at.replace(' ','T')).toLocaleString()}`:undefined}>{slaLabel(ticket)}</em>:null}</span></button>
            {mode==='staff'?<span>{ticket.reply_count??0}{ticket.needs_reply?<i className="sbay-need-reply">Need Reply</i>:null}</span>:<span className={`sbay-ticket-priority sbay-ticket-priority--${ticket.priority}`}>{ticket.priority}</span>}
            {mode === 'staff' ? <span>{ticket.agent_name||'Unassigned'}</span> : null}
            <span>{new Date(ticket.updated_at || ticket.created_at).toLocaleDateString()}</span>
          </div>
        ))}
        <footer>
          {mode === 'staff' ? <div><select aria-label="Bulk actions" disabled={!selected.length || bulkPending} value={bulkAction} onChange={(event) => setBulkAction(event.target.value)}><option value="">Bulk Actions</option><optgroup label="Assignment"><option value="assignment:me">Assign to Me</option><option value="assignment:0">Unassign</option>{options?.agents.map((agent) => <option value={`assignment:${agent.id}`} key={`agent-${agent.id}`}>Assign to {agent.name}</option>)}</optgroup><optgroup label="Department">{options?.departments.map((department) => <option value={`department:${department.id}`} key={`department-${department.id}`}>Move to {department.name}</option>)}</optgroup><optgroup label="Priority"><option value="priority:normal">Priority: Normal</option><option value="priority:medium">Priority: Medium</option><option value="priority:high">Priority: High</option><option value="priority:urgent">Priority: Urgent</option></optgroup><optgroup label="State"><option value="state:trash">Move to Trash</option><option value="state:active">Restore</option></optgroup></select><button disabled={!bulkAction || bulkPending} onClick={applyBulkAction}>{bulkPending ? 'Applying…' : 'Apply'}</button></div> : <span />}
          <span>Showing {first}–{last} of {result?.total ?? 0}</span>
        </footer>
      </div>
      <nav className="sbay-ticket-pagination" aria-label="Ticket pagination"><button disabled={!result || result.page <= 1} onClick={() => update({ page: query.page - 1 })}>‹</button><span>{result?.page ?? 1}</span><button disabled={!result || result.page >= result.totalPages} onClick={() => update({ page: query.page + 1 })}>›</button></nav>
    </section>
  );
}
