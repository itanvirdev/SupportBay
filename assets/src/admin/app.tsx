import { lazy, StrictMode, Suspense, useCallback, useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { adminDownload, adminGet, adminPost, adminUpload } from './api';
import { getAdminConfig } from './config';
import './styles.scss';
import { TicketWorkspace, ticketQueryString, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../shared/tickets/TicketWorkspace';
import '../shared/tickets/workspace.scss';
import { TicketConversation, type ConversationMessage, type ConversationTicket, type TicketAttachment, type TicketContext } from '../shared/tickets/TicketConversation';
import type { SavedReply } from '../shared/tickets/SavedReplyPicker';
import { CustomerProfile, type CustomerProfileData } from './CustomerProfile';
import { CustomerDirectory } from './CustomerDirectory';
import { VerificationDirectory } from './VerificationDirectory';
import { Preloader } from '../shared/components/Preloader';
import { RequestState } from '../shared/components/RequestState';

const ReportsWorkspace = lazy(() => import('./ReportsWorkspace').then((module) => ({ default: module.ReportsWorkspace })));
const SettingsWorkspace = lazy(() => import('./SettingsWorkspace').then((module) => ({ default: module.SettingsWorkspace })));

async function loadTickets(query: TicketQueryParams): Promise<TicketPage> {
  const response = await adminGet<WorkspaceTicket[]>(`tickets?${ticketQueryString(query)}`);
  return {
    items: response.data,
    page: Number(response.meta.page) || 1,
    total: Number(response.meta.total) || 0,
    totalPages: Number(response.meta.total_pages) || 1,
  };
}

async function loadSavedReplies(departmentId?: number|null): Promise<SavedReply[]> {
  return (await adminGet<SavedReply[]>(`saved-replies?orderby=usage&department_id=${departmentId??0}`)).data;
}

async function trackSavedReply(id: number): Promise<void> {
  await adminPost(`saved-replies/${id}/use`, {});
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
  const detailRequestId=useRef(0);
  const detailMutationPending=useRef(false);

  useEffect(() => {
    if (config.section !== 'tickets' || ticketId || customerId || customerDirectory || verificationDirectory) return;
    adminGet<TicketContext['options']>('admin/tickets/options')
      .then((response) => setQueueOptions(response.data))
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket filters could not be loaded.'));
  }, [config.section, ticketId, customerId, customerDirectory, verificationDirectory]);

  const loadTicketDetail=useCallback(async(background=false)=>{
    if(!ticketId)return;
    const currentRequest=++detailRequestId.current;
    if(!background)setError(null);
    try{
      const [ticket,messages,context]=await Promise.all([
      adminGet<ConversationTicket>(`tickets/${ticketId}`),
      adminGet<ConversationMessage[]>(`tickets/${ticketId}/messages`),
      adminGet<TicketContext>(`admin/tickets/${ticketId}/context`),
      ]);
      if(currentRequest===detailRequestId.current)setDetail({ticket:ticket.data,messages:messages.data,context:context.data});
    }catch(reason){if(!background&&currentRequest===detailRequestId.current)setError(reason instanceof Error?reason.message:'Ticket could not be loaded.');}
  },[ticketId]);

  useEffect(()=>{void loadTicketDetail(false);},[loadTicketDetail]);
  useEffect(()=>{
    if(!ticketId||!config.ticketListAutoRefreshEnabled)return;
    const interval=window.setInterval(()=>{
      if(document.hidden||detailMutationPending.current)return;
      void loadTicketDetail(true);
    },Math.max(5,config.ticketListAutoRefreshInterval)*1000);
    return()=>window.clearInterval(interval);
  },[config.ticketListAutoRefreshEnabled,config.ticketListAutoRefreshInterval,loadTicketDetail,ticketId]);

  const loadCustomerProfile = async (id: number) => {
    setError(null);
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
    detailMutationPending.current=true;detailRequestId.current++;
    try{
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
    }finally{detailMutationPending.current=false;}
  };

  const downloadAttachment = async (file: TicketAttachment) => {
    const blob = await adminDownload(`admin/attachments/${file.id}/download`);
    const url = URL.createObjectURL(blob); const link = document.createElement('a');
    link.href = url; link.download = file.original_name; link.click();
    window.setTimeout(() => URL.revokeObjectURL(url), 0);
  };

  const mutateTicket = async (action: string, value: unknown) => {
    if (!ticketId || !detail) return;
    detailMutationPending.current=true;detailRequestId.current++;
    try{
      const ticket = await adminPost<ConversationTicket>(`admin/tickets/${ticketId}/actions`, { action, value });
      const context = await adminGet<TicketContext>(`admin/tickets/${ticketId}/context`);
      setDetail({...detail, ticket: ticket.data, context: context.data});
    }finally{detailMutationPending.current=false;}
  };

  const transition = async (action: 'resolve' | 'close' | 'reopen') => {
    if (!ticketId || !detail) return;
    detailMutationPending.current=true;detailRequestId.current++;
    try{
      const response = await adminPost<ConversationTicket>(`tickets/${ticketId}/${action}`, {});
      setDetail({ ...detail, ticket: response.data });
    }finally{detailMutationPending.current=false;}
  };

  const bulkTickets = async (ticketIds: number[], action: string, value: unknown) => {
    const response = await adminPost('admin/tickets/bulk-actions', {
      ticket_ids: ticketIds,
      action,
      value,
    });
    return {
      updated: Number(response.meta.updated) || 0,
      failed: Number(response.meta.failed) || 0,
    };
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
      {error && !ticketId && !customerId ? <div className="sbay-admin-error" role="alert">{error}</div> : null}

      {config.section === 'tickets' ? (
        verificationDirectory ? <VerificationDirectory back={()=>{window.location.href=config.adminUrl;}}/> : customerDirectory ? <CustomerDirectory back={()=>{window.location.href=config.adminUrl;}} openCustomer={id=>{window.location.href=`${config.adminUrl}&customer=${id}&return_customers=1`;}}/> : customerId ? (customerProfile ? <CustomerProfile profile={customerProfile} back={()=>{window.location.href=returnTicketId?`${config.adminUrl}&ticket=${returnTicketId}`:returnCustomers?`${config.adminUrl}&customers=1`:config.adminUrl;}} openTicket={id=>{window.location.href=`${config.adminUrl}&ticket=${id}`;}} changeState={changeCustomerState}/> : error ? <RequestState title="Customer profile could not be loaded" message={error} retry={()=>void loadCustomerProfile(customerId).catch((reason:unknown)=>setError(reason instanceof Error?reason.message:'Customer profile could not be loaded.'))}/> : <Preloader label="Loading customer profile…" />) : ticketId ? (detail ? <TicketConversation ticket={detail.ticket} messages={detail.messages} context={detail.context} statusLabels={config.ticketStatusLabels} back={() => { window.location.href = config.adminUrl; }} submit={addMessage} transition={transition} download={downloadAttachment} mutate={mutateTicket} loadSavedReplies={loadSavedReplies} trackSavedReply={trackSavedReply} merge={mergeTicket} split={splitTicket} openCustomer={config.canManageCustomers?id=>{window.location.href=`${config.adminUrl}&customer=${id}&return_ticket=${ticketId}`;}:undefined} /> : error ? <RequestState title="Ticket could not be loaded" message={error} retry={()=>void loadTicketDetail(false)}/> : <Preloader label="Loading ticket conversation…" />) : <TicketWorkspace
          mode="staff"
          load={loadTickets}
          options={queueOptions}
          bulk={bulkTickets}
          openCustomers={config.canManageCustomers?()=>{window.location.href=`${config.adminUrl}&customers=1`;}:undefined}
          openVerifications={config.canViewVerifications?()=>{window.location.href=`${config.adminUrl}&verifications=1`;}:undefined}
          openTicket={(ticket) => { window.location.href = `${config.adminUrl}&ticket=${ticket.id}`; }}
          autoRefresh={{enabled:config.ticketListAutoRefreshEnabled,interval:config.ticketListAutoRefreshInterval}}
          needReplyFilterEnabled={config.needReplyFilterEnabled}
          statusLabels={config.ticketStatusLabels}
        />
      ) : null}

      {config.section === 'reports' ? (
        <Suspense fallback={<Preloader label="Loading reports workspace…" />}><ReportsWorkspace /></Suspense>
      ) : null}

      {config.section === 'settings' ? (
        <Suspense fallback={<Preloader label="Loading settings workspace…" />}><SettingsWorkspace /></Suspense>
      ) : null}
    </main>
  );
}

const root = document.getElementById('supportbay-admin-app');

if (root) {
  createRoot(root).render(<StrictMode><AdminApp /></StrictMode>);
}
