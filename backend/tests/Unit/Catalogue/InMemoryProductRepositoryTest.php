<?php

namespace Tests\Unit\Catalogue;

use App\Catalogue\Product;
use App\Catalogue\Sources\InMemoryProductRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Products;
use Tests\TestCase;

final class InMemoryProductRepositoryTest extends TestCase
{
    private InMemoryProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryProductRepository([
            Products::make(1, title: 'Mens Cotton Jacket', description: 'Warm outerwear', category: "men's clothing"),
            Products::make(2, title: 'Rain Jacket Women Windbreaker', description: 'Lightweight', category: "women's clothing"),
            Products::make(3, title: 'Gold Bracelet', description: "Women's jewellery", category: 'jewelery'),
            Products::make(4, title: 'SanDisk SSD', description: 'Fast storage drive', category: 'electronics'),
            Products::make(5, title: 'Womens T Shirt', description: 'Casual cotton', category: "women's clothing"),
        ]);
    }

    /**
     * @return iterable<string, array{string, list<int>}>
     */
    public static function searchCases(): iterable
    {
        yield 'matches title, case-insensitively' => ['SANDISK', [4]];
        yield 'matches description' => ['storage', [4]];
        yield 'matches category name' => ['electronics', [4]];
        yield 'matches the start of a word' => ['jack', [1, 2]];
        yield 'requires every word to match' => ['cotton jacket', [1]];
        yield 'does not match inside a word' => ['mens', [1]];
        yield 'ignores apostrophes' => ['womens', [2, 3, 5]];
        yield 'ignores curly apostrophes in the term' => ['women’s', [2, 3, 5]];
        yield 'collapses extra whitespace' => ['  gold   bracelet ', [3]];
        yield 'returns nothing when nothing matches' => ['spaceship', []];
        yield 'returns nothing for a blank term' => ['   ', []];
    }

    #[Test]
    #[DataProvider('searchCases')]
    public function it_searches_products(string $term, array $expectedIds): void
    {
        $this->assertSame($expectedIds, $this->ids($this->repository->search($term)));
    }

    #[Test]
    public function it_treats_regex_characters_in_the_term_literally(): void
    {
        $this->assertSame([], $this->repository->search('.*'));
        $this->assertSame([], $this->repository->search('/'));
    }

    #[Test]
    public function it_finds_many_in_the_requested_order_and_skips_unknown_ids(): void
    {
        $this->assertSame([4, 1, 3], $this->ids($this->repository->findMany([4, 99, 1, 3])));
    }

    #[Test]
    public function it_finds_one_product_by_id(): void
    {
        $this->assertSame('Gold Bracelet', $this->repository->find(3)?->title);
        $this->assertNull($this->repository->find(99));
    }

    #[Test]
    public function it_lists_each_category_once_in_first_seen_order(): void
    {
        $slugs = array_map(fn ($category) => $category->slug, $this->repository->categories());

        $this->assertSame(['mens-clothing', 'womens-clothing', 'jewelery', 'electronics'], $slugs);
    }

    #[Test]
    public function it_filters_products_by_category_slug(): void
    {
        $this->assertSame([2, 5], $this->ids($this->repository->inCategory('womens-clothing')));
        $this->assertSame([], $this->repository->inCategory('unknown'));
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
