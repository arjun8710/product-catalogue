import { describe, expect, it, vi } from 'vitest'
import { ApiError, request } from './client'

describe('API client', () => {
  it('returns the parsed JSON body for successful responses', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => Response.json({ data: [1, 2] })))

    await expect(request('/things')).resolves.toEqual({ data: [1, 2] })
  })

  it('turns validation responses into an ApiError with field errors', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => Response.json({ message: 'Invalid', errors: { email: ['Bad email'] } }, { status: 422 })),
    )

    const error = await request('/things').catch((e: unknown) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ status: 422, message: 'Invalid', fieldErrors: { email: ['Bad email'] } })
  })

  it('uses a fallback message when an error response has no JSON body', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => new Response('<html>Bad gateway</html>', { status: 502 })))

    await expect(request('/things')).rejects.toMatchObject({
      status: 502,
      message: 'Something went wrong. Please try again.',
    })
  })

  it('reports network failures as an ApiError with status 0', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => Promise.reject(new TypeError('Failed to fetch'))))

    await expect(request('/things')).rejects.toMatchObject({
      status: 0,
      message: 'We could not reach the server. Check your connection and try again.',
    })
  })

  it('sends JSON bodies with the right headers', async () => {
    const fetchMock = vi.fn(async () => new Response(null, { status: 202 }))
    vi.stubGlobal('fetch', fetchMock)

    await request('/enquiries', { method: 'POST', body: { name: 'Ada' } })

    expect(fetchMock).toHaveBeenCalledWith('/api/enquiries', {
      method: 'POST',
      signal: undefined,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: '{"name":"Ada"}',
    })
  })
})
