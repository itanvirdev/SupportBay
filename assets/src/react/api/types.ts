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

export interface PortalProfile {
  id: number;
  display_name: string;
  email: string;
  avatar_url: string | null;
  company: string | null;
  phone: string | null;
  country: string | null;
  timezone: string | null;
  language: string | null;
  state: string;
  source: string;
}

export interface UpdateProfileInput {
  company: string;
  phone: string;
  country: string;
  timezone: string;
  language: string;
}

export interface PortalProviderConnection {
  slug: string;
  name: string;
  connected: boolean;
  reference: string | null;
  connect_url: string;
}

export interface PortalTicket {
  id: number;
  track_id: string;
  subject: string;
  status: string;
  priority: string;
  source: string;
  purchase_verification_id: number | null;
  created_at: string;
  updated_at: string | null;
}

export interface PortalMessage {
  id: number;
  author_type: string;
  type: string;
  content: string;
  edited_at: string | null;
  created_at: string;
  attachments: PortalAttachment[];
}

export interface PortalAttachment {
  id: number;
  message_id: number;
  original_name: string;
  file_size: number;
  extension: string;
  mime_type: string;
  category: string;
  is_previewable: boolean;
  created_at: string;
}

export interface PortalTicketDetail {
  ticket: PortalTicket;
  messages: PortalMessage[];
  verification: PortalVerification | null;
  custom_fields: PortalTicketCustomFieldValue[];
}

export interface PortalTicketCustomFieldValue {
  id: number;
  name: string;
  type: PortalCustomFieldType;
  value: string;
}

export interface PortalDepartment {
  id: number;
  name: string;
  description: string | null;
}
export interface PortalCategory {
  id: number;
  name: string;
  description: string | null;
  department_id: number | null;
}

export type PortalCustomFieldType =
  | 'text' | 'textarea' | 'number' | 'select'
  | 'checkbox' | 'date' | 'email' | 'url';

export interface PortalCustomField {
  id: number;
  name: string;
  slug: string;
  type: PortalCustomFieldType;
  options: string[];
  is_required: boolean;
  department_id: number | null;
  sort_order: number;
}

export interface CreateTicketInput {
  subject: string;
  content: string;
  department_id: number;
  category_id: number | null;
  provider: string;
  purchase_reference: string;
  custom_fields: Record<number, string>;
}

export interface PortalPurchaseProvider {
  slug: string;
  name: string;
  purchase_field_label: string;
  license_required: boolean;
  check_support_expiry: boolean;
}

export interface PortalVerification {
  id: number;
  provider: string;
  reference?: string;
  product_id: string | null;
  product_name: string | null;
  license_type: string | null;
  support_expires_at: string | null;
  purchased_at: string | null;
  status: string;
  verified_at: string | null;
}
