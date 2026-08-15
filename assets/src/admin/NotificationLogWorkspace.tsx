import { FormEvent, useEffect, useState } from 'react';
import { adminGet, adminPost, adminPut } from './api';

interface NotificationRetention {
  enabled: boolean;
  retention_days: number;
  batch_size: number;
}

interface NotificationLog {
  id: number;
  ticket_id: number | null;
  user_id: number | null;
  channel: string;
  event: string;
  recipient: string;
  subject: string;
  status: string;
  provider: string | null;
  provider_message_id: string | null;
  error_message: string | null;
  retry_count: number;
  can_retry: boolean;
  scheduled_at: string | null;
  sent_at: string | null;
  delivered_at: string | null;
  created_at: string;
  updated_at: string | null;
}

interface LogMeta {
  page?: number;
  total?: number;
  total_pages?: number;
  statuses?: string[];
}

const date = (value: string | null) => value
  ? new Date(value.includes('T') ? value : value.replace(' ', 'T')).toLocaleString()
  : '—';

export function NotificationLogWorkspace() {
  const [logs, setLogs] = useState<NotificationLog[]>([]);
  const [selected, setSelected] = useState<NotificationLog | null>(null);
  const [statuses, setStatuses] = useState<string[]>([]);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [eventKey, setEventKey] = useState('');
  const [channel, setChannel] = useState('');
  const [loading, setLoading] = useState(true);
  const [retrying, setRetrying] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [retention, setRetention] = useState<NotificationRetention | null>(null);
  const [savingRetention, setSavingRetention] = useState(false);

  const load = async () => {
    setLoading(true); setError(null);
    try {
      const query = new URLSearchParams({ page: String(page), per_page: '20' });
      if (search) query.set('search', search);
      if (status) query.set('status', status);
      if (eventKey) query.set('event', eventKey);
      if (channel) query.set('channel', channel);
      const response = await adminGet<NotificationLog[]>(`admin/notifications?${query}`);
      const meta = response.meta as LogMeta;
      setLogs(response.data);
      setStatuses(Array.isArray(meta.statuses) ? meta.statuses : []);
      setTotal(Number(meta.total) || 0);
      setTotalPages(Math.max(1, Number(meta.total_pages) || 1));
      if (selected && !response.data.some((log) => log.id === selected.id)) setSelected(null);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification logs could not be loaded.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { void load(); }, [page, search, status, eventKey, channel]);

  useEffect(() => {
    adminGet<NotificationRetention>('admin/notification-retention')
      .then((response) => setRetention(response.data))
      .catch((reason) => setError(reason instanceof Error ? reason.message : 'Retention settings could not be loaded.'));
  }, []);

  const saveRetention = async () => {
    if (!retention) return;
    setSavingRetention(true); setError(null); setNotice(null);
    try {
      const response = await adminPut<NotificationRetention>('admin/notification-retention', retention);
      setRetention(response.data); setNotice('Notification retention settings saved.');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Retention settings could not be saved.');
    } finally {
      setSavingRetention(false);
    }
  };

  const cleanup = async () => {
    setSavingRetention(true); setError(null); setNotice(null);
    try {
      const response = await adminPost<{ deleted: number; cutoff: string }>('admin/notification-retention/cleanup', {});
      setNotice(`${response.data.deleted} expired notification log${response.data.deleted === 1 ? '' : 's'} removed.`);
      setPage(1); await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification cleanup could not run.');
    } finally {
      setSavingRetention(false);
    }
  };

  const inspect = async (id: number) => {
    setError(null);
    try {
      const response = await adminGet<NotificationLog>(`admin/notifications/${id}`);
      setSelected(response.data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Delivery diagnostics could not be loaded.');
    }
  };

  const retry = async (log: NotificationLog) => {
    setRetrying(log.id); setError(null); setNotice(null);
    try {
      const response = await adminPost<NotificationLog>(`admin/notifications/${log.id}/retry`, {});
      setSelected(response.data);
      setNotice(`Notification #${log.id} sent successfully.`);
      await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification retry failed.');
      await load();
      await inspect(log.id);
    } finally {
      setRetrying(null);
    }
  };

  const searchLogs = (submitEvent: FormEvent) => {
    submitEvent.preventDefault(); setPage(1); setSearch(searchInput.trim());
  };

  const reset = () => {
    setSearchInput(''); setSearch(''); setStatus(''); setEventKey(''); setChannel(''); setPage(1); setSelected(null);
  };

  return <section className="sbay-log-workspace">
    <header><div><small>Email notifications</small><h2>Delivery Logs</h2><p>Inspect delivery status and retry eligible failures without exposing stored message content.</p></div><button type="button" onClick={() => void load()} disabled={loading}>Refresh</button></header>
    {retention ? <section className="sbay-retention-settings">
      <div><h3>Log retention</h3><p>Daily cleanup removes only old successful, cancelled, and retry-exhausted deliveries. Active and retry-eligible deliveries are always preserved.</p></div>
      <label className="sbay-switch"><input type="checkbox" checked={retention.enabled} onChange={(event) => setRetention({ ...retention, enabled: event.target.checked })}/>Enable cleanup</label>
      <label><span>Keep logs for</span><input type="number" min="7" max="3650" value={retention.retention_days} onChange={(event) => setRetention({ ...retention, retention_days: Number(event.target.value) })}/><small>days</small></label>
      <label><span>Daily batch</span><input type="number" min="50" max="1000" value={retention.batch_size} onChange={(event) => setRetention({ ...retention, batch_size: Number(event.target.value) })}/><small>records</small></label>
      <div className="sbay-retention-actions"><button type="button" onClick={() => void saveRetention()} disabled={savingRetention}>Save policy</button><button type="button" onClick={() => void cleanup()} disabled={savingRetention || !retention.enabled}>Run cleanup now</button></div>
    </section> : null}
    <form className="sbay-log-filters" onSubmit={searchLogs}>
      <label><span>Search</span><input value={searchInput} onChange={(event) => setSearchInput(event.target.value)} placeholder="Recipient, subject, or error"/></label>
      <label><span>Status</span><select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><option value="">All statuses</option>{statuses.map((item) => <option value={item} key={item}>{item}</option>)}</select></label>
      <label><span>Event</span><input value={eventKey} onChange={(event) => setEventKey(event.target.value.replace(/[^a-z0-9_-]/gi, '').toLowerCase())} placeholder="ticket_created"/></label>
      <label><span>Channel</span><select value={channel} onChange={(event) => { setChannel(event.target.value); setPage(1); }}><option value="">All channels</option><option value="email">Email</option></select></label>
      <button type="submit">Apply</button><button type="button" onClick={reset}>Reset</button>
    </form>
    {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    {notice ? <div className="sbay-admin-notice" role="status">{notice}</div> : null}
    <div className="sbay-log-layout">
      <div className="sbay-log-list">
        <div className="is-header"><span>Delivery</span><span>Status</span><span>Attempts</span><span>Created</span></div>
        {loading ? <p>Loading delivery logs…</p> : logs.length === 0 ? <p>No notification logs match these filters.</p> : logs.map((log) => <button type="button" className={selected?.id === log.id ? 'is-active' : ''} onClick={() => void inspect(log.id)} key={log.id}>
          <span><strong>{log.subject}</strong><small>{log.recipient}</small><small>{log.event.replace(/_/g, ' ')} · {log.channel}</small></span><span><i className={`is-${log.status}`}>{log.status}</i>{log.error_message ? <small>{log.error_message}</small> : null}</span><span>{log.retry_count} / 3</span><time>{date(log.created_at)}</time>
        </button>)}
        <footer><span>Showing {logs.length} of {total}</span><nav aria-label="Notification log pagination"><button type="button" disabled={page <= 1 || loading} onClick={() => setPage(page - 1)}>‹</button><strong>{page} / {totalPages}</strong><button type="button" disabled={page >= totalPages || loading} onClick={() => setPage(page + 1)}>›</button></nav></footer>
      </div>
      <aside className="sbay-log-detail">
        {selected ? <><header><div><small>Delivery #{selected.id}</small><h3>{selected.subject}</h3></div><i className={`is-${selected.status}`}>{selected.status}</i></header><dl>
          <div><dt>Recipient</dt><dd>{selected.recipient}</dd></div><div><dt>Event</dt><dd>{selected.event}</dd></div><div><dt>Channel</dt><dd>{selected.channel}</dd></div><div><dt>Provider</dt><dd>{selected.provider || '—'}</dd></div><div><dt>Ticket</dt><dd>{selected.ticket_id || '—'}</dd></div><div><dt>Retries</dt><dd>{selected.retry_count} of 3</dd></div><div><dt>Scheduled</dt><dd>{date(selected.scheduled_at)}</dd></div><div><dt>Sent</dt><dd>{date(selected.sent_at)}</dd></div><div><dt>Delivered</dt><dd>{date(selected.delivered_at)}</dd></div><div><dt>Updated</dt><dd>{date(selected.updated_at)}</dd></div>{selected.error_message ? <div className="is-error"><dt>Delivery error</dt><dd>{selected.error_message}</dd></div> : null}
        </dl>{selected.can_retry ? <button type="button" className="is-primary" disabled={retrying !== null} onClick={() => void retry(selected)}>{retrying === selected.id ? 'Retrying…' : 'Retry delivery'}</button> : <p>This delivery is not eligible for retry.</p>}</> : <p>Select a delivery to inspect safe diagnostics.</p>}
      </aside>
    </div>
  </section>;
}
