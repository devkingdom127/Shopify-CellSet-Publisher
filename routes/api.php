<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth.shopify')->group(function() {
    Route::get('/settings', [Settings::class, 'index']);
    Route::post('/settings', [Settings::class, 'save']);
    Route::get('/themes', [Themes::class, 'index']);
    Route::post('/themes', [Themes::class, 'save']);
});

Route::get('/products/{product_handle}', [Products::class, 'index']);

Route::post('/GetHandles', [App\Http\Controllers\ProductController::class, 'validateHandle'])->name('GetHandles');
Route::post('/GetProductData', [App\Http\Controllers\ProductController::class, 'getProductData'])->name('GetProductData');
