import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { bracelet, gamingDrive, ssd } from './fixtures'
import { categoriesRoute, mockApi, renderApp } from './utils'

describe('Home page', () => {
  it('shows the featured top rated products, in the order the API returns them', async () => {
    mockApi({ ...categoriesRoute(), 'GET /api/products/featured': { data: [ssd, bracelet, gamingDrive] } })

    renderApp('/')

    expect(screen.getByRole('heading', { level: 1, name: 'Top rated products' })).toBeInTheDocument()
    const tiles = await screen.findAllByRole('article')
    expect(tiles.map((tile) => within(tile).getByRole('heading').textContent)).toEqual([
      ssd.title,
      bracelet.title,
      gamingDrive.title,
    ])
    // Formatting follows the machine's locale ("$109.00" or "US$109.00"), so match loosely.
    expect(within(tiles[0]).getByText(/\$109\.00/)).toBeInTheDocument()
  })

  it('shows a friendly error when the catalogue is unavailable', async () => {
    mockApi({
      ...categoriesRoute(),
      'GET /api/products/featured': () => ({
        status: 503,
        body: { message: 'The product catalogue is temporarily unavailable. Please try again shortly.' },
      }),
    })

    renderApp('/')

    expect(await screen.findByRole('alert')).toHaveTextContent('temporarily unavailable')
  })
})
