import { FormEvent, useCallback, useEffect, useState } from 'react';
import { adminGet } from './api';

interface VerificationItem {
  id: number;
  provider: string;
  reference: string;
  customer_id: number | null;
  customer_name: string | null;
  product_id: string | null;
  product_name: string | null;
  license_type: string | null;
  support_expires_at: string | null;
  support_status: 'active' | 'expired' | 'unknown';
  verification_status: string;
  ticket_count: number;
  last_checked_at: string | null;
  verified_at: string | null;
  updated_at: string;
}

interface DirectoryPage {
  items: VerificationItem[];
  page: number;
  total: number;
  totalPages: number;
  providers: string[];
  statuses: string[];
}

interface Props {
  back: () => void;
}

const labels: Record<string, string> = { pending: 'Pending', verified: 'Verified', expired: 'Expired', invalid: 'Invalid', revoked: 'Revoked' };

export function VerificationDirectory({ back }: Props) {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [draft, setDraft] = useState('');
  const [provider, setProvider] = useState('');
  const [status, setStatus] = useState('');
  const [sort, setSort] = useState('updated_at:desc');
  const [result, setResult] = useState<DirectoryPage | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    const [orderby, order] = sort.split(':');
    const query = new URLSearchParams({ page: String(page), per_page: '20', search, provider, status, orderby, order });
    try {
      const response = await adminGet<VerificationItem[]>(`verifications?${query}`);
      setResult({
        items: response.data,
        page: Number(response.meta.page) || 1,
        total: Number(response.meta.total) || 0,
        totalPages: Number(response.meta.total_pages) || 1,
        providers: Array.isArray(response.meta.providers) ? response.meta.providers as string[] : [],
        statuses: Array.isArray(response.meta.statuses) ? response.meta.statuses as string[] : [],
      });
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Verifications could not be loaded.');
    }
  }, [page, search, provider, status, sort]);

  useEffect(() => { void load(); }, [load]);
  const update = (setter: (value: string) => void, value: string) => { setter(value); setPage(1); };
  const submit = (event: FormEvent) => { event.preventDefault(); setPage(1); setSearch(draft.trim()); };
  const first = result?.total ? ((result.page - 1) * 20) + 1 : 0;
  const last = result ? Math.min(result.page * 20, result.total) : 0;

  return <section className="sbay-verification-directory">
    <header><div><button className="sbay-back" onClick={back}>← Tickets</button><small>Purchase verification</small><h1>Verification Management</h1><p>Search provider purchases, review entitlement state, and find related support activity.</p></div><strong>{result?.total ?? '—'} records</strong></header>
    <div className="sbay-verification-filters">
      <form onSubmit={submit}><input aria-label="Search verifications" value={draft} onChange={(event) => setDraft(event.target.value)} placeholder="Search product, customer, or purchase reference"/><button>Search</button></form>
      <select aria-label="Provider" value={provider} onChange={(event) => update(setProvider, event.target.value)}><option value="">All Providers</option>{result?.providers.map((item) => <option key={item} value={item}>{item}</option>)}</select>
      <select aria-label="Verification status" value={status} onChange={(event) => update(setStatus, event.target.value)}><option value="">All Statuses</option>{result?.statuses.map((item) => <option key={item} value={item}>{labels[item] || item}</option>)}</select>
      <select aria-label="Sort verifications" value={sort} onChange={(event) => update(setSort, event.target.value)}><option value="updated_at:desc">Recently Updated</option><option value="verified_at:desc">Recently Verified</option><option value="support_expires_at:asc">Support Expiring First</option><option value="product:asc">Product Name</option><option value="provider:asc">Provider</option></select>
      <button disabled={!search && !provider && !status && sort === 'updated_at:desc'} onClick={() => { setDraft(''); setSearch(''); setProvider(''); setStatus(''); setSort('updated_at:desc'); setPage(1); }}>Reset</button>
    </div>
    {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    <div className="sbay-verification-table">
      <div className="is-header"><span>Purchase</span><span>Status</span><span>Support</span><span>Tickets</span><span>Last checked</span></div>
      {!result ? <p>Loading verifications…</p> : result.items.length === 0 ? <p>No verifications match these filters.</p> : result.items.map((item) => <div className="sbay-verification-row" key={item.id}>
        <span><strong>{item.product_name || item.product_id || 'Unknown product'}</strong><small>{item.provider} · {item.reference}{item.customer_name ? ` · ${item.customer_name}` : ''}</small></span>
        <span><i className={`is-${item.verification_status}`}>{labels[item.verification_status] || item.verification_status}</i><small>{item.license_type || 'License unknown'}</small></span>
        <span><i className={`is-${item.support_status}`}>{item.support_status}</i><small>{item.support_expires_at ? new Date(item.support_expires_at).toLocaleDateString() : 'No expiry'}</small></span>
        <span><strong>{item.ticket_count}</strong><small>related</small></span>
        <span><time>{new Date(item.last_checked_at || item.updated_at).toLocaleDateString()}</time><small>{item.last_checked_at ? 'Provider check' : 'Record update'}</small></span>
      </div>)}
      <footer><span>Showing {first}–{last} of {result?.total ?? 0}</span><nav aria-label="Verification pagination"><button disabled={!result || result.page <= 1} onClick={() => setPage(page - 1)}>‹</button><span>{result?.page ?? 1}</span><button disabled={!result || result.page >= result.totalPages} onClick={() => setPage(page + 1)}>›</button></nav></footer>
    </div>
  </section>;
}
