<?php

namespace App\Http\Controllers\API;

use App\Promotion;
use App\Http\Controllers\Controller;
use App\Traits\SanitizeTrait;
use App\Services\PromotionService;

class PromotionController extends Controller {

    use SanitizeTrait;

    public function index($promotion_id, PromotionService $promotion_service){
        $promotion_id = $this->sanitizeField($promotion_id);

        $promotion_item = Promotion::where('id', $promotion_id)->get()->first();
        $promotion_details = $promotion_service->details( $promotion_item );

        $promotion_details['products'] = $promotion_item->products;

        return response()->json(['data' => $promotion_details]);
    }
}
