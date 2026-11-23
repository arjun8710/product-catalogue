import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { vi } from 'vitest'
import { AppRoutes } from '../App'

type Handler = (request: { url: URL; body: unknown }) => { status?: number; body?: unknown }

/**
 * Stubs fetch at the network boundary so tests exercise the real API client,
 * React Query and routing. Routes are keyed like "GET /api/products/featured".
 * Unmatched requests fail loudly.
 */
export function mockApi(routes: Record<string, Handler | object>) {
  const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
    const url = new URL(String(input), 'http://localhost')
    const method = init?.method ?? 'GET'
    const route = routes[`${method} ${url.pathname}`]

    if (route === undefined) {
      throw new Error(`Unexpected request: ${method} ${url.pathname}${url.search}`)
    }

    const body = init?.body ? JSON.parse(String(init.body)) : undefined
    const result = typeof route === 'function' ? (route as Handler)({ url, body }) : { body: route }

    return new Response(JSON.stringify(result.body ?? {}), {
      status: result.status ?? 200,
      headers: { 'Content-Type': 'application/json' },
    })
  })

  vi.stubGlobal('fetch', fetchMock)
  return fetchMock
}

/** Renders the whole app at a given URL, with a fresh cache and no retries. */
export function renderApp(url = '/') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  const user = userEvent.setup()
  const result = render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[url]}>
        <AppRoutes />
      </MemoryRouter>
    </QueryClientProvider>,
  )

  return { user, ...result }
}

/** The categories call happens on every page via the header navigation. */
export const categoriesRoute = (categories: object[] = []) => ({ 'GET /api/categories': { data: categories } })
