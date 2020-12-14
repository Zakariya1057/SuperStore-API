<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\GroceryListTrait;
use App\Traits\GroceryTrait;
use App\Traits\MonitoringTrait;
use App\Traits\PromotionTrait;
use App\Traits\StoreTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class HomeController extends Controller {
    use StoreTrait;
    use MonitoringTrait;
    use PromotionTrait;
    use GroceryListTrait;
    use GroceryTrait;

    public function show(Request $request){
        $user = $request->user();

        $data['monitoring'] = $this->monitoring_products($user->id);
        $data['lists'] = $this->lists_progress($user->id);
        $data['groceries'] = $this->grocery_items($user->id);

        $cache_key = 'home_page';

        $retrieved_data = Redis::get($cache_key);
        if($retrieved_data){
            $retrieved_data = (array)json_decode( $retrieved_data );
            $data['featured'] = $retrieved_data['featured'];
            $data['stores'] = $retrieved_data['stores'];
            $data['categories'] = $retrieved_data['categories'];
            $data['promotions'] = $retrieved_data['promotions'];
        } else {

            $data['featured'] = $this->featured_items();
            $data['stores'] = $this->stores_by_type(1,false);
            $data['categories'] = $this->home_categories();
            $data['promotions'] = $this->store_promotions(1);
    
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
