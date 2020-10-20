<?php

namespace App\Traits;

use App\Casts\HTMLDecode;
use App\GroceryList;
use App\GroceryListItem;
use App\Product;
use App\Casts\PromotionCalculator;
use App\ChildCategory;
use App\FeaturedItem;
use App\ParentCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait GroceryListTrait {

    protected function update_list($list){
        // Get all products for list.
        // Check if any promotion
        // Multiple quantity with price
        
        if($list instanceOf GroceryList){

            $casts = ['promotion' => PromotionCalculator::class];

            $items =  GroceryListItem::
            join('products','products.id','grocery_list_items.product_id')
            ->leftJoin('promotions','promotions.id','products.promotion_id')
            ->where('list_id',$list->id)
            ->select(
                'products.id as product_id',
                'grocery_list_items.quantity as product_quantity',
                'products.price as product_price',
                'grocery_list_items.total_price',
                'grocery_list_items.ticked_off',
                'promotions.id as promotion_id',
                'promotions.name as promotion'
            )
            ->withCasts($casts)
            ->get();

            $status = 'Not Started';

            $total_price = 0;

            $total_items = count($items);
            $ticked_off_items = 0;

            $promotions = [];

            foreach($items as $item){

                if(!is_null($item->promotion)){
                    $promotion = (object)$item->promotion;

                    if(key_exists($promotion->id,$promotions)){
                        $promotions[$promotion->id]['products'][] = $item;
                    } else {
                        $promotions[$promotion->id] = [
                            'details' => $promotion,
                            'products' => [$item],
                         ];
                    }
                }

                $total_price += $item->total_price;

                if($item->ticked_off == 1){
                    $ticked_off_items++;
                }
            }

            $new_total_price = 0;

            $update = ['old_total_price' => NULL, 'total_price' => $total_price, 'status' => $status];

            foreach($promotions as $promotion){
                $promotion = (object)$promotion;

                $details = $promotion->details;
                $products = $promotion->products;

                $product_count = count($products);

                $quantity = $details->quantity;

                $new_total = 0;

                Log::debug("$product_count >= $quantity");

                if($product_count >= $quantity){

                    $highest_price = 0;
                    $previous_total_price = 0;
                    $total_quantity = 0;

                    // Get the most expensive item
                    foreach($products as $product){
                        $previous_total_price = $previous_total_price + $product->total_price;
                        $total_quantity = $total_quantity + $product->product_quantity;

                        if($product->product_price > $highest_price){
                            $highest_price = $product->product_price;
                        }
                    }
                    
                    $remainder = ($total_quantity % $promotion->quantity);
                    $goes_into_fully = floor($total_quantity / $promotion->quantity);

                    Log::debug('Old Promoted Products total: '.$previous_total_price);
                    Log::debug('Most Expensive Price: '.$highest_price);

                    if( !is_null($promotion->for_quantity)){
                        $new_total = ( $goes_into_fully * ( $promotion->for_quantity * $highest_price)) + ($remainder * $highest_price);
                    } else {
                        Log::debug("($goes_into_fully * $promotion->price) + ($remainder * $highest_price);");
                        $new_total = ($goes_into_fully * $promotion->price) + ($remainder * $highest_price);
                    }

                    $new_total_price = ($total_price - $previous_total_price) + $new_total;

                    Log::debug('Old Total Price: '.$new_total);

                    if($new_total != $previous_total_price){
                        $update['old_total_price'] = $total_price;
                        $update['total_price'] = $new_total_price;
                    }
                    // Log::debug('New New Total Price: '.$new_total_price);

                }

            }

            if($ticked_off_items == $total_items){
                $update['status'] = 'Completed';
            } elseif($ticked_off_items > 0){
                $update['status'] = 'In Progress';
            }

            GroceryList::where('id',$list->id)->update($update);
        }

    }

    protected function show_list($list_id, $user_id){

        $product = new Product();

        $list = GroceryList::where([ [ 'id', $list_id],['user_id', $user_id] ])->first();

        if(!$list){
            return response()->json(['data' => ['error' => 'No List Found For User']], 404);
        }

        $product = new Product();

        $casts = $product->casts ?? [];
        $casts['promotion'] = PromotionCalculator::class;

        $list_items = GroceryListItem::where([ ['list_id', $list->id] ])
        ->select([
            'grocery_list_items.id as id',
            'grocery_list_items.product_id as product_id',
            'parent_categories.name as category_name',
            'parent_categories.id as category_id',
            'products.name as name',
            'grocery_list_items.total_price as total_price',
            'products.price as price',
            'grocery_list_items.quantity as quantity',
            'category_aisles.name as aisle_name',
            'products.weight as weight',
            'products.small_image as small_image',
            'products.large_image as large_image',
            'grocery_list_items.ticked_off as ticked_off',
            'promotions.id as promotion_id',
            'promotions.name as promotion'
        ])
        ->join('parent_categories', 'parent_categories.id','=','grocery_list_items.parent_category_id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->leftJoin('promotions', 'promotions.id','=','products.promotion_id')
        ->leftJoin('category_aisles', function ($join) use($list) {
            $join->on('category_aisles.category_id','=','grocery_list_items.parent_category_id')
                 ->where('category_aisles.store_id',$list->store_id);
        })
        ->orderBy('grocery_list_items.parent_category_id','ASC')
        ->withCasts(
            $casts
        )
        ->get();

        $categories = [];

        foreach($list_items as $list_item){

            $category_id = $list_item->category_id;
            $category_name = html_entity_decode($list_item->category_name, ENT_QUOTES);
            $aisle_name = $list_item->aisle_name;
            
            unset($list_item->category_name);
            unset($list_item->aisle_name);
            unset($list_item->category_id);
            
            if(key_exists($category_name, $categories)){
                $categories[$category_name]['items'][] = $list_item;
            } else {
                $categories[$category_name] = [ 
                    'id' => $category_id,
                    'name' =>  $category_name,
                    'aisle_name' => $aisle_name,
                    'items' => [$list_item]
                ];
            }
            
        }

        $list->categories = array_values($categories);

        return $list;
    }

    public function grocery_items($user_id){
        $product = new Product();
        return GroceryList::where('user_id', $user_id)->select('products.*')->join('grocery_list_items','grocery_list_items.list_id','grocery_lists.id')->join('products', 'products.id','=','grocery_list_items.product_id')->orderBy('grocery_lists.updated_at', 'DESC')->limit(15)->withCasts($product->casts)->get();
    }

    public function featured_items(){
        $product = new Product();
        return FeaturedItem::select('products.*')->whereRaw('type = "products" AND week = WEEK(NOW()) AND year = YEAR(NOW())')->join('products', 'products.id','=','featured_id')->orderBy('featured_items.updated_at', 'DESC')->limit(10)->withCasts($product->casts)->get();
    }

    protected function item_price($product_id,$quantity=1){
        $product = Product::where('products.id',$product_id)->leftJoin('promotions', 'promotions.id','=','products.promotion_id')->select('products.price', 'promotions.id as promotion_id','promotions.name as promotion')->withCasts(['promotion' => PromotionCalculator::class])->get()->first();
    
        $price = $product->price;
        $total = 0;

        if($quantity == 0){
            return $total;
        }

        if(!is_null($product->promotion)){
            $promotion_details = (object)$product->promotion;
            $remainder = ($quantity % $promotion_details->quantity);
            $goes_into_fully = floor($quantity / $promotion_details->quantity);

            if($quantity < $promotion_details->quantity){
                $total = $quantity * $price;   
            } else {

                if( !is_null($promotion_details->for_quantity)){
                    $total = ( $goes_into_fully * ( $promotion_details->for_quantity * $price)) + ($remainder * $price);
                } else {
                    $total = ($goes_into_fully * $promotion_details->price) + ($remainder * $price);
                }
            }

        } else {
            $total = $quantity * $price;
        }
        
        return $total;

    }

    function home_categories(){

        $product = new Product();
        $casts = $product->casts;

        $categories = FeaturedItem::select('parent_categories.*')->whereRaw('type = "categories" AND week = WEEK(NOW()) AND year = YEAR(NOW())')->join('parent_categories','parent_categories.id','featured_id')->withCasts(['name' => HTMLDecode::class])->limit(10)->get();

        $results = [];
        foreach($categories as $category){
            $results[$category->name] = ChildCategory::where('child_categories.parent_category_id', $category->id)
            ->join('category_products','category_products.child_category_id','child_categories.id')
            ->join('products','products.id','category_products.product_id')
            ->select(
                'products.*'
            )->limit(15)->withCasts($casts)->get();

        }

        return $results;
    }

    function lists_progress($user_id){
        return GroceryList::where('user_id', $user_id)->orderByRaw('(ticked_off_items/ total_items) DESC, `grocery_lists`.`updated_at` DESC')->get();
    }

}

?>