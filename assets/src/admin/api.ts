import { getAdminConfig } from './config';

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  meta: Record<string, unknown>;
}

export async function adminGet<T>(path: string): Promise<ApiResponse<T>> {
  return adminRequest<T>(path, { method: 'GET' });
}

export async function adminPost<T>(path: string, body: object): Promise<ApiResponse<T>> {
  return adminRequest<T>(path, { method: 'POST', body: JSON.stringify(body) });
}

export async function adminPut<T>(path: string, body: object): Promise<ApiResponse<T>> {
  return adminRequest<T>(path, { method: 'PUT', body: JSON.stringify(body) });
}

export async function adminDelete<T>(path: string): Promise<ApiResponse<T>> {
  return adminRequest<T>(path, { method: 'DELETE' });
}

export async function adminUpload<T>(path: string, file: File): Promise<ApiResponse<T>> {
  const body = new FormData();
  body.append('file', file);
  return adminRequest<T>(path, { method: 'POST', body });
}

export async function adminDownload(path: string): Promise<Blob> {
  return (await adminDownloadFile(path)).blob;
}

export async function adminDownloadFile(path: string): Promise<{blob: Blob; filename: string | null}> {
  const config = getAdminConfig();
  const response = await fetch(`${config.restUrl}${path}`, {
    credentials: 'same-origin',
    headers: { Accept: 'application/octet-stream', 'X-WP-Nonce': config.restNonce },
  });
  if (!response.ok) throw new Error('File could not be downloaded.');
  const disposition = response.headers.get('Content-Disposition') || '';
  const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
  const plain = disposition.match(/filename="?([^";]+)"?/i)?.[1];
  return {
    blob: await response.blob(),
    filename: encoded ? decodeURIComponent(encoded) : plain || null,
  };
}

async function adminRequest<T>(path: string, options: RequestInit): Promise<ApiResponse<T>> {
  const config = getAdminConfig();
  const response = await fetch(`${config.restUrl}${path}`, {
    ...options,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-WP-Nonce': config.restNonce,
      ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
    },
  });
  const payload = await response.json() as ApiResponse<T>;

  if (!response.ok || payload.success === false) {
    throw new Error(payload.message || 'SupportBay could not load this information.');
  }

  return payload;
}
