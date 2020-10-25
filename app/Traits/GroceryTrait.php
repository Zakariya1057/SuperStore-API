<?php

namespace App\Traits;

use App\Casts\HTMLDecode;
use App\Product;
use App\ChildCategory;
use App\GrandParentCategory;
use App\FeaturedItem;
use App\GroceryList;

trait GroceryTrait {

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

    }

    public function home_categories(){

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
            ->limit(15)->withCasts($casts)->get();

        }

        return $results;
    }

    public function lists_progress($user_id){
        return GroceryList::where('user_id', $user_id)->orderByRaw('(ticked_off_items/ total_items) DESC, `grocery_lists`.`updated_at` DESC')->get();
    }

    public function featured_items(){
        $product = new Product();
        return FeaturedItem::select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
        ->whereRaw('type = "products"')
        ->join('products', 'products.id','=','featured_id')
        ->join('category_products','category_products.product_id','products.id')
        ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
        ->orderBy('featured_items.updated_at', 'DESC')
        ->limit(10)->groupBy('category_products.product_id')->withCasts($product->casts)->get();
    }

}

?>