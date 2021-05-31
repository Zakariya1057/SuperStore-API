<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Services\Category\CategoryService;
use App\Services\GroceryList\GroceryListService;
use App\Services\User\LocationService;
use App\Services\Logger\LoggerService;
use App\Services\Product\MonitoringService;
use App\Services\Product\ProductService;
use App\Services\Product\PromotionService;
use App\Services\Sanitize\SanitizeService;
use App\Services\Store\StoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller {

    private 
        $sanitize_service, 
        $list_service, 
        $monitoring_service, 
        $store_service, 
        $category_service, 
        $promotion_service, 
        $product_service, 
        $logger_service,
        $location_service;

    function __construct(
        SanitizeService $sanitize_service, 
        GroceryListService $list_service, 
        CategoryService $category_service, 
        MonitoringService $monitoring_service, 
        StoreService $store_service, 
        PromotionService $promotion_service, 
        ProductService $product_service,
        LoggerService $logger_service,
        LocationService $location_service
        ){

        $this->sanitize_service = $sanitize_service;
        $this->list_service = $list_service;
        $this->store_service = $store_service;
        $this->monitoring_service = $monitoring_service;
        $this->promotion_service = $promotion_service;
        $this->category_service = $category_service;
        $this->product_service = $product_service;
        $this->logger_service = $logger_service;
        $this->location_service = $location_service;
    }

    public function show(HomeRequest $request){
        $user_id = Auth::id();
        
        $validated_data = $request->validated();

        $data = $this->sanitize_service->sanitizeAllFields($validated_data['data']);
        $store_type_id = $data['store_type_id'];

        $this->logger_service->log('home.show', $request);
        
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;

        $region_id = $data['region_id'];

        $data['stores'] = $this->store_service->stores_by_type($store_type_id, false, $latitude, $longitude);

        if(Auth::check()){

            if(!is_null($latitude) && !is_null($longitude)){
                $this->location_service->update_location($user_id, $latitude, $longitude);
            }
            
            $data['monitoring'] = $this->monitoring_service->all($user_id, $region_id, $store_type_id);
            $data['lists'] = $this->list_service->lists_progress($user_id, $store_type_id);
            $data['groceries'] = $this->list_service->recent_items($user_id, $region_id, $store_type_id);
        } else {
            $data['monitoring'] = $data['lists'] = $data['groceries'] = [];
        }

        $cache_key = "home_page_{$store_type_id}_{$region_id}";

        $retrieved_data = Redis::get($cache_key);
        
        if($retrieved_data){
            $retrieved_data = (array)json_decode( $retrieved_data );
            $data['featured'] = $retrieved_data['featured'];
            $data['categories'] = $retrieved_data['categories'];
            $data['promotions'] = $retrieved_data['promotions'];
        } else {
            $data['featured'] = $this->product_service->featured($region_id, $store_type_id);
            $data['categories'] = $this->category_service->featured($region_id, $store_type_id);
            $data['promotions'] = $this->promotion_service->featured($store_type_id);
    
            Redis::set($cache_key, json_encode($data));
            Redis::expire($cache_key, 604800);
        }

        return response()->json(['data' => $data]);
    }

}
