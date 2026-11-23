import { useParams } from 'react-router'
import { ApiError } from '../api/client'
import { useCategoryListing } from '../api/queries'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorMessage, Loading } from '../components/StatusMessage'
import { NotFoundPage } from './NotFoundPage'

export function CategoryPage() {
  const { slug = '' } = useParams()
  const { data: listing, isPending, error } = useCategoryListing(slug)

  if (isPending) return <Loading />
  if (error instanceof ApiError && error.isNotFound) return <NotFoundPage />
  if (error) return <ErrorMessage error={error} />

  const { category, topRated, others } = listing

  return (
    <>
      <h1 className="category-title">{category.name}</h1>

      <section aria-labelledby="top-rated-heading">
        <h2 id="top-rated-heading">Top rated</h2>
        <ProductGrid products={topRated} />
      </section>

      {others.length > 0 && (
        <section aria-labelledby="more-heading">
          <h2 id="more-heading">More in {category.name}</h2>
          <ProductGrid products={others} />
        </section>
      )}
    </>
  )
}
