<?php

namespace App\Http\Resources;

use App\Catalogue\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Category $resource
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
        ];
    }
}
