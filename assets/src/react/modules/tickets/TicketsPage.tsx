import { useCallback } from 'react';
import { portalApi } from '../../api/portal';
import { TicketWorkspace, ticketQueryString, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../../../shared/tickets/TicketWorkspace';
import '../../../shared/tickets/workspace.scss';
import { getConfig } from '../../core/config';

interface TicketsPageProps {
  navigate: (path: string) => void;
}

export function TicketsPage({ navigate }: TicketsPageProps) {
  const config=getConfig();
  const load = useCallback(async (query: TicketQueryParams): Promise<TicketPage> => {
    const response = await portalApi.tickets(ticketQueryString(query));
    return {
      items: response.data,
      page: Number(response.meta.page) || 1,
      total: Number(response.meta.total) || 0,
      totalPages: Number(response.meta.total_pages) || 1,
      showCategories: Boolean(response.meta.show_categories),
    };
  }, []);

  return (
    <section className="sbay-page">
      <TicketWorkspace
        mode="customer"
        load={load}
        openTicket={(ticket: WorkspaceTicket) => navigate(`/support/tickets/${ticket.id}/`)}
        createTicket={() => navigate('/support/tickets/new/')}
        autoRefresh={{enabled:config.ticketListAutoRefreshEnabled,interval:config.ticketListAutoRefreshInterval}}
        statusLabels={config.ticketStatusLabels}
      />
    </section>
  );
}
