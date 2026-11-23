import { request } from './client'
import type { Category, CategoryListing, EnquiryInput, Product } from './types'

interface Envelope<T> {
  data: T
}

export async function getFeaturedProducts(signal?: AbortSignal): Promise<Product[]> {
  return (await request<Envelope<Product[]>>('/products/featured', { signal })).data
}

export async function getCategories(signal?: AbortSignal): Promise<Category[]> {
  return (await request<Envelope<Category[]>>('/categories', { signal })).data
}

export async function getCategoryListing(slug: string, signal?: AbortSignal): Promise<CategoryListing> {
  return (await request<Envelope<CategoryListing>>(`/categories/${encodeURIComponent(slug)}`, { signal })).data
}

export async function searchProducts(term: string, signal?: AbortSignal): Promise<Product[]> {
  const query = new URLSearchParams({ q: term })
  return (await request<Envelope<Product[]>>(`/products/search?${query}`, { signal })).data
}

export async function getProduct(id: number, signal?: AbortSignal): Promise<Product> {
  return (await request<Envelope<Product>>(`/products/${id}`, { signal })).data
}

export async function submitEnquiry(enquiry: EnquiryInput): Promise<void> {
  await request('/enquiries', { method: 'POST', body: enquiry })
}
