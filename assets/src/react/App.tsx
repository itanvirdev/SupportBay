import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { portalApi } from './api/portal';
import type { PortalOverview } from './api/types';
import { PortalLayout, type PortalRoute } from './components/PortalLayout';
import { PortalState } from './components/PortalState';
import { DashboardPage } from './modules/dashboard/DashboardPage';
import { PurchasesPage } from './modules/purchases/PurchasesPage';
import { TicketDetailPage } from './modules/tickets/TicketDetailPage';
import { TicketsPage } from './modules/tickets/TicketsPage';
import './styles/portal.scss';

interface RouteMatch {
  active: PortalRoute;
  ticketId?: number;
}

function matchRoute(pathname: string): RouteMatch {
  const ticketMatch = pathname.match(/^\/support\/tickets\/(\d+)\/?$/);

  if (ticketMatch) {
    return { active: 'tickets', ticketId: Number(ticketMatch[1]) };
  }

  if (/^\/support\/tickets\/?$/.test(pathname)) {
    return { active: 'tickets' };
  }

  if (/^\/support\/purchases\/?$/.test(pathname)) {
    return { active: 'purchases' };
  }

  return { active: 'overview' };
}

function App() {
  const [overview, setOverview] = useState<PortalOverview | null>(null);
  const [pathname, setPathname] = useState(window.location.pathname);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    portalApi.overview().then(setOverview).catch(() => setFailed(true));
  }, []);

  useEffect(() => {
    const onPopState = () => setPathname(window.location.pathname);
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  const navigate = (path: string) => {
    window.history.pushState({}, '', path);
    setPathname(path);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (failed) {
    return <PortalState title="We couldn't load your portal" message="Please try again in a moment." />;
  }

  if (!overview) {
    return <PortalState loading message="Loading your support workspace…" />;
  }

  const route = matchRoute(pathname);
  let page = <DashboardPage overview={overview} navigate={navigate} />;

  if (route.active === 'tickets') {
    page = route.ticketId
      ? <TicketDetailPage ticketId={route.ticketId} navigate={navigate} />
      : <TicketsPage navigate={navigate} />;
  } else if (route.active === 'purchases') {
    page = <PurchasesPage />;
  }

  return (
    <PortalLayout overview={overview} active={route.active} navigate={navigate}>
      {page}
    </PortalLayout>
  );
}

const root = document.getElementById('supportbay-customer-portal');

if (root) {
  createRoot(root).render(
    <StrictMode>
      <App />
    </StrictMode>,
  );
}
