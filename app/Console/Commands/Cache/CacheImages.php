<?php

namespace App\Console\Commands\Cache;

use App\Models\Product;
use App\Services\Image\ImageService;
use Illuminate\Console\Command;

class CacheImages extends Command
{

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
    private $image_service;

    public function __construct(ImageService $image_service)
    {
        parent::__construct();
        $this->image_service = $image_service;
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

            $this->image_service->get_image("{$site_product_id}_small.jpg",'products',true);
            $this->image_service->get_image("{$site_product_id}_large.jpg",'products',true);
        }

        $this->info('Image Caching Complete');

    }

}