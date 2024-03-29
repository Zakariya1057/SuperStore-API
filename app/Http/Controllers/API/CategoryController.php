<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryProductRequest;
use App\Services\Category\CategoryService;
use App\Services\Logger\LoggerService;
use App\Services\Sanitize\SanitizeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller {

    private $sanitize_service, $category_service, $logger_service;

    function __construct(SanitizeService $sanitize_service, CategoryService $category_service, LoggerService $logger_service){
        $this->sanitize_service = $sanitize_service;
        $this->category_service = $category_service;
        $this->logger_service = $logger_service;
    }

    public function grand_parent_categories($supermarket_chain_id, Request $request){

        $supermarket_chain_id = $this->sanitize_service->sanitizeField($supermarket_chain_id);

        $this->logger_service->log('category.grand_parent_categories', $request);

        $grand_parent_categories = Cache::remember('grand_parent_category_'.$supermarket_chain_id, now()->addWeek(1), function () use ($supermarket_chain_id){
            return $this->category_service->grand_parent_categories($supermarket_chain_id);
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

    public function category_products($child_category_id, CategoryProductRequest $request){

        $validated_data = $request->validated();

        $data = $validated_data['data'];
        $data = $this->sanitize_service->sanitizeAllFields($data);

        $page = $this->sanitize_service->sanitizeField((int)$request->page ?? 1);

        $child_category_id = $this->sanitize_service->sanitizeField($child_category_id);

        $this->logger_service->log('category.products', $request);

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';
        $dietary = $data['dietary'] ?? '';
        $brand = $data['brand'] ?? '';
        $promotion = $data['promotion'] ?? '';
        $product_group = $data['product_group'] ?? '';
        $availability_type = $data['availability_type'] ?? '';
        $region_id = $data['region_id'];
        $supermarket_chain_id = $data['supermarket_chain_id'] ?? 1;
        
        $categories = Cache::remember("category_products_{$child_category_id}_supermarket_chain_{$supermarket_chain_id}_region_{$region_id}_page_{$page}_sort_{$sort}_order_{$order}_brand_{$brand}_promotion_{$promotion}_dietary_{$dietary}_product_group_{$product_group}_availability_type_{$availability_type}" , now()->addWeek(1), function () use ($child_category_id, $data){
            return $this->category_service->category_products($child_category_id, $data);
        });

        return response()->json(['data' => $categories]);
    }
}
