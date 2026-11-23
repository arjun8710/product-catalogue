import { Link } from 'react-router'

export function NotFoundPage() {
  return (
    <>
      <h1>Page not found</h1>
      <p>
        We could not find what you were looking for. <Link to="/">Back to the home page</Link>
      </p>
    </>
  )
}
