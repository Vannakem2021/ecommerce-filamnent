<?php

use App\Http\Controllers\Api\ProductSpecificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Product Specifications API
Route::prefix('products')->group(function () {
    Route::get('{product}/specifications', [ProductSpecificationController::class, 'index'])
        ->name('api.products.specifications');
    
    Route::get('specifications/filterable', [ProductSpecificationController::class, 'filterable'])
        ->name('api.specifications.filterable');
});
