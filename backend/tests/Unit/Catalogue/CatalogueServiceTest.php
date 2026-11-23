<?php

namespace Tests\Unit\Catalogue;

use App\Catalogue\CatalogueService;
use App\Catalogue\Product;
use App\Catalogue\Sources\InMemoryProductRepository;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tests\Support\Products;
use Tests\TestCase;

final class CatalogueServiceTest extends TestCase
{
    #[Test]
    public function featured_returns_the_chosen_products_in_the_chosen_order(): void
    {
        $service = $this->service(featuredIds: [3, 1, 2]);

        $this->assertSame([3, 1, 2], $this->ids($service->featured()));
    }

    #[Test]
    public function featured_skips_and_logs_products_that_no_longer_exist(): void
    {
        $logger = Mockery::spy(LoggerInterface::class);
        $service = $this->service(featuredIds: [1, 404, 2], logger: $logger);

        $this->assertSame([1, 2], $this->ids($service->featured()));
        $logger->shouldHaveReceived('warning')->once()->with(Mockery::any(), ['missing_ids' => [404]]);
    }

    #[Test]
    public function category_listing_puts_the_highest_rated_first_and_keeps_the_rest_in_source_order(): void
    {
        $service = $this->service(
            products: [
                Products::make(1, category: 'electronics', rating: 2.0),
                Products::make(2, category: 'electronics', rating: 4.8, ratingCount: 100),
                Products::make(3, category: 'electronics', rating: 3.1),
                Products::make(4, category: 'jewelery', rating: 5.0),
                Products::make(5, category: 'electronics', rating: 4.8, ratingCount: 400),
                Products::make(6, category: 'electronics', rating: 1.0),
                Products::make(7, category: 'electronics', rating: 3.0),
            ],
            topRatedCount: 3,
        );

        $listing = $service->categoryListing('electronics');

        $this->assertNotNull($listing);
        $this->assertSame('electronics', $listing->category->slug);
        // Equal averages are broken by the number of ratings.
        $this->assertSame([5, 2, 3], $this->ids($listing->topRated));
        $this->assertSame([1, 6, 7], $this->ids($listing->others));
    }

    #[Test]
    public function category_listing_handles_categories_smaller_than_the_top_rated_count(): void
    {
        $service = $this->service(
            products: [Products::make(1, category: 'jewelery', rating: 4.0)],
            topRatedCount: 3,
        );

        $listing = $service->categoryListing('jewelery');

        $this->assertSame([1], $this->ids($listing->topRated));
        $this->assertSame([], $listing->others);
    }

    #[Test]
    public function category_listing_is_null_for_an_unknown_category(): void
    {
        $this->assertNull($this->service()->categoryListing('does-not-exist'));
    }

    /**
     * @param  list<Product>|null  $products
     * @param  list<int>  $featuredIds
     */
    private function service(
        ?array $products = null,
        array $featuredIds = [],
        int $topRatedCount = 3,
        ?LoggerInterface $logger = null,
    ): CatalogueService {
        $products ??= [Products::make(1), Products::make(2), Products::make(3)];

        return new CatalogueService(
            products: new InMemoryProductRepository($products),
            logger: $logger ?? new NullLogger,
            featuredProductIds: $featuredIds,
            categoryTopRatedCount: $topRatedCount,
        );
    }

    /**
     * @param  list<Product>  $products
     * @return list<int>
     */
    private function ids(array $products): array
    {
        return array_map(fn (Product $product) => $product->id, $products);
    }
}
