<?php

return [

    /*
    | Which product data source backs the catalogue. Each source is an adapter
    | implementing App\Catalogue\Contracts\ProductRepository; see
    | App\Providers\CatalogueServiceProvider for the binding.
    */
    'source' => env('CATALOGUE_SOURCE', 'fakestore'),

    'sources' => [
        'fakestore' => [
            'base_url' => env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com'),
            'timeout_seconds' => (int) env('FAKESTORE_TIMEOUT_SECONDS', 3),
            'cache_ttl_seconds' => (int) env('FAKESTORE_CACHE_TTL_SECONDS', 600),
        ],
    ],

    /*
    | The products the business has chosen as "top rated", shown on the home
    | page in this order. Comma-separated product IDs.
    */
    'featured_product_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('FEATURED_PRODUCT_IDS', '3,5,11,12,18')),
    ))),

    // How many of the highest-rated products to highlight on a category page.
    'category_top_rated_count' => (int) env('CATEGORY_TOP_RATED_COUNT', 3),

    // ISO 4217 code. Fake Store prices carry no currency, so we declare one.
    'currency' => env('CATALOGUE_CURRENCY', 'USD'),

    // Public URL of the web storefront, used for product links in emails.
    'storefront_url' => rtrim((string) env('STOREFRONT_URL', 'http://localhost:8080'), '/'),

    'sales_inbox' => [
        'address' => env('SALES_INBOX_ADDRESS', 'sales@example.com'),
        'name' => env('SALES_INBOX_NAME', 'Sales'),
    ],

];
