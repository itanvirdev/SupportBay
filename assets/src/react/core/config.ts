export interface SupportBayConfig {
  restUrl: string;
  restNonce: string;
  portalUrl: string;
  logoutUrl: string;
  siteName: string;
  homeUrl: string;
  resetPasswordUrl: string;
  registrationEnabled: boolean;
  authenticated: boolean;
}

declare global {
  interface Window {
    supportBayPortal?: SupportBayConfig;
  }
}

export function getConfig(): SupportBayConfig {
  if (!window.supportBayPortal) {
    throw new Error('SupportBay portal configuration is unavailable.');
  }

  return window.supportBayPortal;
}
