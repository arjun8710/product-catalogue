<?php

namespace App\Providers;

use App\Catalogue\CatalogueService;
use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Sources\FakeStoreProductRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class CatalogueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, function (Application $app) {
            $source = config('catalogue.source');

            return match ($source) {
                'fakestore' => new FakeStoreProductRepository(
                    http: $app->make(Http::class),
                    cache: $app->make('cache.store'),
                    logger: $app->make('log'),
                    baseUrl: config('catalogue.sources.fakestore.base_url'),
                    timeoutSeconds: config('catalogue.sources.fakestore.timeout_seconds'),
                    cacheTtlSeconds: config('catalogue.sources.fakestore.cache_ttl_seconds'),
                    currency: config('catalogue.currency'),
                ),
                default => throw new InvalidArgumentException("Unknown catalogue source [{$source}]."),
            };
        });

        $this->app->bind(CatalogueService::class, fn (Application $app) => new CatalogueService(
            products: $app->make(ProductRepository::class),
            logger: $app->make('log'),
            featuredProductIds: config('catalogue.featured_product_ids'),
            categoryTopRatedCount: config('catalogue.category_top_rated_count'),
        ));
    }
}
