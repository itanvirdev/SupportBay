import { getAdminConfig } from './config';

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  meta: Record<string, unknown>;
}

export async function adminGet<T>(path: string): Promise<ApiResponse<T>> {
  const config = getAdminConfig();
  const response = await fetch(`${config.restUrl}${path}`, {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-WP-Nonce': config.restNonce,
    },
  });
  const payload = await response.json() as ApiResponse<T>;

  if (!response.ok || payload.success === false) {
    throw new Error(payload.message || 'SupportBay could not load this information.');
  }

  return payload;
}
