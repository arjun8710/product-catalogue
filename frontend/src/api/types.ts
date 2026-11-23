// Shapes returned by our backend API (not Fake Store's): see backend/app/Http/Resources.

export interface Category {
  slug: string
  name: string
}

export interface Price {
  /** Amount in minor units, e.g. cents. */
  minorUnits: number
  /** ISO 4217 currency code. */
  currency: string
}

export interface Rating {
  average: number
  count: number
}

export interface Product {
  id: number
  title: string
  description: string
  price: Price
  category: Category
  imageUrl: string
  rating: Rating
}

export interface CategoryListing {
  category: Category
  topRated: Product[]
  others: Product[]
}

export interface EnquiryInput {
  productId: number
  name: string
  email: string
  phone?: string
  message?: string
}
