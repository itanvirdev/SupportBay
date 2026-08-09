export interface AdminConfig {
  restUrl: string;
  restNonce: string;
  siteName: string;
  adminUrl: string;
  userName: string;
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
