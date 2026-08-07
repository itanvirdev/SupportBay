import { apiGet, apiPost } from './client';
import type {
  CreateTicketInput,
  PortalDepartment,
  PortalMessage,
  PortalOverview,
  PortalTicket,
  PortalTicketDetail,
  PortalVerification,
} from './types';

export const portalApi = {
  overview: () => apiGet<PortalOverview>('portal'),
  tickets: () => apiGet<PortalTicket[]>('portal/tickets'),
  createTicket: (input: CreateTicketInput) =>
    apiPost<PortalTicket>('portal/tickets', input),
  ticket: (ticketId: number) =>
    apiGet<PortalTicketDetail>(`portal/tickets/${ticketId}`),
  reply: (ticketId: number, content: string) =>
    apiPost<PortalMessage>(`portal/tickets/${ticketId}/replies`, { content }),
  departments: () => apiGet<PortalDepartment[]>('portal/departments'),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
};
