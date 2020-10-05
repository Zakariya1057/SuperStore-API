<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // id INT NOT NULL AUTO_INCREMENT,
    // name VARCHAR(500) NOT NULL,
	// large_image VARCHAR(200) NOT NULL,
    // small_image VARCHAR(200) NOT NULL,
    // description VARCHAR(2000) NULL DEFAULT NULL,
    
    // price DECIMAL(4,2) NOT NULL,
    // old_price DECIMAL(4,2) NULL DEFAULT NULL,
    // is_on_sale BIT(1) NULL DEFAULT NULL,
    // promotion_id INT NULL DEFAULT NULL,
    
    // recommended_searched BIT(1) NULL DEFAULT NULL,
    // reviews_searched BIT(1) NULL DEFAULT NULL,

    // weight VARCHAR(100) NOT NULL,
    // brand VARCHAR(100) NOT NULL,
    // dietary_info VARCHAR(100) NULL DEFAULT NULL,
    // allergen_info varchar(100) NULL DEFAULT NULL,
    // storage varchar(800) NULL DEFAULT NULL,

    // avg_rating DECIMAL(4,2) NULL DEFAULT NULL,
    // total_reviews_count INT UNSIGNED NULL DEFAULT NULL,

    // parent_category_id BIGINT NOT NULL,
    // site_type_id BIGINT NOT NULL,
    // site_product_id BIGINT NOT NULL,
	
    // url VARCHAR(500) NOT NULL,
    
    // created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    // updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),

    // PRIMARY KEY (id),
    // UNIQUE (site_product_id)

    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('large_image')->nullable();
            $table->string('small_image')->nullable();
            $table->text('description')->nullable();

            $table->decimal('price', 4,2);
            $table->decimal('old_price', 4,2)->nullable();

            $table->boolean('is_on_sale')->nullable();
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->unsignedBigInteger('site_product_id')->nullable();

            $table->boolean('recommended_searched')->nullable();
            $table->boolean('reviews_searched')->nullable();

            $table->string('weight')->nullable();
            $table->string('brand');
            $table->text('storage')->nullable();

            $table->string('dietary_info')->nullable();
            $table->string('allergen_info')->nullable();

            $table->decimal('avg_rating', 4,2)->nullable();
            $table->integer('total_reviews_count')->nullable();
            
            $table->text('url')->nullable();

            $table->unsignedBigInteger('parent_category_id');
            $table->unsignedBigInteger('store_type_id');


            $table->foreign('parent_category_id')->references('id')->on('child_categories');
            $table->foreign('store_type_id')->references('id')->on('store_types');
            $table->foreign('promotion_id')->references('id')->on('promotions');

            $table->unique('site_product_id');

            $table->index('name');
            $table->index('dietary_info');
            $table->index('parent_category_id');
            $table->index('store_type_id');

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
        Schema::dropIfExists('products');
    }
}
