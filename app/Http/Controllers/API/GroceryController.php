<?php

namespace App\Http\Controllers\API;

use App\ChildCategory;
use App\GrandParentCategory;
use App\ParentCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Support\Facades\Cache;
use App\Casts\HTMLDecode;

class GroceryController extends Controller
{
    
    public function categories($store_id)
    {

        $grand_parent_categories = Cache::remember('categories_'.$store_id, now()->addWeek(1), function () use ($store_id){
            $grand_parent_categories = GrandParentCategory::where('store_type_id', $store_id)->get();

            foreach($grand_parent_categories as $category){
                $category->child_categories;
            }

            return $grand_parent_categories;
        });

        return response()->json(['data' => $grand_parent_categories]);
    }

    public function products($parent_cateogy_id){

        $categories = Cache::remember('categoriy_products_'.$parent_cateogy_id, now()->addWeek(1), function () use ($parent_cateogy_id){

            $product = new Product();
            $casts = $product->casts;

            $casts['category_name'] = HTMLDecode::class;
            
            $products = ChildCategory::where('child_categories.parent_category_id', $parent_cateogy_id)
            ->join('category_products','category_products.child_category_id','child_categories.id')
            ->join('products', 'products.id', 'category_products.product_id')
            ->select(
                'products.*',
                'child_categories.id as category_id','child_categories.name as category_name',
                'child_categories.parent_category_id as category_parent_category_id'
            )
            ->withCasts( $casts )
            ->get();

            $categories = [];

            foreach($products as $product){

                if(key_exists($product->category_id , $categories)){
                    $categories[$product->category_id]['products'][] = $product;
                } else {
                    $categories[$product->category_id] = [
                        'id' => $product->category_id,
                        'name' => $product->category_name,
                        'parent_category_id' => $product->category_parent_category_id,
                        'products' => [$product]
                    ];
                }
            }

            $categories = array_values($categories);

            return $categories;

        });

        // return $parent_categories;
        return response()->json(['data' => $categories]);
    }
}
