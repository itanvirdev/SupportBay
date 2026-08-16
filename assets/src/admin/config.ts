export interface AdminConfig {
  restUrl: string;
  restNonce: string;
  siteName: string;
  adminUrl: string;
  userName: string;
  canManageCustomers: boolean;
  canViewVerifications: boolean;
  canExportReports: boolean;
  canManageSavedReplies: boolean;
  canManageCategories: boolean;
  section: 'tickets' | 'reports' | 'settings';
}

declare global {
  interface Window {
    supportBayAdmin?: AdminConfig;
  }
}

export function getAdminConfig(): AdminConfig {
  if (!window.supportBayAdmin) {
    throw new Error('SupportBay administrator configuration is unavailable.');
  }

  return window.supportBayAdmin;
}
