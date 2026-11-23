import { useSearchParams } from 'react-router'
import { useProductSearch } from '../api/queries'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorMessage, Loading } from '../components/StatusMessage'

export function SearchPage() {
  const [searchParams] = useSearchParams()
  const term = (searchParams.get('q') ?? '').trim()
  const { data: products, isPending, error } = useProductSearch(term)

  if (term === '') {
    return (
      <>
        <h1>Search</h1>
        <p>Enter a search term above to find products.</p>
      </>
    )
  }

  return (
    <section aria-labelledby="search-heading">
      <h1 id="search-heading">Results for “{term}”</h1>
      {isPending && <Loading label="Searching…" />}
      {error && <ErrorMessage error={error} />}
      {products && products.length === 0 && <p>No products match “{term}”. Try a different search term.</p>}
      {products && products.length > 0 && (
        <>
          <p className="result-count" role="status">
            {products.length} {products.length === 1 ? 'product' : 'products'} found
          </p>
          <ProductGrid products={products} />
        </>
      )}
    </section>
  )
}
