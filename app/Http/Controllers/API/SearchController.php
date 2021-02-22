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
use App\Traits\StoreTrait;
use Exception;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
class SearchController extends Controller {

    use StoreTrait;

    private $client, $sanitize_service, $search_service;

    function __construct(SanitizeService $sanitize_service, SearchService $search_service){
        $this->sanitize_service = $sanitize_service;
        $this->search_service = $search_service;
        $this->client = ClientBuilder::create()->setRetries(3)->setHosts(['host' => env('ELASTICSEARCH_HOST')])->build();
    }

    public function suggestions($query){

        $query = $this->sanitize_service->sanitizeField($query);

        $results = [
            'stores' => [],
            'parent_categories' => [],
            'child_categories' => [],
            'products' => []
        ];

        $cache_key = 'search_suggestions_' . str_replace(' ','_', $query);

        if(Redis::get($cache_key)){
            $results = json_decode(Redis::get($cache_key));
        } else {

            try {

                $types = [
                    'stores' => 2, 
                    'categories' => 3, 
                    'products' => 8
                ];
    
                $total_items = 0;

                foreach($types as $type => $limit){
                    $response = $this->search_service->search($this->client, $type, $query, $type == 'products' && $total_items <= 3 ? 12 : $limit);
                    $results[$type] = [];
    
                    $item_type = $type;
                    $unique_names = [];

                    foreach($response['hits']['hits'] as $item){
                        $source = $item['_source'];
                        $name = trim($source['name']);

                        if($type == 'categories'){
                            $item_type = $source['type'];
                        }
                        
                        $total_items++;

                        if(!key_exists($name, $unique_names)){
                            $results[$item_type][] = ['id' => $source['id'], 'name' => $name];
                            $unique_names[$name] = 1;
                        }

                    }

                }
    
            } catch(Exception $e){
                // Backup search in case elasticsearch fails from now
                Log::critical('Elasticsearch Error: ' . $e);
    
                $stores = StoreType::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(2)->get()->toArray();
                $child_categories = ChildCategory::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(5)->get()->toArray();
                $parent_categories = ParentCategory::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(5)->get()->toArray();
    
                if((count($child_categories) + count($parent_categories)) <= 3 ){
                    $product_limit = 10;
                } else {
                    $product_limit = 5;
                }
    
                $products = Product::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->orderByRaw('total_reviews_count / avg_rating desc')->limit($product_limit)->get()->toArray();
        
                $results['stores'] = $stores ?? [];
                $results['parent_categories'] = $parent_categories ?? [];
                $results['child_categories'] = $child_categories ?? [];
                $results['products'] = $products ?? [];            
            }

            Redis::set($cache_key, json_encode($results));
            Redis::expire($cache_key, 86400);

        }

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

        $detail = $data['detail'];
        $type = strtolower($data['type']);

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';
        $dietary = $data['dietary'] ?? '';
        $category = $data['category'] ?? '';
        $child_category = $data['child_category'] ?? '';
        $brand = $data['brand'] ?? '';

        $text_search = $data['text_search'] ?? false;

        if($text_search){
            // Search all matching elasticsearch items. Return array of their IDs, use to query database down below
            $list_id = [];

            $search_type = preg_replace('/child_|parent_/i','',$type);

            $response = $this->search_service->search($this->client, $search_type, html_entity_decode($detail, ENT_QUOTES), 30);

            foreach($response['hits']['hits'] as $item){
                $source = $item['_source'];
                $name = trim($source['name']);
                $list_id[ $source['id'] ] = $name;
            }

            $list_id = array_keys($list_id);
        }

        $cache_key = "search_results_type:{$type}_detail:{$detail}_sort:{$sort}_order:{$order}_diatary:{$dietary}_child_category:{$child_category}_category:{$category}_brand:{$brand}_text_search:{$text_search}";
        $cache_key = str_replace(' ','_',$cache_key);

        $results = array(
            'stores' => [],
            'products' => [],
            'filter' => null
        );

        if(Redis::get($cache_key)){
            $results = json_decode(Redis::get($cache_key));
            Log::debug('Retrieved');
        } else {
            Log::debug('Fetched');
            $product = new Product();
        
            $casts = $product->casts;
    
            if($type == 'stores'){
                $stores = $this->stores_by_type($detail);
                $results['stores'] = $stores;
            } else {
    
                $base_query = ParentCategory::
                select(
                    'products.*',
                    'parent_categories.id as parent_category_id',
                    'parent_categories.name as parent_category_name',
                    'child_categories.id as child_category_id',
                    'child_categories.name as child_category_name',
                    'promotions.name as promotion'
                )
                ->join('category_products','category_products.parent_category_id','parent_categories.id')
                ->join('products','products.id','category_products.product_id')
                ->join('child_categories','child_categories.id','category_products.child_category_id')
                ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
                ->groupBy('products.id')
                ->withCasts($casts);
                
                if($text_search){
                    if($type == 'products'){
                        $base_query = $base_query->whereIn('products.id', $list_id);
                    } elseif($type == 'child_categories'){
                        $base_query = $base_query->where('child_categories.id',$list_id);
                    } elseif($type == 'parent_categories'){
                        $base_query = $base_query->where('parent_categories.id', $list_id);
                    }
                } else {
                    if($type == 'products'){
                        $base_query = $base_query->where('products.name', 'like', "$detail%");
                    } elseif($type == 'child_categories'){
                        $base_query = $base_query->where('child_categories.name',$detail);
                    } elseif($type == 'parent_categories'){
                        $base_query = $base_query->where('parent_categories.name', $detail);
                    }
                }
    
                if(key_exists('sort', $data) && key_exists('order', $data)){
                    $sort = strtolower($data['sort']);
                    $order = strtoupper($data['order']);
    
                    if($order != 'ASC' && $order != 'DESC'){
                        throw new Exception('Unknown order by option.', 422);
                    }
    
                    $sort_options = [
                        'rating' => 'avg_rating',
                        'price' => 'price',
                    ];
    
                    if(key_exists($sort,$sort_options)){
                        $base_query = $base_query->orderByRaw($sort_options[$sort] . ' '. $order);
                    } else {
                        throw new Exception('Unknown sort by option.', 422);
                    }
    
                } else {
                    $base_query = $base_query->orderByRaw('total_reviews_count / avg_rating desc');
                }
    
                if(key_exists('dietary', $data)){
                    $dietary_list = explode(',',$data['dietary']);

                    foreach($dietary_list as $dietary){
                        $base_query = $base_query->where('dietary_info','like', "%$dietary%");
                    }
                    
                }

                if(key_exists('brand', $data)){
                    $brand = $data['brand'];
                    $base_query = $base_query->where('brand',$brand);
                }
                
                if(key_exists('child_category', $data)){
                    $category = $data['child_category'];
                    $base_query = $base_query->where('child_categories.name',$category);
                }
                
                $paginator = $base_query->paginate(100);

                $results['products'] = $paginator->items();

                $results['paginate'] = [
                    'from' => 0,
                    'current' => $paginator->currentPage(),
                    'to' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'next_page_url' => $paginator->url( $paginator->currentPage() + 1),
                    'current_page_url' => $paginator->url( $paginator->currentPage() ),
                    'prev_page_url' => $paginator->previousPageUrl(),
                    'more_available' => $paginator->hasMorePages(),
                ];
    
            }

            Redis::set($cache_key, json_encode($results));
            Redis::expire($cache_key, 86400);

        }

        return response()->json(['data' => $results]);

    }
    
    
}
