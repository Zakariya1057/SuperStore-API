<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateGrandParentCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('grand_parent_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->string('site_category_id');
            $table->unsignedBigInteger('store_type_id');

            $table->boolean('enabled')->default(1);

            $table->unsignedBigInteger('store_id')->nullable();
            
            $table->foreign('store_type_id')->references('id')->on('store_types');

            $table->unique(['name', 'store_type_id']);

            $table->index('site_category_id');
            
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
        Schema::dropIfExists('grand_parent_categories');
    }
}
