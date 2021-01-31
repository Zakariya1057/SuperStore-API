<?php

namespace App\Traits;

use App\FeaturedItem;
use App\Services\PromotionService;

trait PromotionTrait {

    protected function store_promotions($store_id){

        $promotion_service = new PromotionService();

        $promotions_list = FeaturedItem::select('promotions.id as promotion_id', 'name as promotion')->whereRaw('type = "promotions"')->join('promotions','promotions.id','featured_id')->limit(10)->get();

        $promotion_results = [];

        foreach($promotions_list as $promotion){
            $promotion_results[] = $promotion_service->details( $promotion );
        }

        return response()->json(['data' => $promotion_results]);
    }

}

?>