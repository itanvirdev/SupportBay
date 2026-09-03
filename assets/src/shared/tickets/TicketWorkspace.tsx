import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Preloader } from '../components/Preloader';
import { RequestState } from '../components/RequestState';
import { TicketQueueTabs } from './workspace/TicketQueueTabs';
import { TicketRow, TicketTableHeader } from './workspace/TicketRow';
import { TicketPagination } from './workspace/TicketPagination';
import type { TicketPage, TicketQueryParams, TicketWorkspaceOptions, WorkspaceTicket } from './workspace/types';
import { TagMultiSelect } from './TagMultiSelect';
export type { TicketPage, TicketQueryParams, WorkspaceTicket } from './workspace/types';

interface TicketWorkspaceProps {
  mode: 'staff' | 'customer';
  load: (query: TicketQueryParams) => Promise<TicketPage>;
  openTicket: (ticket: WorkspaceTicket) => void;
  createTicket?: () => void;
  options?: TicketWorkspaceOptions;
  bulk?: (ticketIds: number[], action: string, value: unknown) => Promise<{updated:number;failed:number}>;
  autoRefresh?: {enabled:boolean;interval:number};
  needReplyFilterEnabled?: boolean;
  statusLabels?:Record<string,string>;
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
    category_id: query.categoryId,
    tag_ids: query.tagId,
    custom_field_id: query.customFieldId,
    custom_field_value: query.customFieldValue,
    need_reply: String(query.needReply),
    orderby: query.orderby,
    order: query.order,
  }).toString();
}

const defaults: TicketQueryParams = {
  page: 1, perPage: 20, search: '', status: '', state: 'active', priority: '',
  assignment: '', agentId:'', categoryId:'', tagId:'', customFieldId:'', customFieldValue:'', needReply:false, orderby: 'updated_at', order: 'desc',
};

export function TicketWorkspace({ mode, load, openTicket, createTicket, options, bulk, autoRefresh, needReplyFilterEnabled = true, statusLabels = {} }: TicketWorkspaceProps) {
  const [query, setQuery] = useState(defaults);
  const [draftSearch, setDraftSearch] = useState('');
  const [result, setResult] = useState<TicketPage | null>(null);
  const [selected, setSelected] = useState<number[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [bulkNotice, setBulkNotice] = useState<string | null>(null);
  const [refresh, setRefresh] = useState(0);
  const [bulkAction, setBulkAction] = useState('');
  const [bulkCustomFieldValue, setBulkCustomFieldValue] = useState('');
  const [bulkTagIds, setBulkTagIds] = useState<number[]>([]);
  const [bulkPending, setBulkPending] = useState(false);
  const [loading, setLoading] = useState(true);
  const requestId=useRef(0);
  const filterCustomFields = useMemo(
    () => options?.custom_fields.filter((field) =>
      !query.categoryId
      || field.category_ids.length === 0
      || field.category_ids.includes(Number(query.categoryId))
    ) ?? [],
    [options, query.categoryId],
  );
  const selectedCustomField = options?.custom_fields.find((field) => field.id === Number(query.customFieldId));
  const bulkCustomFieldId = bulkAction.startsWith('custom_field:') ? Number(bulkAction.split(':')[1]) : 0;
  const bulkCustomField = options?.custom_fields.find((field) => field.id === bulkCustomFieldId);
  const showCategories = mode === 'staff'
    ? (options?.categories.length ?? 0) > 0
    : Boolean(result?.showCategories);
  const showTags = mode === 'staff' && (options?.tags.length ?? 0) > 0;
  const showCustomFields = mode === 'staff' && (options?.custom_fields.length ?? 0) > 0;

  const update = (changes: Partial<TicketQueryParams>) => setQuery((current) => ({ ...current, ...changes, page: changes.page ?? 1 }));
  const toggleNeedReply = () => setQuery((current) => {
    const enabled = !current.needReply;
    return {
      ...current,
      needReply: enabled,
      orderby: !enabled && current.orderby === 'need_reply' ? 'updated_at' : current.orderby,
      order: !enabled && current.orderby === 'need_reply' ? 'desc' : current.order,
      page: 1,
    };
  });
  const reload = useCallback((background = false) => {
    const currentRequest=++requestId.current;
    if (!background) setLoading(true);
    setError(null);
    load(query).then(response=>{if(currentRequest===requestId.current)setResult(response);}).catch((reason: unknown) => {
      if(currentRequest===requestId.current)setError(reason instanceof Error ? reason.message : 'Tickets could not be loaded.');
    }).finally(() => { if (!background&&currentRequest===requestId.current) setLoading(false); });
  }, [load, query]);

  useEffect(() => { reload(false); }, [reload, refresh]);
  useEffect(()=>{
    if(!autoRefresh?.enabled)return;
    const interval=window.setInterval(()=>{
      if(document.hidden||bulkPending||selected.length>0)return;
      reload(true);
    },Math.max(5,autoRefresh.interval)*1000);
    return()=>window.clearInterval(interval);
  },[autoRefresh?.enabled,autoRefresh?.interval,bulkPending,reload,selected.length]);
  useEffect(() => { setSelected([]); }, [result]);

  const submitSearch = (event: FormEvent) => {
    event.preventDefault();
    update({ search: draftSearch.trim() });
  };
  const applyBulkAction = async () => {
    if (!bulk || !selected.length || !bulkAction) return;
    const [action, value = ''] = bulkAction.split(':');
    const payload = action === 'custom_field'
      ? { field_id: Number(value), value: bulkCustomFieldValue }
      : value;
    setBulkPending(true);
    setError(null);
    setBulkNotice(null);
    try {
      const outcomes = action==='tags_add'
        ? await Promise.all(bulkTagIds.map(tagId=>bulk(selected,'tag_add',tagId)))
        : [await bulk(selected, action, payload)];
      const outcome = outcomes.reduce((total,current)=>({updated:Math.max(total.updated,current.updated),failed:total.failed+current.failed}),{updated:0,failed:0});
      setBulkNotice(`Bulk action updated ${outcome.updated} ticket${outcome.updated===1?'':'s'}${outcome.failed?`; ${outcome.failed} failed`:'.'}`);
      setSelected([]);
      setBulkAction('');
      setBulkCustomFieldValue('');
      setBulkTagIds([]);
      setRefresh((current) => current + 1);
    } catch (reason: unknown) {
      setError(reason instanceof Error ? reason.message : 'Bulk ticket action failed.');
    } finally {
      setBulkPending(false);
    }
  };
  const allSelected = Boolean(result?.items.length && result.items.every((ticket) => selected.includes(ticket.id)));
  const first = result && result.total ? ((result.page - 1) * query.perPage) + 1 : 0;
  const last = result ? Math.min(result.page * query.perPage, result.total) : 0;
  const bulkCustomFieldControl = bulkCustomField?.type === 'select'
    ? <select aria-label="Bulk custom field value" value={bulkCustomFieldValue} onChange={(event)=>setBulkCustomFieldValue(event.target.value)}><option value="">Clear value</option>{bulkCustomField.options.map(option=><option key={option}>{option}</option>)}</select>
    : bulkCustomField?.type === 'checkbox'
      ? <select aria-label="Bulk custom field value" value={bulkCustomFieldValue} onChange={(event)=>setBulkCustomFieldValue(event.target.value)}><option value="">Clear value</option><option value="1">Checked</option><option value="0">Not checked</option></select>
      : bulkCustomField
        ? <input aria-label="Bulk custom field value" type={['number','date','email','url'].includes(bulkCustomField.type)?bulkCustomField.type:'text'} value={bulkCustomFieldValue} onChange={(event)=>setBulkCustomFieldValue(event.target.value)} placeholder="Leave empty to clear"/>
        : null;

  return (
    <section className={`sbay-ticket-workspace sbay-ticket-workspace--${mode}`}>
      <TicketQueueTabs mode={mode} query={query} update={update} refresh={()=>setRefresh(value=>value+1)} createTicket={createTicket}/>
      {bulkNotice?<p className="sbay-ticket-bulk-notice" role="status">{bulkNotice}</p>:null}

      <div className="sbay-ticket-filters">
        <div className="sbay-ticket-statuses">
          {['active', 'inactive'].map((state) => <button className={query.state === state && !query.status ? 'is-active' : ''} key={state} onClick={() => update({ state, status: '' })}>{state[0].toUpperCase() + state.slice(1)}</button>)}
          <button className={query.status === 'closed' ? 'is-active' : ''} onClick={() => update({ state: '', status: 'closed' })}>{statusLabels.closed??'Closed'}</button>
          <button className={!query.state && !query.status ? 'is-active' : ''} onClick={() => update({ state: '', status: '' })}>All</button>
          {mode==='staff'&&needReplyFilterEnabled?<label className="sbay-ticket-need-reply-toggle"><input type="checkbox" role="switch" checked={query.needReply} onChange={toggleNeedReply}/><span aria-hidden="true"/><strong>Need Reply</strong></label>:null}
        </div>
        <form onSubmit={submitSearch}><input aria-label="Search tickets" onChange={(event) => setDraftSearch(event.target.value)} placeholder="Search keyword or ticket ID" value={draftSearch} /><button aria-label="Submit search">⌕</button></form>
        <div className="sbay-ticket-filter-row">
          {showCategories?<select aria-label="Category" value={query.categoryId} onChange={event=>update({categoryId:event.target.value,customFieldId:'',customFieldValue:''})}><option value="">All Categories</option><option value="uncategorized">Uncategorized</option>{options?.categories.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select>:null}
          {showTags?<TagMultiSelect label="Tag Filter" tags={options?.tags??[]} value={query.tagId?query.tagId.split(',').map(Number).filter(Boolean):[]} change={ids=>update({tagId:ids.join(',')})}/>:null}
          {showCustomFields?<select aria-label="Custom field" value={query.customFieldId} onChange={event=>update({customFieldId:event.target.value,customFieldValue:''})}><option value="">All Custom Fields</option>{filterCustomFields.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select>:null}
          {mode==='staff'&&query.customFieldId?(selectedCustomField?.type==='select'?<select aria-label="Custom field value" value={query.customFieldValue} onChange={event=>update({customFieldValue:event.target.value})}><option value="">Any Value</option>{selectedCustomField.options.map(option=><option key={option}>{option}</option>)}</select>:selectedCustomField?.type==='checkbox'?<select aria-label="Custom field value" value={query.customFieldValue} onChange={event=>update({customFieldValue:event.target.value})}><option value="">Any Value</option><option value="1">Checked</option><option value="0">Not Checked</option></select>:<input aria-label="Custom field value" value={query.customFieldValue} onChange={event=>update({customFieldValue:event.target.value})} placeholder="Exact custom field value"/>):null}
          {mode==='staff'?<select aria-label="Agent" value={query.agentId} onChange={event=>update({agentId:event.target.value,assignment:''})}><option value="">All Agents</option>{options?.agents.map(item=><option value={item.id} key={item.id}>{item.name}</option>)}</select>:null}
          <select aria-label="Priority" value={query.priority} onChange={(event) => update({ priority: event.target.value })}><option value="">All Priorities</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
          <select aria-label="Sort tickets" value={`${query.orderby}:${query.order}`} onChange={(event) => { const [orderby, order] = event.target.value.split(':'); update({ orderby, order }); }}><option value="updated_at:desc">Updated (Newest First)</option><option value="updated_at:asc">Updated (Oldest First)</option><option value="created_at:desc">Created (Newest First)</option><option value="priority:desc">Priority (Highest First)</option></select>
          <button disabled={query.search === '' && query.priority === '' && query.status === '' && query.state === 'active' && query.assignment === '' && query.agentId === '' && query.categoryId === '' && query.tagId === '' && query.customFieldId === '' && !query.needReply} onClick={() => { setDraftSearch(''); setQuery(defaults); }}>Reset Filters</button>
        </div>
      </div>

      {error && result ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
      <div className="sbay-ticket-table">
        <TicketTableHeader mode={mode} allSelected={allSelected} toggleAll={()=>setSelected(allSelected?[]:result?.items.map(ticket=>ticket.id)??[])}/>
        {loading ? <Preloader label="Loading tickets…" /> : error && !result ? <RequestState compact title="Tickets could not be loaded" message={error} retry={() => reload(false)} /> : !result || result.items.length === 0 ? <RequestState compact title={query.search || query.priority || query.status || query.assignment || query.agentId || query.categoryId || query.tagId || query.customFieldId || query.needReply ? 'No matching tickets' : 'No tickets yet'} message={query.search || query.priority || query.status || query.assignment || query.agentId || query.categoryId || query.tagId || query.customFieldId || query.needReply ? 'Adjust or reset the filters to see other conversations.' : mode === 'customer' ? 'Your support conversations will appear here after you create a ticket.' : 'New customer conversations will appear here.'} action={query.search || query.priority || query.status || query.assignment || query.agentId || query.categoryId || query.tagId || query.customFieldId || query.needReply ? () => { setDraftSearch(''); setQuery(defaults); } : createTicket} actionLabel={query.search || query.priority || query.status || query.assignment || query.agentId || query.categoryId || query.tagId || query.customFieldId || query.needReply ? 'Reset filters' : createTicket ? 'Create ticket' : undefined} /> : result.items.map((ticket) => (
          <TicketRow key={ticket.id} mode={mode} ticket={ticket} selected={selected.includes(ticket.id)} toggle={()=>setSelected(current=>current.includes(ticket.id)?current.filter(id=>id!==ticket.id):[...current,ticket.id])} open={()=>openTicket(ticket)} showCategories={showCategories} statusLabels={statusLabels}/>
        ))}
        <footer>
          {mode === 'staff' ? <div><select aria-label="Bulk actions" disabled={!selected.length || bulkPending} value={bulkAction} onChange={(event) => {setBulkAction(event.target.value);setBulkCustomFieldValue('');setBulkTagIds([]);}}><option value="">Bulk Actions</option><optgroup label="Assignment"><option value="assignment:me">Assign to Me</option><option value="assignment:0">Unassign</option>{options?.agents.map((agent) => <option value={`assignment:${agent.id}`} key={`agent-${agent.id}`}>Assign to {agent.name}</option>)}</optgroup>{showCategories?<optgroup label="Category"><option value="category:0">Clear Category</option>{options?.categories.map((category) => <option value={`category:${category.id}`} key={`category-${category.id}`}>Category: {category.name}</option>)}</optgroup>:null}{showTags?<optgroup label="Tags"><option value="tags_add">Add Tags…</option>{options?.tags.map(tag=><option value={`tag_remove:${tag.id}`} key={`tag-remove-${tag.id}`}>Remove tag: {tag.name}</option>)}</optgroup>:null}{showCustomFields?<optgroup label="Custom Fields">{options?.custom_fields.map(field=><option value={`custom_field:${field.id}`} key={`custom-field-${field.id}`}>Set field: {field.name}</option>)}</optgroup>:null}<optgroup label="Priority"><option value="priority:normal">Priority: Normal</option><option value="priority:medium">Priority: Medium</option><option value="priority:high">Priority: High</option><option value="priority:urgent">Priority: Urgent</option></optgroup><optgroup label="State"><option value="state:trash">Move to Trash</option><option value="state:active">Restore</option></optgroup></select>{bulkAction==='tags_add'?<TagMultiSelect tags={options?.tags??[]} value={bulkTagIds} change={setBulkTagIds}/>:bulkCustomFieldControl}<button disabled={!bulkAction || bulkPending || (bulkAction==='tags_add'&&!bulkTagIds.length)} onClick={applyBulkAction}>{bulkPending ? 'Applying…' : 'Apply'}</button></div> : <span />}
          <span>Showing {first}–{last} of {result?.total ?? 0}</span>
        </footer>
      </div>
      <TicketPagination page={result?.page??1} totalPages={result?.totalPages??1} previous={()=>update({page:query.page-1})} next={()=>update({page:query.page+1})}/>
    </section>
  );
}
