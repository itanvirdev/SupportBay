import { apiDownload, apiGet, apiGetResponse, apiPost, apiUpload } from './client';
import type {
  CreateTicketInput,
  CreatedPortalTicket,
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
  PortalTag,
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
    apiPost<CreatedPortalTicket>('portal/tickets', input),
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
  categories: () => apiGet<PortalCategory[]>('portal/categories'),
  tags: () => apiGet<PortalTag[]>('portal/tags'),
  customFields: (categoryId: number | null) => apiGet<PortalCustomField[]>(
    `portal/custom-fields${categoryId ? `?category_id=${categoryId}` : ''}`,
  ),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
  purchaseProviders: () =>
    apiGet<PortalPurchaseProvider[]>('portal/purchase-providers'),
};
