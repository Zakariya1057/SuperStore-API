<?php

namespace App\Http\Controllers\API;

use App\Models\ChildCategory;
use App\Models\ParentCategory;
use App\Models\Product;
use App\Models\StoreType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SanitizeService;
use App\Services\SearchService;
use App\Services\StoreService;
use Exception;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
class SearchController extends Controller {

    private $sanitize_service, $search_service, $store_service;

    function __construct(SanitizeService $sanitize_service, SearchService $search_service, StoreService $store_service){
        $this->sanitize_service = $sanitize_service;
        $this->search_service = $search_service;
        $this->store_service = $store_service;
        
    }

    public function suggestions($query){
        $query = $this->sanitize_service->sanitizeField($query);
        $results = $this->search_service->suggestions($query);
        return response()->json(['data' => $results]);
    }

    public function results(Request $request){

        $validated_data = $request->validate([
            'data.type' => 'required',
            'data.detail' => 'required',

            'data.sort' => '', // Rating, Price, Sugar, etc.
            'data.order' => '', // asc/desc

            'data.dietary' => '',  // Halal, Vegetarian
            'data.child_category' => '',
            'data.brand' => '',

            'data.text_search' => ''
        ]);

        $data = $validated_data['data'];

        $data = $this->sanitize_service->sanitizeAllFields($data);

        $results = $this->search_service->results($data);

        return response()->json(['data' => $results]);

    }
    
    
}
