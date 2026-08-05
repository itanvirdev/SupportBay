import { apiGet } from './client';
import type {
  PortalOverview,
  PortalTicket,
  PortalVerification,
} from './types';

export const portalApi = {
  overview: () => apiGet<PortalOverview>('portal'),
  tickets: () => apiGet<PortalTicket[]>('portal/tickets'),
  verifications: () =>
    apiGet<PortalVerification[]>('portal/verifications'),
};
