export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  meta: Record<string, unknown>;
}

export interface PortalCustomer {
  id: number;
  state: string;
  source: string;
  avatar_url: string | null;
  company: string | null;
  country: string | null;
  timezone: string | null;
  language: string | null;
  last_login_at: string | null;
}

export interface PortalOverview {
  customer: PortalCustomer;
  summary: {
    tickets: number;
    verifications: number;
  };
}

export interface PortalTicket {
  id: number;
  subject: string;
  status: string;
  priority: string;
  source: string;
  purchase_verification_id: number | null;
  created_at: string;
  updated_at: string | null;
}

export interface PortalVerification {
  id: number;
  provider: string;
  product_id: string | null;
  product_name: string | null;
  license_type: string | null;
  support_expires_at: string | null;
  purchased_at: string | null;
  status: string;
  verified_at: string | null;
}
