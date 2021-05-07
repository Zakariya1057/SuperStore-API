<?php

namespace App\Services;

use App\Models\FeaturedItem;
use App\Models\Promotion;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Redis;

class PromotionService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function all($store_type_id){
        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);


        $cache_key = 'all_promotions_' . $store_type_id;
        $promotions = Redis::get($cache_key);

        if(is_null($promotions)){
            $promotions = Promotion::where('store_type_id', $store_type_id)->orderBy('id', 'DESC')->limit(100)->get();
            
            Redis::set($cache_key, json_encode($promotions));

            Redis::expire($cache_key, 604800);
        } else {
            $promotions = json_decode($promotions);
        }

        return $promotions;
    }

    public function get($promotion_id){

        $promotion_id = $this->sanitize_service->sanitizeField($promotion_id);

        $promotion = Promotion::where('id', $promotion_id)->first();

        if(!is_null($promotion)){
            $promotion->products;
        } else {
            throw new Exception('Promotion not found.', 404);
        }

        return $promotion;
    }

    public function featured($store_type_id){
        $promotion = new Promotion();
        
        $promotions = [];

        $featured_promotions = FeaturedItem::select(
            'promotions.id as promotion_id',
            'promotions.title as promotion_title',
            'promotions.name as promotion_name',
            'promotions.quantity as promotion_quantity',
            'promotions.price as promotion_price',
            'promotions.for_quantity as promotion_for_quantity',

            'promotions.store_type_id as promotion_store_type_id',

            'promotions.minimum as promotion_minimum',
            'promotions.maximum as promotion_maximum',
            
            'promotions.expires as promotion_expires',
            'promotions.starts_at as promotion_starts_at',
            'promotions.ends_at as promotion_ends_at',

            'promotions.enabled as promotion_enabled',
        )
        ->where([ ['featured_items.store_type_id', $store_type_id], ['type', 'promotions'], ['promotions.store_type_id', $store_type_id] ])
        ->join('promotions','promotions.id','featured_id')
        ->groupBy('featured_id')
        ->withCasts($promotion->casts)->limit(10)
        ->get();


        foreach($featured_promotions as $promotion){
            $this->set_product_promotion($promotion);
            if(!is_null($promotion->promotion)){
                $promotions[] = $promotion->promotion;
            }
        }

        return $promotions;
    }

    public function set_product_promotion($item){
        $promotion = new Promotion();

        $promotions_fields = [
            'id',
            'url',

            'title',
            'name',
    
            'quantity',
            'price',
            'for_quantity',

            'minimum',
            'maximum',
    
            'store_type_id',
            
            'expires',
            'starts_at',
            'ends_at',

            'enabled'
        ];

        foreach($promotions_fields as $field){
            $item_field = 'promotion_' . $field;

            $promotion->{$field} = $item->{$item_field} ?? null;
            unset($item->{$item_field});
        }

        if(is_null($promotion->id) || !$promotion->enabled){
            $item->promotion = null;
        } else {
            $item->promotion = $promotion;

            // If promotion expired, don't return it
            if(!is_null($promotion->ends_at)){
                // Convert two dates
                if(Carbon::now()->diffInDays($promotion->ends_at) < 0){
                    $item->promotion = null;
                }
            }
        }

    }
}

?>