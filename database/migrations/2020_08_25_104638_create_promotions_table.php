<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    // CREATE TABLE promotions (
    //     id INT NOT NULL AUTO_INCREMENT,
    //     name VARCHAR(500) NOT NULL,
    //     site_promotion_id VARCHAR(100) NOT NULL,
    //     url VARCHAR(500) NOT NULL,
        
    //     expires BIT(1) NULL DEFAULT NULL,
        
    //     starts_at TIMESTAMP NULL DEFAULT NULL,
    //     ends_at TIMESTAMP NULL DEFAULT NULL,
        
    //     created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    //     updated_at TIMESTAMP NOT NULL DEFAULT NOW() ON UPDATE NOW(),
        
    //     PRIMARY KEY (id),
    //     UNIQUE(url)
    // ) ENGINE=INNODB;

    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('site_promotion_id');
            $table->string('url');

            $table->boolean('expires')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('store_type_id');
            
            $table->unique('site_promotion_id');
            $table->index('name');
            $table->index('store_type_id');

            $table->foreign('store_type_id')->references('id')->on('store_types');
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
        Schema::dropIfExists('promotions');
    }
}
