import { NavLink } from 'react-router'
import { useCategories } from '../api/queries'

export function CategoryNav() {
  const { data: categories } = useCategories()

  // Navigation is secondary: if categories fail to load, pages still work, so render nothing.
  if (!categories?.length) return null

  return (
    <nav aria-label="Categories" className="category-nav">
      <ul>
        {categories.map((category) => (
          <li key={category.slug}>
            <NavLink to={`/categories/${category.slug}`}>{category.name}</NavLink>
          </li>
        ))}
      </ul>
    </nav>
  )
}
