<?php

namespace App\Console\Commands\Expire;

use App\Events\GroceryListChangedEvent;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\ProductPrice;
use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpirePromotion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:promotion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all expired promotions from database.';

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
        $this->info('Fetching All Expired Promotions');

        DB::beginTransaction();

        $promotion_ids = Promotion::whereDate('ends_at', '>', Carbon::now())->pluck('promotions.id');
        $promotions_count = count($promotion_ids);

        $this->info( $promotions_count . ' Expired Promotions Found');

        $product_ids = ProductPrice::whereIn('promotion_id', $promotion_ids)->pluck('product_id');

        $list_ids = GroceryListItem::whereIn('product_id', $product_ids)->groupBy('grocery_list_items.list_id')->pluck('grocery_list_items.list_id');
        $lists = GroceryList::whereIn('id', $list_ids)->get();

        foreach($lists as $list){
            $user = User::where('id', $list->user_id)->get()->first();
            Auth::login($user);

            $this->info('Grocery List Found Using Product: ' . $list->id);
            event(new GroceryListChangedEvent($list));
        }

        ProductPrice::whereIn('product_id', $product_ids)->update(['promotion_id' => null]);
        Promotion::whereIn('id', $promotion_ids)->delete();

        // Get all promotions without products
        $empty_promotions_ids = Promotion::leftJoin('product_prices', 'promotions.id', 'product_prices.promotion_id')->whereNull('product_id')->pluck('promotions.id');
        $this->info('Empty Promotions Without Any Products: ' . count($empty_promotions_ids));
        Promotion::whereIn('id', $empty_promotions_ids)->delete();

        DB::commit();

    }

}
