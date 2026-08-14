import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { adminDownload, adminGet, adminPost, adminUpload } from './api';
import { getAdminConfig } from './config';
import './styles.scss';
import { TicketWorkspace, ticketQueryString, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../shared/tickets/TicketWorkspace';
import '../shared/tickets/workspace.scss';
import { TicketConversation, type ConversationMessage, type ConversationTicket, type TicketAttachment, type TicketContext } from '../shared/tickets/TicketConversation';
import { CustomerProfile, type CustomerProfileData } from './CustomerProfile';
import { CustomerDirectory } from './CustomerDirectory';
import { ProviderWorkspace } from './ProviderWorkspace';
import { VerificationDirectory } from './VerificationDirectory';

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
  const customerId = Number(new URLSearchParams(window.location.search).get('customer')) || null;
  const returnTicketId = Number(new URLSearchParams(window.location.search).get('return_ticket')) || null;
  const customerDirectory = new URLSearchParams(window.location.search).get('customers') === '1';
  const verificationDirectory = new URLSearchParams(window.location.search).get('verifications') === '1';
  const returnCustomers = new URLSearchParams(window.location.search).get('return_customers') === '1';
  const [detail, setDetail] = useState<{ ticket: ConversationTicket; messages: ConversationMessage[]; context: TicketContext } | null>(null);
  const [customerProfile, setCustomerProfile] = useState<CustomerProfileData | null>(null);
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
    if (config.section !== 'tickets' || ticketId || customerId || customerDirectory || verificationDirectory) return;
    adminGet<TicketContext['options']>('admin/tickets/options')
      .then((response) => setQueueOptions(response.data))
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket filters could not be loaded.'));
  }, [config.section, ticketId, customerId, customerDirectory, verificationDirectory]);

  useEffect(() => {
    if (!ticketId) return;
    Promise.all([
      adminGet<ConversationTicket>(`tickets/${ticketId}`),
      adminGet<ConversationMessage[]>(`tickets/${ticketId}/messages`),
      adminGet<TicketContext>(`admin/tickets/${ticketId}/context`),
    ]).then(([ticket, messages, context]) => setDetail({ ticket: ticket.data, messages: messages.data, context: context.data }))
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket could not be loaded.'));
  }, [ticketId]);

  const loadCustomerProfile = async (id: number) => {
    const response = await adminGet<CustomerProfileData>(`admin/customers/${id}/profile`);
    setCustomerProfile(response.data);
  };

  useEffect(() => {
    if (!customerId) return;
    loadCustomerProfile(customerId)
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Customer profile could not be loaded.'));
  }, [customerId]);

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

  const bulkTickets = async (ticketIds: number[], action: string, value: string) => {
    await adminPost('admin/tickets/bulk-actions', {
      ticket_ids: ticketIds,
      action,
      value,
    });
  };

  const mergeTicket = async (targetId: number) => {
    if (!ticketId) return;
    await adminPost<ConversationTicket>(`admin/tickets/${ticketId}/merge`, { target_id: targetId });
    window.location.href = `${config.adminUrl}&ticket=${targetId}`;
  };

  const splitTicket = async (messageIds: number[], subject: string) => {
    if (!ticketId) return;
    const response = await adminPost<ConversationTicket>(`admin/tickets/${ticketId}/split`, {
      message_ids: messageIds,
      subject,
    });
    window.location.href = `${config.adminUrl}&ticket=${response.data.id}`;
  };

  const changeCustomerState = async (state: 'registered'|'suspended') => {
    if (!customerId) return;
    await adminPost(`customers/${customerId}/state`, { state });
    await loadCustomerProfile(customerId);
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
        verificationDirectory ? <VerificationDirectory back={()=>{window.location.href=config.adminUrl;}}/> : customerDirectory ? <CustomerDirectory back={()=>{window.location.href=config.adminUrl;}} openCustomer={id=>{window.location.href=`${config.adminUrl}&customer=${id}&return_customers=1`;}}/> : customerId ? (customerProfile ? <CustomerProfile profile={customerProfile} back={()=>{window.location.href=returnTicketId?`${config.adminUrl}&ticket=${returnTicketId}`:returnCustomers?`${config.adminUrl}&customers=1`:config.adminUrl;}} openTicket={id=>{window.location.href=`${config.adminUrl}&ticket=${id}`;}} changeState={changeCustomerState}/> : <p>Loading customer profile…</p>) : ticketId ? (detail ? <TicketConversation ticket={detail.ticket} messages={detail.messages} context={detail.context} back={() => { window.location.href = config.adminUrl; }} submit={addMessage} transition={transition} download={downloadAttachment} mutate={mutateTicket} merge={mergeTicket} split={splitTicket} openCustomer={config.canManageCustomers?id=>{window.location.href=`${config.adminUrl}&customer=${id}&return_ticket=${ticketId}`;}:undefined} /> : <p>Loading ticket conversation…</p>) : <TicketWorkspace
          mode="staff"
          load={loadTickets}
          options={queueOptions}
          bulk={bulkTickets}
          openCustomers={config.canManageCustomers?()=>{window.location.href=`${config.adminUrl}&customers=1`;}:undefined}
          openVerifications={config.canViewVerifications?()=>{window.location.href=`${config.adminUrl}&verifications=1`;}:undefined}
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
        <ProviderWorkspace />
      ) : null}
    </main>
  );
}

const root = document.getElementById('supportbay-admin-app');

if (root) {
  createRoot(root).render(<StrictMode><AdminApp /></StrictMode>);
}
