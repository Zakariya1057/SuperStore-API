<?php

namespace App\Console\Commands\Expire;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpireSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all expired product sales from database.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Fetch all expired promotions, get all matching products remove product_id and then delete them
        $this->info('Fetching All Expired Sales');

        DB::beginTransaction();

        $product_prices = ProductPrice::where('sale_ends_at', '<', Carbon::now())
        ->select('products.*', 'product_prices.id as product_price_id', 'product_prices.sale_ends_at', 'product_prices.price', 'product_prices.old_price')
        ->join('products', 'products.id', 'product_prices.product_id')
        ->get();

        $product_ids = [];
        $count = count($product_prices);

        $this->info($count . ' Product prices with expired sales');

        foreach($product_prices as $product){
            $product_price_id = $product->product_price_id;

            $product_id = $product->id;
            $name = $product->name;
            $ends_at = $product->sale_ends_at;

            $price = $product->price;
            $old_price = $product->old_price;

            $this->info("Expired Sale Found: $ends_at [$product_id] $name - $price -> $old_price");

            $product_ids[] = $product_id;

            ProductPrice::where('id', $product_price_id)->update([
                'price' => $old_price, 
                'old_price' => null,
                'sale_ends_at' => null,
                'is_on_sale' => null
            ]);
        }

        $this->info('Finding Grocery Lists Containing Product');

        $list_ids = GroceryListItem::whereIn('product_id', array_unique($product_ids))->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');

        $lists = GroceryList::whereIn('id', $list_ids)->get();

        foreach($lists as $list){
            $this->info('List Found Using Product: ' . $list->id);

            $user = User::where('id', $list->user_id)->get()->first();
            Auth::login($user);

            event(new GroceryListChangedEvent($list));
        }

        if($count > 0){
            Artisan::call('cache:home');
        }
        
        DB::commit();

    }

}
