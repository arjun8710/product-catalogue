<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\CatalogueService;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class FeaturedProductController extends Controller
{
    public function __invoke(CatalogueService $catalogue): AnonymousResourceCollection
    {
        return ProductResource::collection($catalogue->featured());
    }
}
