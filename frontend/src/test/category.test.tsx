import { screen, within } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { electronics, gamingDrive, hardDrive, jewelery, ssd } from './fixtures'
import { categoriesRoute, mockApi, renderApp } from './utils'

describe('Category page', () => {
  it('shows top rated products and then the other products after clicking a category link', async () => {
    mockApi({
      ...categoriesRoute([electronics, jewelery]),
      'GET /api/products/featured': { data: [] },
      'GET /api/categories/electronics': {
        data: { category: electronics, topRated: [gamingDrive, ssd], others: [hardDrive] },
      },
    })
    const { user } = renderApp('/')

    const nav = await screen.findByRole('navigation', { name: 'Categories' })
    await user.click(within(nav).getByRole('link', { name: 'electronics' }))

    const topRated = (await screen.findByRole('heading', { name: 'Top rated' })).closest('section')!
    expect(within(topRated).getAllByRole('article')).toHaveLength(2)
    expect(within(topRated).getByText(gamingDrive.title)).toBeInTheDocument()
    expect(within(topRated).getByText(ssd.title)).toBeInTheDocument()

    const more = screen.getByRole('heading', { name: 'More in electronics' }).closest('section')!
    expect(within(more).getAllByRole('article')).toHaveLength(1)
    expect(within(more).getByText(hardDrive.title)).toBeInTheDocument()
  })

  it('shows not found for an unknown category', async () => {
    mockApi({
      ...categoriesRoute(),
      'GET /api/categories/spaceships': () => ({ status: 404, body: { message: 'Category not found.' } }),
    })

    renderApp('/categories/spaceships')

    expect(await screen.findByRole('heading', { name: 'Page not found' })).toBeInTheDocument()
  })
})
