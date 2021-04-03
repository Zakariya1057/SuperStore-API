<?php

namespace App\Services;

use App\Models\ChildCategory;
use App\Models\ParentCategory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StoreType;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SearchService {

    private $client, $store_service, $refine_service;

    public function __construct(StoreService $store_service, RefineService $refine_service){
        $this->client = ClientBuilder::create()->setRetries(3)->setHosts(['host' => env('ELASTICSEARCH_HOST')])->build();
       
        $this->store_service = $store_service;
        $this->refine_service = $refine_service;

        $this->promotion_service = new PromotionService();
    }

    ///////////////////////////////////////////     Suggestions     ///////////////////////////////////////////

    public function suggestions($query, $store_type_id){
        
        $results = [
            'stores' => [],
            'parent_categories' => [],
            'child_categories' => [],
            'products' => [],
            'promotions' => [],
            'store_sales' => [],
            'brands' => [],
        ];

        $cache_key = 'search_suggestions:' . str_replace(' ','_', $query) . '_store_type_id:' . $store_type_id;

        $cached_results = Redis::get($cache_key);

        if($cached_results){
            $results = json_decode($cached_results);
        } else {

            try {
                $this->suggestions_by_group($query, $results, $store_type_id);
            } catch(Exception $e){
                // Backup search in case elasticsearch fails from now
                Log::critical('Elasticsearch Error: ' . $e);
                $this->database_suggestions($query, $results, $store_type_id);         
            }


            if(count($results['stores']) > 0){
                $store = (object)$results['stores'][0];

                $results['store_sales'][] = [
                    'id' => $store->id,
                    'name' => $store->name . ' Sales'
                ];
            }

            Redis::set($cache_key, json_encode($results));
            Redis::expire($cache_key, 86400);

        }

        return $results;
    }

    private function suggestions_by_group($query, &$results, $store_type_id){
        $types = [
            'stores' => 2, 
            'brands' => 2,
            'categories' => 3, 
            'products' => 8,
            'promotions' => 1
        ];

        $total_items = 0;

        foreach($types as $type => $limit){
            $response = $this->elastic_search($this->client, $type, $query, $store_type_id, $type == 'products' && $total_items <= 3 ? 12 : $limit);
            $results[$type] = [];

            $item_type = $type;
            $unique_names = [];

            foreach($response['hits']['hits'] as $item){
                $source = $item['_source'];
                $name = trim($source['name']);

                if($type == 'categories'){
                    $item_type = $source['type'];
                }

                if($type == 'brands'){
                    $name = trim($source['brand']);
                }
                
                $total_items++;

                if(!key_exists($name, $unique_names)){
                    $results[$item_type][] = ['id' => $source['id'], 'name' => $name];
                    $unique_names[$name] = 1;
                }

            }

        }
    }

    private function database_suggestions($query, &$results, $store_type_id){
        $stores = StoreType::select('id','name')->where([ ['id', $store_type_id], ['name', 'like', "%$query%"] ])->groupBy('name')->limit(2)->get()->toArray();
        $child_categories = ChildCategory::select('id','name')->where([ ['store_type_id', $store_type_id], ['name', 'like', "%$query%"] ])->groupBy('name')->limit(5)->get()->toArray();
        $parent_categories = ParentCategory::select('id','name')->where([ ['store_type_id', $store_type_id], ['name', 'like', "%$query%"] ])->groupBy('name')->limit(5)->get()->toArray();
        $promotions = Promotion::select('id','name')->where([ ['store_type_id', $store_type_id], ['name', 'like', "%$query%"] ])->groupBy('name')->limit(2)->get()->toArray();
        $brands = Product::select(['id','brand as name'])->where([ ['store_type_id', $store_type_id], ['brand', 'like', "%$query%"] ])->groupBy('brand')->orderByRaw('total_reviews_count / avg_rating desc')->limit(2)->get()->toArray();

        if((count($child_categories) + count($parent_categories)) <= 3 ){
            $product_limit = 10;
        } else {
            $product_limit = 5;
        }

        $products = Product::select('id','name')->where([ ['store_type_id', $store_type_id], ['name', 'like', "%$query%"] ])->groupBy('name')->orderByRaw('total_reviews_count / avg_rating desc')->limit($product_limit)->get()->toArray();

        $results['stores'] = $stores ?? [];
        $results['parent_categories'] = $parent_categories ?? [];
        $results['child_categories'] = $child_categories ?? [];
        $results['products'] = $products ?? []; 
        $results['promotions'] = $promotions ?? []; 
        $results['brands'] = $brands ?? []; 
    }

    ///////////////////////////////////////////     Suggestions     ///////////////////////////////////////////


    ///////////////////////////////////////////     Results        ///////////////////////////////////////////

    // Products
    public function product_results($data, $page = 1){

        $query = $data['query'];
        $type = strtolower($data['type']);

        $store_type_id = $data['store_type_id'];

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';
        $dietary = $data['dietary'] ?? '';
        $category = $data['category'] ?? '';
        $child_category = $data['child_category'] ?? '';
        $brand = $data['brand'] ?? '';

        $text_search = $data['text_search'] ?? false;

        $item_ids = [];

        if($text_search){
            // Search all matching elasticsearch items. Return array of their IDs, use to query database down below
            $search_type = preg_replace('/child_|parent_/i','',$type);

            $response = $this->elastic_search($this->client, $search_type, html_entity_decode($query, ENT_QUOTES), $store_type_id, 50, true);

            foreach($response['hits']['hits'] as $item){
                $source = $item['_source'];
                $name = trim($source['name']);
                $item_ids[ $source['id'] ] = $name;
            }

            $item_ids = array_keys($item_ids);
        }

        $cache_key = "product_search_results_{$query}_store_type_id:{$store_type_id}_sort:{$sort}_order:{$order}_diatary:{$dietary}_child_category:{$child_category}_category:{$category}_brand:{$brand}_text_search:{$text_search}_page:$page";
        $cache_key = str_replace(' ','_',$cache_key);

        $results = array(
            'products' => [],
            'filter' => null
        );
        
        $cached_results = Redis::get($cache_key);

        if($cached_results){
            $results = json_decode($cached_results);
        } else {
            $product = new Product();
        
            $casts = $product->casts;

            $base_query = ParentCategory::
            select(
                'products.*',
                'parent_categories.id as parent_category_id',
                'parent_categories.name as parent_category_name',
                'child_categories.id as child_category_id',
                'child_categories.name as child_category_name',

                'promotions.store_type_id as promotion_store_type_id',
                'promotions.name as promotion_name',
                'promotions.quantity as promotion_quantity',
                'promotions.price as promotion_price',
                'promotions.for_quantity as promotion_for_quantity',
    
                'promotions.minimum as promotion_minimum',
                'promotions.maximum as promotion_maximum',
                
                'promotions.expires as promotion_expires',
                'promotions.starts_at as promotion_starts_at',
                'promotions.ends_at as promotion_ends_at',
            )
            ->join('category_products','category_products.parent_category_id','parent_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->join('child_categories','child_categories.id','category_products.child_category_id')
            ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
            ->groupBy('products.id')
            ->withCasts($casts);
            
            if($text_search){
                $base_query = $this->text_search_where($item_ids, $type, $base_query);
            } else {
                $base_query = $this->search_where($type, $query, $store_type_id, $base_query);
            }   

            $results = $this->refine_service->refine_results($base_query, $data, $item_ids);

            Redis::set($cache_key, json_encode($results));
            Redis::expire($cache_key, 86400);

        }

        return $results;
    }

    public function store_results($store_type_id, $latitude, $longitude){
        return $this->store_service->stores_by_type($store_type_id, true, $latitude, $longitude);
    }

    private function search_where($type, $detail, $store_type_id, Builder $base_query){
        if($type == 'products'){
            $base_query = $base_query->where([ ['products.store_type_id',  $store_type_id], ['products.name', 'like', "$detail%"] ]);
        } elseif($type == 'child_categories'){
            $base_query = $base_query->where([ ['child_categories.store_type_id', $store_type_id], ['child_categories.name',$detail] ]);
        } elseif($type == 'parent_categories'){
            $base_query = $base_query->where([ ['parent_categories.store_type_id', $store_type_id], ['parent_categories.name', $detail] ]);
        } elseif($type == 'promotions'){
            $base_query = $base_query->where([ ['promotions.store_type_id', $store_type_id], ['promotions.name', $detail] ]);
        } elseif($type == 'brands'){
            $base_query = $base_query->where([ ['products.store_type_id', $store_type_id], ['brand', $detail] ]);
        } elseif($type == 'store_sales'){
            $base_query = $base_query
            ->where('products.store_type_id', $store_type_id)
            ->where(function($query) {
                $query->where('products.is_on_sale', 1)->orwhereNotNull('products.promotion_id');
            });
        }

        return $base_query;
    }

    private function text_search_where($item_ids, $type, Builder $base_query){

        if(count($item_ids) > 0){

            if($type == 'products'){
                $base_query = $base_query->whereIn('products.id', $item_ids);
            } elseif($type == 'child_categories'){
                $base_query = $base_query->where('child_categories.id',$item_ids);
            } elseif($type == 'parent_categories'){
                $base_query = $base_query->where('parent_categories.id', $item_ids);
            } elseif($type == 'promotions'){
                $base_query = $base_query->where('promotions.id', $item_ids);
            }

        } else {
            $base_query = $base_query->where('products.id', 0);
        }

        return $base_query;
    }


    ///////////////////////////////////////////     Results        ///////////////////////////////////////////


    private function elastic_search(Client $client, $index, $query, $store_type_id, $limit=10, $text_search = false): Array{

        $index = strtolower($index);
        $query = strtolower($query);
        
        $sort = [];
        $operator = 'and';

        if($index == 'products'){

            if($text_search){
                $fields_match = ['name'];
                $fields_should = ['name'];
            } else {
                $fields_match = ['name','description','brand','dietary_info'];
                $fields_should = ['name', 'weight','brand'];
    
                $sort = [
                        
                    [
                        '_script' => [
                            'type' => 'number',
                            'script' => [
                                'lang' => 'painless',
                                'source' => "
                                if(doc['avg_rating'].value > 0 && doc['total_reviews_count'].value > 0){
                                    _score + ( (doc['total_reviews_count'].value * 0.0001) / doc['avg_rating'].value) 
                                } else {
                                    0
                                }
                                "
                            ],
                            'order' => 'desc'
                        ]
                    ],
    
                    '_score',
                    
                ];
    
                $operator = 'or';
            }


        } elseif($index == 'brands'){
            $fields_match = ['brand'];
            $fields_should = ['brand'];

            $index = 'products';
        } else if($index == 'promotions'){
            $fields_match = ['name'];
            $fields_should = [];
            $operator = 'and';
        } else {
            $fields_match = ['name'];
            $fields_should = ['name', 'weight'];
        }

        $params = [
            'index' => $index,
            'body'  => [
                'size' => $limit,
                
                    'query' => [

                        'bool' => [
                            'must' => [
                                [
                                    'match' => [
                                        'store_type_id' => [ 
                                            'query' => $store_type_id
                                        ]
                                    ]
                                ],
                    
                                [
                                    'multi_match' => [
                                        'query' => $query,
                                        'fields' => $fields_match,
                                        'operator' => $operator,
                                        'fuzziness' => 'auto'
                                    ],
                                ],

                            ],
                            'should' => [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => $fields_should,
                                    'operator' => 'and'
                                ]
                            ]
                        ],
                    ],

                    'sort' => $sort
            ]
        ];

        // if($index == 'products'){
        //     dd(json_encode($params));
        // }

        return $client->search($params);
        
    }


}
?>