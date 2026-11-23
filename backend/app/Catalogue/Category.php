<?php

namespace App\Catalogue;

use Illuminate\Support\Str;

final readonly class Category
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}

    public static function fromName(string $name): self
    {
        return new self(Str::slug($name), $name);
    }
}
