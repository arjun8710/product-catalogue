<?php

namespace App\Catalogue\Contracts;

use App\Catalogue\Category;
use App\Catalogue\Exceptions\ProductSourceUnavailable;
use App\Catalogue\Product;

/**
 * The boundary between the catalogue and wherever product data lives.
 *
 * Swapping Fake Store for a database, a PIM or a commerce platform means
 * writing a new implementation of this interface and changing the binding
 * in CatalogueServiceProvider. Nothing else in the app should need to change.
 *
 * Every method may throw ProductSourceUnavailable when the source cannot be reached.
 */
interface ProductRepository
{
    /**
     * @return list<Category>
     *
     * @throws ProductSourceUnavailable
     */
    public function categories(): array;

    /**
     * @return list<Product>
     *
     * @throws ProductSourceUnavailable
     */
    public function inCategory(string $categorySlug): array;

    /**
     * @throws ProductSourceUnavailable
     */
    public function find(int $id): ?Product;

    /**
     * Products for the given IDs, in the order requested. Unknown IDs are skipped.
     *
     * @param  list<int>  $ids
     * @return list<Product>
     *
     * @throws ProductSourceUnavailable
     */
    public function findMany(array $ids): array;

    /**
     * Products whose title, description or category contain every word of the term,
     * matched case-insensitively from the start of a word ("jack" matches "jacket",
     * "mens" does not match "womens"). A blank term matches nothing.
     *
     * @return list<Product>
     *
     * @throws ProductSourceUnavailable
     */
    public function search(string $term): array;
}
