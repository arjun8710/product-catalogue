<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Contracts\ProductRepository;
use App\Catalogue\Product;

/**
 * Answers catalogue queries over a list of products held in memory.
 *
 * Used directly in tests, and by sources whose upstream has no query API of
 * its own (Fake Store has no search endpoint, for example).
 */
final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<int, Product> */
    private array $byId = [];

    /**
     * @param  list<Product>  $products
     */
    public function __construct(private readonly array $products)
    {
        foreach ($products as $product) {
            $this->byId[$product->id] = $product;
        }
    }

    public function categories(): array
    {
        $categories = [];

        foreach ($this->products as $product) {
            $categories[$product->category->slug] ??= $product->category;
        }

        return array_values($categories);
    }

    public function inCategory(string $categorySlug): array
    {
        return array_values(array_filter(
            $this->products,
            fn (Product $product) => $product->category->slug === $categorySlug,
        ));
    }

    public function find(int $id): ?Product
    {
        return $this->byId[$id] ?? null;
    }

    public function findMany(array $ids): array
    {
        $found = [];

        foreach ($ids as $id) {
            if (isset($this->byId[$id])) {
                $found[] = $this->byId[$id];
            }
        }

        return $found;
    }

    public function search(string $term): array
    {
        $words = preg_split('/\s+/u', self::normalise($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return [];
        }

        return array_values(array_filter($this->products, function (Product $product) use ($words) {
            $haystack = self::normalise(implode(' ', [
                $product->title,
                $product->description,
                $product->category->name,
            ]));

            foreach ($words as $word) {
                // Match from the start of a word: "jack" finds "jacket",
                // but "mens" must not match inside "womens".
                $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'/u';

                if (preg_match($pattern, $haystack) !== 1) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Lower-case and drop apostrophes so "womens", "women's" and "Women’s" all match.
     */
    private static function normalise(string $text): string
    {
        return str_replace(["'", '’', '‘'], '', mb_strtolower(trim($text)));
    }
}
