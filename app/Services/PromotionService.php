<?php

namespace App\Services;

use App\Models\FeaturedItem;
use App\Models\Promotion;

class PromotionService {

    public function featured($store_type_id){
        $promotion = new Promotion();
        return FeaturedItem::select('promotions.id as promotion_id', 'name as promotion', 'promotions.store_type_id')
        ->where([ ['featured_items.store_type_id', $store_type_id], ['type', 'promotions'] ])
        ->join('promotions','promotions.id','featured_id')
        ->withCasts($promotion->casts)->limit(10)
        ->get()->pluck('promotion')
        ->toArray() ?? [];
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

        if(is_null($promotion->name)){
            return null;
        } else {
            $item->promotion = $promotion;
        }

    }
}

?>