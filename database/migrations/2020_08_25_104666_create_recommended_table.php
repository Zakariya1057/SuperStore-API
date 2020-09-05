<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // id INT NOT NULL AUTO_INCREMENT,
    // product_id BIGINT NOT NULL,
    // recommended_product_id BIGINT NOT NULL,
    
    // created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    // updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    // PRIMARY KEY (id),
    // CONSTRAINT unique_recommended_product UNIQUE (product_id , recommended_product_id)

    public function up()
    {
        Schema::create('recommended', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('recommended_product_id');

            $table->unique(['product_id','recommended_product_id']);

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('recommended_product_id')->references('id')->on('products');

            $table->index('product_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recommended');
    }
}
