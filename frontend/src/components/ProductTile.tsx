import { Link } from 'react-router'
import type { Product } from '../api/types'
import { formatPrice } from '../format'
import { RatingSummary } from './RatingSummary'

export function ProductTile({ product }: { product: Product }) {
  return (
    <article className="product-tile">
      <Link to={`/products/${product.id}`} className="product-tile__link">
        <div className="product-tile__image">
          <img src={product.imageUrl} alt="" loading="lazy" width={200} height={200} />
        </div>
        <h3 className="product-tile__title">{product.title}</h3>
      </Link>
      <p className="product-tile__price">{formatPrice(product.price)}</p>
      <RatingSummary rating={product.rating} />
    </article>
  )
}
