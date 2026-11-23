import { ApiError } from '../api/client'

export function Loading({ label = 'Loading…' }: { label?: string }) {
  return (
    <p className="status" role="status">
      {label}
    </p>
  )
}

export function ErrorMessage({ error }: { error: unknown }) {
  const message = error instanceof ApiError ? error.message : 'Something went wrong. Please try again.'

  return (
    <p className="status status--error" role="alert">
      {message}
    </p>
  )
}
