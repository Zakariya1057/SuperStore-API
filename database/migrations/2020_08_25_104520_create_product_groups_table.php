<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateProductGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->unsignedBigInteger('child_category_id');

            $table->string('site_product_group_id');
            $table->unsignedBigInteger('store_type_id');

            $table->boolean('enabled')->default(1);
            
            $table->unsignedBigInteger('store_id')->nullable();
            
            $table->foreign('store_type_id')->references('id')->on('store_types');
            $table->foreign('child_category_id')->references('id')->on('child_categories');

            $table->unique(['name', 'store_type_id', 'child_category_id'],'product_groups_unique');
            
            $table->index('site_product_group_id');
            
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
        Schema::dropIfExists('product_groups');
    }
}
