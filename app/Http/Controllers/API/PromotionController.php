<?php

namespace App\Http\Controllers\API;

use App\Models\Promotion;
use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use App\Services\SanitizeService;

class PromotionController extends Controller {

    private $sanitize_service;

    function __construct(SanitizeService $sanitize_service){
        $this->sanitize_service = $sanitize_service;
    }

    public function index($promotion_id, PromotionService $promotion_service){
        $promotion_id = $this->sanitize_service->sanitizeField($promotion_id);

        $promotion_item = Promotion::where('id', $promotion_id)->get()->first();
        $promotion_details = $promotion_service->details( $promotion_item );

        $promotion_details['products'] = $promotion_item->products;

        return response()->json(['data' => $promotion_details]);
    }
}
