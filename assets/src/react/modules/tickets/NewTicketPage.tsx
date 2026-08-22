import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalCategory, PortalCustomField, PortalDepartment, PortalPurchaseProvider } from '../../api/types';
import { FilePicker } from '../../components/FilePicker';
import { Preloader } from '../../../shared/components/Preloader';
import { getConfig } from '../../core/config';

interface NewTicketPageProps {
  navigate: (path: string) => void;
}

export function NewTicketPage({ navigate }: NewTicketPageProps) {
  const config=getConfig();
  const [departments, setDepartments] = useState<PortalDepartment[]>([]);
  const [providers, setProviders] = useState<PortalPurchaseProvider[]>([]);
  const [categories, setCategories] = useState<PortalCategory[]>([]);
  const [customFields, setCustomFields] = useState<PortalCustomField[]>([]);
  const [customFieldValues, setCustomFieldValues] = useState<Record<number, string>>({});
  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');
  const [departmentId, setDepartmentId] = useState(0);
  const [categoryId, setCategoryId] = useState(0);
  const [provider, setProvider] = useState('');
  const [purchaseReference, setPurchaseReference] = useState('');
  const [loading, setLoading] = useState(true);
  const [categoriesLoading, setCategoriesLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [files, setFiles] = useState<File[]>([]);
  const selectedProvider=providers.find(item=>item.slug===provider)??null;

  useEffect(() => {
    Promise.all([portalApi.departments(), portalApi.purchaseProviders()])
      .then(([departmentData, providerData]) => {
        setDepartments(departmentData);
        setProviders(providerData);
        setDepartmentId(departmentData[0]?.id ?? 0);
        setProvider(providerData[0]?.slug ?? '');
      })
      .catch(() => setError('Ticket options could not be loaded.'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!departmentId) {
      setCategories([]);
      setCategoryId(0);
      setCustomFields([]);
      setCustomFieldValues({});
      return;
    }

    setCategoriesLoading(true);
    setCategories([]);
    setCategoryId(0);
    setCustomFields([]);
    setCustomFieldValues({});
    Promise.all([
      portalApi.categories(departmentId),
      portalApi.customFields(departmentId),
    ])
      .then(([categoryItems, fieldItems]) => {
        setCategories(categoryItems);
        setCategoryId(categoryItems[0]?.id ?? 0);
        setCustomFields(fieldItems);
      })
      .catch(() => setError('Ticket options could not be loaded.'))
      .finally(() => setCategoriesLoading(false));
  }, [departmentId]);

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const ticket = await portalApi.createTicket({
        subject,
        content,
        department_id: departmentId,
        category_id: categoryId || null,
        provider,
        purchase_reference: purchaseReference.trim(),
        custom_fields: customFieldValues,
      });
      const detail = await portalApi.ticket(ticket.id);
      const openingMessage = detail.messages[0];

      if (openingMessage) {
        await Promise.all(
          files.map((file) => portalApi.uploadAttachment(
            ticket.id,
            openingMessage.id,
            file,
          )),
        );
      }
      navigate(`/support/tickets/${ticket.id}/`);
    } catch (exception) {
      setError(exception instanceof Error ? exception.message : 'Ticket could not be created.');
      setSubmitting(false);
    }
  };

  return (
    <section className="sbay-page sbay-form-page">
      <button className="sbay-back" type="button" onClick={() => navigate('/support/tickets/')}>
        ← Back to tickets
      </button>
      <header className="sbay-page__header">
        <div>
          <span className="sbay-kicker">Start a conversation</span>
          <h1>Create a ticket</h1>
          <p>Tell the support team what you need help with.</p>
        </div>
      </header>

      {loading ? <Preloader label="Loading ticket options…" /> : (
        <form className="sbay-ticket-form" onSubmit={submit}>
          {providers.length > 1 ? <label>
            <span>{config.purchaseProviderFieldLabel}</span>
            <select value={provider} onChange={(event) => setProvider(event.target.value)} required>
              {providers.map((item) => (
                <option value={item.slug} key={item.slug}>{item.name}</option>
              ))}
            </select>
          </label> : null}
          <label>
            <span>Subject</span>
            <input value={subject} onChange={(event) => setSubject(event.target.value)} required maxLength={255} />
          </label>
          {departments.length > 1 ? <label>
            <span>Department</span>
            <select value={departmentId} onChange={(event) => setDepartmentId(Number(event.target.value))} required>
              {departments.map((department) => (
                <option value={department.id} key={department.id}>{department.name}</option>
              ))}
            </select>
          </label> : null}
          {categoriesLoading ? <Preloader label="Loading categories…" compact /> : categories.length ? (
            <label>
              <span>Category</span>
              <select value={categoryId} onChange={(event) => setCategoryId(Number(event.target.value))} required>
                {categories.map((category) => (
                  <option value={category.id} key={category.id}>{category.name}</option>
                ))}
              </select>
            </label>
          ) : null}
          {customFields.map((field) => {
            const value = customFieldValues[field.id] ?? '';
            const update = (nextValue: string) => setCustomFieldValues((current) => ({
              ...current,
              [field.id]: nextValue,
            }));

            if (field.type === 'textarea') {
              return <label key={field.id}><span>{field.name}</span><textarea value={value} onChange={(event) => update(event.target.value)} required={field.is_required} rows={4} /></label>;
            }
            if (field.type === 'select') {
              return (
                <label key={field.id}>
                  <span>{field.name}</span>
                  <select value={value} onChange={(event) => update(event.target.value)} required={field.is_required}>
                    <option value="">Select {field.name}</option>
                    {field.options.map((option) => <option value={option} key={option}>{option}</option>)}
                  </select>
                </label>
              );
            }
            if (field.type === 'checkbox') {
              return (
                <label className="sbay-checkbox-field" key={field.id}>
                  <input type="checkbox" checked={value === '1'} onChange={(event) => update(event.target.checked ? '1' : '0')} required={field.is_required} />
                  <span>{field.name}</span>
                </label>
              );
            }
            return (
              <label key={field.id}>
                <span>{field.name}</span>
                <input type={field.type} value={value} onChange={(event) => update(event.target.value)} required={field.is_required} />
              </label>
            );
          })}
          {config.fileUploadEnabled?<FilePicker files={files} onChange={setFiles} disabled={submitting} maxSizeMb={config.fileUploadMaxSizeMb} allowedExtensions={config.fileUploadAllowedExtensions}/>:null}
          {selectedProvider?<label>
            <span>{selectedProvider.purchase_field_label}</span>
            <input value={purchaseReference} onChange={(event) => setPurchaseReference(event.target.value)} required={selectedProvider.license_required} autoComplete="off" />
            <small>{selectedProvider.license_required?'A valid purchase code/key is required.':'Purchase verification is optional.'}{selectedProvider.check_support_expiry?' Active support is required.':''}</small>
          </label>:null}
          <label>
            <span>How can we help?</span>
            <textarea value={content} onChange={(event) => setContent(event.target.value)} rows={8} required />
          </label>
          {departments.length === 0 ? <p className="sbay-form-error">No support departments are currently available.</p> : null}
          {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
          <button className="sbay-primary-button" type="submit" disabled={submitting || categoriesLoading || departments.length === 0 || (categories.length > 0 && categoryId === 0)}>
            {submitting ? 'Creating…' : 'Create ticket'}
          </button>
        </form>
      )}
    </section>
  );
}
