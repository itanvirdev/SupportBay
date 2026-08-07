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
  return apiRequest<T>(path, { method: 'GET' });
}

export async function apiPost<T>(
  path: string,
  body: object,
): Promise<T> {
  return apiRequest<T>(path, {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

export async function apiUpload<T>(path: string, file: File): Promise<T> {
  const body = new FormData();
  body.append('file', file);

  return apiRequest<T>(path, { method: 'POST', body });
}

async function apiRequest<T>(
  path: string,
  options: RequestInit,
): Promise<T> {
  const config = getConfig();
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-WP-Nonce': config.restNonce,
  };

  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${config.restUrl}${path}`, {
    ...options,
    credentials: 'same-origin',
    headers,
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
