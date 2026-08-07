import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalTicketDetail } from '../../api/types';
import { formatDate, formatDateTime } from '../../core/date';

interface TicketDetailPageProps {
  ticketId: number;
  navigate: (path: string) => void;
}

export function TicketDetailPage({ ticketId, navigate }: TicketDetailPageProps) {
  const [detail, setDetail] = useState<PortalTicketDetail | null>(null);
  const [missing, setMissing] = useState(false);

  useEffect(() => {
    portalApi.ticket(ticketId).then(setDetail).catch(() => setMissing(true));
  }, [ticketId]);

  if (missing) {
    return <p className="sbay-empty">This ticket could not be found.</p>;
  }

  if (!detail) {
    return <p className="sbay-empty">Loading ticket conversation…</p>;
  }

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
            </article>
          ))}
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
