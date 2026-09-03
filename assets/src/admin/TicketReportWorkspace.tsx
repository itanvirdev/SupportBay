import { useEffect, useMemo, useRef, useState } from 'react';
import { adminDownloadFile, adminGet } from './api';
import { getAdminConfig } from './config';
import { Preloader } from '../shared/components/Preloader';
import { RequestState } from '../shared/components/RequestState';

interface TicketMetricRow { tickets: number; responses: number; need_reply: number; closed: number; }
interface TicketReport {
  range: { from: string; to: string };
  summary: TicketMetricRow & { resolved: number };
  daily: Array<TicketMetricRow & { date: string }>;
  categories: Array<TicketMetricRow & { category: string }>;
  tags: Array<TicketMetricRow & { tag: string }>;
  custom_fields: Array<TicketMetricRow & { value: string }>;
  agents: Array<TicketMetricRow & { agent: string }>;
}
interface ReportCustomField { id:number;name:string;type:string;options:string[];category_ids:number[] }
interface ReportOptions { categories: Array<{id:number;name:string}>; tags:Array<{id:number;name:string}>; custom_fields:ReportCustomField[]; agents: Array<{id:number;name:string}>; }

const isoDate = (date: Date) => {
  const offset = date.getTimezoneOffset();
  return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
};

export function TicketReportWorkspace() {
  const canExport = getAdminConfig().canExportReports;
  const today = isoDate(new Date());
  const start = new Date(); start.setDate(start.getDate() - 29);
  const defaults = { dateFrom: isoDate(start), dateTo: today, period: '30', categoryId: '', tagId: '', customFieldId: '', customFieldValue: '', agentId: '', priority: '' };
  const [filters, setFilters] = useState(defaults);
  const [options, setOptions] = useState<ReportOptions>({ categories: [], tags: [], custom_fields: [], agents: [] });
  const [report, setReport] = useState<TicketReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  const query = () => {
    const params = new URLSearchParams({ date_from: filters.dateFrom, date_to: filters.dateTo });
    if (filters.categoryId) params.set('category_id', filters.categoryId);
    if (filters.tagId) params.set('tag_id', filters.tagId);
    if (filters.customFieldId) params.set('custom_field_id', filters.customFieldId);
    if (filters.customFieldId && filters.customFieldValue) params.set('custom_field_value', filters.customFieldValue);
    if (filters.agentId) params.set('assigned_agent_id', filters.agentId);
    if (filters.priority) params.set('priority', filters.priority);
    return params;
  };

  useEffect(() => {
    adminGet<ReportOptions>('admin/tickets/options').then((response) => setOptions(response.data))
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'Report filters could not be loaded.'));
  }, []);

  const requestId = useRef(0);
  const load = async () => {
    const currentRequest = ++requestId.current;
    setLoading(true); setError(null);
    try {
      const response = await adminGet<TicketReport>(`reports/tickets?${query()}`);
      if (currentRequest === requestId.current) setReport(response.data);
    } catch (reason) {
      if (currentRequest === requestId.current) setError(reason instanceof Error ? reason.message : 'Ticket report could not be loaded.');
    } finally { if (currentRequest === requestId.current) setLoading(false); }
  };
  useEffect(() => {
    const timer = window.setTimeout(() => void load(), 250);
    return () => window.clearTimeout(timer);
  }, [filters]);
  const customFields = useMemo(() => options.custom_fields.filter((field) =>
    !filters.categoryId
    || field.category_ids.length === 0
    || field.category_ids.includes(Number(filters.categoryId))
  ), [options.custom_fields, filters.categoryId]);
  const selectedCustomField = options.custom_fields.find((field) => field.id === Number(filters.customFieldId));
  const resetFilters = () => setFilters(defaults);
  const selectPeriod = (period:string) => {
    const dateFrom = new Date();
    dateFrom.setDate(dateFrom.getDate() - (Number(period) - 1));
    setFilters({...filters,period,dateFrom:isoDate(dateFrom),dateTo:today});
  };
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
  const maximum = Math.max(1, ...(report?.daily.map((row) => Math.max(row.tickets, row.responses, row.need_reply, row.closed)) ?? [1]));
  const scaleMaximum = Math.max(4, Math.ceil(maximum / 4) * 4);
  const chartWidth = 1000;
  const chartHeight = 340;
  const plot = { left: 54, right: 18, top: 18, bottom: 86 };
  const plotWidth = chartWidth - plot.left - plot.right;
  const plotHeight = chartHeight - plot.top - plot.bottom;
  const yTicks = Array.from({length: 5}, (_, index) => (scaleMaximum / 4) * index);
  const dateLabel = (value:string) => new Intl.DateTimeFormat(undefined, {month:'long',day:'numeric',year:'numeric',timeZone:'UTC'}).format(new Date(`${value}T00:00:00Z`));

  return <section className="sbay-notification-report">
    <header><div><small>Support performance</small><h2>Support Tickets Report</h2><p>Track ticket activity, response performance, outstanding replies, and team workload.</p></div><div>{canExport?<button type="button" onClick={() => void exportReport()} disabled={exporting}>{exporting?'Exporting…':'Export CSV'}</button>:null}<button type="button" onClick={() => void load()} disabled={loading}>Refresh</button></div></header>
    <div className="sbay-report-filters sbay-ticket-report-filters">
      <label><span>Period</span><select aria-label="Report period" value={filters.period} onChange={(event)=>selectPeriod(event.target.value)}><option value="60">Last 60 Days</option><option value="30">Last 30 Days</option><option value="14">Last 14 Days</option><option value="7">Last 7 Days</option></select></label>
      {options.categories.length?<label><span>Category</span><select value={filters.categoryId} onChange={(event) => setFilters({...filters,categoryId:event.target.value,customFieldId:'',customFieldValue:''})}><option value="">All categories</option><option value="uncategorized">Uncategorized</option>{options.categories.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}
      {options.tags.length?<label><span>Tag</span><select value={filters.tagId} onChange={(event) => setFilters({...filters,tagId:event.target.value})}><option value="">All tags</option>{options.tags.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}
      {options.custom_fields.length?<label><span>Custom field</span><select value={filters.customFieldId} onChange={(event) => setFilters({...filters,customFieldId:event.target.value,customFieldValue:''})}><option value="">All custom fields</option>{customFields.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}
      {filters.customFieldId?<label><span>Exact value</span>{selectedCustomField?.type==='select'?<select value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})}><option value="">Any value</option>{selectedCustomField.options.map(option=><option key={option}>{option}</option>)}</select>:selectedCustomField?.type==='checkbox'?<select value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})}><option value="">Any value</option><option value="1">Checked</option><option value="0">Not checked</option></select>:<input value={filters.customFieldValue} onChange={(event)=>setFilters({...filters,customFieldValue:event.target.value})} placeholder="Any value"/>}</label>:null}
      {options.agents.length?<label><span>Agent</span><select value={filters.agentId} onChange={(event) => setFilters({...filters,agentId:event.target.value})}><option value="">All agents</option>{options.agents.map((item)=><option value={item.id} key={item.id}>{item.name}</option>)}</select></label>:null}
      <label><span>Priority</span><select value={filters.priority} onChange={(event) => setFilters({...filters,priority:event.target.value})}><option value="">All priorities</option><option value="normal">Normal</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></label>
      <button className="sbay-report-reset" type="button" onClick={resetFilters} disabled={JSON.stringify(filters)===JSON.stringify(defaults)} title="Clear filters" aria-label="Clear report filters">↺</button>
    </div>
    {error && report ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    {error && !report && !loading ? <RequestState title="Ticket report could not be loaded" message={error} retry={()=>void load()}/> : loading && !report ? <Preloader label="Loading ticket performance…" /> : report ? <>
      <div className="sbay-report-summary sbay-ticket-report-summary">
        <article><span>Tickets</span><strong>{report.summary.tickets}</strong></article>
        <article><span>Responses</span><strong>{report.summary.responses}</strong></article>
        <article className="is-failed"><span>Need reply</span><strong>{report.summary.need_reply}</strong></article>
        <article className="is-success"><span>Resolved / closed</span><strong>{report.summary.resolved + report.summary.closed}</strong></article>
      </div>
      <section className={`sbay-report-chart${loading?' is-loading':''}`} aria-busy={loading}><header><h3>Daily support activity</h3><span>{report.range.from} — {report.range.to}</span></header><div className="sbay-ticket-chart-scroll"><svg viewBox={`0 0 ${chartWidth} ${chartHeight}`} role="img" aria-label="Daily tickets, responses, need reply, and resolved or closed chart">{yTicks.map((tick)=><g key={tick}><line x1={plot.left} y1={plot.top+plotHeight-(tick/scaleMaximum)*plotHeight} x2={chartWidth-plot.right} y2={plot.top+plotHeight-(tick/scaleMaximum)*plotHeight}/><text x={plot.left-10} y={plot.top+plotHeight-(tick/scaleMaximum)*plotHeight+4} textAnchor="end">{tick}</text></g>)}{report.daily.map((row,index)=>{const slot=plotWidth/Math.max(1,report.daily.length);const barWidth=Math.min(6,slot/5);const x=plot.left+index*slot+slot/2;const series=[['is-ticket',row.tickets],['is-response',row.responses],['is-need-reply',row.need_reply],['is-closed',row.closed]] as const;return <g key={row.date}><title>{`${dateLabel(row.date)}: ${row.tickets} tickets, ${row.responses} responses, ${row.need_reply} need reply, ${row.closed} resolved or closed`}</title>{series.map(([className,value],seriesIndex)=>{const height=(value/scaleMaximum)*plotHeight;return <rect key={className} className={className} x={x-(barWidth*2)+(seriesIndex*barWidth)} y={plot.top+plotHeight-height} width={Math.max(1,barWidth-1)} height={Math.max(value?1:0,height)}/>})}<text className="is-date" transform={`translate(${x-3} ${plot.top+plotHeight+14}) rotate(45)`}>{dateLabel(row.date)}</text></g>})}</svg></div><footer><span><i className="is-ticket"/>Tickets</span><span><i className="is-response"/>Responses</span><span><i className="is-need-reply"/>Need Reply</span><span><i className="is-closed"/>Resolved / Closed</span></footer></section>
      <div className="sbay-report-breakdowns">
        <section><h3>By category</h3><div className="is-header"><span>Category</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.categories.length?report.categories.map((row)=><div key={row.category}><strong>{row.category}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No category data in this range.</p>}</section>
        <section><h3>By tag</h3><div className="is-header"><span>Tag</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.tags.length?report.tags.map((row)=><div key={row.tag}><strong>{row.tag}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No tag data in this range.</p>}</section>
        {filters.customFieldId?<section><h3>By custom field value{selectedCustomField?`: ${selectedCustomField.name}`:''}</h3><div className="is-header"><span>Value</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.custom_fields.length?report.custom_fields.map((row)=><div key={row.value}><strong>{row.value==='1'&&selectedCustomField?.type==='checkbox'?'Checked':row.value==='0'&&selectedCustomField?.type==='checkbox'?'Not checked':row.value}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No custom field data in this range.</p>}</section>:null}
        <section><h3>By agent</h3><div className="is-header"><span>Agent</span><span>Tickets</span><span>Responses</span><span>Need reply</span></div>{report.agents.length?report.agents.map((row)=><div key={row.agent}><strong>{row.agent}</strong><span>{row.tickets}</span><span>{row.responses}</span><span>{row.need_reply}</span></div>):<p>No agent data in this range.</p>}</section>
      </div>
    </> : null}
  </section>;
}
