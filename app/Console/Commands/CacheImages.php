<?php

namespace App\Console\Commands;

use App\GrandParentCategory;
use App\Models\Product;
use App\Traits\GroceryTrait;
use App\Traits\ImageTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheImages extends Command
{
    use ImageTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches all product images';

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
     * @return mixed
     */
    public function handle(){
        
        $this->info('Image Caching Start');

        // Get total rows of products
        $product_count = Product::whereNotNull('large_image')->count();
        $this->info('Products Found: ' . $product_count);

        for($i = 1; $i < $product_count; $i++){
            $this->info('Caching Images For Product ID: '. $i);

            $product = Product::where('id', $i)->get()->first();
            $site_product_id = $product->site_product_id;

            $this->get_image("{$site_product_id}_small.jpg",'products',true);
            $this->get_image("{$site_product_id}_large.jpg",'products',true);
        }

        $this->info('Image Caching Complete');

    }

}