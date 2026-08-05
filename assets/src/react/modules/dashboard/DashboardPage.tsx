import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type {
  PortalOverview,
  PortalTicket,
  PortalVerification,
} from '../../api/types';

interface DashboardData {
  overview: PortalOverview;
  tickets: PortalTicket[];
  verifications: PortalVerification[];
}

function formatDate(value: string | null): string {
  if (!value) return 'Not available';

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value.replace(' ', 'T')));
}

export function DashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    Promise.all([
      portalApi.overview(),
      portalApi.tickets(),
      portalApi.verifications(),
    ])
      .then(([overview, tickets, verifications]) => {
        if (active) setData({ overview, tickets, verifications });
      })
      .catch((reason: unknown) => {
        if (active) {
          setError(
            reason instanceof Error
              ? reason.message
              : 'The portal could not be loaded.',
          );
        }
      });

    return () => {
      active = false;
    };
  }, []);

  if (error) {
    return (
      <main className="sbay-state" role="alert">
        <span className="sbay-state__eyebrow">Connection interrupted</span>
        <h1>We couldn&apos;t open your support dashboard.</h1>
        <p>{error}</p>
        <button type="button" onClick={() => window.location.reload()}>
          Try again
        </button>
      </main>
    );
  }

  if (!data) {
    return (
      <main className="sbay-state" aria-live="polite">
        <div className="sbay-loader" />
        <p>Preparing your SupportBay workspace…</p>
      </main>
    );
  }

  const { overview, tickets, verifications } = data;
  const firstName = overview.customer.company ?? 'there';

  return (
    <div className="sbay-shell">
      <aside className="sbay-sidebar">
        <a className="sbay-brand" href="./" aria-label="SupportBay dashboard">
          <span className="sbay-brand__mark">S</span>
          <span>SupportBay</span>
        </a>
        <nav aria-label="Customer portal">
          <a className="is-active" href="./">Overview</a>
          <a href="#tickets">Tickets</a>
          <a href="#purchases">Purchases</a>
        </nav>
        <div className="sbay-sidebar__help">
          <span>Need a hand?</span>
          <p>Your support history and purchases stay together here.</p>
        </div>
      </aside>

      <main className="sbay-main">
        <header className="sbay-header">
          <div>
            <span className="sbay-kicker">Customer workspace</span>
            <h1>Welcome back, {firstName}.</h1>
            <p>Here&apos;s the latest across your products and support.</p>
          </div>
          <span className="sbay-account">
            {overview.customer.avatar_url ? (
              <img src={overview.customer.avatar_url} alt="" />
            ) : (
              <span>{firstName.charAt(0).toUpperCase()}</span>
            )}
            <strong>{overview.customer.state}</strong>
          </span>
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
          <section className="sbay-panel" id="tickets">
            <div className="sbay-panel__heading">
              <div>
                <span className="sbay-kicker">Recent activity</span>
                <h2>Your tickets</h2>
              </div>
              <span className="sbay-count">{tickets.length}</span>
            </div>
            {tickets.length === 0 ? (
              <p className="sbay-empty">No support conversations yet.</p>
            ) : (
              <div className="sbay-list">
                {tickets.slice(0, 4).map((ticket) => (
                  <article key={ticket.id}>
                    <span className={`sbay-status sbay-status--${ticket.status}`}>
                      {ticket.status}
                    </span>
                    <div>
                      <h3>{ticket.subject}</h3>
                      <p>Updated {formatDate(ticket.updated_at)}</p>
                    </div>
                    <strong>{ticket.priority}</strong>
                  </article>
                ))}
              </div>
            )}
          </section>

          <section className="sbay-panel" id="purchases">
            <div className="sbay-panel__heading">
              <div>
                <span className="sbay-kicker">Entitlements</span>
                <h2>Verified purchases</h2>
              </div>
              <span className="sbay-count">{verifications.length}</span>
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
      </main>
    </div>
  );
}
