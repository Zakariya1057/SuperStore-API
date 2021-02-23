<?php

namespace App\Services;

use App\Casts\HTMLDecode;
use App\Models\Product;
use App\Models\ChildCategory;
use App\Models\GrandParentCategory;
use App\Models\FeaturedItem;


class CategoryService {
    
    public function grocery_categories($store_type_id){

        $grand_parent_categories = GrandParentCategory::where('store_type_id', $store_type_id)->get();

        foreach($grand_parent_categories as $category){
            $category->child_categories;
        }

        return $grand_parent_categories;
    }

    public function grocery_products($parent_cateogy_id){

        $product = new Product();
        $casts = $product->casts;

        $casts['category_name'] = HTMLDecode::class;
        
        $products = ChildCategory::where('child_categories.parent_category_id', $parent_cateogy_id)
        ->join('category_products','category_products.child_category_id','child_categories.id')
        ->join('parent_categories','parent_categories.id','child_categories.parent_category_id')
        ->join('products', 'products.id', 'category_products.product_id')
        ->select(
            'products.*',
            'child_categories.id as child_category_id','child_categories.name as child_category_name',
            'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name'
        )
        ->withCasts( $casts )
        ->get();

        $categories = [];

        foreach($products as $product){
            
            if(key_exists($product->child_category_id , $categories)){
                $categories[$product->child_category_id]['products'][] = $product;
            } else {
                $categories[$product->child_category_id] = [
                    'id' => $product->child_category_id,
                    'name' => $product->child_category_name,
                    'parent_category_id' => $product->parent_category_id,
                    'products' => [$product]
                ];
            }
        }

        $categories = array_values($categories);

        return $categories;

    }

    public function featured(){

        $product = new Product();
        $casts = $product->casts;

        $categories = FeaturedItem::select('parent_categories.*')->whereRaw('type = "categories"')->join('parent_categories','parent_categories.id','featured_id')->withCasts(['name' => HTMLDecode::class])->limit(10)->get();

        $results = [];
        foreach($categories as $category){
            $results[$category->name] = ChildCategory::where('child_categories.parent_category_id', $category->id)
            ->join('category_products','category_products.child_category_id','child_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
            ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
            ->limit(15)->groupBy('category_products.product_id')->withCasts($casts)->get();

        }

        return $results;
    }

}
?>