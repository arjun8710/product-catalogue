<?php

namespace Tests\Unit\Catalogue;

use App\Catalogue\Exceptions\ProductSourceUnavailable;
use App\Catalogue\Sources\FakeStoreProductRepository;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Tests\TestCase;

final class FakeStoreProductRepositoryTest extends TestCase
{
    #[Test]
    public function it_maps_fake_store_products_into_our_product_model(): void
    {
        $this->fakeFakeStore();

        $product = $this->repository()->find(2);

        $this->assertNotNull($product);
        $this->assertSame(2, $product->id);
        // Fake Store's title has a trailing space; we trim it.
        $this->assertSame('Mens Casual Premium Slim Fit T-Shirts', $product->title);
        $this->assertSame(2230, $product->price->minorUnits);
        $this->assertSame('USD', $product->price->currency);
        $this->assertSame('mens-clothing', $product->category->slug);
        $this->assertSame("men's clothing", $product->category->name);
        $this->assertSame(4.1, $product->rating->average);
        $this->assertSame(259, $product->rating->count);
        $this->assertStringStartsWith('https://', $product->imageUrl);
    }

    #[Test]
    public function it_caches_the_upstream_payload_between_calls(): void
    {
        $this->fakeFakeStore();
        $repository = $this->repository();

        $repository->categories();
        $repository->find(1);
        $repository->search('jacket');

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_skips_malformed_products_instead_of_failing_the_whole_catalogue(): void
    {
        Http::fake(['fakestore.test/products' => Http::response([
            ['id' => 1, 'title' => 'Valid', 'price' => 9.99, 'category' => 'electronics'],
            ['id' => 'two', 'title' => 'Bad id', 'price' => 1, 'category' => 'electronics'],
            ['id' => 3, 'title' => 'Missing price', 'category' => 'electronics'],
            'not even an object',
        ])]);

        $products = $this->repository()->inCategory('electronics');

        $this->assertCount(1, $products);
        $this->assertSame('Valid', $products[0]->title);
        $this->assertSame('', $products[0]->description);
        $this->assertSame(0.0, $products[0]->rating->average);
    }

    #[Test]
    public function it_reports_the_source_as_unavailable_when_fake_store_errors(): void
    {
        Http::fake(['fakestore.test/products' => Http::response('Server error', 500)]);

        $this->expectException(ProductSourceUnavailable::class);

        $this->repository()->categories();
    }

    #[Test]
    public function it_reports_the_source_as_unavailable_when_fake_store_cannot_be_reached(): void
    {
        Http::fake(['fakestore.test/products' => Http::failedConnection()]);

        $this->expectException(ProductSourceUnavailable::class);

        $this->repository()->categories();
    }

    #[Test]
    public function it_reports_the_source_as_unavailable_when_the_payload_is_not_a_list(): void
    {
        Http::fake(['fakestore.test/products' => Http::response(['error' => 'unexpected'])]);

        $this->expectException(ProductSourceUnavailable::class);

        $this->repository()->categories();
    }

    #[Test]
    public function it_does_not_cache_a_failed_fetch(): void
    {
        Http::fakeSequence('fakestore.test/products')
            ->push('Server error', 500)
            ->push('Server error', 500)
            ->push($this->fakeStoreProducts());
        $repository = $this->repository();

        try {
            $repository->categories();
            $this->fail('Expected the first call to fail.');
        } catch (ProductSourceUnavailable) {
            // Expected: both retry attempts failed.
        }

        $this->assertCount(4, $repository->categories());
    }

    private function repository(): FakeStoreProductRepository
    {
        return new FakeStoreProductRepository(
            http: $this->app->make(HttpFactory::class),
            cache: new CacheRepository(new ArrayStore),
            logger: new NullLogger,
            baseUrl: 'https://fakestore.test',
            timeoutSeconds: 1,
            cacheTtlSeconds: 60,
            currency: 'USD',
        );
    }
}
