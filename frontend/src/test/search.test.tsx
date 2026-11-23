import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { hardDrive, ssd } from './fixtures'
import { categoriesRoute, mockApi, renderApp } from './utils'

describe('Search', () => {
  it('shows only the matching products after clicking the search button', async () => {
    const fetchMock = mockApi({
      ...categoriesRoute(),
      'GET /api/products/featured': { data: [ssd, hardDrive] },
      'GET /api/products/search': ({ url }) => ({
        body: { data: url.searchParams.get('q') === 'external drive' ? [hardDrive] : [] },
      }),
    })
    const { user } = renderApp('/')

    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), '  external drive ')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(await screen.findByRole('heading', { name: 'Results for “external drive”' })).toBeInTheDocument()
    expect(await screen.findByText('1 product found')).toBeInTheDocument()
    expect(screen.getByText(hardDrive.title)).toBeInTheDocument()
    expect(screen.queryByText(ssd.title)).not.toBeInTheDocument()
    expect(fetchMock).toHaveBeenCalledWith('/api/products/search?q=external+drive', expect.anything())
  })

  it('tells the customer when nothing matches', async () => {
    mockApi({ ...categoriesRoute(), 'GET /api/products/search': { data: [] } })

    renderApp('/search?q=spaceship')

    expect(await screen.findByText('No products match “spaceship”. Try a different search term.')).toBeInTheDocument()
  })

  it('does not search for a blank term', async () => {
    const fetchMock = mockApi({ ...categoriesRoute(), 'GET /api/products/featured': { data: [] } })
    const { user } = renderApp('/')

    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), '   ')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(screen.getByRole('heading', { level: 1, name: 'Top rated products' })).toBeInTheDocument()
    expect(fetchMock).not.toHaveBeenCalledWith(expect.stringContaining('/search'), expect.anything())
  })
})
