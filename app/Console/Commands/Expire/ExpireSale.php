<?php

namespace App\Console\Commands\Expire;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        $product_ids = ProductPrice::whereDate('sale_ends_at', '<', Carbon::now())
        ->select('products.*', 'product_prices.id as product_price_id', 'product_prices.sale_ends_at', 'product_prices.price', 'product_prices.old_price')
        ->join('products', 'products.id', 'product_prices.product_id')
        ->groupBy('product_id')->pluck('products.id');

        $this->info( count($product_ids) . ' Product prices with expired sales');

        ProductPrice::whereIn('product_id', $product_ids)->update([
            'price' => DB::raw("`old_price`"), 
            'old_price' => null,
            'sale_ends_at' => null,
            'is_on_sale' => null
        ]);

        $this->info('Finding Grocery Lists Containing Product');

        $list_ids = GroceryListItem::whereIn('product_id', $product_ids)->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');

        $lists = GroceryList::whereIn('id', $list_ids)->get();

        foreach($lists as $list){
            $this->info('List Found Using Product: ' . $list->id);

            $user = User::where('id', $list->user_id)->get()->first();
            Auth::login($user);

            event(new GroceryListChangedEvent($list));
        }
        
        DB::commit();

    }

}
