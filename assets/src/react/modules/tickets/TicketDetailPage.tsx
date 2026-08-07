import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalTicketDetail } from '../../api/types';
import { formatDate, formatDateTime } from '../../core/date';
import { FilePicker } from '../../components/FilePicker';

interface TicketDetailPageProps {
  ticketId: number;
  navigate: (path: string) => void;
}

export function TicketDetailPage({ ticketId, navigate }: TicketDetailPageProps) {
  const [detail, setDetail] = useState<PortalTicketDetail | null>(null);
  const [missing, setMissing] = useState(false);
  const [reply, setReply] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [files, setFiles] = useState<File[]>([]);

  useEffect(() => {
    portalApi.ticket(ticketId).then(setDetail).catch(() => setMissing(true));
  }, [ticketId]);

  if (missing) {
    return <p className="sbay-empty">This ticket could not be found.</p>;
  }

  if (!detail) {
    return <p className="sbay-empty">Loading ticket conversation…</p>;
  }

  const canReply = ['open', 'pending', 'answered'].includes(detail.ticket.status);

  const submitReply = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const message = await portalApi.reply(ticketId, reply);
      const attachments = await Promise.all(
        files.map((file) => portalApi.uploadAttachment(ticketId, message.id, file)),
      );
      setDetail({
        ...detail,
        messages: [...detail.messages, { ...message, attachments }],
      });
      setReply('');
      setFiles([]);
    } catch (exception) {
      setError(exception instanceof Error ? exception.message : 'Reply could not be added.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <section className="sbay-page">
      <button className="sbay-back" type="button" onClick={() => navigate('/support/tickets/')}>
        ← Back to tickets
      </button>
      <header className="sbay-ticket-header">
        <div>
          <span className="sbay-kicker">Ticket #{detail.ticket.track_id}</span>
          <h1>{detail.ticket.subject}</h1>
          <p>Opened {formatDate(detail.ticket.created_at)}</p>
        </div>
        <span className={`sbay-badge sbay-badge--${detail.ticket.status}`}>
          {detail.ticket.status}
        </span>
      </header>

      <div className="sbay-detail-grid">
        <section className="sbay-thread" aria-label="Ticket conversation">
          {detail.messages.length === 0 ? (
            <p className="sbay-empty">No messages have been added yet.</p>
          ) : detail.messages.map((message) => (
            <article
              className={message.author_type === 'customer' ? 'is-customer' : 'is-support'}
              key={message.id}
            >
              <div className="sbay-message__meta">
                <strong>{message.author_type === 'customer' ? 'You' : 'Support team'}</strong>
                <time>{formatDateTime(message.created_at)}</time>
              </div>
              <p>{message.content}</p>
              {message.attachments.length > 0 ? (
                <ul className="sbay-attachments" aria-label="Attachments">
                  {message.attachments.map((attachment) => (
                    <li key={attachment.id}>
                      <strong>{attachment.original_name}</strong>
                      <small>{Math.max(1, Math.round(attachment.file_size / 1024))} KB</small>
                    </li>
                  ))}
                </ul>
              ) : null}
            </article>
          ))}
          {canReply ? (
            <form className="sbay-reply-form" onSubmit={submitReply}>
              <label htmlFor="sbay-ticket-reply">Add a reply</label>
              <textarea
                id="sbay-ticket-reply"
                value={reply}
                onChange={(event) => setReply(event.target.value)}
                rows={5}
                required
              />
              <FilePicker files={files} onChange={setFiles} disabled={submitting} />
              {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
              <button className="sbay-primary-button" type="submit" disabled={submitting}>
                {submitting ? 'Sending…' : 'Send reply'}
              </button>
            </form>
          ) : (
            <p className="sbay-reply-closed">This ticket is closed to new replies.</p>
          )}
        </section>

        <aside className="sbay-ticket-aside">
          <h2>Ticket details</h2>
          <dl>
            <div><dt>Status</dt><dd>{detail.ticket.status}</dd></div>
            <div><dt>Priority</dt><dd>{detail.ticket.priority}</dd></div>
            <div><dt>Source</dt><dd>{detail.ticket.source}</dd></div>
          </dl>
          {detail.verification ? (
            <div className="sbay-linked-product">
              <span className="sbay-verified">Verified purchase</span>
              <h3>{detail.verification.product_name ?? 'Verified product'}</h3>
              <p>{detail.verification.license_type ?? 'Purchase verified'}</p>
            </div>
          ) : null}
        </aside>
      </div>
    </section>
  );
}
