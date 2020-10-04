<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateOpeningHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

     // id INT NOT NULL AUTO_INCREMENT,

	// open TIME NOT NULL,
	// close TIME NOT NULL,
    
	// day_of_week SMALLINT NOT NULL, 
	// store_id INT NULL DEFAULT NULL,
    
    // closed_today BIT(1) NULL DEFAULT NULL,
    
	// created_at TIMESTAMP NOT NULL DEFAULT NOW(),
	// updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
    
	// PRIMARY KEY (id),
	// CONSTRAINT unique_opening_hour UNIQUE (store_id , day_of_week)

    public function up()
    {
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();

            $table->time('opens_at');
            $table->time('closes_at');

            $table->smallInteger('day_of_week');
            $table->unsignedBigInteger('store_id');

            $table->boolean('closed_today')->nullable();

            $table->foreign('store_id')->references('id')->on('stores');
            
            $table->index('store_id');
            $table->index('day_of_week');

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
        Schema::dropIfExists('opening_hours');
    }
}
