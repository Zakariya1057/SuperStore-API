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

Route::prefix('product/{product}')->group(function () {
    Route::get('/', 'ProductController@show')->name('product.show');
    Route::get('/reviews', 'ReviewController@index')->name('review.index');
    Route::post('/favourite', 'FavouriteProductsController@update')->name('favourite.update');
});

Route::get('/favourites', 'FavouriteProductsController@index')->name('favourite.index');

Route::prefix('image')->group(function () {
    Route::get('/{type}/{name}', 'ImageController@show')->name('image.show');
});

Route::prefix('list')->group(function () {
    Route::get('/', 'ListViewController@index')->name('list.index');
    Route::post('/create', 'ListViewController@create')->name('list.create');
    Route::post('/delete', 'ListViewController@delete')->name('list.delete');
    Route::post('/update', 'ListViewController@update')->name('list.update');
    
    Route::get('/{list}', 'ListViewController@show')->name('list.show');
    Route::post('{list}/restart', 'ListViewController@restart')->name('list.restart');

    Route::prefix('{list}/item')->group(function () {
        Route::post('/create', 'GroceryListViewController@create')->name('list_item.create');
        Route::post('/update', 'GroceryListViewController@update')->name('list_item.update');
        Route::post('/delete', 'GroceryListViewController@delete')->name('list_item.delete');
    });
});

Route::get('promotion/{promotion_id}', 'PromotionController@index')->name('promotion.index');