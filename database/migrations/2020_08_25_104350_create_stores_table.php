<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->text('description')->nullable();

            $table->text('store_image')->nullable();

            $table->text('google_url')->nullable();
            $table->text('uber_url')->nullable();
            $table->text('site_url')->nullable();

            $table->unsignedBigInteger('store_site_id')->unique();
            $table->unsignedBigInteger('store_type_id');

            $table->index('name');
            $table->index('store_site_id');

            $table->foreign('store_type_id')->references('id')->on('store_types');
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
        Schema::dropIfExists('stores');
    }
}
