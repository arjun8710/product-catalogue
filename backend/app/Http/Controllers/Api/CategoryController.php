<?php

namespace App\Http\Controllers\Api;

use App\Catalogue\CatalogueService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryListingResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function index(CatalogueService $catalogue): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalogue->categories());
    }

    public function show(string $slug, CatalogueService $catalogue): CategoryListingResource
    {
        $listing = $catalogue->categoryListing($slug) ?? abort(404, 'Category not found.');

        return new CategoryListingResource($listing);
    }
}
