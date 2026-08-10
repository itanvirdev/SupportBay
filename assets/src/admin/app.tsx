import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { adminDownload, adminGet, adminPost, adminUpload } from './api';
import { getAdminConfig } from './config';
import './styles.scss';
import { TicketWorkspace, ticketQueryString, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../shared/tickets/TicketWorkspace';
import '../shared/tickets/workspace.scss';
import { TicketConversation, type ConversationMessage, type ConversationTicket, type TicketAttachment, type TicketContext } from '../shared/tickets/TicketConversation';

interface TicketSummary {
  tickets: number;
}

async function loadTickets(query: TicketQueryParams): Promise<TicketPage> {
  const response = await adminGet<WorkspaceTicket[]>(`tickets?${ticketQueryString(query)}`);
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
  const ticketId = Number(new URLSearchParams(window.location.search).get('ticket')) || null;
  const [detail, setDetail] = useState<{ ticket: ConversationTicket; messages: ConversationMessage[]; context: TicketContext } | null>(null);
  const [queueOptions, setQueueOptions] = useState<TicketContext['options']>();

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

  useEffect(() => {
    if (config.section !== 'tickets' || ticketId) return;
    adminGet<TicketContext['options']>('admin/tickets/options')
      .then((response) => setQueueOptions(response.data))
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket filters could not be loaded.'));
  }, [config.section, ticketId]);

  useEffect(() => {
    if (!ticketId) return;
    Promise.all([
      adminGet<ConversationTicket>(`tickets/${ticketId}`),
      adminGet<ConversationMessage[]>(`tickets/${ticketId}/messages`),
      adminGet<TicketContext>(`admin/tickets/${ticketId}/context`),
    ]).then(([ticket, messages, context]) => setDetail({ ticket: ticket.data, messages: messages.data, context: context.data }))
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket could not be loaded.'));
  }, [ticketId]);

  const addMessage = async (content: string, type: 'reply' | 'internal_note', files: File[], close: boolean) => {
    if (!ticketId || !detail) return;
    const response = await adminPost<ConversationMessage>(`tickets/${ticketId}/messages`, { content, type });
    const uploaded = await Promise.all(files.map((file) => adminUpload<TicketAttachment>(
      `admin/tickets/${ticketId}/messages/${response.data.id}/attachments`, file,
    )));
    const nextDetail = {
      ...detail,
      messages: [...detail.messages, response.data],
      context: {...detail.context, attachments: [...detail.context.attachments, ...uploaded.map(item=>item.data)]},
    };
    if (close) {
      const closed = await adminPost<ConversationTicket>(`tickets/${ticketId}/close`, {});
      setDetail({...nextDetail, ticket: closed.data});
    } else {
      setDetail(nextDetail);
    }
  };

  const downloadAttachment = async (file: TicketAttachment) => {
    const blob = await adminDownload(`admin/attachments/${file.id}/download`);
    const url = URL.createObjectURL(blob); const link = document.createElement('a');
    link.href = url; link.download = file.original_name; link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 0);
  };

  const mutateTicket = async (action: string, value: string | number) => {
    if (!ticketId || !detail) return;
    const ticket = await adminPost<ConversationTicket>(`admin/tickets/${ticketId}/actions`, { action, value });
    const context = await adminGet<TicketContext>(`admin/tickets/${ticketId}/context`);
    setDetail({...detail, ticket: ticket.data, context: context.data});
  };

  const transition = async () => {
    if (!ticketId || !detail) return;
    const action = detail.ticket.status === 'closed' ? 'reopen' : 'close';
    const response = await adminPost<ConversationTicket>(`tickets/${ticketId}/${action}`, {});
    setDetail({ ...detail, ticket: response.data });
  };

  return (
    <main className={`sbay-admin-main sbay-admin-main--${config.section}`}>
      <header className="sbay-admin-workspace-header">
        <div><small>{content.eyebrow}</small><h1>{content.title}</h1></div>
        <span>{config.userName || 'Administrator'}</span>
      </header>
      <p className="sbay-admin-intro">{content.description}</p>
      {error ? <div className="sbay-admin-error" role="alert">{error}</div> : null}

      {config.section === 'tickets' ? (
        ticketId ? (detail ? <TicketConversation ticket={detail.ticket} messages={detail.messages} context={detail.context} back={() => { window.location.href = config.adminUrl; }} submit={addMessage} transition={transition} download={downloadAttachment} mutate={mutateTicket} /> : <p>Loading ticket conversation…</p>) : <TicketWorkspace
          mode="staff"
          load={loadTickets}
          options={queueOptions}
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
