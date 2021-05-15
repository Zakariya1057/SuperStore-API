<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use App\Services\PromotionService;
use Illuminate\Http\Request;

class PromotionController extends Controller {

    private $logger_service, $promotion_service;

    function __construct(LoggerService $logger_service, PromotionService $promotion_service){
        $this->logger_service = $logger_service;
        $this->promotion_service = $promotion_service;
    }

    public function all($store_type_id, Request $request){
        $this->logger_service->log('promotion.all', $request);

        $promotions = $this->promotion_service->all($store_type_id);
        
        return response()->json(['data' => $promotions]);
    }

    public function groups($store_type_id, $title, Request $request){
        $this->logger_service->log('promotion.all', $request);
        
        $promotions = $this->promotion_service->group($store_type_id, $title);
        
        return response()->json(['data' => $promotions]);
    }

    public function show($promotion_id, Request $request){
        $this->logger_service->log('promotion.index', $request);

        $promotion = $this->promotion_service->get($promotion_id);
        
        return response()->json(['data' => $promotion]);
    }
}
