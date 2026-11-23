import { useFeaturedProducts } from '../api/queries'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorMessage, Loading } from '../components/StatusMessage'

export function HomePage() {
  const { data: products, isPending, isError, error } = useFeaturedProducts()

  return (
    <section aria-labelledby="featured-heading">
      <h1 id="featured-heading">Top rated products</h1>
      {isPending && <Loading />}
      {isError && <ErrorMessage error={error} />}
      {products && <ProductGrid products={products} />}
    </section>
  )
}
