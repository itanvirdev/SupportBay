import { FormEvent, useEffect, useState } from 'react';
import { adminDownloadFile, adminGet } from './api';
import { getAdminConfig } from './config';

interface MetricRow {
  total: number;
  successful: number;
  failed: number;
}

interface DailyMetric extends MetricRow { date: string; }
interface EventMetric extends MetricRow { event: string; }
interface ChannelMetric extends MetricRow { channel: string; }

interface NotificationReport {
  range: { from: string; to: string };
  filters: { channel: string | null; event: string | null };
  summary: MetricRow & {
    queued: number;
    cancelled: number;
    retries: number;
    success_rate: number;
    failure_rate: number;
  };
  daily: DailyMetric[];
  events: EventMetric[];
  channels: ChannelMetric[];
}

const isoDate = (date: Date) => {
  const offset = date.getTimezoneOffset();
  return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 10);
};

export function NotificationReportWorkspace() {
  const canExport = getAdminConfig().canExportReports;
  const today = isoDate(new Date());
  const initialFrom = new Date(); initialFrom.setDate(initialFrom.getDate() - 29);
  const [dateFrom, setDateFrom] = useState(isoDate(initialFrom));
  const [dateTo, setDateTo] = useState(today);
  const [channel, setChannel] = useState('');
  const [eventKey, setEventKey] = useState('');
  const [applied, setApplied] = useState({ dateFrom: isoDate(initialFrom), dateTo: today, channel: '', eventKey: '' });
  const [report, setReport] = useState<NotificationReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  const query = () => {
    const params = new URLSearchParams({ date_from: applied.dateFrom, date_to: applied.dateTo });
    if (applied.channel) params.set('channel', applied.channel);
    if (applied.eventKey) params.set('event', applied.eventKey);
    return params;
  };

  const load = async () => {
    setLoading(true); setError(null);
    try {
      const response = await adminGet<NotificationReport>(`reports/notifications?${query()}`);
      setReport(response.data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification report could not be loaded.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { void load(); }, [applied]);

  const apply = (submitEvent: FormEvent) => {
    submitEvent.preventDefault();
    setApplied({ dateFrom, dateTo, channel, eventKey: eventKey.trim() });
  };

  const exportReport = async () => {
    setExporting(true); setError(null);
    try {
      const file = await adminDownloadFile(`reports/notifications/export?${query()}`);
      const url = URL.createObjectURL(file.blob); const link = document.createElement('a');
      link.href = url; link.download = file.filename || 'supportbay-notification-report.csv'; link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 0);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification report could not be exported.');
    } finally { setExporting(false); }
  };

  const maximum = Math.max(1, ...(report?.daily.map((row) => row.total) ?? [1]));

  return <section className="sbay-notification-report">
    <header><div><small>Notification performance</small><h2>Delivery Report</h2><p>Track delivery volume, success, failures, retries, events, and channels from notification audit records.</p></div><div>{canExport?<button type="button" onClick={() => void exportReport()} disabled={exporting}>{exporting?'Exporting…':'Export CSV'}</button>:null}<button type="button" onClick={() => void load()} disabled={loading}>Refresh</button></div></header>
    <form className="sbay-report-filters" onSubmit={apply}>
      <label><span>From</span><input type="date" value={dateFrom} max={dateTo} onChange={(event) => setDateFrom(event.target.value)}/></label>
      <label><span>To</span><input type="date" value={dateTo} min={dateFrom} max={today} onChange={(event) => setDateTo(event.target.value)}/></label>
      <label><span>Channel</span><select value={channel} onChange={(event) => setChannel(event.target.value)}><option value="">All channels</option><option value="email">Email</option></select></label>
      <label><span>Event</span><input value={eventKey} onChange={(event) => setEventKey(event.target.value.replace(/[^a-z0-9_-]/gi, '').toLowerCase())} placeholder="ticket_created"/></label>
      <button type="submit" disabled={loading}>Apply report</button>
    </form>
    {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    {loading && !report ? <p>Loading notification metrics…</p> : report ? <>
      <div className="sbay-report-summary">
        <article><span>Total deliveries</span><strong>{report.summary.total}</strong></article>
        <article className="is-success"><span>Successful</span><strong>{report.summary.successful}</strong><small>{report.summary.success_rate}% success rate</small></article>
        <article className="is-failed"><span>Failed</span><strong>{report.summary.failed}</strong><small>{report.summary.failure_rate}% failure rate</small></article>
        <article><span>Queued</span><strong>{report.summary.queued}</strong></article>
        <article><span>Retry attempts</span><strong>{report.summary.retries}</strong></article>
      </div>
      <section className="sbay-report-chart"><header><h3>Daily delivery trend</h3><span>{report.range.from} — {report.range.to}</span></header><div>{report.daily.map((row) => <article key={row.date} title={`${row.date}: ${row.total} deliveries`}><div><i className="is-success" style={{height: `${(row.successful / maximum) * 100}%`}}/><i className="is-failed" style={{height: `${(row.failed / maximum) * 100}%`}}/></div><small>{row.date.slice(5)}</small></article>)}</div><footer><span><i className="is-success"/>Successful</span><span><i className="is-failed"/>Failed</span></footer></section>
      <div className="sbay-report-breakdowns">
        <section><h3>By event</h3><div className="is-header"><span>Event</span><span>Total</span><span>Success</span><span>Failed</span></div>{report.events.length ? report.events.map((row) => <div key={row.event}><strong>{row.event.replace(/_/g, ' ')}</strong><span>{row.total}</span><span>{row.successful}</span><span>{row.failed}</span></div>) : <p>No event data in this range.</p>}</section>
        <section><h3>By channel</h3><div className="is-header"><span>Channel</span><span>Total</span><span>Success</span><span>Failed</span></div>{report.channels.length ? report.channels.map((row) => <div key={row.channel}><strong>{row.channel}</strong><span>{row.total}</span><span>{row.successful}</span><span>{row.failed}</span></div>) : <p>No channel data in this range.</p>}</section>
      </div>
    </> : null}
  </section>;
}
