<?php

namespace App\Catalogue\Exceptions;

use RuntimeException;
use Throwable;

final class ProductSourceUnavailable extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self("Product source unavailable: {$reason}", previous: $previous);
    }
}
