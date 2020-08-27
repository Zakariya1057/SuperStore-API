<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIngredientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // id INT NOT NULL AUTO_INCREMENT,
    // name VARCHAR(500) NOT NULL,
	// product_id INT NOT NULL,
    
    // created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    // updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    
    // PRIMARY KEY (id),
    // CONSTRAINT unique_ingredient UNIQUE (product_id,name)

    public function up()
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('product_id');

            $table->foreign('product_id')->references('id')->on('products');

            $table->unique(['name','product_id']);
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
        Schema::dropIfExists('ingredients');
    }
}
