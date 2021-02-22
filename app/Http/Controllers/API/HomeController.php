<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GroceryService;
use App\Services\ListService;
use App\Services\MonitoringService;
use App\Services\PromotionService;
use App\Services\SanitizeService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class HomeController extends Controller {

    private $sanitize_service, $list_service, $monitoring_service, $store_service, $grocery_service, $promotion_service;

    function __construct(SanitizeService $sanitize_service, ListService $list_service, GroceryService $grocery_service, MonitoringService $monitoring_service, StoreService $store_service, PromotionService $promotion_service){
        $this->sanitize_service = $sanitize_service;
        $this->list_service = $list_service;
        $this->store_service = $store_service;
        $this->monitoring_service = $monitoring_service;
        $this->promotion_service = $promotion_service;
        $this->grocery_service = $grocery_service;
    }

    public function show(Request $request){
        $user = $request->user();

        if(!is_null($user)){
            $data['monitoring'] = $this->monitoring_service->monitoring_products($user->id);
            $data['lists'] = $this->list_service->lists_progress($user->id);
            $data['groceries'] = $this->list_service->list_items($user->id);
        } else {
            $data['monitoring'] = $data['lists'] = $data['groceries'] = [];
        }

        $cache_key = 'home_page';

        $retrieved_data = Redis::get($cache_key);
        if($retrieved_data){
            $retrieved_data = (array)json_decode( $retrieved_data );
            $data['featured'] = $retrieved_data['featured'];
            $data['stores'] = $retrieved_data['stores'];
            $data['categories'] = $retrieved_data['categories'];
            $data['promotions'] = $retrieved_data['promotions'];
        } else {

            $data['featured'] = $this->grocery_service->featured_items();
            $data['stores'] = $this->store_service->stores_by_type(1,false);
            $data['categories'] = $this->grocery_service->home_categories();
            $data['promotions'] = $this->promotion_service->store_promotions(1);
    
            Redis::set($cache_key, json_encode($data));
            Redis::expire($cache_key, 604800);
        }

        foreach($data as $key => $value){
            if($value == []){
                $data[$key] = null;
            }
        }

        return response()->json(['data' => $data]);
    }

}
