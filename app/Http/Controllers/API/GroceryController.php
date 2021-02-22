<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\GroceryService;
use App\Services\SanitizeService;
use Illuminate\Support\Facades\Cache;

class GroceryController extends Controller {

    private $sanitize_service, $grocery_service;

    function __construct(SanitizeService $sanitize_service, GroceryService $grocery_service){
        $this->sanitize_service = $sanitize_service;
        $this->grocery_service = $grocery_service;
    }

    public function categories($store_type_id){

        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);

        $grand_parent_categories = Cache::remember('categories_'.$store_type_id, now()->addWeek(1), function () use ($store_type_id){
            return $this->grocery_service->grocery_categories($store_type_id);
        });

        return response()->json(['data' => $grand_parent_categories]);
    }

    public function products($parent_cateogy_id){

        $parent_cateogy_id = $this->sanitize_service->sanitizeField($parent_cateogy_id);

        $categories = Cache::remember('category_products_'.$parent_cateogy_id, now()->addWeek(1), function () use ($parent_cateogy_id){
            return $this->grocery_service->grocery_products($parent_cateogy_id);
        });

        return response()->json(['data' => $categories]);
    }
}
