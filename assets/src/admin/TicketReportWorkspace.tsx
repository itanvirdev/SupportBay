import { FormEvent, useEffect, useMemo, useState } from 'react';
import { adminDownloadFile, adminGet } from './api';
import { getAdminConfig } from './config';
import { Preloader } from '../shared/components/Preloader';

interface TicketMetricRow { tickets: number; responses: number; need_reply: number; closed: number; }
interface TicketReport {
  range: { from: string; to: string };
  summary: TicketMetricRow & { resolved: number; average_first_response_minutes: number; sla:{enabled:boolean;targets:Record<string,number>;within_target:number;breached:number;awaiting_within_target:number}; response_bands:{under_1h:number;from_1h_to_4h:number;from_4h_to_24h:number;over_24h:number;no_response:number} };
  daily: Array<TicketMetricRow & { date: string }>;
  departments: Array<TicketMetricRow & { department: string }>;
  categories: Array<TicketMetricRow & { category: string }>;
  tags: Array<TicketMetricRow & { tag: string }>;
  custom_fields: Array<TicketMetricRow & { value: string }>;
  agents: Array<TicketMetricRow & { agent: string }>;
}
interface ReportCustomField { id:number;name:string;type:string;options:string[];department_id:number|null }
interface ReportOptions { departments: Array<{id:number;name:string}>; categories: Array<{id:number;name:string;department_id:number|null}>; tags:Array<{id:number;name:string}>; custom_fields:ReportCustomField[]; agents: Array<{id:number;name:string}>; }

const isoDate = (date: Date) => {
  const offset = date.getTimezoneOffset();
  return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
};
const responseTime = (minutes: number) => minutes >= 60
  ? `${Math.floor(minutes / 60)}h ${Math.round(minutes % 60)}m`
  : `${Math.round(minutes)}m`;

export function TicketReportWorkspace() {
  const canExport = getAdminConfig().canExportReports;
  const today = isoDate(new Date());
  const start = new Date(); start.setDate(start.getDate() - 29);
  const defaults = { dateFrom: isoDate(start), dateTo: today, departmentId: '', categoryId: '', tagId: '', customFieldId: '', customFieldValue: '', agentId: '', priority: '' };
  const [filters, setFilters] = useState(defaults);
  const [applied, setApplied] = useState(defaults);
  const [options, setOptions] = useState<ReportOptions>({ departments: [], categories: [], tags: [], custom_fields: [], agents: [] });
  const [report, setReport] = useState<TicketReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  const query = () => {
    const params = new URLSearchParams({ date_from: applied.dateFrom, date_to: applied.dateTo });
    if (applied.departmentId) params.set('department_id', applied.departmentId);
    if (applied.categoryId) params.set('category_id', applied.categoryId);
    if (applied.tagId) params.set('tag_id', applied.tagId);
    if (applied.customFieldId) params.set('custom_field_id', applied.customFieldId);
    if (applied.customFieldId && applied.customFieldValue) params.set('custom_field_value', applied.customFieldValue);
    if (applied.agentId) params.set('assigned_agent_id', applied.agentId);
    if (applied.priority) params.set('priority', applied.priority);
    return params;
  };

  useEffect(() => {
    adminGet<ReportOptions>('admin/tickets/options').then((response) => setOptions(response.data))
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'Report filters could not be loaded.'));
  }, []);

  const load = async () => {
    setLoading(true); setError(null);
    try {
      const response = await adminGet<TicketReport>(`reports/tickets?${query()}`);
      setReport(response.data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Ticket report could not be loaded.');
    } finally { setLoading(false); }
  };
  useEffect(() => { void load(); }, [applied]);
  const categories = useMemo(() => options.categories.filter((category) =>
    !filters.departmentId
    || category.department_id === null
    || category.department_id === Number(filters.departmentId)
  ), [options.categories, filters.departmentId]);
  const customFields = useMemo(() => options.custom_fields.filter((field) =>
    !filters.departmentId
    || field.department_id === null
    || field.department_id === Number(filters.departmentId)
  ), [options.custom_fields, filters.departmentId]);
  const selectedCustomField = options.custom_fields.find((field) => field.id === Number(filters.customFieldId));
  const appliedCustomField = options.custom_fields.find((field) => field.id === Number(applied.customFieldId));
  const apply = (event: FormEvent) => { event.preventDefault(); setApplied(filters); };
  const exportReport = async () => {
    setExporting(true); setError(null);
    try {
      const file = await adminDownloadFile(`reports/tickets/export?${query()}`);
      const url = URL.createObjectURL(file.blob); const link = document.createElement('a');
      link.href = url; link.download = file.filename || 'supportbay-ticket-report.csv'; link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 0);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Ticket report could not be exported.');
    } finally { setExporting(false); }
  };
  const maximum = Math.max(1, ...(report?.daily.map((row) => Math.max(row.tickets, row.responses)) ?? [1]));

  return <section className="sbay-notification-report">
    <header><div><small>Support performance</small><h2>Support Tickets Report</h2><p>Track ticket activity, response performance, outstanding replies, and team workload.</p></div><div>{canExport?<button type="button" onClick={() => void exportReport()} disabled={exporting}>{exporting?'Exporting…':'Export CSV'}</button>:null}<button type="button" onClick={() => void load()} disabled={loading}>Refresh</button></div></header>
    <form className="sbay-report-filters sbay-ticket-report-filters" onSubmit={apply}>
      <label><span>From</span><input type="date" value={filters.dateFrom} max={filters.dateTo} onChange={(event) => setFilters({...filters,dateFrom:event.target.value})}/></label>
      <label><span>To</span><input type="date" value={filters.dateTo} min={filters.dateFrom} max={today} onChange={(event) => setFilters({...filters,dateTo:event.target.value})}/></label>
      <label><span>Department</span><select value={filters.departmentId} onChange={(event) => setFilters({...filters,departmentId:event.target.value,categoryId:'',customFieldId:'',customFieldValue:''})}><option value="">All departments</option>{options.departments.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      <label><span>Category</span><select value={filters.categoryId} onChange={(event) => setFilters({...filters,categoryId:event.target.value})}><option value="">All categories</option><option value="uncategorized">Uncategorized</option>{categories.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      <label><span>Tag</span><select value={filters.tagId} onChange={(event) => setFilters({...filters,tagId:event.target.value})}><option value="">All tags</option>{options.tags.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      <label><span>Custom field</span><select value={filters.customFieldId} onChange={(event) => setFilters({...filters,customFieldId:event.target.value,customFieldValue:''})}><option value="">All custom fields</option>{customFields.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      {filters.customFieldId?<label><span>Exact value</span>{selectedCustomField?.type==='select'?<select value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})}><option value="">Any value</option>{selectedCustomField.options.map(option=><option key={option}>{option}</option>)}</select>:selectedCustomField?.type==='checkbox'?<select value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})}><option value="">Any value</option><option value="1">Checked</option><option value="0">Not checked</option></select>:<input value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})} placeholder="Any value"/>}</label>:null}
      <label><span>Agent</span><select value={filters.agentId} onChange={(event) => setFilters({...filters,agentId:event.target.value})}><option value="">All agents</option>{options.agents.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
      <label><span>Priority</span><select value={filters.priority} onChange={(event) => setFilters({...filters,priority:event.target.value})}><option value="">All priorities</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></label>
      <button type="submit" disabled={loading}>Apply report</button>
    </form>
    {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    {loading && !report ? <Preloader label="Loading ticket performance…" /> : report ? <>
      <div className="sbay-report-summary sbay-ticket-report-summary">
        <article><span>Tickets</span><strong>{report.summary.tickets}</strong></article>
        <article><span>Responses</span><strong>{report.summary.responses}</strong></article>
        <article className="is-failed"><span>Need reply</span><strong>{report.summary.need_reply}</strong></article>
        <article className="is-success"><span>Resolved / closed</span><strong>{report.summary.resolved + report.summary.closed}</strong></article>
        <article><span>Avg. first response</span><strong>{responseTime(report.summary.average_first_response_minutes)}</strong></article>
      </div>
      <section className="sbay-sla-report"><header><div><h3>First-response SLA</h3><p>{report.summary.sla.enabled?'Calendar-time targets are active.':'SLA reporting is currently disabled in Settings.'}</p></div><span>Priority targets: {Object.entries(report.summary.sla.targets).map(([priority,minutes])=>`${priority} ${responseTime(minutes)}`).join(' · ')}</span></header><div><article className="is-success"><span>Within target</span><strong>{report.summary.sla.within_target}</strong></article><article className="is-failed"><span>Breached</span><strong>{report.summary.sla.breached}</strong></article><article><span>Awaiting within target</span><strong>{report.summary.sla.awaiting_within_target}</strong></article></div><footer>{Object.entries(report.summary.response_bands).map(([band,total])=><span key={band}><strong>{total}</strong>{band.replace(/_/g,' ').replace('from ','').replace('over ','24h+ ').replace('under ','<')}</span>)}</footer></section>
      <section className="sbay-report-chart"><header><h3>Daily support activity</h3><span>{report.range.from} — {report.range.to}</span></header><div>{report.daily.map((row)=><article key={row.date} title={`${row.date}: ${row.tickets} tickets, ${row.responses} responses`}><div><i className="is-ticket" style={{height:`${(row.tickets/maximum)*100}%`}}/><i className="is-response" style={{height:`${(row.responses/maximum)*100}%`}}/></div><small>{row.date.slice(5)}</small></article>)}</div><footer><span><i className="is-ticket"/>Tickets</span><span><i className="is-response"/>Responses</span></footer></section>
      <div className="sbay-report-breakdowns">
        <section><h3>By department</h3><div className="is-header"><span>Department</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.departments.length?report.departments.map((row)=><div key={row.department}><strong>{row.department}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No department data in this range.</p>}</section>
        <section><h3>By category</h3><div className="is-header"><span>Category</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.categories.length?report.categories.map((row)=><div key={row.category}><strong>{row.category}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No category data in this range.</p>}</section>
        <section><h3>By tag</h3><div className="is-header"><span>Tag</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.tags.length?report.tags.map((row)=><div key={row.tag}><strong>{row.tag}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No tag data in this range.</p>}</section>
        {applied.customFieldId?<section><h3>By custom field value{appliedCustomField?`: ${appliedCustomField.name}`:''}</h3><div className="is-header"><span>Value</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.custom_fields.length?report.custom_fields.map((row)=><div key={row.value}><strong>{row.value==='1'&&appliedCustomField?.type==='checkbox'?'Checked':row.value==='0'&&appliedCustomField?.type==='checkbox'?'Not checked':row.value}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No custom field data in this range.</p>}</section>:null}
        <section><h3>By agent</h3><div className="is-header"><span>Agent</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.agents.length?report.agents.map((row)=><div key={row.agent}><strong>{row.agent}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No agent data in this range.</p>}</section>
      </div>
    </> : null}
  </section>;
}
