import type { Product } from '../api/types'
import { ProductTile } from './ProductTile'

export function ProductGrid({ products }: { products: Product[] }) {
  return (
    <ul className="product-grid">
      {products.map((product) => (
        <li key={product.id}>
          <ProductTile product={product} />
        </li>
      ))}
    </ul>
  )
}
