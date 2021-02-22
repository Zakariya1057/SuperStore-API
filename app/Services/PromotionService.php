<?php

namespace App\Services;

use App\Models\FeaturedItem;
use App\Models\Promotion;

class PromotionService {

    public function featured($store_id){
        $promotion = new Promotion();
        return FeaturedItem::select('promotions.id as promotion_id', 'name as promotion')->whereRaw('type = "promotions"')->join('promotions','promotions.id','featured_id')->withCasts($promotion->casts)->limit(10)->get()->pluck('promotion')->toArray();
    }

    public function details(Promotion $promotion){

        if(is_null($promotion)){
            return;
        }

        $name = html_entity_decode($promotion->name, ENT_QUOTES);
        preg_match('/(\d+).+£(\d+\.*\d*)$/',$name,$price_promotion_matches);

        $quantity = $price = $for_quantity = null;

        if($price_promotion_matches){
            $quantity = (int)$price_promotion_matches[1];
            $price = (float)$price_promotion_matches[2];
        }

        preg_match('/(\d+).+\s(\d+)$/',$name,$quantity_promotion_matches);
        if($quantity_promotion_matches){
            $quantity = (int)$quantity_promotion_matches[1];
            $for_quantity = (int)$quantity_promotion_matches[2];
        }

        if(!$quantity_promotion_matches && !$price_promotion_matches){
            return null;
        }

        return ['id' => $promotion->id, 'name' => $name, 'quantity' => $quantity, 'price' => $price, 'for_quantity' => $for_quantity];
    }
}

?>