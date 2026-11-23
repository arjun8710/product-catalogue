<?php

namespace App\Http\Resources;

use App\Catalogue\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public API shape of a product. Kept separate from the domain model so the
 * API contract with clients does not change when a data source does.
 *
 * @property Product $resource
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        return [
            'id' => $product->id,
            'title' => $product->title,
            'description' => $product->description,
            'price' => [
                'minorUnits' => $product->price->minorUnits,
                'currency' => $product->price->currency,
            ],
            'category' => new CategoryResource($product->category),
            'imageUrl' => $product->imageUrl,
            'rating' => [
                'average' => $product->rating->average,
                'count' => $product->rating->count,
            ],
        ];
    }
}
