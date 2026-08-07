import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalDepartment, PortalVerification } from '../../api/types';

interface NewTicketPageProps {
  navigate: (path: string) => void;
}

export function NewTicketPage({ navigate }: NewTicketPageProps) {
  const [departments, setDepartments] = useState<PortalDepartment[]>([]);
  const [purchases, setPurchases] = useState<PortalVerification[]>([]);
  const [subject, setSubject] = useState('');
  const [content, setContent] = useState('');
  const [departmentId, setDepartmentId] = useState(0);
  const [purchaseId, setPurchaseId] = useState(0);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    Promise.all([portalApi.departments(), portalApi.verifications()])
      .then(([departmentData, purchaseData]) => {
        setDepartments(departmentData);
        setPurchases(purchaseData);
        setDepartmentId(departmentData[0]?.id ?? 0);
      })
      .catch(() => setError('Ticket options could not be loaded.'))
      .finally(() => setLoading(false));
  }, []);

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const ticket = await portalApi.createTicket({
        subject,
        content,
        department_id: departmentId,
        purchase_verification_id: purchaseId || null,
      });
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

      {loading ? <p className="sbay-empty">Loading ticket options…</p> : (
        <form className="sbay-ticket-form" onSubmit={submit}>
          <label>
            <span>Subject</span>
            <input value={subject} onChange={(event) => setSubject(event.target.value)} required maxLength={255} />
          </label>
          <label>
            <span>Department</span>
            <select value={departmentId} onChange={(event) => setDepartmentId(Number(event.target.value))} required>
              {departments.map((department) => (
                <option value={department.id} key={department.id}>{department.name}</option>
              ))}
            </select>
          </label>
          <label>
            <span>Verified purchase <small>Optional</small></span>
            <select value={purchaseId} onChange={(event) => setPurchaseId(Number(event.target.value))}>
              <option value={0}>No linked purchase</option>
              {purchases.map((purchase) => (
                <option value={purchase.id} key={purchase.id}>{purchase.product_name ?? 'Verified product'}</option>
              ))}
            </select>
          </label>
          <label>
            <span>How can we help?</span>
            <textarea value={content} onChange={(event) => setContent(event.target.value)} rows={8} required />
          </label>
          {departments.length === 0 ? <p className="sbay-form-error">No support departments are currently available.</p> : null}
          {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
          <button className="sbay-primary-button" type="submit" disabled={submitting || departments.length === 0}>
            {submitting ? 'Creating…' : 'Create ticket'}
          </button>
        </form>
      )}
    </section>
  );
}
