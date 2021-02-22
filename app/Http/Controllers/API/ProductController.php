<?php

namespace App\Http\Controllers\API;

use App\Models\Product;
use App\Models\Recommended;
use App\Models\FavouriteProducts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonitoredProduct;
use App\Services\ProductService;
use App\Services\SanitizeService;
use Exception;

class ProductController extends Controller {

    private $sanitize_service, $product_service;

    function __construct(SanitizeService $sanitize_service, ProductService $product_service){
        $this->sanitize_service = $sanitize_service;
        $this->product_service = $product_service;
    }

    public function show(Request $request, $product_id){

        $user = $request->user();

        $product_id = $this->sanitize_service->sanitizeField($product_id);

        $product = $this->product_service->get($product_id, $user);

        if(!$product){
            throw new Exception('No product found.', 404);
        }

        return response()->json(['data' => $product]);
    }

}