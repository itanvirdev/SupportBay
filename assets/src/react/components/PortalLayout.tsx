import type { ReactNode } from 'react';
import type { PortalOverview } from '../api/types';
import { getConfig } from '../core/config';

export type PortalRoute = 'overview' | 'tickets' | 'purchases' | 'profile';

interface PortalLayoutProps {
  overview: PortalOverview;
  active: PortalRoute;
  navigate: (path: string) => void;
  children: ReactNode;
}

export function PortalLayout({
  overview,
  active,
  navigate,
  children,
}: PortalLayoutProps) {
  const name = overview.customer.company ?? 'Customer';
  const config = getConfig();

  const link = (route: PortalRoute, path: string, label: string) => (
    <a
      className={active === route ? 'is-active' : undefined}
      href={path}
      onClick={(event) => {
        event.preventDefault();
        navigate(path);
      }}
    >
      {label}
    </a>
  );

  return (
    <div className="sbay-shell">
      <aside className="sbay-sidebar">
        <a
          className="sbay-brand"
          href="/support/"
          aria-label="SupportBay dashboard"
          onClick={(event) => {
            event.preventDefault();
            navigate('/support/');
          }}
        >
          <img src={config.portalLogoUrl} alt={config.siteName}/>
        </a>
        <nav aria-label="Customer portal">
          {link('overview', '/support/', 'Overview')}
          {link('tickets', '/support/tickets/', 'Tickets')}
          {link('purchases', '/support/purchases/', 'Purchases')}
          {config.wordpressProfileEnabled
            ? <a href={config.wordpressProfileUrl}>Profile</a>
            : link('profile', '/support/profile/', 'Profile')}
        </nav>
        <div className="sbay-sidebar__help">
          <span>Need a hand?</span>
          <p>Your support history and purchases stay together here.</p>
        </div>
      </aside>

      <main className="sbay-main">
        <div className="sbay-topbar">
          <span className="sbay-account">
            {overview.customer.avatar_url ? (
              <img src={overview.customer.avatar_url} alt="" />
            ) : (
              <span>{name.charAt(0).toUpperCase()}</span>
            )}
            <strong>{name}</strong>
          </span>
          <a className="sbay-logout" href={config.logoutUrl}>Sign out</a>
        </div>
        {config.availabilityNotices.map(notice=><aside className={`sbay-availability-notice is-${notice.type}`} role="status" key={notice.type}>{notice.message}</aside>)}
        {children}
      </main>
    </div>
  );
}
