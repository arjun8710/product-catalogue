<?php

namespace App\Catalogue;

final readonly class Rating
{
    public function __construct(
        public float $average,
        public int $count,
    ) {}
}
