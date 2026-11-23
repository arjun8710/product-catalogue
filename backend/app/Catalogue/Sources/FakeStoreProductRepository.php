<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Category;
use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Exceptions\ProductSourceUnavailable;
use App\Catalogue\Money;
use App\Catalogue\Product;
use App\Catalogue\Rating;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\HttpClientException;
use Psr\Log\LoggerInterface;

/**
 * Product data from https://fakestoreapi.com.
 *
 * The whole catalogue is about 20 products, so we fetch it in one call, cache the
 * raw payload, and answer every query in memory. A larger source would push
 * filtering and search down to the provider instead.
 */
final class FakeStoreProductRepository implements ProductRepository
{
    private const CACHE_KEY = 'catalogue:fakestore:products';

    public function __construct(
        private readonly Http $http,
        private readonly Cache $cache,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly int $cacheTtlSeconds,
        private readonly string $currency,
    ) {}

    public function categories(): array
    {
        return $this->catalogue()->categories();
    }

    public function inCategory(string $categorySlug): array
    {
        return $this->catalogue()->inCategory($categorySlug);
    }

    public function find(int $id): ?Product
    {
        return $this->catalogue()->find($id);
    }

    public function findMany(array $ids): array
    {
        return $this->catalogue()->findMany($ids);
    }

    public function search(string $term): array
    {
        return $this->catalogue()->search($term);
    }

    private function catalogue(): InMemoryProductRepository
    {
        // Cache the raw payload rather than mapped objects so cached entries
        // survive changes to our own Product class between deploys.
        $payload = $this->cache->remember(self::CACHE_KEY, $this->cacheTtlSeconds, fn () => $this->fetchProducts());

        return new InMemoryProductRepository($this->mapProducts($payload));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchProducts(): array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->connectTimeout($this->timeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->retry(2, 200, throw: false)
                ->get('/products');
        } catch (HttpClientException $e) {
            throw ProductSourceUnavailable::because('could not connect to Fake Store', $e);
        }

        if ($response->failed()) {
            throw ProductSourceUnavailable::because("Fake Store responded with HTTP {$response->status()}");
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_is_list($payload)) {
            throw ProductSourceUnavailable::because('Fake Store returned an unexpected payload');
        }

        return $payload;
    }

    /**
     * @param  list<mixed>  $payload
     * @return list<Product>
     */
    private function mapProducts(array $payload): array
    {
        $products = [];

        foreach ($payload as $item) {
            $product = is_array($item) ? $this->mapProduct($item) : null;

            if ($product === null) {
                $this->logger->warning('Skipping malformed Fake Store product', ['item' => $item]);

                continue;
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapProduct(array $item): ?Product
    {
        $hasRequiredFields = is_int($item['id'] ?? null)
            && is_string($item['title'] ?? null)
            && is_numeric($item['price'] ?? null)
            && is_string($item['category'] ?? null);

        if (! $hasRequiredFields) {
            return null;
        }

        $rating = is_array($item['rating'] ?? null) ? $item['rating'] : [];

        return new Product(
            id: $item['id'],
            title: trim($item['title']),
            description: is_string($item['description'] ?? null) ? trim($item['description']) : '',
            price: Money::fromDecimal($item['price'], $this->currency),
            category: Category::fromName($item['category']),
            imageUrl: is_string($item['image'] ?? null) ? $item['image'] : '',
            rating: new Rating(
                average: (float) ($rating['rate'] ?? 0),
                count: (int) ($rating['count'] ?? 0),
            ),
        );
    }
}
