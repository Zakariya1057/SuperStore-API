<?php

namespace App\Http\Controllers\API;

use App\ChildCategory;
use App\ParentCategory;
use App\Product;
use App\StoreType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\SanitizeTrait;
use App\Traits\SearchTrait;
use App\Traits\StoreTrait;
use Exception;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
class SearchController extends Controller {

    use SanitizeTrait;
    use StoreTrait;
    use SearchTrait;

    public function suggestions($query){

        $query = $this->sanitizeField($query);

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

                $client = ClientBuilder::create()->setRetries(3)->setHosts(['host' => env('ELASTICSEARCH_HOST')])->build();
                $types = [
                    'stores' => 2, 
                    'categories' => 3, 
                    'products' => 5
                ];
    
                foreach($types as $type => $limit){
                    $response = $this->search($client, $type, $query, $limit);
                    $results[$type] = [];
    
                    foreach($response['hits']['hits'] as $item){
                        $source = $item['_source'];
    
                        if($type == 'categories'){
                            $type = $source['type'];
                        }
    
                        $results[$type][] = ['id' => $source['id'], 'name' => $source['name']];
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
        ]);

        $data = $validated_data['data'];

        $data = $this->sanitizeAllFields($data);

        $detail = $data['detail'];
        $type = strtolower($data['type']);

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';
        $dietary = $data['dietary'] ?? '';
        $category = $data['category'] ?? '';
        $child_category = $data['child_category'] ?? '';
        $brand = $data['brand'] ?? '';

        $cache_key = "search_results_type:{$type}_detail:{$detail}_sort:{$sort}_order:{$order}_diatary:{$dietary}_child_category:{$child_category}_category:{$category}_brand:{$brand}";
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
                
                if($type == 'products'){
                    $base_query = $base_query->where('products.name', 'like', "$detail%");
                } elseif($type == 'child_categories'){
                    $base_query = $base_query->where('child_categories.name',$detail);
                } elseif($type == 'parent_categories'){
                    $base_query = $base_query->where('parent_categories.name', $detail);
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
