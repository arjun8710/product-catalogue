<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercises the API end to end, with only the Fake Store HTTP call faked.
 * Each test arranges its own upstream response so failure cases stay isolated.
 */
final class CatalogueApiTest extends TestCase
{
    #[Test]
    public function home_page_shows_the_five_chosen_top_rated_products_in_order(): void
    {
        $this->fakeFakeStore();

        $response = $this->getJson('/api/products/featured')->assertOk();

        $this->assertSame([3, 5, 11, 12, 18], $this->ids($response, 'data'));
        $response->assertJsonStructure(['data' => [[
            'id', 'title', 'description', 'imageUrl',
            'price' => ['minorUnits', 'currency'],
            'category' => ['slug', 'name'],
            'rating' => ['average', 'count'],
        ]]]);
    }

    #[Test]
    public function it_lists_categories_with_url_safe_slugs(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['slug' => 'mens-clothing', 'name' => "men's clothing"],
                ['slug' => 'jewelery', 'name' => 'jewelery'],
                ['slug' => 'electronics', 'name' => 'electronics'],
                ['slug' => 'womens-clothing', 'name' => "women's clothing"],
            ]]);
    }

    #[Test]
    public function category_page_shows_top_rated_products_then_the_rest_of_the_category(): void
    {
        $this->fakeFakeStore();

        $response = $this->getJson('/api/categories/mens-clothing')->assertOk();

        $response->assertJsonPath('data.category.slug', 'mens-clothing');
        $this->assertSame([3, 2, 1], $this->ids($response, 'data.topRated'));
        $this->assertSame([4], $this->ids($response, 'data.others'));
    }

    #[Test]
    public function category_page_returns_404_for_an_unknown_category(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/categories/spaceships')
            ->assertNotFound()
            ->assertJsonPath('message', 'Category not found.');
    }

    #[Test]
    public function search_only_returns_products_matching_the_term(): void
    {
        $this->fakeFakeStore();

        $response = $this->getJson('/api/products/search?q=jacket')->assertOk();

        $this->assertSame([3, 15, 16, 17], $this->ids($response, 'data'));

        foreach ($response->json('data') as $product) {
            $this->assertMatchesRegularExpression('/jacket/i', $product['title'].' '.$product['description']);
        }
    }

    #[Test]
    public function search_returns_an_empty_list_when_nothing_matches(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/products/search?q=spaceship')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    #[Test]
    public function search_requires_a_term(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/products/search')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/products/search?q=')->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson('/api/products/search?q='.str_repeat('a', 101))->assertUnprocessable();
    }

    #[Test]
    public function product_page_returns_the_product_details(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/products/1')
            ->assertOk()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.title', 'Fjallraven - Foldsack No. 1 Backpack, Fits 15 Laptops')
            ->assertJsonPath('data.price', ['minorUnits' => 10995, 'currency' => 'USD']);
    }

    #[Test]
    public function product_page_returns_404_for_an_unknown_product(): void
    {
        $this->fakeFakeStore();

        $this->getJson('/api/products/999')->assertNotFound()->assertJsonPath('message', 'Product not found.');
        $this->getJson('/api/products/abc')->assertNotFound();
    }

    #[Test]
    public function it_returns_503_with_a_friendly_message_when_the_product_source_is_down(): void
    {
        Http::fake(['fakestore.test/products' => Http::response('Bad gateway', 502)]);

        $this->getJson('/api/products/featured')
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'The product catalogue is temporarily unavailable. Please try again shortly.');
    }

    /**
     * @return list<int>
     */
    private function ids(TestResponse $response, string $path): array
    {
        return array_column($response->json($path), 'id');
    }
}
