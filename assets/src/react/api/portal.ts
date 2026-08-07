import { apiDownload, apiGet, apiPost, apiUpload } from './client';
import type {
  CreateTicketInput,
  PortalDepartment,
  PortalAttachment,
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
  closeTicket: (ticketId: number) =>
    apiPost<PortalTicket>(`portal/tickets/${ticketId}/close`, {}),
  reopenTicket: (ticketId: number) =>
    apiPost<PortalTicket>(`portal/tickets/${ticketId}/reopen`, {}),
  uploadAttachment: (ticketId: number, messageId: number, file: File) =>
    apiUpload<PortalAttachment>(
      `portal/tickets/${ticketId}/messages/${messageId}/attachments`,
      file,
    ),
  downloadAttachment: (attachmentId: number) =>
    apiDownload(`portal/attachments/${attachmentId}/download`),
  departments: () => apiGet<PortalDepartment[]>('portal/departments'),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
};
