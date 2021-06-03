<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use App\Services\Product\PromotionService;
use Illuminate\Http\Request;

class PromotionController extends Controller {

    private $logger_service, $promotion_service;

    function __construct(LoggerService $logger_service, PromotionService $promotion_service){
        $this->logger_service = $logger_service;
        $this->promotion_service = $promotion_service;
    }

    public function all($store_type_id, Request $request){
        $this->logger_service->log('promotion.all', $request);

        $region_id = $request->input('region_id') ?? 8;

        $promotions = $this->promotion_service->all($store_type_id, $region_id);
        
        return response()->json(['data' => $promotions]);
    }

    public function groups($store_type_id, $title, Request $request){
        $this->logger_service->log('promotion.all', $request);
        
        $region_id = $request->input('region_id') ?? 8;

        $promotions = $this->promotion_service->group($region_id, $store_type_id, $title);
        
        return response()->json(['data' => $promotions]);
    }

    public function show($promotion_id, Request $request){
        $this->logger_service->log('promotion.index', $request);

        $region_id = $request->input('region_id') ?? 8;

        $promotion = $this->promotion_service->get($region_id, $promotion_id);
        
        return response()->json(['data' => $promotion]);
    }
}
