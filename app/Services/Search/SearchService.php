<?php

namespace App\Services\Search;

use Exception;

use App\Models\ChildCategory;
use App\Models\ParentCategory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StoreType;
use App\Services\Product\PromotionService;
use App\Services\RefinePaginate\RefineService;
use App\Services\Store\StoreService;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SearchService {

    private $client, $store_service, $refine_service;

    public function __construct(StoreService $store_service, RefineService $refine_service, PromotionService $promotion_service){
        $this->client = ClientBuilder::create()->setRetries(3)->setHosts(['host' => env('ELASTICSEARCH_HOST')])->build();
       
        $this->store_service = $store_service;
        $this->refine_service = $refine_service;

        $this->promotion_service = $promotion_service;
    }

    ///////////////////////////////////////////     Suggestions     ///////////////////////////////////////////

    public function suggestions($query, $store_type_id){
        
        $results = [
            'stores' => [],

            'product_groups' => [],
            'child_categories' => [],
            'parent_categories' => [],

            'brands' => [],
            'promotions' => [],
            'store_sales' => [],
            
            'products' => [],
            
            'corrections' => []
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

            preg_match('/sale|discount|offer|promotion/i', $query, $sale_matches);

            // If the search term contains sale or discount then show this
            if(count($results['stores']) > 0){
                $store = (object)$results['stores'][0];

                $results['store_sales'][] = [
                    'id' => $store->id,
                    'name' => $store->name . ' Offers'
                ];
            } else if($sale_matches) {
                $results['store_sales'][] = [
                    'id' => (int)$store_type_id,
                    'name' => 'Offers'
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
            'categories' => 3, 
            'products' => 3,
            'promotions' => 3,
            'brands' => 2,
        ];

        $fetch_limits = [
            'products' => 20,
            'categories' => 5
        ];

        // Suggested Correct Word. Vread -> Bread
        $highlighted_terms = [];

        $unique_terms = [];

        $total_items = 0;

        foreach($types as $type => $limit){

            $elsatic_limit = $fetch_limits[$type] ?? $limit;

            $response = $this->elastic_search($this->client, $type, $query, $store_type_id, $elsatic_limit);

            // If searched and no results found then might be a multi word problem. Use or instead again.
            $total_results = $response['hits']['total']['value'];
            if($total_results < 3){
                $response = $this->elastic_search($this->client, $type, $query, $store_type_id, $elsatic_limit, false, 'auto', 'or');
            }

            $results[$type] = [];

            $item_type = $type;
            $unique_names = [];

            foreach($response['hits']['hits'] as $item){
                $source = $item['_source'];
                $name = trim($source['name']);

                if($type != 'promotions' && key_exists('highlight', $item)){
                    $correct_term = ucwords(strtolower($item['highlight']['name'][0]));
                    $correct_term = preg_replace("/\s*[-,.+@';:=()&*%]$/", '', $correct_term);

                    if(!is_numeric($correct_term)){
                        preg_match("/^\s*[-,.+@';:=()&*%]/", $correct_term, $matches);

                        if(!$matches){
                            // If suggestion begins with weird characters then ignore
                            if( !key_exists($correct_term .'s', $highlighted_terms) && !key_exists($correct_term .'es', $highlighted_terms) ){
                                $highlighted_terms[$correct_term] = $source['id'];
                            }
                        }
                    }
                }
                
                if($type == 'categories'){
                    $item_type = $source['type'];
                }

                if($type == 'brands'){
                    $name = trim($source['brand']);
                }
                
                if( ($type == 'products' || $type == 'brands') && key_exists(strtolower($name), $unique_terms)){
                    continue;
                }

                $unique_terms[strtolower($name)] = 1;

                $total_items++;

                if(!key_exists($name, $unique_names)){
                    $results[$item_type][] = ['id' => $source['id'], 'name' => $name];
                    $unique_names[$name] = 1;
                }

            }

            $results[$type] = array_slice($results[$type], 0, $limit); 

        }

        $this->sort_corrections($highlighted_terms, $query);

        foreach( $highlighted_terms as $term => $id){
            if(strlen($term) > 2){
                if( ( !key_exists(strtolower($term), $unique_terms) ) &&  count($results['corrections']) < 3){
                    $results['corrections'][] = [
                        'id' => $id,
                        'name' => $term
                    ];
                }
            }
        }
    }

    private function sort_corrections(&$highlighted_terms, $query){
        $keys = array_map(function($term) use($query){
            return similar_text($query, $term);
        }, array_keys($highlighted_terms));

        array_multisort($keys, SORT_DESC, $highlighted_terms);
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
        $region_id = $data['region_id'];

        $products_limit_elastic = 300;

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';
        $dietary = $data['dietary'] ?? '';
        $category = $data['category'] ?? '';
        $promotion = $data['promotion'] ?? '';
        $product_group = $data['product_group'] ?? '';
        $brand = $data['brand'] ?? '';

        $availability_type = $data['availability_type'] ?? '';

        $fuzziness = 0;

        $text_search = $data['text_search'] ?? false;

        $item_ids = [];

        if($text_search){
            $fuzziness = 1;
        }

        if($type == 'products'){
            if(!$text_search){
                $text_search = true;
                $data['text_search'] = true;
            }
        }

        if($text_search){
            // Search all matching elasticsearch items. Return array of their IDs, use to query database down below
            $search_type = preg_replace('/child_|parent_/i','',$type);

            $response = $this->elastic_search($this->client, $search_type, html_entity_decode($query, ENT_QUOTES), $store_type_id, $products_limit_elastic, true, $fuzziness);

            // If searched and no results found then might be a multi word problem. Use or instead again.
            $total_results = $response['hits']['total']['value'];
            if($total_results < 3){
                $response = $this->elastic_search($this->client, $search_type, html_entity_decode($query, ENT_QUOTES), $store_type_id, 50, true, $fuzziness,'or');
            }

            // dd($response['hits']['hits']);

            foreach($response['hits']['hits'] as $item){
                $source = $item['_source'];
                $name = trim($source['name']);
                $item_ids[ $source['id'] ] = $name;
            }

            $item_ids = array_keys($item_ids);
        }

        $cache_key = "product_search_results_{$query}_store_type_id:{$store_type_id}_region_id:{$region_id}_sort:{$sort}_order:{$order}_diatary:{$dietary}_product_group:{$product_group}_category:{$category}_brand:{$brand}_promotion:{$promotion}_text_search:{$text_search}_availability_type:{$availability_type}_page:$page";
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

                'product_prices.price', 
                'product_prices.old_price',
                'product_prices.is_on_sale', 
                'product_prices.sale_ends_at', 
                'product_prices.promotion_id', 
                'product_prices.region_id',

                'parent_categories.id as parent_category_id',

                'parent_categories.name as parent_category_name',
                'child_categories.id as child_category_id',
                'child_categories.name as child_category_name',

                'product_groups.name as product_group_name',

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

                'promotions.enabled as promotion_enabled',
            )
            ->join('category_products','category_products.parent_category_id','parent_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->join('product_prices','product_prices.product_id','products.id')
            ->join('child_categories','child_categories.id','category_products.child_category_id')
            ->leftJoin('product_groups','product_groups.id','category_products.product_group_id')
            ->leftJoin('promotions', 'promotions.id','=','product_prices.promotion_id')
            ->groupBy('products.id')
            ->where([ ['products.store_type_id', $store_type_id], ['product_prices.region_id', $region_id], [ 'products.enabled', 1], ['child_categories.enabled', 1] ])
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
        } elseif($type == 'product_groups'){
            $base_query = $base_query->where([ ['child_categories.store_type_id', $store_type_id], ['product_groups.name',$detail] ]);
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
            ->orderBy('product_price.price', 'DESC')
            ->whereNotNull('product_prices.promotion_id');
        }

        return $base_query;
    }

    private function text_search_where($item_ids, $type, Builder $base_query){

        if(count($item_ids) > 0){

            if($type == 'products'){
                $base_query = $base_query->whereIn('products.id', $item_ids);
            } elseif($type == 'product_groups'){
                $base_query = $base_query->where('product_groups.id',$item_ids);
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


    private function elastic_search(Client $client, $index, $query, $store_type_id, $limit=10, $text_search = false, $fuzziness = 'auto', $operator = 'and'): Array{

        $index = strtolower($index);
        $query = strtolower($query);
        
        $sort = [];

        $highlight = [
            'pre_tags' => '',
            'post_tags' => '', 
            'fields' => [
              'name' => [
                  'fragment_size' =>  strpos($query, ' ') === false ? 1 : strlen($query),
                  'number_of_fragments' => 1,
                  'order' => 'score'
                ]
            ]
        ];

        if($index == 'products'){

            $fields_should = [
                'product_group_names',
                'child_category_names', 
                'parent_category_names',

                'description', 
                'brand', 
                'weight',
            ];

            if($text_search){
                $fields_match = ['name'];
            } else {
                $fields_match = ['name','brand', 'dietary_info'];
    
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
                                        'fuzziness' => $fuzziness
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

                    'sort' => $sort,

                    'highlight' => $highlight
            ]
        ];

        // if($index == 'categories'){
            // dd(json_encode($params));
        // }
        
        return $client->search($params);
        
    }


}
?>