<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('child_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->unsignedBigInteger('parent_category_id');

            $table->unsignedBigInteger('site_category_id')->unique();
            $table->unsignedBigInteger('store_type_id');

            $table->unsignedBigInteger('store_id')->nullable();
            
            $table->foreign('store_type_id')->references('id')->on('store_types');
            $table->foreign('parent_category_id')->references('id')->on('parent_categories');

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
        Schema::dropIfExists('child_categories');
    }
}
