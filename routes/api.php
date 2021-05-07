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
    Route::post('/location', 'API\UserController@location')->name('user.location')->middleware('auth:sanctum');
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
    Route::post('/home', 'API\HomeController@show')->name('home.show');

    Route::prefix('store')->group(function () {
        Route::get('/{store_id}', 'API\StoreController@show')->name('store.show');
    });
    
    Route::prefix('groceries')->group(function () {
        Route::get('grand_parent_categories/{store_type_id}', 'API\CategoryController@grand_parent_categories')->name('grocery.grand_parent_categories');
        Route::get('child_categories/{parent_categories}', 'API\CategoryController@child_categories')->name('grocery.child_categories');
        Route::post('category_products/{child_category_id}', 'API\CategoryController@category_products')->name('grocery.category_products');
    });

    Route::prefix('promotion')->group(function () {
        Route::get('all/{store_type_id}', 'API\PromotionController@all')->name('promotion.all');
        Route::get('{promotion_id}', 'API\PromotionController@show')->name('promotion.show');
    });

    // Route::get('promotion/{promotion_id}', 'API\PromotionController@show')->name('promotion.show');
    
    Route::prefix('search')->group(function () {
        Route::post('/suggestions', 'API\SearchController@suggestions')->name('search.suggestions');

        Route::prefix('results')->group(function () {
            Route::post('stores', 'API\SearchController@store_results')->name('search.store_results');
            Route::post('product', 'API\SearchController@product_results')->name('search.product_results');
            Route::post('promotion', 'API\SearchController@promotion_results')->name('search.promotion_results');
        });
    });

    Route::post('/feedback/create', 'API\FeedbackController@create')->name('feedback.create');
    Route::post('/report/issue', 'API\ReportController@create')->name('report.create');
});

Route::middleware('auth:sanctum')->group(function () { # Authenticate Users
    
    Route::get('/favourites', 'API\FavouriteController@index')->name('favourite.index');

    Route::prefix('monitor')->group(function () {
        Route::post('products', 'API\MonitoredController@index')->name('monitor.index');
    });

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
        Route::get('stores/{store_type_id}', 'API\ListController@index')->name('list.index');
        Route::post('/create', 'API\ListController@create')->name('list.create');
        Route::post('/delete', 'API\ListController@delete')->name('list.delete');
        Route::post('/update', 'API\ListController@update')->name('list.update');
        Route::post('/restart', 'API\ListController@restart')->name('list.restart');
        
        Route::get('/{id}', 'API\ListController@show')->name('list.show');
        
        Route::prefix('offline')->group(function () {
            Route::post('/deleted', 'API\ListController@offline_delete')->name('list.offline.delete');
            Route::post('/edited', 'API\ListController@offline_edited')->name('list.offline.edited');
        });
    
        Route::prefix('{id}/item')->group(function () {
            Route::post('/create', 'API\ListItemController@create')->name('list_item.create');
            Route::post('/update', 'API\ListItemController@update')->name('list_item.update');
            Route::post('/delete', 'API\ListItemController@delete')->name('list_item.delete');
        });
    });

});

