import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { ApiError } from '../api/client'
import { useProduct } from '../api/queries'
import type { Product } from '../api/types'
import { EnquiryForm } from '../components/EnquiryForm'
import { RatingSummary } from '../components/RatingSummary'
import { ErrorMessage, Loading } from '../components/StatusMessage'
import { formatPrice } from '../format'
import { NotFoundPage } from './NotFoundPage'

export function ProductPage() {
  const { id = '' } = useParams()
  const productId = Number(id)
  const { data: product, isPending, error } = useProduct(productId)

  if (!Number.isInteger(productId) || productId <= 0) return <NotFoundPage />
  if (isPending) return <Loading />
  if (error instanceof ApiError && error.isNotFound) return <NotFoundPage />
  if (error) return <ErrorMessage error={error} />

  // Keyed so enquiry state resets if the route switches straight to another product.
  return <ProductDetails key={product.id} product={product} />
}

function ProductDetails({ product }: { product: Product }) {
  const [isEnquiring, setIsEnquiring] = useState(false)

  return (
    <article className="product-detail">
      <div className="product-detail__image">
        <img src={product.imageUrl} alt={product.title} width={400} height={400} />
      </div>

      <div className="product-detail__info">
        <p className="product-detail__category">
          <Link to={`/categories/${product.category.slug}`}>{product.category.name}</Link>
        </p>
        <h1>{product.title}</h1>
        <p className="product-detail__price">{formatPrice(product.price)}</p>
        <RatingSummary rating={product.rating} />
        <p className="product-detail__description">{product.description}</p>

        {isEnquiring ? (
          <EnquiryForm product={product} />
        ) : (
          <button type="button" className="button-primary" onClick={() => setIsEnquiring(true)}>
            Enquire
          </button>
        )}
      </div>
    </article>
  )
}
