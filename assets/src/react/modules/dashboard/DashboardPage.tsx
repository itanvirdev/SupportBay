import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type {
  PortalOverview,
  PortalTicket,
  PortalVerification,
} from '../../api/types';
import { formatDate } from '../../core/date';
import { getConfig } from '../../core/config';

interface DashboardPageProps {
  overview: PortalOverview;
  navigate: (path: string) => void;
}

export function DashboardPage({ overview, navigate }: DashboardPageProps) {
  const config=getConfig();
  const [tickets, setTickets] = useState<PortalTicket[]>([]);
  const [verifications, setVerifications] = useState<PortalVerification[]>([]);

  useEffect(() => {
    Promise.all([portalApi.tickets(), portalApi.verifications()]).then(
      ([ticketData, verificationData]) => {
        setTickets(ticketData.data);
        setVerifications(verificationData);
      },
    );
  }, []);

  const firstName = overview.customer.company ?? 'there';

  return (
    <>
      <header className="sbay-header">
        <div>
          <span className="sbay-kicker">Customer workspace</span>
          <h1>Welcome back, {firstName}.</h1>
          <p>Here&apos;s the latest across your products and support.</p>
        </div>
      </header>

      <section className="sbay-stats" aria-label="Account summary">
        <article>
          <span>Support tickets</span>
          <strong>{overview.summary.tickets}</strong>
          <small>All conversations</small>
        </article>
        <article>
          <span>Verified purchases</span>
          <strong>{overview.summary.verifications}</strong>
          <small>Connected products</small>
        </article>
        <article className="sbay-stats__accent">
          <span>Account standing</span>
          <strong>{overview.customer.state}</strong>
          <small>Ready for support</small>
        </article>
      </section>

      <div className="sbay-grid">
        <section className="sbay-panel">
          <div className="sbay-panel__heading">
            <div>
              <span className="sbay-kicker">Recent activity</span>
              <h2>Your tickets</h2>
            </div>
            <button
              className="sbay-text-button"
              type="button"
              onClick={() => navigate('/support/tickets/')}
            >
              View all
            </button>
          </div>
          {tickets.length === 0 ? (
            <p className="sbay-empty">No support conversations yet.</p>
          ) : (
            <div className="sbay-list">
              {tickets.slice(0, 4).map((ticket) => (
                <button
                  className="sbay-list__row"
                  type="button"
                  key={ticket.id}
                  onClick={() => navigate(`/support/tickets/${ticket.id}/`)}
                >
                  <span className={`sbay-status sbay-status--${ticket.status}`}>
                    {config.ticketStatusLabels[ticket.status]??ticket.status}
                  </span>
                  <span>
                    <strong>{ticket.subject}</strong>
                    <small>Updated {formatDate(ticket.updated_at)}</small>
                  </span>
                  <em>{ticket.priority}</em>
                </button>
              ))}
            </div>
          )}
        </section>

        <section className="sbay-panel">
          <div className="sbay-panel__heading">
            <div>
              <span className="sbay-kicker">Entitlements</span>
              <h2>Verified purchases</h2>
            </div>
            <button
              className="sbay-text-button"
              type="button"
              onClick={() => navigate('/support/purchases/')}
            >
              View all
            </button>
          </div>
          {verifications.length === 0 ? (
            <p className="sbay-empty">No verified purchases connected.</p>
          ) : (
            <div className="sbay-products">
              {verifications.slice(0, 3).map((verification) => (
                <article key={verification.id}>
                  <span className="sbay-product-mark">
                    {(verification.product_name ?? 'P').charAt(0)}
                  </span>
                  <div>
                    <h3>{verification.product_name ?? 'Verified product'}</h3>
                    <p>{verification.license_type ?? 'Purchase verified'}</p>
                    <small>
                      Support until {formatDate(verification.support_expires_at)}
                    </small>
                  </div>
                  <span className="sbay-verified">Verified</span>
                </article>
              ))}
            </div>
          )}
        </section>
      </div>
    </>
  );
}
