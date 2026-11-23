// Same-origin by default: nginx (Docker) or the Vite dev server proxies /api to the backend.
const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api'

export type FieldErrors = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly fieldErrors: FieldErrors

  constructor(message: string, status: number, fieldErrors: FieldErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }

  get isNotFound(): boolean {
    return this.status === 404
  }

  get isClientError(): boolean {
    return this.status >= 400 && this.status < 500
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST'
  body?: unknown
  signal?: AbortSignal
}

export async function request<T>(path: string, { method = 'GET', body, signal }: RequestOptions = {}): Promise<T> {
  let response: Response

  try {
    response = await fetch(`${BASE_URL}${path}`, {
      method,
      signal,
      headers: {
        Accept: 'application/json',
        ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    })
  } catch (error) {
    if (signal?.aborted) throw error
    throw new ApiError('We could not reach the server. Check your connection and try again.', 0)
  }

  const payload: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const { message, errors } = (payload ?? {}) as { message?: string; errors?: FieldErrors }
    throw new ApiError(message ?? fallbackMessage(response.status), response.status, errors ?? {})
  }

  return payload as T
}

function fallbackMessage(status: number): string {
  if (status === 404) return 'We could not find what you were looking for.'
  if (status === 429) return 'Too many requests. Please wait a moment and try again.'
  return 'Something went wrong. Please try again.'
}
