export interface SupportBayConfig {
  restUrl: string;
  restNonce: string;
  portalUrl: string;
  logoutUrl: string;
  siteName: string;
  portalLogoUrl: string;
  homeUrl: string;
  footerCopyrightText: string;
  removePoweredByBranding: boolean;
  wordpressAuthEnabled: boolean;
  wordpressLoginUrl: string;
  wordpressRegistrationUrl: string;
  wordpressProfileEnabled: boolean;
  wordpressProfileUrl: string;
  ticketListAutoRefreshEnabled: boolean;
  ticketListAutoRefreshInterval: number;
  fileUploadEnabled: boolean;
  fileUploadMaxSizeMb: number;
  fileUploadAllowedExtensions: string[];
  attachmentPopupPreviewEnabled: boolean;
  ticketStatusLabels: Record<string,string>;
  resetPasswordUrl: string;
  registrationEnabled: boolean;
  guestTicketCreationEnabled: boolean;
  purchaseProviderFieldLabel: string;
  oauthLoginProviders: Array<{slug:string;name:string;url:string}>;
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
