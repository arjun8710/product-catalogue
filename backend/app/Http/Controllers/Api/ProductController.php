<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\CatalogueService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ProductController extends Controller
{
    public function search(Request $request, CatalogueService $catalogue): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        return ProductResource::collection($catalogue->search($validated['q']));
    }

    public function show(int $id, CatalogueService $catalogue): ProductResource
    {
        $product = $catalogue->product($id) ?? abort(404, 'Product not found.');

        return new ProductResource($product);
    }
}
