<?php

namespace App\Enquiries;

use App\Catalogue\Product;

final readonly class Enquiry
{
    public function __construct(
        public Product $product,
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $message,
    ) {}
}
