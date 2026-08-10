import { FormEvent, useCallback, useEffect, useState } from 'react';

export interface WorkspaceTicket {
  id: number;
  track_id: string;
  subject: string;
  status: string;
  state?: string;
  priority: string;
  assigned_agent_id?: number | null;
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
  orderby: string;
  order: string;
}

interface TicketWorkspaceProps {
  mode: 'staff' | 'customer';
  load: (query: TicketQueryParams) => Promise<TicketPage>;
  openTicket: (ticket: WorkspaceTicket) => void;
  createTicket?: () => void;
}

const defaults: TicketQueryParams = {
  page: 1, perPage: 20, search: '', status: '', state: 'active', priority: '',
  assignment: '', orderby: 'updated_at', order: 'desc',
};

export function TicketWorkspace({ mode, load, openTicket, createTicket }: TicketWorkspaceProps) {
  const [query, setQuery] = useState(defaults);
  const [draftSearch, setDraftSearch] = useState('');
  const [result, setResult] = useState<TicketPage | null>(null);
  const [selected, setSelected] = useState<number[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [refresh, setRefresh] = useState(0);

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
  const allSelected = Boolean(result?.items.length) && result?.items.every((ticket) => selected.includes(ticket.id));
  const first = result && result.total ? ((result.page - 1) * query.perPage) + 1 : 0;
  const last = result ? Math.min(result.page * query.perPage, result.total) : 0;

  return (
    <section className={`sbay-ticket-workspace sbay-ticket-workspace--${mode}`}>
      <div className="sbay-ticket-tabs" role="tablist" aria-label="Ticket queues">
        <button className={!query.assignment && query.state !== 'trash' ? 'is-active' : ''} onClick={() => update({ assignment: '', state: 'active', status: '' })}>▤ All Tickets</button>
        {mode === 'staff' ? <button className={query.assignment === 'mine' ? 'is-active' : ''} onClick={() => update({ assignment: 'mine', state: 'active', status: '' })}>♙ My Tickets</button> : null}
        {mode === 'staff' ? <button className={query.assignment === 'unassigned' ? 'is-active' : ''} onClick={() => update({ assignment: 'unassigned', state: 'active', status: '' })}>◉ Unassigned</button> : null}
        {mode === 'staff' ? <button className={query.state === 'trash' ? 'is-active' : ''} onClick={() => update({ assignment: '', state: 'trash', status: '' })}>♲ Trashed</button> : null}
        <div className="sbay-ticket-tabs__actions">
          <button aria-label="Refresh tickets" onClick={() => setRefresh((value) => value + 1)}>↻</button>
          {createTicket ? <button className="is-primary" onClick={createTicket}>＋ Add Ticket</button> : null}
        </div>
      </div>

      <div className="sbay-ticket-filters">
        <div className="sbay-ticket-statuses">
          {['active', 'inactive'].map((state) => <button className={query.state === state && !query.status ? 'is-active' : ''} key={state} onClick={() => update({ state, status: '' })}>{state[0].toUpperCase() + state.slice(1)}</button>)}
          <button className={query.status === 'closed' ? 'is-active' : ''} onClick={() => update({ state: '', status: 'closed' })}>Closed</button>
          <button className={!query.state && !query.status ? 'is-active' : ''} onClick={() => update({ state: '', status: '' })}>All</button>
        </div>
        <form onSubmit={submitSearch}><input aria-label="Search tickets" onChange={(event) => setDraftSearch(event.target.value)} placeholder="Search keyword or ticket ID" value={draftSearch} /><button aria-label="Submit search">⌕</button></form>
        <div className="sbay-ticket-filter-row">
          <select aria-label="Priority" value={query.priority} onChange={(event) => update({ priority: event.target.value })}><option value="">All Priorities</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
          <select aria-label="Sort tickets" value={`${query.orderby}:${query.order}`} onChange={(event) => { const [orderby, order] = event.target.value.split(':'); update({ orderby, order }); }}><option value="updated_at:desc">Updated (Newest First)</option><option value="updated_at:asc">Updated (Oldest First)</option><option value="created_at:desc">Created (Newest First)</option><option value="priority:desc">Priority (Highest First)</option></select>
          <button disabled={query.search === '' && query.priority === '' && query.status === '' && query.state === 'active' && query.assignment === ''} onClick={() => { setDraftSearch(''); setQuery(defaults); }}>Reset Filters</button>
        </div>
      </div>

      {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
      <div className="sbay-ticket-table">
        <div className="sbay-ticket-row sbay-ticket-row--header">
          {mode === 'staff' ? <input aria-label="Select all tickets" checked={allSelected} onChange={() => setSelected(allSelected ? [] : result?.items.map((ticket) => ticket.id) ?? [])} type="checkbox" /> : <span />}
          <span>Title</span><span>Priority</span>{mode === 'staff' ? <span>Agent</span> : null}<span>Date</span>
        </div>
        {!result ? <p className="sbay-ticket-empty">Loading tickets…</p> : result.items.length === 0 ? <p className="sbay-ticket-empty">No tickets match these filters.</p> : result.items.map((ticket) => (
          <div className="sbay-ticket-row" key={ticket.id}>
            {mode === 'staff' ? <input aria-label={`Select ${ticket.subject}`} checked={selected.includes(ticket.id)} onChange={() => setSelected((current) => current.includes(ticket.id) ? current.filter((id) => id !== ticket.id) : [...current, ticket.id])} type="checkbox" /> : <span className="sbay-ticket-avatar">{ticket.subject.charAt(0)}</span>}
            <button className="sbay-ticket-title" onClick={() => openTicket(ticket)}><strong>{ticket.subject}</strong><span><i>{ticket.status}</i> #{ticket.track_id}</span></button>
            <span className={`sbay-ticket-priority sbay-ticket-priority--${ticket.priority}`}>{ticket.priority}</span>
            {mode === 'staff' ? <span>{ticket.assigned_agent_id ? `#${ticket.assigned_agent_id}` : 'Unassigned'}</span> : null}
            <span>{new Date(ticket.updated_at || ticket.created_at).toLocaleDateString()}</span>
          </div>
        ))}
        <footer>
          {mode === 'staff' ? <div><select aria-label="Bulk actions" disabled={!selected.length}><option>Bulk Actions</option></select><button disabled>Apply</button></div> : <span />}
          <span>Showing {first}–{last} of {result?.total ?? 0}</span>
        </footer>
      </div>
      <nav className="sbay-ticket-pagination" aria-label="Ticket pagination"><button disabled={!result || result.page <= 1} onClick={() => update({ page: query.page - 1 })}>‹</button><span>{result?.page ?? 1}</span><button disabled={!result || result.page >= result.totalPages} onClick={() => update({ page: query.page + 1 })}>›</button></nav>
    </section>
  );
}
