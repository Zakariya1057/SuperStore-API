<?php

namespace App\Console\Commands;

use App\Services\CategoryService;
use App\Services\GroceryService;
use App\Services\ProductService;
use App\Services\PromotionService;
use App\Services\StoreService;
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

        $cache_key = 'home_page';

        $featured_items = $this->product_service->featured();
        $stores = $this->store_service->stores_by_type(1,false);
        $categories = $this->category_service->featured();
        $promotions = $this->promotion_service->featured(1);

        $data = [
            'stores' => $stores,
            'featured' => $featured_items,
            'promotions' => $promotions,
            'categories' => $categories,
        ];

        Redis::set($cache_key, json_encode($data));
        Redis::expire($cache_key, 604800);

        $this->info('---- Weekly Home Cache Complete ----');
        
    }
}