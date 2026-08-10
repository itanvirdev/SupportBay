import { useCallback } from 'react';
import { portalApi } from '../../api/portal';
import { TicketWorkspace, type TicketPage, type TicketQueryParams, type WorkspaceTicket } from '../../../shared/tickets/TicketWorkspace';
import '../../../shared/tickets/workspace.scss';

interface TicketsPageProps {
  navigate: (path: string) => void;
}

function queryString(query: TicketQueryParams): string {
  return new URLSearchParams(Object.entries(query).map(([key, value]) => [key, String(value)])).toString();
}

export function TicketsPage({ navigate }: TicketsPageProps) {
  const load = useCallback(async (query: TicketQueryParams): Promise<TicketPage> => {
    const response = await portalApi.tickets(queryString(query));
    return {
      items: response.data,
      page: Number(response.meta.page) || 1,
      total: Number(response.meta.total) || 0,
      totalPages: Number(response.meta.total_pages) || 1,
    };
  }, []);

  return (
    <section className="sbay-page">
      <TicketWorkspace
        mode="customer"
        load={load}
        openTicket={(ticket: WorkspaceTicket) => navigate(`/support/tickets/${ticket.id}/`)}
        createTicket={() => navigate('/support/tickets/new/')}
      />
    </section>
  );
}
