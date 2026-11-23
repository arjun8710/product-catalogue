import type { Price } from './api/types'

/** Formats a price in the visitor's locale. Assumes a two-decimal currency, like the backend. */
export function formatPrice({ minorUnits, currency }: Price): string {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(minorUnits / 100)
}
