import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalCategory, PortalCustomField, PortalPurchaseProvider, PortalTag } from '../../api/types';
import { FilePicker } from '../../components/FilePicker';
import { Preloader } from '../../../shared/components/Preloader';
import { RichTextEditor } from '../../../shared/editor/RichTextEditor';
import { getConfig } from '../../core/config';
import { TagMultiSelect } from '../../../shared/tickets/TagMultiSelect';

interface NewTicketPageProps {
  navigate: (path: string) => void;
}

export function NewTicketPage({ navigate }: NewTicketPageProps) {
  const config=getConfig();
  const [providers, setProviders] = useState<PortalPurchaseProvider[]>([]);
  const [categories, setCategories] = useState<PortalCategory[]>([]);
  const [tags, setTags] = useState<PortalTag[]>([]);
  const [tagIds, setTagIds] = useState<number[]>([]);
  const [customFields, setCustomFields] = useState<PortalCustomField[]>([]);
  const [customFieldValues, setCustomFieldValues] = useState<Record<number, string>>({});
  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');
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
    Promise.all([portalApi.categories(), portalApi.purchaseProviders(), portalApi.tags()])
      .then(([categoryData, providerData, tagData]) => {
        setCategories(categoryData);
        setProviders(providerData);
        setTags(tagData);
        setCategoryId(0);
        setProvider(providerData.length===1?providerData[0].slug:'');
      })
      .catch(() => setError('Ticket options could not be loaded.'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    setCategoriesLoading(true);
    setCustomFields([]);
    setCustomFieldValues({});
    portalApi.customFields(categoryId || null)
      .then(setCustomFields)
      .catch(() => setError('Ticket options could not be loaded.'))
      .finally(() => setCategoriesLoading(false));
  }, [categoryId]);

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const ticket = await portalApi.createTicket({
        subject,
        content,
        category_id: categoryId || null,
        provider,
        purchase_reference: purchaseReference.trim(),
        custom_fields: customFieldValues,
        tag_ids: tagIds,
      });
      if (files.length > 0) {
        const uploads = await Promise.allSettled(
          files.map((file) => portalApi.uploadAttachment(
            ticket.id,
            ticket.opening_message_id,
            file,
          )),
        );
        const failedUploads = uploads.filter((result) => result.status === 'rejected').length;
        if (failedUploads > 0) {
          window.sessionStorage.setItem(
            `sbay-ticket-attachment-warning:${ticket.id}`,
            failedUploads === files.length
              ? 'Your ticket was created, but its attachments could not be uploaded. You can attach them in a reply.'
              : `Your ticket was created, but ${failedUploads} attachment${failedUploads === 1 ? '' : 's'} could not be uploaded.`,
          );
        }
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
            <span>{config.purchaseProviderFieldLabel} <b aria-hidden="true">*</b></span>
            <select value={provider} onChange={(event) => {setProvider(event.target.value);setPurchaseReference('');}} required>
              <option value="">-- Select {config.purchaseProviderFieldLabel} --</option>
              {providers.map((item) => (
                <option value={item.slug} key={item.slug}>{item.name}</option>
              ))}
            </select>
          </label> : null}
          {categoriesLoading ? <Preloader label="Loading categories…" compact /> : (
            <label>
              <span>Category <b aria-hidden="true">*</b></span>
              <select value={categoryId} onChange={(event) => setCategoryId(Number(event.target.value))} required disabled={categories.length===0}>
                <option value={0}>-- Select Category --</option>
                {categories.map((category) => (
                  <option value={category.id} key={category.id}>{category.name}</option>
                ))}
              </select>
              {categories.length===0?<small>No active categories are available. Please contact the support team.</small>:null}
            </label>
          )}
          <label>
            <span>Subject</span>
            <input value={subject} onChange={(event) => setSubject(event.target.value)} required maxLength={255} />
          </label>
          <TagMultiSelect tags={tags} value={tagIds} change={setTagIds}/>
          {customFields.map((field) => {
            const value = customFieldValues[field.id] ?? '';
            const update = (nextValue: string) => setCustomFieldValues((current) => ({
              ...current,
              [field.id]: nextValue,
            }));

            if (field.type === 'textarea') {
              return <label key={field.id}><span>{field.name}</span><textarea placeholder={field.placeholder??undefined} value={value} onChange={(event) => update(event.target.value)} required={field.is_required} rows={4} /></label>;
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
                <input type={field.type} placeholder={field.placeholder??undefined} value={value} onChange={(event) => update(event.target.value)} required={field.is_required} />
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
            <RichTextEditor value={content} onChange={setContent} disabled={submitting} />
          </label>
          {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
          <button className="sbay-primary-button" type="submit" disabled={submitting || categoriesLoading || categoryId === 0 || (providers.length > 1 && provider === '')}>
            {submitting ? 'Creating…' : 'Create ticket'}
          </button>
        </form>
      )}
    </section>
  );
}
