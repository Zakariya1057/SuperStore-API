<?php

namespace App\Services\GroceryList;

use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\Product;
use App\Services\Product\PromotionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroceryListSharedService {

    private $promotion_service;

    function __construct(PromotionService $promotion_service)
    {
        $this->promotion_service = $promotion_service;
    }
    
    ////////////////////////////////////////////    SELECT List    //////////////////////////////////////////// 

    public function show_list(int $list_id, int $user_id){

        $user = Auth::user();
        $region_id = $user->region_id;

        $product = new Product();

        $list = GroceryList::where([ ['id', $list_id],['user_id', $user_id] ])->first();

        if(!$list){
            throw new Exception('No list found for user.', 404);
        }

        $supermarket_chain_id = $list->supermarket_chain_id;

        $product = new Product();
        $casts = $product->casts;

        $items = GroceryListItem::where([ ['list_id', $list->id], ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id] ])
        ->select([
            'grocery_list_items.id as id',
            'grocery_list_items.product_id as product_id',
            'parent_categories.name as category_name',
            'parent_categories.id as category_id',
            'products.name as name',
            'grocery_list_items.total_price as total_price',
            'products.currency as currency',
            'grocery_list_items.quantity as quantity',
            'products.weight as weight',
            'products.small_image as small_image',
            'products.large_image as large_image',
            'grocery_list_items.ticked_off as ticked_off',

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',

            'promotions.id as promotion_id',
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
        ])
        ->join('parent_categories', 'parent_categories.id','=','grocery_list_items.parent_category_id')
        ->join('products', 'products.id','=','grocery_list_items.product_id')
        ->join('product_prices','product_prices.product_id','products.id')
        ->leftJoin('promotions', 'promotions.id','=','product_prices.promotion_id')
        ->orderBy('grocery_list_items.id','ASC')
        ->withCasts(
            $casts
        )
        ->get();

        $list->categories = $this->group_by_categories($items);

        return $list;
    }

    private function group_by_categories($items){
        $categories = [];

        foreach($items as $item){

            $this->promotion_service->set_product_promotion($item);

            $category_id = $item->category_id;
            $category_name = html_entity_decode($item->category_name, ENT_QUOTES);
            
            unset($item->category_name);
            unset($item->category_id);
            
            if(key_exists($category_name, $categories)){
                $categories[$category_name]['items'][] = $item;
            } else {
                $categories[$category_name] = [ 
                    'id' => $category_id,
                    'name' =>  $category_name,
                    'aisle_name' => '',
                    'items' => [$item]
                ];
            }
            
        }

        return array_values($categories);

    }

    public function item_price($product_id, $quantity=1, ?int $region_id = null){
        if(is_null($region_id)){
            $region_id = Auth::user()->region_id;
        }

        $product = Product::join('product_prices', 'products.id','=','product_prices.product_id')
        ->select(
            'products.*',

            'product_prices.price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',
        )
        ->where([['region_id', $region_id], ['products.id',$product_id]])
        ->get()->first();

        if(is_null($product)){
            return 0;
        }

        $product->region_id = $region_id;
        $promotion = $product->promotion;
    
        return $this->calculate_item_price($product, $promotion, $quantity);

    }

    private function calculate_item_price($product, $promotion, int $quantity){
        
        $price = $product->price;

        // Check if sale expired, use old price instead of new
        if(!is_null($product->sale_ends_at)){
            if(Carbon::now()->diffInDays($product->sale_ends_at) < 0){
                $price = $product->old_price;
            }
        }

        $total = 0;

        if($quantity == 0){
            return $total;
        }

        if(!is_null($promotion) && $promotion->enabled){

            $promotion_expired = false;

            if(!is_null($promotion->ends_at)){
                if(Carbon::now()->diffInDays($promotion->ends_at) < 0){
                    $promotion_expired = true;
                }
            }

            if(!$promotion_expired){

                if(!is_null($promotion->minimum)){
                    $minimum = $promotion->minimum;
                    $promotion_price = $promotion->price;
    
                    if($quantity >= $minimum){
                        $total = $quantity * $promotion_price;
                    } else {
                        $total = $quantity * $price;
                    }
    
                } 
                // else if (!is_null($promotion->maximum)){
                    // $maximum_quantity = $promotion->maximum;

                    // // If 4 products, max is 2. 
                    // // Then promotion price for two first.
                    // // Then normal for the rest of products
                    // for($i = 0; $i < $quantity; $i++){
                    //     if($i < $maximum_quantity){
                    //         $total += $promotion->price;
                    //     } else {
                    //         $total += $product->total_price;
                    //     }
                    // }
                // } 
                else if (!is_null($promotion->quantity)){
                    $remainder = ($quantity % $promotion->quantity);
                    $goes_into_fully = floor($quantity / $promotion->quantity);
    
                    if($quantity < $promotion->quantity){
                        $total = $quantity * $price;   
                    } else {
    
                        if( !is_null($promotion->for_quantity)){
                            $total = ( $goes_into_fully * ( $promotion->for_quantity * $price)) + ($remainder * $price);
                        } else {
                            $total = ($goes_into_fully * $promotion->price) + ($remainder * $price);
                        }
                    }
                } else {
                    $total = $quantity * $price;
                }

            } else {
                $total = $quantity * $price;
            }

        } else {
            $total = $quantity * $price;
        }
        
        return $total;
    }

    ////////////////////////////////////////////    SELECT List    //////////////////////////////////////////// 



    ////////////////////////////////////////////    UPDATE List    //////////////////////////////////////////// 

    public function update_list(GroceryList $list, ?int $region_id = null, int $supermarket_chain_id = null){
        if(is_null($region_id) || is_null($supermarket_chain_id)){
            $user = Auth::user();
            $region_id = $user->region_id;
            $supermarket_chain_id = $user->supermarket_chain_id;
        }

        $product = new Product();
        $casts = $product->casts;

        $items = GroceryListItem::
        join('products','products.id','grocery_list_items.product_id')
        ->join('product_prices','product_prices.product_id','products.id')
        ->leftJoin('promotions', 'promotions.id','=','product_prices.promotion_id')
        ->select(
            'products.id as product_id',
            'grocery_list_items.quantity as product_quantity',
            'grocery_list_items.ticked_off',

            'product_prices.price as product_price', 
            'product_prices.old_price',
            'product_prices.is_on_sale', 
            'product_prices.sale_ends_at', 
            'product_prices.promotion_id as promotion_id', 
            'product_prices.region_id',
            'product_prices.supermarket_chain_id',

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
        ->where([ ['list_id',$list->id], ['product_prices.region_id', $region_id], ['product_prices.supermarket_chain_id', $supermarket_chain_id] ])
        ->withCasts($casts)
        ->get();

        $list_data = $this->group_list_items($items);

        $promotions = $list_data['promotions'];
        $total_price = $list_data['total_price'];
        $total_price_without_promotion_items =  $list_data['total_price_without_promotion_items'];
        $ticked_off_items = $list_data['ticked_off_items'];

        $update = [];

        $price_data = $this->parse_promotion_data($promotions, $total_price, $total_price_without_promotion_items);

        $update['ticked_off_items'] = $ticked_off_items;
        $update['total_items'] = count($items);


        $update['old_total_price'] = $price_data['old_total_price'];
        $update['total_price'] = $price_data['total_price'];

        $update['status'] = $this->get_list_status(count($items), $ticked_off_items);

        DB::transaction(function () use($list, $update){
            GroceryList::where('id',$list->id)->update($update);
        }, 5);
        
    }

    private function parse_promotion_data($promotions, $total_price, $total_price_without_promotion_items): array {

        $new_promotion_total_price = 0;

        // For All Products Within Promotion Group
        foreach($promotions as $promotion){
            $promotion = (object)$promotion;

            $promotion_details = $promotion->details;
            $products = $promotion->products;

            $total_quantity = count($products);
            $total_items_price = 0;

            foreach($products as $product){
                $total_items_price += $product->total_price;
            }
            
            if(!is_null($promotion_details->quantity)){

                $quantity = $promotion_details->quantity;

                $new_total = 0;

                if($total_quantity >= $quantity){

                    $highest_price = 0;
                    $previous_total_price = 0;
    
                    // Get the most expensive item
                    foreach($products as $product){
                        $previous_total_price = $previous_total_price + $product->total_price;
    
                        if($product->product_price > $highest_price){
                            $highest_price = $product->product_price;
                        }
                    }
                    
                    $remainder = ($total_quantity % $promotion_details->quantity);
                    $goes_into_fully = floor($total_quantity / $promotion_details->quantity);
    
                    if( !is_null($promotion_details->for_quantity)){
                        $new_total = ( $goes_into_fully * ( $promotion_details->for_quantity * $highest_price)) + ($remainder * $highest_price);
                    } else {
                        $new_total = ($goes_into_fully * $promotion_details->price) + ($remainder * $highest_price);
                    }

                    $new_promotion_total_price += $new_total;

                } else {
                    $new_promotion_total_price += $total_items_price;
                }

            } else if(!is_null($promotion_details->minimum)){

                $minimum_quantity = $promotion_details->minimum;

                if($total_quantity >= $minimum_quantity){
                    $new_promotion_total_price += $total_quantity * $promotion_details->price;
                } else {
                    $new_promotion_total_price += $total_items_price;
                }
            } else if(!is_null($promotion_details->maximum)){

                $maximum_quantity = $promotion_details->maximum;

                // If 4 products, max is 2. 
                // Then promotion price for two first.
                // Then normal for the rest of products
                $index = 0;

                Log::debug("Products Count: " . sizeof($products));

                foreach($products as $product){
                    if($index < $maximum_quantity){
                        Log::debug("$index | Product Price: ". $promotion_details->price);
                        $new_promotion_total_price += $promotion_details->price;
                    } else {
                        Log::debug("$index | Total Price: ". $product->product_price);
                        $new_promotion_total_price += $product->product_price;
                    }

                    $index++;
                }

            } else {
                $new_promotion_total_price += $total_items_price;
            }

        }

        if($new_promotion_total_price < $total_price){
            return ['total_price' => $total_price_without_promotion_items + $new_promotion_total_price, 'old_total_price' => $total_price];
        } else {
            return ['total_price' => $total_price, 'old_total_price' => NULL];
        }

    }

    private function group_list_items($items): array{

        $promotions = [];
        $total_price = 0;
        $total_price_without_promotion_items = 0;
        $ticked_off_items = 0;

        foreach($items as $item){

            $this->promotion_service->set_product_promotion($item);

            $promotion = $item->promotion;

            $item->price = $item->product_price;

            $item->total_price = $this->calculate_item_price($item, $promotion, $item->product_quantity);

            for($i =0; $i < $item->product_quantity; $i++){
                if(!is_null($promotion) && $promotion->enabled){
    
                    $promotion_expired = false;
                    
                    if(!is_null($promotion->ends_at)){
                        if(Carbon::now()->diffInDays($promotion->ends_at) < 0){
                            $promotion_expired = true;
                        }
                    }
                    
                    if(!$promotion_expired){
                        if(key_exists($promotion->id,$promotions)){
                            $promotions[$promotion->id]['products'][] = $item;
                        } else {
                            $promotions[$promotion->id] = [
                                'details' => $promotion,
                                'products' => [$item],
                            ];
                        }
                    }
                } else {
                    $total_price_without_promotion_items += $item->product_price;
                }
    
                $total_price += $item->product_price;
            }


            if($item->ticked_off == 1){
                $ticked_off_items++;
            }
        }

        return [
            'promotions' => $promotions, 
            'total_price' => $total_price, 
            'total_price_without_promotion_items' => $total_price_without_promotion_items, 
            'ticked_off_items' => $ticked_off_items
        ];
    }

    private function get_list_status(int $total_items, int $ticked_off_items): string { 
        if($total_items == 0 || $ticked_off_items == 0){
            return 'Not Started';
        } else if ($ticked_off_items == $total_items){
            return 'Completed';
        } else {
            return 'In Progress';
        }
    }

    ////////////////////////////////////////////    Update List    //////////////////////////////////////////// 

}
?>