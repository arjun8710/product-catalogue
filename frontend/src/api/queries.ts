import { QueryClient, useQuery } from '@tanstack/react-query'
import { ApiError } from './client'
import { getCategories, getCategoryListing, getFeaturedProducts, getProduct, searchProducts } from './catalogue'

export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 60_000,
        refetchOnWindowFocus: false,
        // Retry transient failures, but not 4xx responses: they will not succeed on retry.
        retry: (failureCount, error) =>
          !(error instanceof ApiError && error.isClientError) && failureCount < 2,
      },
    },
  })
}

export function useFeaturedProducts() {
  return useQuery({
    queryKey: ['products', 'featured'],
    queryFn: ({ signal }) => getFeaturedProducts(signal),
  })
}

export function useCategories() {
  return useQuery({
    queryKey: ['categories'],
    queryFn: ({ signal }) => getCategories(signal),
    staleTime: 10 * 60_000,
  })
}

export function useCategoryListing(slug: string) {
  return useQuery({
    queryKey: ['categories', slug],
    queryFn: ({ signal }) => getCategoryListing(slug, signal),
  })
}

export function useProductSearch(term: string) {
  return useQuery({
    queryKey: ['products', 'search', term],
    queryFn: ({ signal }) => searchProducts(term, signal),
    enabled: term.length > 0,
  })
}

export function useProduct(id: number) {
  return useQuery({
    queryKey: ['products', id],
    queryFn: ({ signal }) => getProduct(id, signal),
    enabled: Number.isInteger(id) && id > 0,
  })
}
