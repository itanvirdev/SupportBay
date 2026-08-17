import { useEffect, useState } from 'react';
import { adminGet, adminPost, adminPut } from './api';
import { Preloader } from '../shared/components/Preloader';

interface NotificationTemplate {
  key: string;
  name: string;
  event: string;
  recipient_type: 'customer' | 'agent' | 'manager';
  status: 'active' | 'inactive';
  subject: string;
  html_content: string;
  plain_text_content: string;
}

interface TemplatePreview {
  subject: string;
  html_content: string;
  plain_text_content: string;
}

interface TemplateMeta {
  placeholders?: string[];
}

interface NotificationPreferences {
  enabled: boolean;
  events: Record<string, Record<string, boolean>>;
}

type DraftField = 'subject' | 'html_content' | 'plain_text_content';

export function NotificationTemplateWorkspace() {
  const [templates, setTemplates] = useState<NotificationTemplate[]>([]);
  const [preferences, setPreferences] = useState<NotificationPreferences | null>(null);
  const [selectedKey, setSelectedKey] = useState('');
  const [draft, setDraft] = useState<NotificationTemplate | null>(null);
  const [placeholders, setPlaceholders] = useState<string[]>([]);
  const [preview, setPreview] = useState<TemplatePreview | null>(null);
  const [previewMode, setPreviewMode] = useState<'desktop' | 'mobile'>('desktop');
  const [activeField, setActiveField] = useState<DraftField>('plain_text_content');
  const [testRecipient, setTestRecipient] = useState('');
  const [loading, setLoading] = useState(true);
  const [working, setWorking] = useState<'preferences' | 'save' | 'reset' | 'preview' | 'test' | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const load = async () => {
    setLoading(true);
    setError(null);
    try {
      const [response, preferenceResponse] = await Promise.all([
        adminGet<NotificationTemplate[]>('admin/notification-templates'),
        adminGet<NotificationPreferences>('admin/notification-preferences'),
      ]);
      setTemplates(response.data);
      setPreferences(preferenceResponse.data);
      const meta = response.meta as TemplateMeta;
      setPlaceholders(Array.isArray(meta.placeholders) ? meta.placeholders : []);
      const nextKey = selectedKey || response.data[0]?.key || '';
      setSelectedKey(nextKey);
      setDraft(response.data.find((template) => template.key === nextKey) ?? null);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification templates could not be loaded.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { void load(); }, []);

  const path = draft ? `admin/notification-templates/${draft.event}/${draft.recipient_type}` : '';
  const payload = draft ? {
    status: draft.status,
    subject: draft.subject,
    html_content: draft.html_content,
    plain_text_content: draft.plain_text_content,
  } : {};

  const run = async (action: 'save' | 'reset' | 'preview' | 'test') => {
    if (!draft || !path) return;
    setWorking(action); setError(null); setNotice(null);
    try {
      if (action === 'save') {
        const response = await adminPut<NotificationTemplate>(path, payload);
        setTemplates((items) => items.map((item) => item.key === response.data.key ? response.data : item));
        setDraft(response.data);
        setNotice('Template saved.');
      } else if (action === 'reset') {
        const response = await adminPost<NotificationTemplate>(`${path}/reset`, {});
        setTemplates((items) => items.map((item) => item.key === response.data.key ? response.data : item));
        setDraft(response.data);
        setNotice('Default template restored.');
      } else if (action === 'preview') {
        const response = await adminPost<TemplatePreview>(`${path}/preview`, payload);
        setPreview(response.data);
      } else {
        await adminPost(`${path}/test-email`, { ...payload, test_recipient: testRecipient });
        setNotice(`Test email sent to ${testRecipient}.`);
      }
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'The template action could not be completed.');
    } finally {
      setWorking(null);
    }
  };

  const savePreferences = async () => {
    if (!preferences) return;
    setWorking('preferences'); setError(null); setNotice(null);
    try {
      const response = await adminPut<NotificationPreferences>('admin/notification-preferences', preferences);
      setPreferences(response.data);
      setNotice('Notification preferences saved.');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Notification preferences could not be saved.');
    } finally {
      setWorking(null);
    }
  };

  const toggleEvent = (event: string, recipient: string, enabled: boolean) => {
    if (!preferences) return;
    setPreferences({
      ...preferences,
      events: {
        ...preferences.events,
        [event]: { ...preferences.events[event], [recipient]: enabled },
      },
    });
  };

  const change = (field: keyof NotificationTemplate, value: string) => {
    if (draft) setDraft({ ...draft, [field]: value });
  };

  const insertPlaceholder = (placeholder: string) => {
    if (!draft) return;
    const token = `{{${placeholder}}}`;
    setDraft({ ...draft, [activeField]: `${draft[activeField]}${draft[activeField] ? ' ' : ''}${token}` });
  };

  return <section className="sbay-template-workspace">
    <header>
      <div><small>Email notifications</small><h2>Notification Templates</h2><p>Edit customer and team email content. Delivery uses your WordPress mail configuration.</p></div>
      <button type="button" onClick={() => void load()} disabled={loading}>Refresh</button>
    </header>
    {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}
    {notice ? <div className="sbay-admin-notice" role="status">{notice}</div> : null}
    {loading ? <Preloader label="Loading notification settings…" /> : null}
    {!loading && preferences ? <section className="sbay-notification-preferences">
      <header><div><small>Delivery controls</small><h3>Email Preferences</h3><p>The master switch and event recipients must be enabled before an active template can be queued.</p></div><label className="sbay-switch"><input type="checkbox" checked={preferences.enabled} onChange={(event) => setPreferences({ ...preferences, enabled: event.target.checked })}/><span>{preferences.enabled ? 'Email enabled' : 'Email paused'}</span></label></header>
      <div className={!preferences.enabled ? 'is-disabled' : ''}>{Object.entries(preferences.events).map(([event, recipients]) => <article key={event}><strong>{event.replace(/_/g, ' ')}</strong><div>{Object.entries(recipients).map(([recipient, enabled]) => <label key={recipient}><input type="checkbox" checked={enabled} disabled={!preferences.enabled} onChange={(changeEvent) => toggleEvent(event, recipient, changeEvent.target.checked)}/><span>{recipient}</span></label>)}</div></article>)}</div>
      <footer><button type="button" className="is-primary" disabled={working !== null} onClick={() => void savePreferences()}>{working === 'preferences' ? 'Saving…' : 'Save preferences'}</button></footer>
    </section> : null}
    {!loading && templates.length === 0 ? <p className="sbay-provider-empty">No notification templates are available.</p> : null}
    {!loading && draft ? <div className="sbay-template-layout">
      <aside aria-label="Notification templates">
        {templates.map((template) => <button type="button" key={template.key} className={template.key === draft.key ? 'is-active' : ''} onClick={() => { setSelectedKey(template.key); setDraft({ ...template }); setPreview(null); setNotice(null); }}>
          <strong>{template.name}</strong><span>{template.recipient_type} · {template.status}</span>
        </button>)}
      </aside>
      <div className="sbay-template-main">
        <div className="sbay-template-editor">
          <header><div><small>{draft.event.replace(/_/g, ' ')}</small><h3>{draft.name}</h3></div><label><span>Status</span><select value={draft.status} onChange={(event) => change('status', event.target.value)}><option value="active">Active</option><option value="inactive">Inactive</option></select></label></header>
          <label><span>Subject</span><input value={draft.subject} onFocus={() => setActiveField('subject')} onChange={(event) => change('subject', event.target.value)}/></label>
          <label><span>HTML content</span><textarea rows={9} value={draft.html_content} onFocus={() => setActiveField('html_content')} onChange={(event) => change('html_content', event.target.value)}/></label>
          <label><span>Plain-text content</span><textarea rows={8} value={draft.plain_text_content} onFocus={() => setActiveField('plain_text_content')} onChange={(event) => change('plain_text_content', event.target.value)}/></label>
          <section className="sbay-template-placeholders"><strong>Available placeholders</strong><p>Click to insert into the last selected field.</p><div>{placeholders.map((placeholder) => <button type="button" key={placeholder} onClick={() => insertPlaceholder(placeholder)}>{`{{${placeholder}}}`}</button>)}</div></section>
          <footer><button type="button" onClick={() => void run('reset')} disabled={working !== null}>Reset default</button><button type="button" onClick={() => void run('preview')} disabled={working !== null}>{working === 'preview' ? 'Rendering…' : 'Preview'}</button><button type="button" className="is-primary" onClick={() => void run('save')} disabled={working !== null}>{working === 'save' ? 'Saving…' : 'Save template'}</button></footer>
        </div>
        <aside className="sbay-template-preview">
          <header><h3>Preview</h3><div><button type="button" className={previewMode === 'desktop' ? 'is-active' : ''} onClick={() => setPreviewMode('desktop')}>Desktop</button><button type="button" className={previewMode === 'mobile' ? 'is-active' : ''} onClick={() => setPreviewMode('mobile')}>Mobile</button></div></header>
          {preview ? <article className={`is-${previewMode}`}><strong>{preview.subject}</strong><div dangerouslySetInnerHTML={{ __html: preview.html_content }}/><details><summary>Plain text</summary><pre>{preview.plain_text_content}</pre></details></article> : <p>Choose Preview to render this draft with sample ticket and customer data.</p>}
          <form onSubmit={(event) => { event.preventDefault(); void run('test'); }}><label><span>Send test email</span><input type="email" value={testRecipient} onChange={(event) => setTestRecipient(event.target.value)} placeholder="you@example.com" required/></label><button disabled={working !== null}>{working === 'test' ? 'Sending…' : 'Send test'}</button></form>
        </aside>
      </div>
    </div> : null}
  </section>;
}
