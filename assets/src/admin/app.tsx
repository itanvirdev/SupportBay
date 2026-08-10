import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { adminGet } from './api';
import { getAdminConfig } from './config';
import './styles.scss';
import { TicketWorkspace, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../shared/tickets/TicketWorkspace';
import '../shared/tickets/workspace.scss';

interface TicketSummary {
  tickets: number;
}

async function loadTickets(query: TicketQueryParams): Promise<TicketPage> {
  const search = new URLSearchParams(Object.entries(query).map(([key, value]) => [key, String(value)]));
  const response = await adminGet<WorkspaceTicket[]>(`tickets?${search.toString()}`);
  return {
    items: response.data,
    page: Number(response.meta.page) || 1,
    total: Number(response.meta.total) || 0,
    totalPages: Number(response.meta.total_pages) || 1,
  };
}

const sectionContent = {
  tickets: {
    eyebrow: 'Ticket workspace',
    title: 'Support Tickets',
    description: 'Review, filter, and manage customer conversations from one workspace.',
  },
  reports: {
    eyebrow: 'Support performance',
    title: 'Reports',
    description: 'Reporting filters, summaries, tables, and charts will live in this workspace.',
  },
  settings: {
    eyebrow: 'SupportBay configuration',
    title: 'Settings',
    description: 'Configure ticket behavior, access, notifications, providers, and integrations here.',
  },
} as const;

function AdminApp() {
  const config = getAdminConfig();
  const content = sectionContent[config.section];
  const [summary, setSummary] = useState<TicketSummary | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (config.section === 'settings') {
      return;
    }

    adminGet<unknown[]>('tickets?per_page=1').then((response) => {
      setSummary({
        tickets: typeof response.meta.total === 'number' ? response.meta.total : response.data.length,
      });
    }).catch((reason: unknown) => {
      setError(reason instanceof Error ? reason.message : 'Workspace data could not be loaded.');
    });
  }, [config.section]);

  return (
    <main className={`sbay-admin-main sbay-admin-main--${config.section}`}>
      <header className="sbay-admin-workspace-header">
        <div><small>{content.eyebrow}</small><h1>{content.title}</h1></div>
        <span>{config.userName || 'Administrator'}</span>
      </header>
      <p className="sbay-admin-intro">{content.description}</p>
      {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}

      {config.section === 'tickets' ? (
        <TicketWorkspace
          mode="staff"
          load={loadTickets}
          openTicket={(ticket) => { window.location.href = `${config.adminUrl}&ticket=${ticket.id}`; }}
        />
      ) : null}

      {config.section === 'reports' ? (
        <section className="sbay-admin-panel">
          <div><small>Report foundation</small><strong>{summary ? summary.tickets : '—'}</strong><span>Current tickets</span></div>
          <p>This page is reserved for date-based ticket, response, need-reply, and closed-ticket reporting. No placeholder analytics are being presented as real data.</p>
        </section>
      ) : null}

      {config.section === 'settings' ? (
        <section className="sbay-settings-foundation">
          <nav aria-label="Settings sections">
            <span className="is-active">General</span><span>Security</span><span>User Roles</span><span>Categories</span><span>Email Notifications</span><span>Integrations</span>
          </nav>
          <div><small>Settings foundation</small><h2>General</h2><p>Settings controls will be added as their configuration services and secure save endpoints are introduced.</p></div>
        </section>
      ) : null}
    </main>
  );
}

const root = document.getElementById('supportbay-admin-app');

if (root) {
  createRoot(root).render(<StrictMode><AdminApp /></StrictMode>);
}
