<?php

namespace App\Services\Category;

use App\Casts\HTMLDecode;
use App\Models\Product;
use App\Models\ChildCategory;
use App\Models\GrandParentCategory;
use App\Models\ParentCategory;
use App\Services\RefinePaginate\RefineService;

class CategoryService {
    
    private $refine_service;

    public function __construct(RefineService $refine_service){
        $this->refine_service = $refine_service;
    }
    
    public function grand_parent_categories(int $company_id){
        $grand_parent_categories = GrandParentCategory::
        where([ 
            ['enabled', 1], 
            ['grand_parent_categories.company_id', $company_id]
        ])
        ->orderBy('index', 'ASC')
        ->with('parent_categories')
        ->get();
        
        return $grand_parent_categories;
    }

    public function child_categories(int $parent_category_id){
        return ChildCategory::where([ ['enabled', 1], ['child_categories.parent_category_id', $parent_category_id] ])->orderBy('index', 'ASC')->get();
    }

    public function category_products(int $child_category_id, $data = []){
        $product = new Product();
        $casts = $product->casts;

        $region_id = $data['region_id'];
        $supermarket_chain_id = $data['supermarket_chain_id'];

        $casts['category_name'] = HTMLDecode::class;
        
        $base_query = ChildCategory::where('child_categories.id', $child_category_id)
        ->select(
            'products.*',

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',
            
            'child_categories.id as child_category_id',
            'child_categories.name as child_category_name', 
            'child_categories.index as child_category_index',

            'parent_categories.id as parent_category_id', 
            'parent_categories.name as parent_category_name',

            'product_groups.name as product_group_name',

            'promotions.supermarket_chain_id as promotion_supermarket_chain_id',
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

        ->join('category_products','category_products.child_category_id','child_categories.id')
        ->join('parent_categories','parent_categories.id','child_categories.parent_category_id')
        ->join('products', 'products.id', 'category_products.product_id')
        ->join('product_prices','product_prices.product_id','products.id')
        ->leftJoin('product_groups','product_groups.id','category_products.product_group_id')
        ->leftJoin('promotions', 'promotions.id','=','product_prices.promotion_id')
        ->where([ ['products.enabled', 1], ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id] ])
        ->withCasts( $casts );
        
        $pagination_data = $this->refine_service->refine_results($base_query, $data);

        $products = $pagination_data['products'];
        $paginate = $pagination_data['paginate'];

        $category = null;

        foreach($products as $index => $product){
            if($index == 0){
                $category = [
                    'id' => $product->child_category_id,
                    'name' => $product->child_category_name,
                    'index' => $product->child_category_index,
                    'parent_category_id' => $product->parent_category_id,
                    'company_id' => $product->company_id,
                    'products' => [],

                    'paginate' => $paginate
                ];
            }

            $category['products'][] = $product;
        }

        return $category;

    }

    public function featured(int $region_id, int $supermarket_chain_id){

        $product = new Product();
        $casts = $product->casts;

        $categories = ParentCategory::groupBy('name')
        ->inRandomOrder()
        ->limit(20)
        ->get();

        $results = [];

        foreach($categories as $category){
            $products = ChildCategory::where([ 
                ['product_prices.region_id', $region_id], 
                ['product_prices.supermarket_chain_id', $supermarket_chain_id], 
                ['child_categories.parent_category_id', $category->id]
            ])
            ->select(
                'products.*',
                
                'product_prices.price', 
                'product_prices.old_price',
                'product_prices.is_on_sale', 
                'product_prices.sale_ends_at', 
                'product_prices.promotion_id', 
                'product_prices.region_id',
                'product_prices.supermarket_chain_id',

                'parent_categories.id as parent_category_id',
                'parent_categories.name as parent_category_name'
            )
            
            ->join('category_products','category_products.child_category_id','child_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->join('product_prices', 'products.id','=','product_prices.product_id')
            ->join('product_groups','product_groups.id','category_products.product_group_id')
            ->join('parent_categories','category_products.parent_category_id','parent_categories.id')

            ->limit(15)->groupBy('category_products.product_id')->withCasts($casts)->get();

            $category->enabled = (bool)$category->enabled;

            $category->products = $products;
            $results[] = $category; 
        }

        return $results ?? [];
    }

}
?>