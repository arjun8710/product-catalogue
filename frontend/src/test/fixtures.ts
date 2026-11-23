import type { Category, Product } from '../api/types'

export const electronics: Category = { slug: 'electronics', name: 'electronics' }
export const jewelery: Category = { slug: 'jewelery', name: 'jewelery' }

export function makeProduct(overrides: Partial<Product> & Pick<Product, 'id' | 'title'>): Product {
  return {
    description: 'A test product.',
    price: { minorUnits: 1999, currency: 'USD' },
    category: electronics,
    imageUrl: `https://images.test/${overrides.id}.png`,
    rating: { average: 4.2, count: 120 },
    ...overrides,
  }
}

export const ssd = makeProduct({ id: 11, title: 'Silicon Power 256GB SSD', price: { minorUnits: 10900, currency: 'USD' } })
export const gamingDrive = makeProduct({ id: 12, title: 'WD 4TB Gaming Drive' })
export const hardDrive = makeProduct({ id: 9, title: 'WD 2TB External Hard Drive', rating: { average: 3.3, count: 203 } })
export const bracelet = makeProduct({ id: 5, title: 'Gold Dragon Bracelet', category: jewelery })
