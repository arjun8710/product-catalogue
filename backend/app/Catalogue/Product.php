<?php

namespace App\Catalogue;

/**
 * Our own product model. Data sources map their payloads into this shape so
 * nothing outside a source adapter depends on a provider's field names.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public Money $price,
        public Category $category,
        public string $imageUrl,
        public Rating $rating,
    ) {}
}
