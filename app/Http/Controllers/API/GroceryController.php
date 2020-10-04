<?php

namespace App\Http\Controllers\API;

use App\ChildCategory;
use App\GrandParentCategory;
use App\ParentCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GroceryController extends Controller
{
    
    public function categories($store_id)
    {
        $grand_parent_categories = GrandParentCategory::where('store_type_id', $store_id)->get();

        foreach($grand_parent_categories as $category){
            $category->child_categories;
        }

        return response()->json(['data' => $grand_parent_categories]);
    }

    public function products($parent_cateogy_id){

        $parent_categories = ChildCategory::where('parent_category_id', $parent_cateogy_id)->get();

        foreach($parent_categories as $category){
            $products = $category->products;
            $category->products_count = count($products);
        }

        // return $parent_categories;
        return response()->json(['data' => $parent_categories]);
    }
}
