import { type ReactNode, useCallback, useEffect, useRef, useState } from 'react';
import { apiDownload, apiGet, apiGetResponse, apiPost, apiPostResponse, apiUpload } from '../../api/client';
import { getConfig } from '../../core/config';
import { Preloader } from '../../../shared/components/Preloader';
import { RequestState } from '../../../shared/components/RequestState';
import { TicketWorkspace, ticketQueryString, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../../../shared/tickets/TicketWorkspace';
import { TicketConversation, type ConversationMessage, type ConversationTicket, type TicketAttachment, type TicketContext } from '../../../shared/tickets/TicketConversation';
import type { SavedReply } from '../../../shared/tickets/SavedReplyPicker';
import { StaffTicketCreateModal } from './StaffTicketCreateModal';
import '../../../shared/tickets/workspace.scss';

interface StaffDetail {
  ticket: ConversationTicket;
  messages: ConversationMessage[];
  context: TicketContext;
}

interface Props {
  navigate: (path: string) => void;
}

async function loadSavedReplies(): Promise<SavedReply[]> {
  return apiGet<SavedReply[]>('saved-replies?orderby=usage');
}

async function trackSavedReply(id: number): Promise<void> {
  await apiPost(`saved-replies/${id}/use`, {});
}

function currentTicketId(): number | null {
  const match = window.location.pathname.match(/\/tickets\/(\d+)\/?$/);
  return match ? Number(match[1]) : null;
}

export function StaffPortalWorkspace({ navigate }: Props) {
  const config = getConfig();
  const [ticketId, setTicketId] = useState<number | null>(currentTicketId());
  const [options, setOptions] = useState<TicketContext['options']>();
  const [detail, setDetail] = useState<StaffDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const requestId = useRef(0);
  const mutationPending = useRef(false);

  useEffect(() => {
    if (ticketId !== null) return;
    apiGet<TicketContext['options']>('admin/tickets/options')
      .then(setOptions)
      .catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Ticket filters could not be loaded.'));
  }, [ticketId]);

  const loadDetail = useCallback(async (background = false) => {
    if (ticketId === null) return;
    const id = ++requestId.current;
    if (!background) {
      setError(null);
      setDetail(null);
    }

    try {
      const [ticket, messages, context] = await Promise.all([
        apiGet<ConversationTicket>(`tickets/${ticketId}`),
        apiGet<ConversationMessage[]>(`tickets/${ticketId}/messages`),
        apiGet<TicketContext>(`admin/tickets/${ticketId}/context`),
      ]);
      if (id === requestId.current) setDetail({ ticket, messages, context });
    } catch (reason) {
      if (!background && id === requestId.current) {
        setError(reason instanceof Error ? reason.message : 'Ticket could not be loaded.');
      }
    }
  }, [ticketId]);

  useEffect(() => { void loadDetail(); }, [loadDetail]);
  useEffect(() => {
    if (ticketId === null || !config.ticketListAutoRefreshEnabled) return;
    const interval = window.setInterval(() => {
      if (!document.hidden && !mutationPending.current) void loadDetail(true);
    }, Math.max(5, config.ticketListAutoRefreshInterval) * 1000);
    return () => window.clearInterval(interval);
  }, [config.ticketListAutoRefreshEnabled, config.ticketListAutoRefreshInterval, loadDetail, ticketId]);

  const openTicket = (ticket: WorkspaceTicket) => {
    navigate(`/support/tickets/${ticket.id}/`);
    setTicketId(ticket.id);
    setDetail(null);
  };

  const back = () => {
    navigate('/support/tickets/');
    setTicketId(null);
    setDetail(null);
    setError(null);
  };

  const loadTickets = async (query: TicketQueryParams): Promise<TicketPage> => {
    const response = await apiGetResponse<WorkspaceTicket[]>(`tickets?${ticketQueryString(query)}`);
    return {
      items: response.data,
      page: Number(response.meta.page) || 1,
      total: Number(response.meta.total) || 0,
      totalPages: Number(response.meta.total_pages) || 1,
    };
  };

  const submit = async (content: string, type: 'reply' | 'internal_note', files: File[], close: boolean) => {
    if (!detail || ticketId === null) return;
    mutationPending.current = true;
    requestId.current++;
    try {
      const message = await apiPost<ConversationMessage>(`tickets/${ticketId}/messages`, { content, type });
      const uploads = await Promise.all(files.map((file) => apiUpload<TicketAttachment>(
        `admin/tickets/${ticketId}/messages/${message.id}/attachments`, file,
      )));
      let ticket = detail.ticket;
      if (close) ticket = await apiPost<ConversationTicket>(`tickets/${ticketId}/close`, {});
      setDetail({
        ...detail,
        ticket,
        messages: [...detail.messages, message],
        context: { ...detail.context, attachments: [...detail.context.attachments, ...uploads] },
      });
    } finally {
      mutationPending.current = false;
    }
  };

  const transition = async (action: 'resolve' | 'close' | 'reopen') => {
    if (!detail || ticketId === null) return;
    mutationPending.current = true;
    try {
      const ticket = await apiPost<ConversationTicket>(`tickets/${ticketId}/${action}`, {});
      setDetail({ ...detail, ticket });
    } finally { mutationPending.current = false; }
  };

  const mutate = async (action: string, value: unknown) => {
    if (!detail || ticketId === null) return;
    mutationPending.current = true;
    try {
      const ticket = await apiPost<ConversationTicket>(`admin/tickets/${ticketId}/actions`, { action, value });
      const context = await apiGet<TicketContext>(`admin/tickets/${ticketId}/context`);
      setDetail({ ...detail, ticket, context });
    } finally { mutationPending.current = false; }
  };

  const permDelete = async () => {
    if (!detail || ticketId === null) return;
    mutationPending.current = true;
    try {
      await apiPost<void>(`admin/tickets/${ticketId}/delete`, {});
      navigate('/support/tickets/');
    } finally { mutationPending.current = false; }
  };

  const bulk = async (ticketIds: number[], action: string, value: unknown) => {
    const response = await apiPostResponse<unknown>('admin/tickets/bulk-actions', { ticket_ids: ticketIds, action, value });
    return {
      updated: Number(response.meta.updated) || 0,
      failed: Number(response.meta.failed) || 0,
    };
  };

  const shell = (content: ReactNode) => (
    <div className="sbay-shell sbay-shell--staff">
      <aside className="sbay-sidebar">
        <a className="sbay-brand" href={config.portalUrl} aria-label={`${config.siteName} support portal`}>
          <img src={config.portalLogoUrl} alt={config.siteName}/>
        </a>
        <nav aria-label="Staff portal">
          <a className="is-active" href={`${config.portalUrl}tickets/`}>Support Tickets</a>
          <a href={config.staffDashboardUrl}>WordPress Dashboard</a>
        </nav>
        <div className="sbay-sidebar__help">
          <span>Staff workspace</span>
          <p>Tickets and customer context are managed here with your assigned permissions.</p>
        </div>
      </aside>
      <main className="sbay-main">
        <div className="sbay-topbar">
          <span className="sbay-account"><span>{(config.currentUserName || 'S').charAt(0).toUpperCase()}</span><strong>{config.currentUserName || 'Support staff'}</strong></span>
          <a className="sbay-logout" href={config.logoutUrl}>Sign out</a>
        </div>
        {content}
      </main>
    </div>
  );

  if (ticketId !== null) {
    if (detail) return shell(<section className="sbay-staff-portal"><TicketConversation ticket={detail.ticket} messages={detail.messages} context={detail.context} statusLabels={config.ticketStatusLabels} back={back} refresh={()=>void loadDetail(false)} submit={submit} transition={transition} download={(file) => apiDownload(`admin/attachments/${file.id}/download`)} previewAttachments={config.attachmentPopupPreviewEnabled} mutate={mutate} permDelete={permDelete} loadSavedReplies={loadSavedReplies} trackSavedReply={trackSavedReply}/></section>);
    if (error) return shell(<RequestState title="Ticket could not be loaded" message={error} retry={() => void loadDetail()}/>);
    return shell(<Preloader label="Loading ticket conversation…"/>);
  }

  return shell(<section className="sbay-staff-portal"><header className="sbay-page__header"><div><small className="sbay-kicker">Support workspace</small><h1>Support Tickets</h1><p>Manage customer conversations from the same workspace available in SupportBay dashboard.</p></div></header>{error ? <RequestState title="Ticket filters could not be loaded" message={error} retry={() => window.location.reload()}/> : <TicketWorkspace mode="staff" load={loadTickets} options={options} bulk={bulk} openTicket={openTicket} createTicket={()=>setCreating(true)} autoRefresh={{ enabled: config.ticketListAutoRefreshEnabled, interval: config.ticketListAutoRefreshInterval }} statusLabels={config.ticketStatusLabels}/>} {creating?<StaffTicketCreateModal close={()=>setCreating(false)} created={id=>{setCreating(false);navigate(`/support/tickets/${id}/`);setTicketId(id);setDetail(null);}}/>:null}</section>);
}
