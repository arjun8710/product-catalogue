<?php

namespace App\Catalogue;

/**
 * A price held in minor units (e.g. cents) to avoid floating-point rounding.
 * Assumes a two-decimal currency, which holds for every currency we use today.
 */
final readonly class Money
{
    public function __construct(
        public int $minorUnits,
        public string $currency,
    ) {}

    public static function fromDecimal(float|int|string $amount, string $currency): self
    {
        return new self((int) round(((float) $amount) * 100), $currency);
    }

    public function format(): string
    {
        return number_format($this->minorUnits / 100, 2).' '.$this->currency;
    }
}
