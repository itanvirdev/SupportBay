import { apiDownload, apiGet, apiGetResponse, apiPost, apiUpload } from './client';
import type {
  CreateTicketInput,
  PortalDepartment,
  PortalCategory,
  PortalCustomField,
  PortalAttachment,
  PortalMessage,
  PortalOverview,
  PortalProfile,
  PortalTicket,
  PortalTicketDetail,
  PortalVerification,
  PortalPurchaseProvider,
  PortalProviderConnection,
  UpdateProfileInput,
} from './types';

export const portalApi = {
  overview: () => apiGet<PortalOverview>('portal'),
  profile: () => apiGet<PortalProfile>('portal/profile'),
  updateProfile: (input: UpdateProfileInput) =>
    apiPost<PortalProfile>('portal/profile', input),
  providerConnections: () =>
    apiGet<PortalProviderConnection[]>('portal/providers'),
  tickets: (query = '') => apiGetResponse<PortalTicket[]>(`portal/tickets${query ? `?${query}` : ''}`),
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
  categories: (departmentId: number) =>
    apiGet<PortalCategory[]>(
      `portal/categories?department_id=${departmentId}`,
    ),
  customFields: (departmentId: number) =>
    apiGet<PortalCustomField[]>(
      `portal/custom-fields?department_id=${departmentId}`,
    ),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
  purchaseProviders: () =>
    apiGet<PortalPurchaseProvider[]>('portal/purchase-providers'),
};
