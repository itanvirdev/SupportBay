import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { adminDelete, adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface Tag {
  id: number;
  name: string;
  slug: string;
  color: string | null;
  status: 'active' | 'inactive';
  updated_at: string;
}

const emptyTag: Tag = {
  id: 0,
  name: '',
  slug: '',
  color: '#216e52',
  status: 'active',
  updated_at: '',
};

export function TagWorkspace() {
  const [items, setItems] = useState<Tag[]>([]);
  const [selected, setSelected] = useState<Tag>(emptyTag);
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setItems((await adminGet<Tag[]>('tags')).data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Tags could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const visible = useMemo(() => items.filter((item) =>
    (!status || item.status === status)
    && `${item.name} ${item.slug}`.toLowerCase().includes(query.toLowerCase()),
  ), [items, query, status]);

  const save = async (event: FormEvent) => {
    event.preventDefault();
    const updating = selected.id > 0;
    setSaving(true);
    setError(null);
    setNotice(null);
    try {
      const payload = {
        name: selected.name,
        slug: selected.slug || selected.name,
        color: selected.color,
        status: selected.status,
      };
      const response = updating
        ? await adminPut<Tag>(`tags/${selected.id}`, payload)
        : await adminPost<Tag>('tags', payload);
      setSelected(response.data);
      setNotice(updating ? 'Tag updated.' : 'Tag created.');
      await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Tag could not be saved.');
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!selected.id || !window.confirm(`Delete “${selected.name}”? Tags used by tickets cannot be deleted.`)) return;
    setSaving(true);
    setError(null);
    setNotice(null);
    try {
      await adminDelete(`tags/${selected.id}`);
      setSelected(emptyTag);
      setNotice('Tag deleted.');
      await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Tag could not be deleted.');
    } finally {
      setSaving(false);
    }
  };

  return <section className="sbay-category-settings sbay-tag-settings">
    <header>
      <div><small>Ticket organization</small><h2>Tags</h2><p>Manage reusable global labels for staff ticket workflows.</p></div>
      <button type="button" onClick={() => { setSelected(emptyTag); setError(null); setNotice(null); }}>Add Tag</button>
    </header>
    {error ? <p className="sbay-admin-error" role="alert">{error}</p> : null}
    {notice ? <p className="sbay-admin-success" role="status">{notice}</p> : null}
    <div className="sbay-category-settings__grid">
      <aside>
        <label>Search<input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search tags…" /></label>
        <label>Status<select value={status} onChange={(event) => setStatus(event.target.value)}><option value="">All Statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
        {loading ? <Preloader label="Loading tags…" compact /> : visible.length === 0 ? <p>No tags found.</p> : <ul>{visible.map((item) => <li key={item.id}><button type="button" className={selected.id === item.id ? 'is-active' : ''} onClick={() => { setSelected(item); setError(null); setNotice(null); }}><i style={{ backgroundColor: item.color ?? '#a7b2ac' }} /><span><strong>{item.name}</strong><small>{item.slug} · {item.status}</small></span></button></li>)}</ul>}
      </aside>
      <form onSubmit={save}>
        <label>Name<input required maxLength={100} value={selected.name} onChange={(event) => setSelected((current) => ({ ...current, name: event.target.value }))} /></label>
        <label>Slug<input maxLength={120} value={selected.slug} onChange={(event) => setSelected((current) => ({ ...current, slug: event.target.value }))} placeholder="Generated from name when empty" /></label>
        <div className="sbay-category-settings__row"><label>Status<select value={selected.status} onChange={(event) => setSelected((current) => ({ ...current, status: event.target.value as Tag['status'] }))}><option value="active">Active</option><option value="inactive">Inactive</option></select></label><label>Color<input type="color" value={selected.color ?? '#216e52'} onChange={(event) => setSelected((current) => ({ ...current, color: event.target.value }))} /></label></div>
        <p>Inactive tags remain visible on historical tickets but cannot be newly assigned.</p>
        <div className="sbay-category-settings__actions"><button disabled={saving || selected.name.trim() === ''}>{saving ? 'Saving…' : selected.id ? 'Save Changes' : 'Create Tag'}</button>{selected.id ? <button type="button" className="is-danger" disabled={saving} onClick={() => void remove()}>Delete</button> : null}</div>
      </form>
    </div>
  </section>;
}
