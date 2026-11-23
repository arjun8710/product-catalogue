<?php

namespace App\Catalogue;

use App\Catalogue\Contracts\ProductRepository;
use Psr\Log\LoggerInterface;

/**
 * Business rules for the storefront: what is featured, what counts as top rated
 * in a category, how search behaves. Knows nothing about where products come from.
 */
final class CatalogueService
{
    /**
     * @param  list<int>  $featuredProductIds
     */
    public function __construct(
        private readonly ProductRepository $products,
        private readonly LoggerInterface $logger,
        private readonly array $featuredProductIds,
        private readonly int $categoryTopRatedCount,
    ) {}

    /**
     * The products the business has hand-picked as "top rated", in their chosen order.
     *
     * @return list<Product>
     */
    public function featured(): array
    {
        $products = $this->products->findMany($this->featuredProductIds);

        if (count($products) < count($this->featuredProductIds)) {
            $foundIds = array_map(fn (Product $product) => $product->id, $products);

            $this->logger->warning('Some featured products no longer exist in the catalogue', [
                'missing_ids' => array_values(array_diff($this->featuredProductIds, $foundIds)),
            ]);
        }

        return $products;
    }

    /**
     * @return list<Category>
     */
    public function categories(): array
    {
        return $this->products->categories();
    }

    /**
     * A category's highest-rated products, followed by the rest of the category.
     * Returns null when the category does not exist.
     */
    public function categoryListing(string $slug): ?CategoryListing
    {
        $category = null;

        foreach ($this->products->categories() as $candidate) {
            if ($candidate->slug === $slug) {
                $category = $candidate;
                break;
            }
        }

        if ($category === null) {
            return null;
        }

        $products = $this->products->inCategory($slug);

        $ranked = $products;
        usort($ranked, fn (Product $a, Product $b) => [$b->rating->average, $b->rating->count, $a->id]
            <=> [$a->rating->average, $a->rating->count, $b->id]);

        $topRated = array_slice($ranked, 0, max(0, $this->categoryTopRatedCount));
        $topRatedIds = array_map(fn (Product $product) => $product->id, $topRated);

        // Keep the source's own ordering for the remaining products.
        $others = array_values(array_filter(
            $products,
            fn (Product $product) => ! in_array($product->id, $topRatedIds, true),
        ));

        return new CategoryListing($category, $topRated, $others);
    }

    /**
     * @return list<Product>
     */
    public function search(string $term): array
    {
        return $this->products->search($term);
    }

    public function product(int $id): ?Product
    {
        return $this->products->find($id);
    }
}
