import { Link, Outlet } from 'react-router'
import { CategoryNav } from './CategoryNav'
import { SearchBar } from './SearchBar'

export function Layout() {
  return (
    <div className="layout">
      <a href="#main" className="skip-link">
        Skip to content
      </a>
      <header className="site-header">
        <div className="container site-header__inner">
          <Link to="/" className="brand">
            Catalogue
          </Link>
          <SearchBar />
        </div>
        <div className="container">
          <CategoryNav />
        </div>
      </header>
      <main id="main" className="container site-main">
        <Outlet />
      </main>
      <footer className="site-footer">
        <div className="container">Proof of concept · product data from Fake Store API</div>
      </footer>
    </div>
  )
}
