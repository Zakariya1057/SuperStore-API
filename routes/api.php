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

Route::prefix('store')->group(function () {
    Route::get('/{store_id}', 'StoreController@show')->name('store.show');
});

Route::prefix('grocery')->group(function () {
    Route::get('{store_type_id}', 'GroceryController@categories')->name('grocery.categories');
    Route::get('products/{store_type_id}', 'GroceryController@products')->name('grocery.products');
});

Route::prefix('product')->group(function () {
    Route::get('/{product}', 'ProductController@show')->name('product.show');
    Route::get('/{product}/reviews', 'ReviewController@index')->name('review.index');
});


Route::prefix('image')->group(function () {
    Route::get('/{type}/{name}', 'ImageController@show')->name('image.show');
});