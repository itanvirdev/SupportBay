import { getConfig } from '../core/config';
import type { ApiResponse } from './types';

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
  }
}

export async function apiGet<T>(path: string): Promise<T> {
  const config = getConfig();
  const response = await fetch(`${config.restUrl}${path}`, {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-WP-Nonce': config.restNonce,
    },
  });
  const payload = (await response.json()) as Partial<ApiResponse<T>> & {
    message?: string;
  };

  if (!response.ok || payload.success === false) {
    throw new ApiError(
      payload.message ?? 'SupportBay could not load this information.',
      response.status,
    );
  }

  return payload.data as T;
}
