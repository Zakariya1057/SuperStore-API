<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProductPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->decimal('price', 9,2);
            $table->decimal('old_price', 9,2)->nullable();

            $table->boolean('available')->default(1);
            
            $table->boolean('is_on_sale')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            
            $table->unsignedBigInteger('promotion_id')->nullable();

            $table->unsignedBigInteger('region_id');
            $table->unsignedBigInteger('product_id');

            $table->foreign('region_id')->references('id')->on('regions');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('promotion_id')->references('id')->on('promotions');

            $table->index('promotion_id');
            $table->index('region_id');
            $table->index('product_id');

            $table->unique(['region_id','product_id']);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_prices');
    }
}
