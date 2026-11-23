import type { Rating } from '../api/types'

export function RatingSummary({ rating }: { rating: Rating }) {
  return (
    <p className="rating">
      <span aria-hidden="true">★ </span>
      {rating.average.toFixed(1)}
      <span className="rating__count"> ({rating.count} ratings)</span>
    </p>
  )
}
