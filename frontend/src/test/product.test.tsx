import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { bracelet, ssd } from './fixtures'
import { categoriesRoute, mockApi, renderApp } from './utils'

describe('Product page', () => {
  it('opens the product details page when a product tile is clicked', async () => {
    mockApi({
      ...categoriesRoute(),
      'GET /api/products/featured': { data: [ssd, bracelet] },
      'GET /api/products/11': { data: { ...ssd, description: 'Fast and reliable storage.' } },
    })
    const { user } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: ssd.title }))

    expect(await screen.findByRole('heading', { level: 1, name: ssd.title })).toBeInTheDocument()
    expect(screen.getByText(/\$109\.00/)).toBeInTheDocument()
    expect(screen.getByText('Fast and reliable storage.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'electronics' })).toHaveAttribute('href', '/categories/electronics')
    expect(screen.getByRole('button', { name: 'Enquire' })).toBeInTheDocument()
  })

  it('shows not found for an unknown product', async () => {
    mockApi({
      ...categoriesRoute(),
      'GET /api/products/999': () => ({ status: 404, body: { message: 'Product not found.' } }),
    })

    renderApp('/products/999')

    expect(await screen.findByRole('heading', { name: 'Page not found' })).toBeInTheDocument()
  })

  it('shows not found for a malformed product id without calling the API', async () => {
    const fetchMock = mockApi(categoriesRoute())

    renderApp('/products/abc')

    expect(await screen.findByRole('heading', { name: 'Page not found' })).toBeInTheDocument()
    expect(fetchMock).not.toHaveBeenCalledWith(expect.stringContaining('/products/'), expect.anything())
  })
})
