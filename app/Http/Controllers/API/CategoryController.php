<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\Services\LoggerService;
use App\Services\SanitizeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller {

    private $sanitize_service, $category_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, CategoryService $category_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->category_service = $category_service;
        $this->logger_service = $logger_service;
    }

    public function grand_parent_categories($store_type_id, Request $request){

        $store_type_id = $this->sanitize_service->sanitizeField($store_type_id);

        $this->logger_service->log('category.grand_parent_categories', $request);

        $grand_parent_categories = Cache::remember('grand_parent_category_'.$store_type_id, now()->addWeek(1), function () use ($store_type_id){
            return $this->category_service->grand_parent_categories($store_type_id);
        });

        return response()->json(['data' => $grand_parent_categories]);
    }

    public function child_categories($parent_cateogy_id, Request $request){

        $parent_cateogy_id = $this->sanitize_service->sanitizeField($parent_cateogy_id);

        $this->logger_service->log('category.child_categories', $request);

        $categories = Cache::remember('child_category_'.$parent_cateogy_id, now()->addWeek(1), function () use ($parent_cateogy_id){
            return $this->category_service->child_categories($parent_cateogy_id);
        });

        return response()->json(['data' => $categories]);
    }

    public function category_products($child_category_id, Request $request){

        $child_category_id = $this->sanitize_service->sanitizeField($child_category_id);

        $this->logger_service->log('category.products', $request);

        $categories = Cache::remember('category_products_'.$child_category_id, now()->addWeek(1), function () use ($child_category_id){
            return $this->category_service->category_products($child_category_id);
        });

        return response()->json(['data' => $categories]);
    }
}
