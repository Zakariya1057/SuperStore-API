<?php

namespace App\Http\Controllers\API;

use App\ChildCategory;
use App\ParentCategory;
use App\Product;
use App\Store;
use App\StoreType;
use Illuminate\Http\Request;
use App\Casts\HTMLDecode;
use App\Http\Controllers\Controller;
// Use elastic search in future

class SearchViewController extends Controller
{

    public function suggestions($query){

        // Possibly Search For:
        // Food Name
        // Food Category
        // Store Types

        $results = array();

        $stores = StoreType::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->limit(2)->get()->toArray();
        $child_categories = ChildCategory::select('id','name')->where('name', 'like', "%$query%")->orderByRaw('CHAR_LENGTH(name)')->groupBy('name')->limit(4)->get()->toArray();
        $parent_categories = ParentCategory::select('id','name')->where('name', 'like', "%$query%")->orderByRaw('CHAR_LENGTH(name)')->groupBy('name')->limit(4)->get()->toArray();
        $products = Product::select('id','name')->where('name', 'like', "%$query%")->groupBy('name')->orderByRaw('total_reviews_count / avg_rating desc')->limit(5)->get()->toArray();

        $results['stores'] = $stores ?? [];
        $results['parent_categories'] = $parent_categories ?? [];
        $results['child_categories'] = $child_categories ?? [];
        $results['products'] = $products ?? [];

        return response()->json(['data' => $results]);
    }

    public function results(Request $request){
        
        $validated_data = $request->validate([
            'data.type' => 'required',
            'data.detail' => 'required'
        ]);

        $data = $validated_data['data'];

        $detail = htmlentities($data['detail'], ENT_QUOTES);
        $type = strtolower($data['type']);

        $results_data = array(
            'stores' => [],
            'products' => []
        );

        $product = new Product();

        $casts = $product->casts ?? [];
        $casts['parent_category_name'] = HTMLDecode::class;

        if($type == 'stores'){

            $stores = Store::select('stores.*', 'store_types.large_logo', 'store_types.small_logo')
            ->where('store_type_id', $detail)
            ->join('store_types', 'store_types.id', '=', 'stores.store_type_id')
            ->withCasts([
                'large_logo' => Image::class,
                'small_logo' => Image::class,
            ])->get();

            foreach($stores as $store){
                $store->location = $store->location;
            }

            $results_data['stores'] = $stores;

        } elseif($type == 'products'){
            $results_data['products'] = ParentCategory::select('products.*','parent_categories.id as parent_category_id','parent_categories.name as parent_category_name')->where('products.name', 'like', "$detail%")->join('child_categories','child_categories.parent_category_id','parent_categories.id')->join('products','products.parent_category_id','child_categories.id')->withCasts($casts)->orderByRaw('total_reviews_count / avg_rating desc')->get();
        } elseif($type == 'child_categories'){
            $results_data['products'] = ParentCategory::select('products.*','parent_categories.id as parent_category_id','parent_categories.name as parent_category_name')->where('child_categories.name', 'like', "$detail%")->join('child_categories','child_categories.parent_category_id','parent_categories.id')->join('products','products.parent_category_id','child_categories.id')->withCasts($casts)->orderByRaw('total_reviews_count / avg_rating desc')->get();
        } elseif($type == 'parent_categories'){
            $results_data['products'] = ParentCategory::select('products.*','parent_categories.id as parent_category_id','parent_categories.name as parent_category_name')->where('parent_categories.name', 'like', "$detail%")->join('child_categories','child_categories.parent_category_id','parent_categories.id')->join('products','products.parent_category_id','child_categories.id')->withCasts($casts)->orderByRaw('total_reviews_count / avg_rating desc')->get();
        }

        return response()->json(['data' => $results_data]);

    }
    
}
