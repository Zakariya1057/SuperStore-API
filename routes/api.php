<?php

use App\Http\Middleware\OptionalAuthentication;
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

Route::any('/', function () {
    return response()->json(['data' => ['status' => 'success']]);
})->name('api.home');

Route::prefix('user')->group(function () {
    Route::post('/register', 'API\UserController@register')->name('user.register');
    Route::post('/login', 'API\UserController@login')->name('user.login');
    Route::post('/update', 'API\UserController@update')->name('user.update')->middleware('auth:sanctum');

    Route::post('/logout', 'API\UserController@logout')->name('user.logout')->middleware('auth:sanctum');
    Route::post('/delete', 'API\UserController@delete')->name('user.delete')->middleware('auth:sanctum');

    Route::prefix('reset')->group(function () {
        Route::post('/send-code', 'API\ResetPasswordController@send_code')->name('user.reset.send_code');
        Route::post('/validate-code', 'API\ResetPasswordController@validate_code')->name('user.reset.validate_code');
        Route::post('/password', 'API\ResetPasswordController@new_password')->name('user.reset.new_password');
    });
});

Route::prefix('image')->group(function () {
    Route::get('/{type}/{name}', 'API\ImageController@show')->name('image.show');
});


Route::middleware(OptionalAuthentication::class)->group(function () { # Optional Authentication
    Route::get('/home', 'API\HomeController@show')->name('home.show');

    Route::prefix('store')->group(function () {
        Route::get('/{store_id}', 'API\StoreController@show')->name('store.show');
    });
    
    Route::prefix('grocery')->group(function () {
        Route::get('categories/{store_type_id}', 'API\CategoryController@categories')->name('grocery.categories');
        Route::get('products/{parent_cateogy_id}', 'API\CategoryController@products')->name('grocery.products');
    });

    Route::get('promotion/{promotion_id}', 'API\PromotionController@index')->name('promotion.index');
    
    Route::prefix('search')->group(function () {
        Route::post('/suggestions', 'API\SearchController@suggestions')->name('search.suggestions');

        Route::prefix('results')->group(function () {
            Route::get('stores/{store_type_id}', 'API\SearchController@store_results')->name('search.store_results');
            Route::post('product', 'API\SearchController@product_results')->name('search.product_results');
            Route::post('promotion', 'API\SearchController@promotion_results')->name('search.promotion_results');
        });
    });

});

Route::middleware('auth:sanctum')->group(function () { # Authenticate Users
    
    Route::get('/favourites', 'API\FavouriteController@index')->name('favourite.index');

    Route::prefix('product/{product}')->group(function () {
        Route::get('/', 'API\ProductController@show')->withoutMiddleware('auth:sanctum')->middleware(OptionalAuthentication::class)->name('product.show');
    
        Route::post('/favourite', 'API\FavouriteController@update')->name('favourite.update');
        Route::post('/monitor', 'API\MonitoredController@update')->name('monitor.update');
    });
    
    Route::prefix('review/{product}')->group(function () {
        Route::get('/show', 'API\ReviewController@show')->name('review.show');
        Route::get('/', 'API\ReviewController@index')->withoutMiddleware('auth:sanctum')->middleware(OptionalAuthentication::class)->name('review.index');
        Route::post('/create', 'API\ReviewController@create')->name('review.create');
        Route::post('/delete', 'API\ReviewController@delete')->name('review.delete');
    });

    Route::prefix('list')->group(function () {
        Route::get('/', 'API\ListController@index')->name('list.index');
        Route::post('/create', 'API\ListController@create')->name('list.create');
        Route::post('/delete', 'API\ListController@delete')->name('list.delete');
        Route::post('/update', 'API\ListController@update')->name('list.update');
        Route::post('/restart', 'API\ListController@restart')->name('list.restart');
        
        Route::get('/{id}', 'API\ListController@show')->name('list.show');
        
    
        Route::prefix('{id}/item')->group(function () {
            Route::post('/create', 'API\ListItemController@create')->name('list_item.create');
            Route::post('/update', 'API\ListItemController@update')->name('list_item.update');
            Route::post('/delete', 'API\ListItemController@delete')->name('list_item.delete');
        });
    });

});

