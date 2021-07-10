<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Logger\LoggerService;
use Illuminate\Http\Request;
use App\Services\Product\ProductService;
use App\Services\Sanitize\SanitizeService;
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

        $region_id = $this->sanitize_service->sanitizeField($request->input('region_id'));
        $supermarket_chain_id = $this->sanitize_service->sanitizeField($request->input('supermarket_chain_id'));

        $product = $this->product_service->get($region_id, $supermarket_chain_id, $product_id, $user);
        
        $this->logger_service->log('product.show', $request);

        if(!$product){
            throw new Exception('Product not found for your region and store.', 404);
        }

        return response()->json(['data' => $product]);
    }

}