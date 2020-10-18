<?php

namespace App\Http\Controllers\API;

use App\ChildCategory;
use App\ParentCategory;
use App\Product;
use App\Store;
use App\StoreType;
use Illuminate\Http\Request;
use App\Casts\HTMLDecode;
use App\Casts\PromotionCalculator;
use App\Http\Controllers\Controller;
use App\OpeningHour;
use App\Traits\SanitizeTrait;
use App\Traits\StoreTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

// Use elastic search in future

class SearchViewController extends Controller
{

    use SanitizeTrait;
    use StoreTrait;

    public function suggestions($query){

        $results = Cache::remember('search_suggestions_'.$query, now()->addDays(1), function () use ($query){
            $results = array();

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

            return $results;
        });

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

        // Cache::flush();

        $results = Cache::remember("search_results_type:{$type}_detail:{$detail}_sort:{$sort}_order:{$order}_diatary:{$dietary}_child_category:{$child_category}_category:{$category}_brand:{$brand}", now()->addDays(1), function () use ($type, $detail, $data){

            $results = array(
                'stores' => [],
                'products' => [],
                'filter' => null
            );

            $product = new Product();
       
            $casts = $product->casts ?? [];
            $casts['parent_category_name'] = HTMLDecode::class;
            $casts['child_category_name'] = HTMLDecode::class;
            $casts['discount'] = PromotionCalculator::class;
    
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
                    'promotions.name as discount'
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
                        return response()->json(['data' => ['error' => 'Unknown order by option.']], 422);
                    }
    
                    $sort_options = [
                        'rating' => 'avg_rating',
                        'price' => 'price',
                    ];
    
                    if(key_exists($sort,$sort_options)){
                        $base_query = $base_query->orderByRaw($sort_options[$sort] . ' '. $order);
                    } else {
                        return response()->json(['data' => ['error' => 'Unknown sort by option.']], 422);
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
                    // 'path' => 
                    // 'count' => 

                ];
    
                // Filters
                if( count($results['products']) > 0 ){

                    $filter_categories = [];
                    $filter_brands = [];
        
                    foreach( $results['products'] as $product ){
                        if(key_exists($product->brand, $filter_brands)){
                            $filter_brands[$product->brand]++;
                        } else {
                            $filter_brands[$product->brand] = 1;
                        }
       
                        if(key_exists($product->child_category_name, $filter_categories)){
                            $filter_categories[$product->child_category_name]++;
                        } else {
                            $filter_categories[$product->child_category_name] = 1;
                        }
    
                    }

                    $results['filter'] = [];

                    $results['filter']['categories'] = $filter_categories; 
                    $results['filter']['brands'] = $filter_brands; 

                }
    
            }

            return $results;

        });

        return response()->json(['data' => $results]);

    }
    
    
}
