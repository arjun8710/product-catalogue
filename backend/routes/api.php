<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EnquiryController;
use App\Http\Controllers\Api\FeaturedProductController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/products/featured', FeaturedProductController::class);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->where('slug', '[a-z0-9-]+');

    Route::post('/enquiries', EnquiryController::class)->middleware('throttle:enquiries');
});
