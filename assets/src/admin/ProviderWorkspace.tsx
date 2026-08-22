import { useEffect, useState } from 'react';
import { adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

export interface ProviderItem {
  id: number;
  slug: string;
  name: string;
  category: string;
  version: string | null;
  status: 'enabled' | 'disabled';
  configured: boolean;
  connection_test_available: boolean;
  last_connected_at: string | null;
  has_error: boolean;
  created_at: string;
  updated_at: string;
}

interface ConfigurationField {
  key: string;
  label: string;
  type: 'text' | 'secret' | 'url' | 'toggle' | 'readonly';
  required: boolean;
  description: string | null;
  value: string;
  configured: boolean | null;
}

interface ProviderConfigurationForm {
  provider: string;
  configured: boolean;
  fields: ConfigurationField[];
}

interface ConnectionTestResponse {
  test: { successful: boolean; message: string };
  provider: ProviderItem;
}

export function ProviderWorkspace() {
  const [providers, setProviders] = useState<ProviderItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [changing, setChanging] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<ProviderItem | null>(null);
  const [configuration, setConfiguration] = useState<ProviderConfigurationForm | null>(null);
  const [values, setValues] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState<number | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [labelEditing, setLabelEditing] = useState<ProviderItem | null>(null);
  const [labelValue, setLabelValue] = useState('');
  const [labelSaving, setLabelSaving] = useState(false);

  const load = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await adminGet<ProviderItem[]>('providers');
      setProviders(response.data);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Providers could not be loaded.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { void load(); }, []);

  const transition = async (provider: ProviderItem) => {
    const action = provider.status === 'enabled' ? 'disable' : 'enable';
    setChanging(provider.id);
    setError(null);
    try {
      const response = await adminPost<ProviderItem>(`providers/${provider.id}/${action}`, {});
      setProviders((items) => items.map((item) => item.id === provider.id ? response.data : item));
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Provider status could not be updated.');
    } finally {
      setChanging(null);
    }
  };

  const testConnection = async (provider: ProviderItem) => {
    setTesting(provider.id);
    setError(null);
    setNotice(null);
    try {
      const response = await adminPost<ConnectionTestResponse>(`providers/${provider.id}/test-connection`, {});
      setProviders((items) => items.map((item) => item.id === provider.id ? response.data.provider : item));
      if (response.data.test.successful) {
        setNotice(response.data.test.message);
      } else {
        setError(response.data.test.message);
      }
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Provider connection could not be tested.');
    } finally {
      setTesting(null);
    }
  };

  const configure = async (provider: ProviderItem) => {
    setEditing(provider);
    setConfiguration(null);
    setError(null);
    try {
      const response = await adminGet<ProviderConfigurationForm>(`providers/${provider.id}/configuration`);
      setConfiguration(response.data);
      setValues(Object.fromEntries(response.data.fields.map((field) => [field.key, field.value || ''])));
    } catch (reason) {
      setEditing(null);
      setError(reason instanceof Error ? reason.message : 'Provider configuration could not be loaded.');
    }
  };

  const saveConfiguration = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!editing) return;
    setSaving(true);
    setError(null);
    try {
      const response = await adminPut<ProviderConfigurationForm>(`providers/${editing.id}/configuration`, { settings: values });
      setConfiguration(response.data);
      setProviders((items) => items.map((item) => item.id === editing.id ? { ...item, configured: response.data.configured } : item));
      setEditing(null);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Provider configuration could not be saved.');
    } finally {
      setSaving(false);
    }
  };

  const saveLabel = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!labelEditing) return;
    setLabelSaving(true);
    setError(null);
    try {
      const response = await adminPut<ProviderItem>(`providers/${labelEditing.id}`, { name: labelValue });
      setProviders(items=>items.map(item=>item.id===labelEditing.id?response.data:item));
      setLabelEditing(null);
    } catch (reason) {
      setError(reason instanceof Error?reason.message:'Provider label could not be saved.');
    } finally {
      setLabelSaving(false);
    }
  };

  return (
    <section className="sbay-provider-content">
        <header>
          <div><small>Installed integrations</small><h2>Providers</h2><p>Manage the integrations registered with this SupportBay installation.</p></div>
          <button type="button" onClick={() => void load()} disabled={loading}>Refresh</button>
        </header>
        {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
        {notice ? <div className="sbay-admin-notice" role="status">{notice}</div> : null}
        {loading ? <Preloader label="Loading integrations…" /> : null}
        {!loading && providers.length === 0 ? <p className="sbay-provider-empty">No providers are installed.</p> : null}
        <div className="sbay-provider-grid">
          {providers.map((provider) => {
            const enabled = provider.status === 'enabled';
            const health = provider.has_error ? 'Connection issue' : provider.last_connected_at ? 'Connected' : 'Not tested';
            return <article key={provider.id} className={provider.has_error ? 'has-error' : ''}>
              <header>
                <div className="sbay-provider-mark" aria-hidden="true">{provider.name.slice(0, 1).toUpperCase()}</div>
                <div><h3>{provider.name}</h3><span>{provider.category.replace(/_/g, ' ')}</span></div>
                <strong className={enabled ? 'is-enabled' : ''}>{enabled ? 'Enabled' : 'Disabled'}</strong>
              </header>
              <dl>
                <div><dt>Version</dt><dd>{provider.version || '—'}</dd></div>
                <div><dt>Configuration</dt><dd>{provider.configured ? 'Configured' : 'Not configured'}</dd></div>
                <div><dt>Connection</dt><dd>{health}</dd></div>
                <div><dt>Last connected</dt><dd>{provider.last_connected_at || 'Never'}</dd></div>
              </dl>
              <footer>
                <span>{provider.has_error ? 'Review this provider before enabling it.' : provider.connection_test_available ? 'This provider supports a direct credential health check.' : provider.slug === 'envato' ? 'Connection is verified after a successful Envato OAuth login.' : provider.configured ? 'Connection testing is not provided by this integration.' : 'Configuration is required for authenticated features.'}</span>
                <div>{provider.connection_test_available ? <button type="button" disabled={!provider.configured || testing === provider.id} onClick={() => void testConnection(provider)}>{testing === provider.id ? 'Testing…' : 'Test connection'}</button> : null}<button type="button" onClick={()=>{setLabelEditing(provider);setLabelValue(provider.name);}}>Edit label</button><button type="button" onClick={() => void configure(provider)}>Configure</button><button type="button" className={enabled ? 'is-danger' : 'is-primary'} disabled={changing === provider.id} onClick={() => void transition(provider)}>{changing === provider.id ? 'Updating…' : enabled ? 'Disable' : 'Enable'}</button></div>
              </footer>
            </article>;
          })}
        </div>
        {editing ? <div className="sbay-provider-dialog" role="dialog" aria-modal="true" aria-labelledby="sbay-provider-dialog-title">
          <form onSubmit={saveConfiguration}>
            <header><div><small>Provider configuration</small><h2 id="sbay-provider-dialog-title">{editing.name}</h2></div><button type="button" aria-label="Close configuration" onClick={() => setEditing(null)}>×</button></header>
            {!configuration ? <Preloader label="Loading configuration…" compact /> : configuration.fields.map((field) => <label key={field.key}>
              <span>{field.label}{field.required ? ' *' : ''}</span>
              <input type={field.type === 'secret' ? 'password' : field.type} value={values[field.key] || ''} placeholder={field.type === 'secret' && field.configured ? 'Saved — leave blank to keep' : ''} required={field.required && !(field.type === 'secret' && field.configured)} onChange={(event) => setValues({ ...values, [field.key]: event.target.value })}/>
              {field.description ? <small>{field.description}</small> : null}
            </label>)}
            {configuration ? <footer><button type="button" onClick={() => setEditing(null)}>Cancel</button><button type="submit" className="is-primary" disabled={saving}>{saving ? 'Saving…' : 'Save configuration'}</button></footer> : null}
          </form>
        </div> : null}
        {labelEditing?<div className="sbay-provider-dialog" role="dialog" aria-modal="true" aria-labelledby="sbay-provider-label-title"><form onSubmit={saveLabel}><header><div><small>Ticket form option</small><h2 id="sbay-provider-label-title">Edit provider label</h2></div><button type="button" aria-label="Close label editor" onClick={()=>setLabelEditing(null)}>×</button></header><label><span>Provider label *</span><input type="text" maxLength={150} value={labelValue} required onChange={event=>setLabelValue(event.target.value)}/><small>Shown as this provider's option when the ticket form has multiple providers.</small></label><footer><button type="button" onClick={()=>setLabelEditing(null)}>Cancel</button><button type="submit" className="is-primary" disabled={labelSaving}>{labelSaving?'Saving…':'Save label'}</button></footer></form></div>:null}
    </section>
  );
}
