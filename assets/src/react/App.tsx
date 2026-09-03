import { lazy, StrictMode, Suspense, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { portalApi } from './api/portal';
import type { PortalOverview } from './api/types';
import { PortalLayout, type PortalRoute } from './components/PortalLayout';
import { PortalState } from './components/PortalState';
import { getConfig } from './core/config';
import { SupportBayConfigProvider } from './core/ConfigProvider';
import './styles/portal.scss';

const AuthPage = lazy(() => import('./modules/auth/AuthPage').then((module) => ({ default: module.AuthPage })));
const ResetPasswordPage = lazy(() => import('./modules/auth/ResetPasswordPage').then((module) => ({ default: module.ResetPasswordPage })));
const DashboardPage = lazy(() => import('./modules/dashboard/DashboardPage').then((module) => ({ default: module.DashboardPage })));
const PurchasesPage = lazy(() => import('./modules/purchases/PurchasesPage').then((module) => ({ default: module.PurchasesPage })));
const ProfilePage = lazy(() => import('./modules/profile/ProfilePage').then((module) => ({ default: module.ProfilePage })));
const TicketDetailPage = lazy(() => import('./modules/tickets/TicketDetailPage').then((module) => ({ default: module.TicketDetailPage })));
const NewTicketPage = lazy(() => import('./modules/tickets/NewTicketPage').then((module) => ({ default: module.NewTicketPage })));
const TicketsPage = lazy(() => import('./modules/tickets/TicketsPage').then((module) => ({ default: module.TicketsPage })));
const StaffPortalWorkspace = lazy(() => import('./modules/tickets/StaffPortalWorkspace').then((module) => ({ default: module.StaffPortalWorkspace })));

interface RouteMatch {
  active: PortalRoute;
  ticketId?: number;
  newTicket?: boolean;
}

function matchRoute(pathname: string): RouteMatch {
  if (/^\/support\/reset-password\/?$/.test(pathname)) {
    return { active: 'reset-password' };
  }

  if (/^\/support\/tickets\/new\/?$/.test(pathname)) {
    return { active: 'tickets', newTicket: true };
  }

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

  if (/^\/support\/profile\/?$/.test(pathname)) {
    return { active: 'profile' };
  }

  return { active: 'overview' };
}

function App() {
  const config = getConfig();
  const portalPath = new URL(config.portalUrl, window.location.origin).pathname.replace(/\/$/, '');
  const [overview, setOverview] = useState<PortalOverview | null>(null);
  const [pathname, setPathname] = useState(window.location.pathname);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (config.authenticated && !config.staffDashboardAccess) portalApi.overview().then(setOverview).catch(() => setFailed(true));
  }, [config.authenticated, config.staffDashboardAccess]);

  useEffect(() => {
    const onPopState = () => setPathname(window.location.pathname);
    window.addEventListener('popstate', onPopState);
    return () => window.removeEventListener('popstate', onPopState);
  }, []);

  const navigate = (path: string) => {
    const target=path.startsWith('/support')?`${portalPath}${path.slice('/support'.length)}`:path;
    window.history.pushState({}, '', target);
    setPathname(target);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const canonicalPath=pathname.startsWith(portalPath)?`/support${pathname.slice(portalPath.length)}`:pathname;
  const guestRoute = config.guestTicketCreationEnabled && /^\/support\/guest-ticket\/?$/.test(canonicalPath);
  const resetPasswordRoute = /^\/support\/reset-password\/?$/.test(canonicalPath);
  const authMode = guestRoute
    ? 'guest'
    : config.registrationEnabled && /^\/support\/register\/?$/.test(canonicalPath)
    ? 'register'
    : 'login';
  const authRoute = /^\/support\/(?:login|register)\/?$/.test(canonicalPath) || guestRoute;

  if (resetPasswordRoute) {
    return <Suspense fallback={<PortalState loading message="Loading reset password page…" />}><ResetPasswordPage navigate={navigate}/></Suspense>;
  }

  if (!config.authenticated) {
    if (config.wordpressAuthEnabled && authMode !== 'guest') {
      const wordpressAuthUrl = authMode === 'register'
        ? config.wordpressRegistrationUrl
        : config.wordpressLoginUrl;
      window.location.replace(wordpressAuthUrl);
      return <PortalState loading message={`Redirecting to WordPress ${authMode}…`} />;
    }

    if (!authRoute) {
      window.history.replaceState({}, '', `${portalPath}/login/`);
    }
    return <Suspense fallback={<PortalState loading message="Loading authentication…" />}><AuthPage mode={authMode} navigate={navigate}/></Suspense>;
  }

  if (config.staffDashboardAccess) return <Suspense fallback={<PortalState loading message="Loading your staff workspace…" />}><StaffPortalWorkspace navigate={navigate}/></Suspense>;

  if (authRoute) {
    window.history.replaceState({}, '', `${portalPath}/`);
    setTimeout(()=>setPathname(`${portalPath}/`),0);
  }

  if (config.wordpressProfileEnabled && /^\/support\/profile\/?$/.test(canonicalPath)) {
    window.location.replace(config.wordpressProfileUrl);
    return <PortalState loading message="Redirecting to your WordPress profile…" />;
  }

  if (failed) {
    return <PortalState title="We couldn't load your portal" message="Please try again in a moment." />;
  }

  if (!overview) {
    return <PortalState loading message="Loading your support workspace…" />;
  }

  const route = matchRoute(canonicalPath);
  let page = <DashboardPage overview={overview} navigate={navigate} />;

  if (route.active === 'tickets') {
    page = route.newTicket
      ? <NewTicketPage navigate={navigate} />
      : route.ticketId
      ? <TicketDetailPage ticketId={route.ticketId} navigate={navigate} />
      : <TicketsPage navigate={navigate} />;
  } else if (route.active === 'purchases') {
    page = <PurchasesPage />;
  } else if (route.active === 'profile') {
    page = (
      <ProfilePage
        onUpdated={(profile) => setOverview({
          ...overview,
          customer: {
            ...overview.customer,
            avatar_url: profile.avatar_url,
            company: profile.company,
            country: profile.country,
            timezone: profile.timezone,
            language: profile.language,
          },
        })}
      />
    );
  }

  return (
    <PortalLayout overview={overview} active={route.active} navigate={navigate}>
      <Suspense fallback={<PortalState loading message="Loading page…" />}>{page}</Suspense>
    </PortalLayout>
  );
}

const root = document.getElementById('supportbay');

if (root) {
  createRoot(root).render(
    <StrictMode>
      <SupportBayConfigProvider>
        <App />
      </SupportBayConfigProvider>
    </StrictMode>,
  );
}
