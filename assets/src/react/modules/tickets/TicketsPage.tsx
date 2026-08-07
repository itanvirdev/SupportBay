import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalTicket } from '../../api/types';
import { formatDate } from '../../core/date';

interface TicketsPageProps {
  navigate: (path: string) => void;
}

export function TicketsPage({ navigate }: TicketsPageProps) {
  const [tickets, setTickets] = useState<PortalTicket[] | null>(null);

  useEffect(() => {
    portalApi.tickets().then(setTickets);
  }, []);

  return (
    <section className="sbay-page">
      <header className="sbay-page__header">
        <div>
          <span className="sbay-kicker">Support history</span>
          <h1>Your tickets</h1>
          <p>Track every conversation and its current status.</p>
        </div>
        <div className="sbay-page__actions">
          <span className="sbay-page__total">{tickets?.length ?? 0} total</span>
          <button
            className="sbay-primary-button"
            type="button"
            onClick={() => navigate('/support/tickets/new/')}
          >
            Create ticket
          </button>
        </div>
      </header>

      <div className="sbay-table-card">
        <div className="sbay-table sbay-table--head" aria-hidden="true">
          <span>Ticket</span><span>Status</span><span>Priority</span><span>Updated</span>
        </div>
        {!tickets ? (
          <p className="sbay-empty">Loading your tickets…</p>
        ) : tickets.length === 0 ? (
          <p className="sbay-empty">You haven&apos;t opened a ticket yet.</p>
        ) : tickets.map((ticket) => (
          <button
            className="sbay-table"
            type="button"
            key={ticket.id}
            onClick={() => navigate(`/support/tickets/${ticket.id}/`)}
          >
            <span>
              <strong>{ticket.subject}</strong>
              <small>#{ticket.track_id}</small>
            </span>
            <span><i className={`sbay-dot sbay-dot--${ticket.status}`} />{ticket.status}</span>
            <span className="sbay-capitalize">{ticket.priority}</span>
            <span>{formatDate(ticket.updated_at)}</span>
          </button>
        ))}
      </div>
    </section>
  );
}
