<?php

namespace App\Console\Commands\Cache;

use App\Models\StoreType;
use App\Services\Category\CategoryService;
use App\Services\Product\ProductService;
use App\Services\Product\PromotionService;
use App\Services\Store\StoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheHome extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:home';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches home for decreasing loading speed.';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $store_service, $category_service, $promotion_service, $product_service;

    function __construct(CategoryService $category_service, StoreService $store_service, PromotionService $promotion_service, ProductService $product_service){
        parent::__construct();
        $this->store_service = $store_service;
        $this->promotion_service = $promotion_service;
        $this->category_service = $category_service;
        $this->product_service = $product_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('---- Weekly Home Cache Start ----');

        foreach(StoreType::get() as $store_type){

            $store_type_id = $store_type->id;
            $store_name = $store_type->name;

            $this->info("---- $store_name Home Cache Start ----");
            
            $cache_key = 'home_page_'. $store_type_id;

            $this->info("Caching Featured Products");
            $featured_items = $this->product_service->featured($store_type_id);

            $this->info("Caching Featured Stores");
            $stores = $this->store_service->stores_by_type($store_type_id);

            $this->info("Caching Featured Categories");
            $categories = $this->category_service->featured($store_type_id);

            $this->info("Caching Featured Promotions");
            $promotions = $this->promotion_service->featured($store_type_id);
    
            $data = [
                'stores' => $stores,
                'featured' => $featured_items,
                'promotions' => $promotions,
                'categories' => $categories,
            ];
    
            Redis::set($cache_key, json_encode($data));
            Redis::expire($cache_key, 604800);

            $this->info("---- $store_name Home Cache Complete ----");
        }


        $this->info('---- Weekly Home Cache Complete ----');
        
    }
}