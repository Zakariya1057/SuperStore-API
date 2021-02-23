<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Services\SanitizeService;
use Exception;

class ProductController extends Controller {

    private $sanitize_service, $product_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, ProductService $product_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->product_service = $product_service;
        $this->logger_service = $logger_service;
    }

    public function show(Request $request, $product_id){

        $user = $request->user();

        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $product = $this->product_service->get($product_id, $user);
        
        $this->logger_service->log('product.show', $request);

        if(!$product){
            throw new Exception('No product found.', 404);
        }

        return response()->json(['data' => $product]);
    }

}