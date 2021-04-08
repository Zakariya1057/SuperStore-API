<?php

namespace App\Http\Controllers\API;

use App\Models\Promotion;
use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use App\Services\PromotionService;
use App\Services\SanitizeService;
use Exception;
use Illuminate\Http\Request;

class PromotionController extends Controller {

    private $sanitize_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->logger_service = $logger_service;
    }

    public function show($promotion_id, Request $request){
        $promotion_id = $this->sanitize_service->sanitizeField($promotion_id);

        $this->logger_service->log('promotion.index',$request);

        $promotion = Promotion::where('id', $promotion_id)->first();

        if(!is_null($promotion)){
            $promotion->products;
        } else {
            throw new Exception('Promotion not found.', 404);
        }
        
        return response()->json(['data' => $promotion]);
    }
}
