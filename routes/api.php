<?php

use App\Http\Controllers\Api\ProductSpecificationController;
use App\Http\Controllers\AddressController;
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

// Test route for debugging
Route::get('test', function () {
    return response()->json(['message' => 'API is working', 'time' => now()]);
});

// Address API Routes
Route::prefix('addresses')->group(function () {
    // Cambodia address data endpoints (public)
    Route::get('provinces', [AddressController::class, 'getProvinces'])
        ->name('api.addresses.provinces');

    Route::get('districts', [AddressController::class, 'getDistricts'])
        ->name('api.addresses.districts');

    Route::get('communes', [AddressController::class, 'getCommunes'])
        ->name('api.addresses.communes');

    Route::get('postal-code', [AddressController::class, 'getPostalCode'])
        ->name('api.addresses.postal-code');

    Route::get('search', [AddressController::class, 'searchAreas'])
        ->name('api.addresses.search');

    // User address management (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [AddressController::class, 'index'])
            ->name('api.addresses.index');

        Route::post('/', [AddressController::class, 'store'])
            ->name('api.addresses.store');

        Route::get('default', [AddressController::class, 'getDefault'])
            ->name('api.addresses.default');

        Route::get('{address}', [AddressController::class, 'show'])
            ->name('api.addresses.show');

        Route::put('{address}', [AddressController::class, 'update'])
            ->name('api.addresses.update');

        Route::delete('{address}', [AddressController::class, 'destroy'])
            ->name('api.addresses.destroy');

        Route::post('{address}/set-default', [AddressController::class, 'setDefault'])
            ->name('api.addresses.set-default');
    });
});
