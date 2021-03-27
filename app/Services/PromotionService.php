<?php

namespace App\Services;

use App\Models\FeaturedItem;
use App\Models\Promotion;
use Carbon\Carbon;

class PromotionService {

    public function featured($store_type_id){
        $promotion = new Promotion();
        
        $promotions = [];

        $featured_promotions = FeaturedItem::select(
            'promotions.id as promotion_id',
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
        )
        ->where([ ['featured_items.store_type_id', $store_type_id], ['type', 'promotions'], ['promotions.store_type_id', $store_type_id] ])
        ->join('promotions','promotions.id','featured_id')
        ->withCasts($promotion->casts)->limit(10)
        ->get();


        foreach($featured_promotions as $promotion){
            $this->set_product_promotion($promotion);
            $promotions[] = $promotion->promotion;
        }

        return $promotions;
    }

    public function set_product_promotion($item){
        $promotion = new Promotion();

        $promotions_fields = [
            'id',
            'url',
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
        ];

        foreach($promotions_fields as $field){
            $item_field = 'promotion_' . $field;

            $promotion->{$field} = $item->{$item_field};
            unset($item->{$item_field});
        }

        if(is_null($item->promotion_id)){
            $item->promotion = null;
        } else {
            $item->promotion = $promotion;

            // If promotion expired, don't return it
            if(!is_null($promotion->ends_at)){
                if(Carbon::now() > $promotion->ends_at){
                    $item->promotion = null;
                }
            }
        }

    }
}

?>