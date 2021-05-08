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
    
    // ) ENGINE=INNODB;

    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('site_promotion_id')->nullable();
            $table->string('url')->nullable();

            $table->string('title')->nullable();
            $table->string('name');

            $table->integer('quantity')->nullable();
            $table->decimal('price', 9,2)->nullable();
            $table->integer('for_quantity')->nullable();

            $table->unsignedBigInteger('site_category_id')->nullable();

            $table->integer('minimum')->nullable();
            $table->integer('maximum')->nullable();

            $table->boolean('expires')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('store_type_id');
            
            $table->boolean('enabled')->default(1);
            
            $table->unique('site_promotion_id');
            
            $table->index('name');
            $table->index('title');
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
