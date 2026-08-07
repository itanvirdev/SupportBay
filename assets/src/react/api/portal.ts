import { apiGet } from './client';
import type {
  PortalOverview,
  PortalTicket,
  PortalTicketDetail,
  PortalVerification,
} from './types';

export const portalApi = {
  overview: () => apiGet<PortalOverview>('portal'),
  tickets: () => apiGet<PortalTicket[]>('portal/tickets'),
  ticket: (ticketId: number) =>
    apiGet<PortalTicketDetail>(`portal/tickets/${ticketId}`),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
};
