<?php

namespace Tests\Support;

use App\Catalogue\Category;
use App\Catalogue\Money;
use App\Catalogue\Product;
use App\Catalogue\Rating;

final class Products
{
    public static function make(
        int $id,
        string $title = 'Test product',
        string $description = '',
        string $category = 'electronics',
        float $rating = 3.0,
        int $ratingCount = 10,
        int $priceMinorUnits = 1000,
    ): Product {
        return new Product(
            id: $id,
            title: $title,
            description: $description,
            price: new Money($priceMinorUnits, 'USD'),
            category: Category::fromName($category),
            imageUrl: "https://images.test/{$id}.png",
            rating: new Rating($rating, $ratingCount),
        );
    }
}
