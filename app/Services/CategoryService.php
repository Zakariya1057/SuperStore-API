<?php

namespace App\Services;

use App\Casts\HTMLDecode;
use App\Models\Product;
use App\Models\ChildCategory;
use App\Models\GrandParentCategory;
use App\Models\FeaturedItem;


class CategoryService {
    
    private $promotion_service, $paginate_service;

    public function __construct(PromotionService $promotion_service, PaginateService $paginate_service){
        $this->paginate_service = $paginate_service;
        $this->promotion_service = $promotion_service;
    }
    
    public function grand_parent_categories($store_type_id){
        $grand_parent_categories = GrandParentCategory::where('grand_parent_categories.store_type_id', $store_type_id)->get();

        foreach($grand_parent_categories as $category){
            $category->parent_categories;
        }

        return $grand_parent_categories;
    }

    public function child_categories($parent_category_id){
        return ChildCategory::where('child_categories.parent_category_id', $parent_category_id)->get();
    }

    public function category_products($child_category_id){
        $product = new Product();
        $casts = $product->casts;

        $casts['category_name'] = HTMLDecode::class;
        
        $base_query = ChildCategory::where('child_categories.id', $child_category_id)
        ->select(
            'products.*',
            'child_categories.store_type_id as store_type_id',
            'child_categories.id as child_category_id','child_categories.name as child_category_name',
            'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name',

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
        
        ->join('category_products','category_products.child_category_id','child_categories.id')
        ->join('parent_categories','parent_categories.id','child_categories.parent_category_id')
        ->join('products', 'products.id', 'category_products.product_id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')

        ->withCasts( $casts );
        // ->get();

        $pagination_data = $this->paginate_service->paginate_results($base_query);

        $products = $pagination_data['products'];
        $paginate = $pagination_data['paginate'];

        $category = null;

        foreach($products as $index => $product){
            $this->promotion_service->set_product_promotion($product);

            if($index == 0){
                $category = [
                    'id' => $product->child_category_id,
                    'name' => $product->child_category_name,
                    'parent_category_id' => $product->parent_category_id,
                    'store_type_id' => $product->store_type_id,
                    'products' => [],

                    'paginate' => $paginate
                ];
            }

            $category['products'][] = $product;
        }

        // $results['products'] = $pagination_data['products'];
        // $results['paginate'] = ;

        // $categories = [];

        // foreach($products as $product){
            
        //     $this->promotion_service->set_product_promotion($product);

        //     if(key_exists($product->child_category_id , $categories)){
        //         $categories[$product->child_category_id]['products'][] = $product;
        //     } else {
        //         $categories[$product->child_category_id] = [
        //             'id' => $product->child_category_id,
        //             'name' => $product->child_category_name,
        //             'parent_category_id' => $product->parent_category_id,
        //             'store_type_id' => $product->store_type_id,
        //             'products' => [$product]
        //         ];
        //     }
        // }

        return $category;

    }

    // public function grocery_categories($store_type_id){
    //     $grand_parent_categories = GrandParentCategory::where('grand_parent_categories.store_type_id', $store_type_id)->get();

    //     foreach($grand_parent_categories as $category){
    //         $category->parent_categories;
    //     }

    //     return $grand_parent_categories;
    // }

    // public function grocery_products($parent_cateogy_id){

    //     $product = new Product();
    //     $casts = $product->casts;

    //     $casts['category_name'] = HTMLDecode::class;
        
    //     $products = ChildCategory::where('child_categories.parent_category_id', $parent_cateogy_id)
    //     ->join('category_products','category_products.child_category_id','child_categories.id')
    //     ->join('parent_categories','parent_categories.id','child_categories.parent_category_id')
    //     ->join('products', 'products.id', 'category_products.product_id')
    //     ->select(
    //         'products.*',
    //         'child_categories.store_type_id as store_type_id',
    //         'child_categories.id as child_category_id','child_categories.name as child_category_name',
    //         'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name'
    //     )
    //     ->withCasts( $casts )
    //     ->get();

    //     $categories = [];

    //     foreach($products as $product){
            
    //         if(key_exists($product->child_category_id , $categories)){
    //             $categories[$product->child_category_id]['products'][] = $product;
    //         } else {
    //             $categories[$product->child_category_id] = [
    //                 'id' => $product->child_category_id,
    //                 'name' => $product->child_category_name,
    //                 'parent_category_id' => $product->parent_category_id,
    //                 'store_type_id' => $product->store_type_id,
    //                 'products' => [$product]
    //             ];
    //         }
    //     }

    //     $categories = array_values($categories);

    //     return $categories;

    // }

    public function featured($store_type_id){

        $product = new Product();
        $casts = $product->casts;

        $categories = FeaturedItem::select('parent_categories.*')
        ->where([ ['parent_categories.store_type_id', $store_type_id],['type', 'categories'] ])
        ->join('parent_categories','parent_categories.id','featured_id')
        ->withCasts(['name' => HTMLDecode::class])->limit(10)->get();

        $results = [];

        foreach($categories as $category){
            $products = ChildCategory::where('child_categories.parent_category_id', $category->id)
            ->join('category_products','category_products.child_category_id','child_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->join('parent_categories','category_products.parent_category_id','parent_categories.id')
            ->select('products.*' ,'parent_categories.id as parent_category_id', 'parent_categories.name as parent_category_name')
            ->limit(15)->groupBy('category_products.product_id')->withCasts($casts)->get();

            $category->products = $products;
            $results[] = $category; 
        }

        return $results ?? [];
    }

}
?>