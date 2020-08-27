<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFacilitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    // id INT NOT NULL AUTO_INCREMENT,
    // name VARCHAR(100) NOT NULL,
	// store_id INT NULL DEFAULT NULL,
    
    // created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    // updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    
    // PRIMARY KEY (id),
    // CONSTRAINT unique_facility UNIQUE (store_id,name)

    public function up()
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('store_id');

            $table->unique(['store_id','name']);
            $table->foreign('store_id')->references('id')->on('stores');

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
        Schema::dropIfExists('facilities');
    }
}
