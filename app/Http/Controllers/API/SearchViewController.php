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
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

// Use elastic search in future

class SearchViewController extends Controller
{

    use SanitizeTrait;

    public function suggestions($query){
        $results = Cache::remember('search_suggestions_'.$query, now()->addMinutes(30), function () use ($query){
            $results = array();

            $stores = StoreType::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(2)->get()->toArray();
            $child_categories = ChildCategory::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(5)->get()->toArray();
            $parent_categories = ParentCategory::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(5)->get()->toArray();
            $products = Product::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->orderByRaw('total_reviews_count / avg_rating desc')->limit(5)->get()->toArray();
    
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
            'data.category' => '',
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
        $brand = $data['brand'] ?? '';

        $results = Cache::remember("search_results_{$type}_{$detail}_{$sort}_{$order}_{$dietary}_{$category}_{$brand}", now()->addMinutes(60), function () use ($type, $detail, $data){

            $results = array(
                'stores' => [],
                'products' => [],
                'filter' => null
            );

            $product = new Product();
       
            $casts = $product->casts ?? [];
            $casts['parent_category_name'] = HTMLDecode::class;
            $casts['discount'] = PromotionCalculator::class;
    
            if($type == 'stores'){
    
                $hour = new OpeningHour();
                $store_type = new StoreType();
    
                $casts = array_merge($hour->casts, $store_type->casts);
    
                $stores = Store::select('stores.*', 'store_types.large_logo', 'store_types.small_logo','closes_at','opens_at')
                ->where('store_type_id', $detail)
                ->join('store_types', 'store_types.id', '=', 'stores.store_type_id')
                ->join('opening_hours', function ($join) {
                    $join->on('opening_hours.store_id', '=', 'stores.id')->where('day_of_week', '=', Carbon::now()->dayOfWeek);
                })
                ->withCasts($casts)->get();
    
                foreach($stores as $store){
                    $store->location;
                }
    
                $results['stores'] = $stores;
    
            } else {
    
                $base_query = ParentCategory::
                select(
                    'products.*',
                    'parent_categories.id as parent_category_id',
                    'parent_categories.name as parent_category_name',
                    'promotions.name as discount'
                )
                ->join('child_categories','child_categories.parent_category_id','parent_categories.id')
                ->join('products','products.parent_category_id','child_categories.id')
                ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
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
                
                if(key_exists('category', $data) && $type != 'parent_categories'){
                    $category = $data['category'];
                    $base_query = $base_query->where('parent_categories.name',$category);
                }
                
                // Get Results
                $results['products'] = $base_query->get();
    
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
       
                        if(key_exists($product->parent_category_name, $filter_categories)){
                            $filter_categories[$product->parent_category_name]++;
                        } else {
                            $filter_categories[$product->parent_category_name] = 1;
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
