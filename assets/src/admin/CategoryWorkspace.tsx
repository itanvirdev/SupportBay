import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { adminDelete, adminGet, adminPost, adminPut } from './api';

interface Category {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  department_id: number | null;
  status: 'active' | 'inactive';
  color: string | null;
  sort_order: number;
  updated_at: string;
}

interface Department { id: number; name: string }

const emptyCategory: Category = {
  id: 0,
  name: '',
  slug: '',
  description: null,
  department_id: null,
  status: 'active',
  color: '#216e52',
  sort_order: 0,
  updated_at: '',
};

export function CategoryWorkspace() {
  const [items, setItems] = useState<Category[]>([]);
  const [departments, setDepartments] = useState<Department[]>([]);
  const [selected, setSelected] = useState<Category>(emptyCategory);
  const [query, setQuery] = useState('');
  const [status, setStatus] = useState('');
  const [department, setDepartment] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [categories, departmentResponse] = await Promise.all([
        adminGet<Category[]>('categories'),
        adminGet<Department[]>('departments?status=active'),
      ]);
      setItems(categories.data);
      setDepartments(departmentResponse.data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Categories could not be loaded.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const departmentNames = useMemo(
    () => new Map(departments.map((item) => [item.id, item.name])),
    [departments],
  );
  const visible = useMemo(() => items.filter((item) =>
    (!status || item.status === status)
    && (!department || (department === 'global'
      ? item.department_id === null
      : item.department_id === Number(department)))
    && `${item.name} ${item.slug} ${item.description ?? ''}`
      .toLowerCase().includes(query.toLowerCase()),
  ), [items, query, status, department]);

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
        description: selected.description,
        department_id: selected.department_id,
        status: selected.status,
        color: selected.color,
        sort_order: selected.sort_order,
      };
      const response = updating
        ? await adminPut<Category>(`categories/${selected.id}`, payload)
        : await adminPost<Category>('categories', payload);
      setSelected(response.data);
      setNotice(updating ? 'Category updated.' : 'Category created.');
      await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Category could not be saved.');
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!selected.id || !window.confirm(`Delete “${selected.name}”? Categories used by tickets cannot be deleted.`)) return;
    setSaving(true);
    setError(null);
    setNotice(null);
    try {
      await adminDelete(`categories/${selected.id}`);
      setSelected(emptyCategory);
      setNotice('Category deleted.');
      await load();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Category could not be deleted.');
    } finally {
      setSaving(false);
    }
  };

  return <section className="sbay-category-settings">
    <header>
      <div><small>Ticket organization</small><h2>Categories</h2><p>Manage global and department-specific ticket classifications.</p></div>
      <button type="button" onClick={() => { setSelected(emptyCategory); setError(null); setNotice(null); }}>Add Category</button>
    </header>
    {error ? <p className="sbay-admin-error" role="alert">{error}</p> : null}
    {notice ? <p className="sbay-admin-success" role="status">{notice}</p> : null}
    <div className="sbay-category-settings__grid">
      <aside>
        <label>Search<input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search categories…" /></label>
        <label>Status<select value={status} onChange={(event) => setStatus(event.target.value)}><option value="">All Statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
        <label>Department scope<select value={department} onChange={(event) => setDepartment(event.target.value)}><option value="">All Scopes</option><option value="global">Global</option>{departments.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
        {loading ? <p>Loading…</p> : visible.length === 0 ? <p>No categories found.</p> : <ul>{visible.map((item) => <li key={item.id}><button type="button" className={selected.id === item.id ? 'is-active' : ''} onClick={() => { setSelected(item); setError(null); setNotice(null); }}><i style={{ backgroundColor: item.color ?? '#a7b2ac' }} /><span><strong>{item.name}</strong><small>{item.department_id ? departmentNames.get(item.department_id) ?? `Department #${item.department_id}` : 'Global'} · {item.status}</small></span></button></li>)}</ul>}
      </aside>
      <form onSubmit={save}>
        <label>Name<input required maxLength={190} value={selected.name} onChange={(event) => setSelected((current) => ({ ...current, name: event.target.value }))} /></label>
        <label>Slug<input maxLength={190} value={selected.slug} onChange={(event) => setSelected((current) => ({ ...current, slug: event.target.value }))} placeholder="Generated from name when empty" /></label>
        <label>Description<textarea rows={4} value={selected.description ?? ''} onChange={(event) => setSelected((current) => ({ ...current, description: event.target.value || null }))} /></label>
        <label>Department scope<select value={selected.department_id ?? ''} onChange={(event) => setSelected((current) => ({ ...current, department_id: Number(event.target.value) || null }))}><option value="">Global — all departments</option>{departments.map((item) => <option value={item.id} key={item.id}>{item.name}</option>)}</select></label>
        <div className="sbay-category-settings__row"><label>Status<select value={selected.status} onChange={(event) => setSelected((current) => ({ ...current, status: event.target.value as Category['status'] }))}><option value="active">Active</option><option value="inactive">Inactive</option></select></label><label>Color<input type="color" value={selected.color ?? '#216e52'} onChange={(event) => setSelected((current) => ({ ...current, color: event.target.value }))} /></label><label>Sort order<input type="number" min="0" value={selected.sort_order} onChange={(event) => setSelected((current) => ({ ...current, sort_order: Number(event.target.value) || 0 }))} /></label></div>
        <div className="sbay-category-settings__actions"><button disabled={saving || selected.name.trim() === ''}>{saving ? 'Saving…' : selected.id ? 'Save Changes' : 'Create Category'}</button>{selected.id ? <button type="button" className="is-danger" disabled={saving} onClick={() => void remove()}>Delete</button> : null}</div>
      </form>
    </div>
  </section>;
}
