import { type FormEvent, useRef } from 'react'
import { useNavigate, useSearchParams } from 'react-router'

export function SearchBar() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const currentTerm = searchParams.get('q') ?? ''
  const inputRef = useRef<HTMLInputElement>(null)

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const term = inputRef.current?.value.trim() ?? ''

    if (term === '') {
      inputRef.current?.focus()
      return
    }

    navigate(`/search?${new URLSearchParams({ q: term })}`)
  }

  return (
    <form role="search" className="search-bar" onSubmit={handleSubmit}>
      <label htmlFor="search-input" className="visually-hidden">
        Search products
      </label>
      {/* key resets the field when the URL's term changes, e.g. on back/forward. */}
      <input
        key={currentTerm}
        ref={inputRef}
        id="search-input"
        type="search"
        name="q"
        placeholder="Search products"
        defaultValue={currentTerm}
        maxLength={100}
        autoComplete="off"
      />
      <button type="submit">Search</button>
    </form>
  )
}
