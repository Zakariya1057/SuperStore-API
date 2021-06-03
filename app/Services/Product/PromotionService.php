<?php

namespace App\Services\Product;

use App\Models\FeaturedItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Sanitize\SanitizeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Redis;

class PromotionService {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function all(int $store_type_id, int $region_id){
        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);
        return Promotion::where([ ['store_type_id', $store_type_id], ['region_id', $region_id]])
        ->whereNotNull('title')
        ->limit(200)
        ->groupBy('title')
        ->orderBy('title', 'ASC')
        ->pluck('title');
    }

    public function group(int $region_id, int $store_type_id, string $title){

        $region_id = $this->sanitize_service->sanitizeField($region_id);

        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);
        $title = $this->sanitize_service->sanitizeField($title);

        $cache_key = "promotion_group_{$store_type_id}_$title";
        $promotions = Redis::get($cache_key);

        if(is_null($promotions)){
            $products = Product::where([ ['promotions.store_type_id', $store_type_id], ['title', $title] ])
            ->select(
                'products.*',
    
                'promotions.store_type_id as promotion_store_type_id',
                'promotions.name as promotion_name',
                'promotions.title as promotion_title',
                'promotions.quantity as promotion_quantity',
                'promotions.price as promotion_price',
                'promotions.for_quantity as promotion_for_quantity',
        
                'product_prices.price', 
                'product_prices.old_price',
                'product_prices.is_on_sale', 
                'product_prices.sale_ends_at', 
                'product_prices.promotion_id', 
                'product_prices.region_id',

                'promotions.minimum as promotion_minimum',
                'promotions.maximum as promotion_maximum',
                
                'promotions.expires as promotion_expires',
                'promotions.starts_at as promotion_starts_at',
                'promotions.ends_at as promotion_ends_at',
        
                'promotions.enabled as promotion_enabled',
            )
            ->join('product_prices','product_prices.product_id','products.id')
            ->join('promotions','promotions.id', 'promotion_id')
            ->where('product_prices.region_id', $region_id)
            ->groupBy('product_prices.product_id')
            ->get();
    
            $promotions = [];
    
            foreach($products as $product){
                $promotion_id = $product->promotion_id;
    
                $this->set_product_promotion($product);
    
                if(key_exists($promotion_id, $promotions)){
                    $promotion_products = $promotions[$promotion_id]['products'];
                    $promotion_products[] = $product;
    
                    $promotions[$promotion_id]['products'] = $promotion_products;
                } else {
                    $promotion = clone $product->promotion;
                    $promotion->products = [$product];
    
                    $promotions[$promotion_id] = $promotion;
                }
               
            }
    
            $promotions = array_values($promotions);

            Redis::set($cache_key, json_encode($promotions));
            Redis::expire($cache_key, 604800);
        } else {
            $promotions = json_decode($promotions);
        }

        return $promotions;
    }

    public function get(int $region_id, int $promotion_id){

        $region_id = $this->sanitize_service->sanitizeField($region_id);

        $promotion_id = $this->sanitize_service->sanitizeField($promotion_id);

        $promotion = Promotion::where([ ['id', $promotion_id], ['region_id', $region_id] ])->first();

        if(!is_null($promotion)){
            $promotion->products;
        } else {
            throw new Exception('Promotion not found.', 404);
        }

        return $promotion;
    }

    public function featured(int $region_id, int $store_type_id){
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
        ->where([ ['featured_items.region_id', $region_id], ['featured_items.store_type_id', $store_type_id], ['type', 'promotions'], ['promotions.store_type_id', $store_type_id] ])
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