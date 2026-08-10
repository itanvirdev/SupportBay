import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalAttachment, PortalTicketDetail } from '../../api/types';
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
  const [transitioning, setTransitioning] = useState(false);
  const [transitionError, setTransitionError] = useState<string | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);
  const [downloadError, setDownloadError] = useState<string | null>(null);

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

  const transitionTicket = async () => {
    const closing = detail.ticket.status !== 'closed';
    const confirmed = window.confirm(
      closing
        ? 'Close this ticket? You will not be able to reply until it is reopened.'
        : 'Reopen this ticket and continue the conversation?',
    );

    if (!confirmed) {
      return;
    }

    setTransitioning(true);
    setTransitionError(null);

    try {
      const ticket = closing
        ? await portalApi.closeTicket(ticketId)
        : await portalApi.reopenTicket(ticketId);
      setDetail({ ...detail, ticket });
    } catch (exception) {
      setTransitionError(
        exception instanceof Error
          ? exception.message
          : 'Ticket status could not be changed.',
      );
    } finally {
      setTransitioning(false);
    }
  };

  const downloadAttachment = async (attachment: PortalAttachment) => {
    setDownloadingId(attachment.id);
    setDownloadError(null);

    try {
      const blob = await portalApi.downloadAttachment(attachment.id);
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = attachment.original_name;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 0);
    } catch (exception) {
      setDownloadError(
        exception instanceof Error
          ? exception.message
          : 'Attachment could not be downloaded.',
      );
    } finally {
      setDownloadingId(null);
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
        <div className="sbay-ticket-actions">
          <span className={`sbay-badge sbay-badge--${detail.ticket.status}`}>
            {detail.ticket.status}
          </span>
          <button
            className={detail.ticket.status === 'closed' ? 'sbay-primary-button' : 'sbay-secondary-button'}
            type="button"
            onClick={transitionTicket}
            disabled={transitioning}
          >
            {transitioning
              ? 'Updating…'
              : detail.ticket.status === 'closed' ? 'Reopen ticket' : 'Close ticket'}
          </button>
        </div>
      </header>

      {transitionError ? <p className="sbay-form-error" role="alert">{transitionError}</p> : null}

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
              <div className="sbay-rich-content" dangerouslySetInnerHTML={{ __html: message.content }} />
              {message.attachments.length > 0 ? (
                <ul className="sbay-attachments" aria-label="Attachments">
                  {message.attachments.map((attachment) => (
                    <li key={attachment.id}>
                      <span>
                        <strong>{attachment.original_name}</strong>
                        <small>{Math.max(1, Math.round(attachment.file_size / 1024))} KB</small>
                      </span>
                      <button
                        type="button"
                        disabled={downloadingId === attachment.id}
                        onClick={() => downloadAttachment(attachment)}
                      >
                        {downloadingId === attachment.id ? 'Downloading…' : 'Download'}
                      </button>
                    </li>
                  ))}
                </ul>
              ) : null}
            </article>
          ))}
          {downloadError ? <p className="sbay-form-error" role="alert">{downloadError}</p> : null}
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
